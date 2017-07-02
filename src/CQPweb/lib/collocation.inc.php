<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 *
 * See http://cwb.sourceforge.net/cqpweb.php
 *
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CQPweb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */



/** 
 * @file 
 * This file contains the code for creating colloc databases and then creating a colloc display.
 */


/* this script emits nothing on stdout until the last minute, because it can alternatively write a plaintext file as HTTP attachment */



/* ------------ *
 * BEGIN SCRIPT *
 * ------------ */



/* initialise variables from settings files  */
require('../lib/environment.inc.php');


/* include function library files */
require("../lib/library.inc.php");
require("../lib/html-lib.inc.php");
require("../lib/concordance-lib.inc.php");
require("../lib/concordance-post.inc.php");
require("../lib/colloc-lib.inc.php");
require("../lib/exiterror.inc.php");
require("../lib/user-lib.inc.php");
require("../lib/metadata.inc.php");
require("../lib/xml.inc.php");
require("../lib/freqtable.inc.php");
require("../lib/cache.inc.php");
require("../lib/subcorpus.inc.php");
require("../lib/db.inc.php");
require("../lib/rface.inc.php");
require("../lib/cwb.inc.php");
require("../lib/cqp.inc.php");




cqpweb_startup_environment();






/* ------------------------------- *
 * initialise variables from $_GET *
 * and perform initial fiddling    *
 * ------------------------------- */

/* variables from collocation-options *
 * ---------------------------------- */


/* this script is passed qname from the collocation-options */
$qname = safe_qname_from_get();




/* $colloc_atts -- a list of attributes separated by ~ 
 * $colloc_atts_list -- same thing as an array 
 */
$colloc_atts = '';
$colloc_atts_list = array();

/* note that this has been set up so that incoming is the same from colloc-options and from self */
if ( 0 < preg_match_all('/collAtt_(\w+)=1/', $_SERVER['QUERY_STRING'], $match, PREG_PATTERN_ORDER) )
{
	foreach ($match[1] as $m)
		$colloc_atts_list[] = $m;
	sort($colloc_atts_list);
	$colloc_atts = '~~';			/* nonzero string signals that a check is needed, see below */
}


/* colloc_range --- the max range number --- if not set / badly specified, this defaults. */
/* note that this has been set up so that incoming is the same from colloc-options and from self */

if ( isset($_GET['maxCollocSpan']) )
	$colloc_range = (int)$_GET['maxCollocSpan'];
else
	$colloc_range = $Config->default_colloc_range;
// TODO need control on this as colloc-options could be hacked!! a default max_max, as it were */




/* parameters unique to this script *
 * -------------------------------- */

/* variables that come from the collocation control form and only affect "this" calculation */

/* note that "calc" in a variable name indicates it is to be used for display,
 * as opposed to variables that are to be used for the database creation & db cache-retrieval */


/* the p-attribute to be used for this script's calculation (it is validated below) */
$att_for_calc = 'word';
if (isset($_GET['collocCalcAtt']))
	$att_for_calc = $_GET['collocCalcAtt'];


/* Window span for the calculation : both must be between -colloc_range and colloc_range */

if (isset($_GET['collocCalcBegin']) && abs($_GET['collocCalcBegin']) <= $colloc_range )
	$calc_range_begin = (int)$_GET['collocCalcBegin'];
else if (isset($User->coll_from))
	$calc_range_begin = (int)$User->coll_from;
else
	/* defaults to 2-left of node, or 2-right of max, whichever is wider */
	$calc_range_begin = ($colloc_range > 2 ? -($colloc_range - 2) : $colloc_range);

if (isset($_GET['collocCalcEnd']) && abs($_GET['collocCalcEnd']) <= $colloc_range )
	$calc_range_end = (int)$_GET['collocCalcEnd'];
else if (isset($User->coll_to))
	$calc_range_end = (int)$User->coll_to;
else
	/* defaults to mirror of the begin value */
	$calc_range_end = -($calc_range_begin);

/* add a restriction on range begin and end: neither can be more than colloc_range (abs-wise) */
if ( abs($calc_range_begin) > $colloc_range )
	$calc_range_begin = $colloc_range * ($calc_range_begin / abs($calc_range_begin));
if ( abs($calc_range_end) > $colloc_range )
	$calc_range_end = $colloc_range * ($calc_range_end / abs($calc_range_end));


$generic_error = array("If you did not specify a position range, then it may be that you have set a bad range in your user settings.");

if ( ! ($calc_range_end >= $calc_range_begin) )
	exiterror_parameter(array_unshift($generic_error, "Your position range does not make sense; the end-point cannot be less than the start-point."));
if ( $calc_range_end == 0 || $calc_range_begin == 0 )
	exiterror_parameter(array_unshift($generic_error, "Your position range does not make sense; you cannot end the range at zero!"));
if ( $calc_range_begin == 0 )
	exiterror_parameter(array_unshift($generic_error, "Your position range does not make sense; you cannot begin the range at zero!"));




/* minimum frequencies for the collocate-node combo and the collocate considered alone */
/* only positive integers allowed */
if (isset($_GET['collocMinfreqTogether']) )
	$calc_minfreq_together = abs((int) $_GET['collocMinfreqTogether']);
else if (isset($USer->coll_freqtogether))
	$calc_minfreq_together = (int)$User->coll_freqtogether;
else
	$calc_minfreq_together = $Config->default_colloc_minfreq;

if (isset($_GET['collocMinfreqColloc']) )
	$calc_minfreq_collocalone = abs((int) $_GET['collocMinfreqColloc']);
else if (isset($User->coll_freqalone))
	$calc_minfreq_collocalone = (int)$User->coll_freqalone;
else
	$calc_minfreq_collocalone = $Config->default_colloc_minfreq;



/* are we to use the overall freq table for the corpus EVEN IF a subsection is specified ? */
if (isset($_GET['freqtableOverride']) )
	$freq_table_override = (bool)$_GET['freqtableOverride'];
else
	$freq_table_override = false;




/* is this the "collocation solo" function? */
if (!empty($_GET['collocSolo']))
{
	$soloform = $_GET['collocSolo'];
	$solomode = true;
}
else
	$solomode = false;


/* and a purely display-related variable */
if (isset($_GET['beginAt']) )
	$begin_at = abs((int) $_GET['beginAt']);
else
	$begin_at = 0;


/* do we want a nice HTML table or a downloadable table? */
if (isset($_GET['tableDownloadMode']) && 1 == (int) $_GET['tableDownloadMode'])
	$download_mode = true;
else
	$download_mode = false;






/* this is an array full of goodies as laid out in the function that creates it */
$statistic = load_statistic_info();

/* calc_stat is the index of the statistic to be used for the collocation table below,
   to be used as an index into the array created above */
if ( isset($_GET['collocCalcStat']) )
	$calc_stat = (int) $_GET['collocCalcStat'];
else if ( isset($User->coll_statistic) )
	$calc_stat = (int)$User->coll_statistic;
else
	$calc_stat = $Config->default_colloc_calc_stat;

if (! isset($statistic[$calc_stat]) )
	/* non-existent stat, so go to default */
	$calc_stat = $Conifig->default_colloc_calc_stat;



/* tag_filter - the tag that displayed collocs must have */
if ( isset($_GET['collocTagFilter'])  &&  $_GET['collocTagFilter'] != '')
	$tag_filter = $_GET['collocTagFilter'];
else
	$tag_filter = false;
/* note it comes from _GET so before use it must be escaped! various functions do this */

/* do not allow a tag filter if the collocation attribute IS the primary annotation */
$primary_annotation = $Corpus->primary_annotation;

if ($primary_annotation == $att_for_calc)
{
	$tag_filter = false;
	$display_tag_filter_control = false;
}
else
	$display_tag_filter_control = true;


/* moreover: if the primary annotation does not exist in the collocation DB, then we ALSO need to hide the filter control */
if (! in_array($primary_annotation, $colloc_atts_list))
	$display_tag_filter_control = false;

/* -------------------------- *
 * end of variable initiation *
 * -------------------------- */







$att_desc = get_corpus_annotations($Corpus->name);
$att_desc['word'] = 'Word';


/* validate list of p-attributes to include && get their names */
if (!empty($colloc_atts))
{
	/* delete anything from the list of atts to put in the database that is not a real annotation */
	foreach ($colloc_atts_list as $k => $v)
		if( ! array_key_exists($v, $att_desc) )
			unset($colloc_atts_list[$k]);
	/* and compile the string-version of the list to what it needs to be to go in the db */
	$colloc_atts = implode('~', $colloc_atts_list);
}

/* validate p-attribute to be used as basis of collocation */
if ( ( ! isset($colloc_atts_list) ) || ( ! in_array($att_for_calc, $colloc_atts_list) ) )
	$att_for_calc = 'word';




$start_time = microtime(true);


/* does a db for the collocation exist? */

/* first get all the info about the query in one handy package */

$query_record = QueryRecord::new_from_qname($qname);
if ($query_record === false)
	exiterror_general("The specified query $qname was not found in cache!");


/* now, search the db list for a db whose parameters match those of the query
 * named as qname; if it doesn't exist, we need to create one */
$type_spec = new DbType(DB_TYPE_COLLOC, $colloc_atts, $colloc_range);
$db_record = check_dblist_parameters($type_spec, $query_record->cqp_query, $query_record->query_scope, $query_record->postprocess);
// 				$query_record->restrictions, $query_record->subcorpus, $query_record->postprocess,
// 				$query_record->query_scope, $query_record->postprocess,
// 				$colloc_atts, $colloc_range);

if ($db_record === false)
{
	$dbname = create_db($type_spec, $qname, $query_record->cqp_query, $query_record->query_scope, $query_record->postprocess);
	$db_record = check_dblist_dbname($dbname);
	$is_new_db = true;
}
else
{
	$dbname = $db_record['dbname'];
	touch_db($dbname);
	$is_new_db = false;
}
/* this dbname & its db_record can be globalled by print functions in the script */

/* for now just find out how many distinct items it has in it */
$db_types_total = mysql_num_rows(do_mysql_query("select distinct(`$att_for_calc`) from $dbname"));




/* OK we have the db for calculating collocations, now we need the basis of comparison */

// /* first: check if there is a reduced restriction n the query record (from a "dist" postprocess.) */
// $reduced_restrictions = $query_record->get_reduced_restrictions();

//TODO: The following explains how it will have to work ultimately.
/*

notes on how this works in the 3.2.7 revisions.
================================================================

FIRST: ask the query record, does it have any distribution-type postprocesses.

IF IT DOES, create a query scope representing the "extra". ($^--text|class~cat.class~cat.class~cat...........
 	(this can be done for calling a m  ethiod "represent dist reductions as scope", and if the new scope is whole corpus, theer are none. As follows:
 
$dist_scope = $query_record->get_dist_reductions_as_scope();
if (QueryScope::TYPE_WHOLE_CORPUS == $dist_scope->type)
	;// no dist type postprocesses
else
	;// see below.

THEN do a query-scope intersect for that "extra" and the original query-scope.
and do the get/make-freqtable stuff on the intersected scope. 


FOR NOW: 

	let the intersection procedur3e return false. IF IT DOES, then we just fall back to using the whole corpus method.
	DO THIS if any kind of intersection is required OTHER THAN a --text restriction with another --text restriction.
	
if (QueryScope::TYPE_WHOLE_CORPUS == $dist_scope->type || false === whatever_the_intersect_func_is($query_record->qscope, $dist_scope) )

AND review whether intersect-create methods could do the jobs currenlty done by other methods in the Distribution func. 

 */

$dist_scope = $query_record->get_extra_filters_as_qscope();

/* can we get an intersect for the $dist_scope and the original scope? */
if (QueryScope::TYPE_WHOLE_CORPUS == $dist_scope->type)
	$intersect_result_scope = false;
else
	$intersect_result_scope = $query_record->qscope->get_intersect($dist_scope);




// if (empty($reduced_restrictions))
if (false == $intersect_result_scope)
{
	/* CODE FOR SITUATION WHERE THERE ARE NO DIST-TYPE POSTPROCESSES (i.e. the original case) */
	
	/* if there is a subcorpus or restriction, then a table for that needs to be found or created */
	if (QueryScope::TYPE_WHOLE_CORPUS != $query_record->qscope->type)
	{
		if ( false !== ($freqtable_record = $query_record->qscope->get_freqtable_record()) ) 
			;
		else
		{
			/* if not found, and if the subsection is not too big, create it */
			$words_in_subsection = $query_record->get_tokens_searched_initially();
			$freq_table_override = ( $words_in_subsection < $Config->collocation_disallow_cutoff ? $freq_table_override : true );
	
			if ( ! $freq_table_override )
				$freqtable_record = subsection_make_freqtables($query_record->qscope);
		}
	}
	
// 	if (!empty($query_record->subcorpus))
// 	{
// 		/* has this subcorpus had its freqtables created? */
// 		$sc = Subcorpus::new_from_id($query_record->subcorpus);
// 		if (false == $sc)
// 			exiterror("The subcorpus in which this query ran seems to have been deleted."); // necessary?
// 		if ( false !== ($freqtable_record = $sc->get_freqtable_record()))
// 			;
// 		else
// 		{
// 			/* if not, and if the subsection is not too big, create it */
// 			$words_in_subsection = $query_record->get_tokens_searched_initially();
// 			$freq_table_override = ( $words_in_subsection < $collocation_disallow_cutoff ? $freq_table_override : true );
	
// 			if ( ! $freq_table_override )
// 				$freqtable_record = subsection_make_freqtables($query_record->subcorpus);
// 		}
// 	}
// 	else if ($query_record->restrictions != '')
// 	{
// 		/* search for a freqtable matching that restriction */
// 		if ( false !== ($freqtable_record = check_freqtable_restriction($query_record->qscope->serialise())) )
// 			;
// 		else
// 		{
// 			/* if there isn't one, and if the subsection is not too big, create it */
// 			$words_in_subsection = $query_record->get_tokens_searched_initially();
// 			$freq_table_override = ( $words_in_subsection < $collocation_disallow_cutoff ? $freq_table_override : true );
	
// 			if ( ! $freq_table_override )
// 				$freqtable_record = subsection_make_freqtables('', $query_record->qscope->serialise());
// 		}
// 	}
}
else
{
	/* CODE FOR SITUATION WHERE THERE IS ONE OR MORE DIST-TYPE POSTPROCESSES */

	/* same as above, but simplified because it CAN'T be a subcorpus; also note that we use the reduced token count
	 * not the initial token count for checking for a freq-table-override.  */
// 	if ( false !== ($freqtable_record = check_freqtable_restriction($reduced_restrictions)) )
	if ( false !== ($freqtable_record = $intersect_result_scope->get_freqtable_record()) )
		;
	else
	{
		$words_in_subsection = $query_record->get_tokens_searched_reduced();
		$freq_table_override = ( $words_in_subsection < $Config->collocation_disallow_cutoff ? $freq_table_override : true );
	
		if ( ! $freq_table_override )
// 			$freqtable_record = subsection_make_freqtables('', $reduced_restrictions);
			$freqtable_record = subsection_make_freqtables($intersect_result_scope);
	}
}

/* nb: freq_table_override is tested in the ELSE condition above. This means that if the override is
 * set to TRUE, but by some chance the freqtable necessary does exist, the override does not kick in.
 * 
 * Note also, if the override is activated, $freqtable_record WON'T be set.
 */


if ( !empty($freqtable_record) )  /* ie (a) IF the if above was true.... */
{
 	/* we are using a subsection (sc or retriction): touch it and assign the freqtable name */
	$freq_table_to_use = "{$freqtable_record->freqtable_name}_{$att_for_calc}";
	$desc_of_basis = 'this subcorpus';
	touch_freqtable($freqtable_record->freqtable_name);
}
else
{
	/* we are not using a subsection, so default to the table for this corpus */
	/* OR the override for a too-much-time-to-calculate subcorpus was engaged */
	$freq_table_to_use = "freq_corpus_{$Corpus->name}_{$att_for_calc}";
	/* this variable is not used here, but IS used in create_statistic_sql_query() */
	$desc_of_basis = 'whole corpus';
}




/* ------------------------------------------------------------------------------------- *
 * send the script off to the separate function if it is the collocation-solo capability *
 * ------------------------------------------------------------------------------------- */
if ($solomode === true)
{
	run_script_for_solo_collocation();
	echo print_html_footer('collocation');
	cqpweb_shutdown_environment();
	exit(0);
}






/* run the BIIIIIIIIIIIIG mysql query */

$sql = create_statistic_sql_query($calc_stat);

$result = do_mysql_query($sql);




/* "time" == time to create the db (if nec), create the freqtable (if nec), + run the BIIIG query */
$time_taken = round(microtime(true) - $start_time, 3);

$description = "There are " . number_format((float)$db_types_total) . " different " 
	. strtolower($att_desc[$att_for_calc]) 
	. "s in your collocation database for &ldquo;" . escape_html($query_record->cqp_query) . "}&rdquo;. (" 
	. $query_record->print_solution_heading(false) . ') ' 
	. format_time_string($time_taken, $is_new_db)
	;




if ($download_mode)
{
	collocation_write_download($att_for_calc, $calc_stat, $att_desc[$att_for_calc], $desc_of_basis, 
		$statistic[$calc_stat]['desc'], $description, $result);
}
else
{

	/* ------------------------------------------------ *
	 * create the HTML for the control panel at the top *
	 * ------------------------------------------------ */
	
	/* first step: generate the SELECT dropdowns for each collocation calculation option */
	
	/* create the P-ATTRIBUTE TO CALCULATE SELECTION BOX */	
	$select_for_colloc = '<select name="collocCalcAtt">
		<option value="word" ' . ('word' == $att_for_calc ? 'selected="selected"' : '') 
		. '>Word form</option>';
	
	if (! empty($colloc_atts_list))
	{
		foreach($colloc_atts_list as $a)
			$select_for_colloc .= "\n\t<option value=\"$a\"" 
				. ($a == $att_for_calc ? ' selected="selected"' : '') 
				. ">{$att_desc[$a]}</option>";
	}
	
	$select_for_colloc .= '</select>';
	
	/* create the CALCULATION STATISTIC SELECTION BOX */
	$select_for_stats = '<select name="collocCalcStat">';
	$select_for_stats .= print_statistic_form_options($calc_stat);
	$select_for_stats .= '</select>';
	
	/* create the RANGE TO CALCULATE SELECTION BOXES */
	list ($tempfrom, $tempto) = print_fromto_form_options($colloc_range, $calc_range_begin, $calc_range_end);
	$select_for_windowfrom = '<select name="collocCalcBegin">' . $tempfrom . '</select>';
	$select_for_windowto   = '<select name="collocCalcEnd">'   . $tempto   . '</select>';
	
	/* create the MINIMUM FREQUENCY OF COLLOCATES SELECTION BOX */
	$select_for_freqtogether = 
		'<select name="collocMinfreqTogether">'
		. print_freqtogether_form_options($calc_minfreq_together) 
		.'</select>';
	$select_for_freqalone = 
		'<select name="collocMinfreqColloc">' 
		. print_freqalone_form_options($calc_minfreq_collocalone)
		. '</select>';
	
	/* create the TAG FILTER SELECTION BOX */
	if (isset($colloc_atts_list) && in_array($primary_annotation, $colloc_atts_list))
	{
		/* was formerly name="collocTagFilterSelect" */
		$select_for_tag = '<select onChange="setCollocTagFilter(this);">
				<option value="-??..__any__..??-"' . ($tag_filter === false ? ' selected="selected"' : '')
				. '>(none)</option>';

		foreach(colloc_table_taglist($primary_annotation, $dbname) as $tag)
			$select_for_tag .= "\n\t\t\t\t<option" . ($tag == $tag_filter ? ' selected="selected"' : ''). ">$tag</option>";
		$select_for_tag .= "\t\t\t</select>\n\n";
	}
	else
	{
		$select_for_tag = '<select><option selected="selected">no restriction</option></select>';
	}



	/* ok, all select-option dropdowns have been dynamically generated : now, write it! */

	echo print_html_header("{$Corpus->title} -- CQPweb collocation results", $Config->css_path, array('cword'));	

	?>
	
	<table class="concordtable" width="100%">
		<form action="redirect.php" method="get">
			<tr>
				<th class="concordtable" colspan="4">Collocation controls</th>
			</tr>
	
			<tr>
				<td class="concordgeneral">Collocation based on:</td>
				<td class="concordgeneral"><?php echo $select_for_colloc; ?></td>
				<td class="concordgeneral">Statistic:</td>
				<td class="concordgeneral"><?php echo $select_for_stats; ?></td>
			</tr>
	
			<tr>
				<td class="concordgeneral">Collocation window <em>from</em>:</td>
				<td class="concordgeneral"><?php echo $select_for_windowfrom; ?></td>
				<td class="concordgeneral">Collocation window <em>to</em>:</td>
				<td class="concordgeneral"><?php echo $select_for_windowto; ?></td>
			</tr>
	
			<tr>
				<td class="concordgeneral">Freq(node, collocate) at least: </td>
				<td class="concordgeneral"><?php echo $select_for_freqtogether; ?></td>
				<td class="concordgeneral">Freq(collocate) at least: </td>
				<td class="concordgeneral"><?php echo $select_for_freqalone; ?></td>
			</tr>
		
			<tr>
				<td class="concordgrey">Filter results by:</td>
				<td class="concordgrey">
					specific collocate: 
					<input type="text" name="collocSolo" size="15" maxlength="40"/>
				</td>
				<td class="concordgrey">
					<?php
					if ($display_tag_filter_control)
					{
						?>
						<script type="text/javascript">
						<!--
						// this function only works within this <td>
						function setCollocTagFilter(fromThisSelect)
						{
							var newValue = fromThisSelect.options[fromThisSelect.selectedIndex].value;
							// work around stupid, stupid Internet Explorer bug
							if (newValue == "")
								newValue = fromThisSelect.options[fromThisSelect.selectedIndex].innerHTML;
							
							if (newValue == "-??..__any__..??-")
								newValue = "";
							var target = document.getElementById('collocTagFilter');
							target.value = newValue;
						}
						//-->
						</script>
						and/or tag: 
						<input name="collocTagFilter" id="collocTagFilter" 
							value="<?php echo $tag_filter;?>"
							type="text" size="5"
						/>
						<?php 
						echo $select_for_tag;
					}
					else
						echo 'tag restriction: n/a';
					?>
				</td>
				<td class="concordgrey">
					<select name="redirect">
						<option value="rerunCollocation">Submit changed parameters</option>
						<option value="collocationDownload">Download collocation results</option>
						<option value="newQuery">New query</option>
						<option value="backFromCollocation">Back to query result</option>
						<!--
							important note: because of intervening "create database" screen,
							the return-target is always the first page of the query -
							unlike the Distribution program, which remembers where we were.
						-->
					</select>
					<input type="submit" value=" Go! " />
				</td>
				<!-- hidden inputs here -->
				<input type="hidden" name="maxCollocSpan" value="<?php echo $colloc_range; ?>"/>
				<input type="hidden" name="qname" value="<?php echo $qname; ?>"/>
				<?php
				if (! empty($colloc_atts_list))
				{
					foreach ($colloc_atts_list as $a)
						echo "\t\t<input type=\"hidden\" name=\"collAtt_$a\" value=\"1\"/>\n";
				}
				?>
				<input type="hidden" name="uT" value="y"/>
			</tr>
		</form>

		<?php
		
		/* display a table row with text, if available as an "extra info" entry in the $sztatisticv variable */
		if (!empty($statistic[$calc_stat]['extra']))
		{
			/* allow variables to be used in this string (safe because this always comes from code) */
			preg_match_all('/\$(\w+)\b/', $statistic[$calc_stat]['extra'], $m, PREG_PATTERN_ORDER);
			foreach($m[1] as $varname)
				if (isset($$varname))
					$statistic[$calc_stat]['extra'] = str_replace("\${$varname}", $$varname, $statistic[$calc_stat]['extra']);
			echo '<tr><td colspan="4" class="concordgrey">&nbsp;<br><u><b>Extra information</b></u>: '
				, $statistic[$calc_stat]['extra']
				, "<br>&nbsp;</td></tr>\n\n";
		}
		?>

	</table>



	<!-- 
		end of collocation control display, start of collocation results display 
	-->

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="<?php echo ($calc_stat != 0 ? 7 : 6); ?>">
				<?php echo $description; ?>
			</th>
		</tr>
		<tr>
			<td class="concordgrey" align="center">No.</td>
			<td class="concordgrey" align="center"><?php echo $att_desc[$att_for_calc];?></td>
			<td class="concordgrey" align="center">Total no. in <?php echo $desc_of_basis; ?></td>
			<td class="concordgrey" align="center">Expected collocate frequency</td>
			<td class="concordgrey" align="center">Observed collocate frequency</td>
			<td class="concordgrey" align="center">In no. of texts</td>
		
			<?php
			if ($calc_stat != 0)
				echo "<td class=\"concordgrey\"><center>{$statistic[$calc_stat]['desc']}</center></td>\n";
			?>
		</tr>
		
	<?php
	
	$i = $begin_at;
	while (( $row = mysql_fetch_assoc($result)) !== false)
	{
		$i++;
	
		/* adjust number formatting : expected -> 3dp, significance -> 4dp, freq-> thousands*/
		if ( empty($row['significance']) )
			$row['significance'] = 'n/a';
		else
			$row['significance'] = round($row['significance'], 3);
		$row['observed'] = number_format((float)$row['observed']);
		$row['expected'] = round($row['expected'], 3);
		$row['freq'] = number_format((float)$row['freq']);
		
		$att_for_calc_tt_show = strtr($row[$att_for_calc], array("'"=>"\'", '"'=>'&quot;'));
		
		$solo = "<a href=\"collocation.php?collocSolo=" . urlencode($row[$att_for_calc]) . '&'
			. url_printget(array(array('collocSolo', '')))
			. "\" onmouseover=\"return escape('Show detailed info on <B>"
			. $att_for_calc_tt_show . "</B>')\">{$row[$att_for_calc]}</a>";
		
		$link = "<a href=\"concordance.php?qname=$qname&newPostP=coll&newPostP_collocDB=$dbname"
			. "&newPostP_collocDistFrom=$calc_range_begin&newPostP_collocDistTo=$calc_range_end"
			. "&newPostP_collocAtt=$att_for_calc&newPostP_collocTarget="
			. urlencode($row[$att_for_calc])
			. "&newPostP_collocTagFilter=" . urlencode($tag_filter)
			. "&uT=y\" onmouseover=\"return escape('Show solutions collocating with <B>"
			. $att_for_calc_tt_show . "</B>')\">{$row['observed']}</a>";
		
		$sig = ($calc_stat == 0 ? '' : "<td class=\"concordgeneral\"><center>{$row['significance']}</center></td>");
		
		/* debug message (while Log Ratio is in development): show the LL alongside the Log Ratio */
		if ($Config->print_debug_messages && isset($row['LogLikelihood'])) 
			$sig = str_replace('</center>', " [LL:{$row['LogLikelihood']}]</center>", $sig);

		echo "
			<tr>
				<td class=\"concordgeneral\"><center>$i</center></td>
				<td class=\"concordgeneral\"><center>$solo</center></td>
				<td class=\"concordgeneral\"><center>{$row['freq']}</center></td>
				<td class=\"concordgeneral\"><center>{$row['expected']}</center></td>
				<td class=\"concordgeneral\"><center>$link</center></td>
				<td class=\"concordgeneral\"><center>{$row['text_id_count']}</center></td>
				$sig
			</tr>
			";
	}
	
	echo '</table>';
	
	
	/* create navlinks */
	$navlinks = '<table class="concordtable" width="100%"><tr><td class="concordgrey" align="left';
	
	if ($begin_at > 0)
	{
		$new_begin_at = $begin_at - $Config->default_collocations_per_page;
		if ($new_begin_at < 0)
			$new_begin_at = 0;
		$navlinks .=  '"><a href="collocation.php?' . url_printget(array(array('beginAt', "$new_begin_at")));
	}
	$navlinks .= "\">&lt;&lt; [Previous {$Config->default_collocations_per_page} collocates]";
	if ($begin_at > 0)
		$navlinks .= '</a>';
	$navlinks .= '</td><td class="concordgrey" align="right';
	
	if ($i == ($begin_at + $Config->default_collocations_per_page) )
		$navlinks .=  '"><a href="collocation.php?' . url_printget(array(array('beginAt', "$i")));
	$navlinks .= "\">[Next {$Config->default_collocations_per_page} collocates] &gt;&gt;";
	if ($i == ($begin_at + $Config->default_collocations_per_page) )
		$navlinks .= '</a>';
	$navlinks .= '</td></tr></table>';
	
	echo $navlinks;
	
	echo print_html_footer('collocation');


} /* endof "else" for "if $download_mode" */



cqpweb_shutdown_environment();

/* and we're done! */


/*
 * =============
 * END OF SCRIPT
 * =============
 */

