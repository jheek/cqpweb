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


/*
 * Note: a lot of the code in the later functions in this file is extremely wonky in terms of encapsulation.
 * 
 * The use of global variables is ridiculous and needs to be stopped.
 */




function print_statistic_form_options($index_to_select)
{
	$statistic = load_statistic_info();
	$output = '';
	foreach($statistic as $index => $s)
	{
		if ($index == 0)		/* this one is saved for last */
			 continue;
		$output .= "\n\t<option value=\"$index\""
			. ($index_to_select == $index ? ' selected="selected"' : '')
			. ">{$s['desc']}</option>"
			;
	}
	
	$output .= "<option value=\"0\" " 
		. ($index_to_select == 0 ? ' selected="selected"' : '') 
		. ">{$statistic[0]['desc']}</option>"
		;

	return $output;
}



function print_fromto_form_options($colloc_range, $index_to_select_from, $index_to_select_to)
{
	global $Corpus;

	/* In the /usr context, there is no corpus... */
	if ($Corpus->specified && $Corpus->main_script_is_r2l)
	{
		$rightlabel = ' after the node';
		$leftlabel  = ' before the node';
	}
	else
	{
		$rightlabel = ' to the Right';
		$leftlabel = ' to the Left'; 
	}

	$output1 = $output2 = '';
	for ($i = -$colloc_range ; $i <= $colloc_range ; $i++)
	{
		if ( $i > 0 )
			$str = $i . $rightlabel;
		else if ( $i < 0 )
			$str = (-1 * $i) . $leftlabel;
		else   /* $i is 0 so do nothing */
			continue;
	
		$output1 .= "\n\t<option value=\"$i\"" 
			. ($i == $index_to_select_from ? ' selected="selected"' : '')
			. ">$str</option>";
		$output2 .= "\n\t<option value=\"$i\"" 
			. ($i == $index_to_select_to   ? ' selected="selected"' : '') 
			. ">$str</option>";
	}
	return array($output1, $output2);
}


function print_freqtogether_form_options($index_to_select)
{
	$string = '';
	foreach(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 50, 100) as $n)
		$string .= '
			<option' . ($n == $index_to_select ? ' selected="selected"' : '')
			. ">$n</option>";
	return $string;
}

function print_freqalone_form_options($index_to_select)
{
	$string = '';
	foreach(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 50, 100, 500, 1000, 5000, 10000, 20000, 50000) as $n)
		$string .= '
			<option' . ($n == $index_to_select ? ' selected="selected"' : '') . ">$n</option>";
	return $string;
}









function load_statistic_info()
{
	$info = array();

	/* the labels for the different stats are as follows ... */
	/* note, the 0 is a special index - ie no statistic! (use in if statements and the like) */
	$info[0]['desc'] = 'Rank by frequency';
	$info[1]['desc'] = 'Mutual information';
	$info[2]['desc'] = 'MI3';
	$info[3]['desc'] = 'Z-score';
	$info[4]['desc'] = 'T-score';
//	$info[5]['desc'] = 'Chi-squared with Yates' correction';		// this apparently turned off in BW?
	$info[6]['desc'] = 'Log-likelihood';
	$info[7]['desc'] = 'Dice coefficient';
	$info[8]['desc'] = 'Log Ratio';
	
	/* the "extra info" bar is here: appears above the actual collocation table. 
	 * Log Ratio has a long explanation; the rest, just short.*/
	$info[1]['extra'] = '<b>Mutual information</b> (MI) is an effect-size measure, scoring the collocation strength: 
		how strongly (how exclusively) two items are bound to one another. It is one of the most commonly used collocation measures, but is
		tends to give excessively high scores if the frequency of the collocate is low (below about 10).';
	$info[2]['extra'] = '<b>MI3</b> is a modified version of Mutual Information which is often used for extracting items of terminology, 
		but not often for collocation more generally, because it over-emphasises high frequency items.';
	$info[3]['extra'] = 'The <b>Z-score</b> is a measure whose results reflect a combination of significance (amount of evidence) and 
		effect size (strength of connection), producing a compromise ranking relative to MI (effect size) and LL (significance).';
	$info[4]['extra'] = 'The <b>T-score</b> is a significance measure that is not recommended for calculating collocations (Log-likelihood is better) 
		but is included in CQPweb for backwards compatability as it was very popular in earlier studies.';
	$info[6]['extra'] = '<b>Log-likelihood</b> scores collocations by significance: the higher the score, the more evidence you 
		have that the association is not due to chance. More frequent words tend to get higher log-likelihood scores, 
		because there is more evidence for such words.';
	$info[7]['extra'] = 'The <b>Dice coefficient</b> is a measure whose results reflect a combination of significance (amount of evidence) and 
		effect size (strength of connection), producing a compromise ranking relative to single statistics.';
	$info[8]['extra'] = '

		The <b>Log Ratio</b> statistic is a measurement of <i>how big the difference is</i> between the (relative) frequency 
		of the collocate alongside the node, and its (relative) frequency in the rest of the corpus or subcorpus.
		<br>&nbsp;<br>
		On its own, Log Ratio is very similar to the Mutual Information measure (both measure <i>effect size</i>). 
		However, CQPweb combines Log Ratio with a statistical-significance filter. 
		The collocate list is <u>sorted</u> by Log Ratio but <u>filtered</u> using Log-likelihood.
		<br>&nbsp;<br>
		Collocates are only included in the list if they are significant at the 5% level (p &lt; 0.05), adjusted using the Šidák
		correction. For <b>your current collocation analysis</b>, that means all collocates displayed have Log-likelihood of at least 
		<b>$LL_CUTOFF</b>.
		<br>&nbsp;<br>
		The use of a log-likelihood filter means that it is not necessary to set high minimum values for <i>Freq(node, collocate)</i>
		and <i>Freq(collocate)</i> when using Log Ratio.
		';
	/* long term, once Log Ratio is no longer a new thing, this may not be needed. */

	return $info;

}



/*
 * TODO
 * here is where the really shonky global-abuse starts.
 * TODO
 */








/**
 * Gets the SQL statement required to generate the collocation table. 
 * 
 * "soloform" is assumed to be pre-escaped with mysql_real_escape_string.
 * 
 * Field names (keys) for the table you get back when you actually
 * run the resulting query:
 *
 * $att 		 -- the collocate itself, with the name of the attribute it comes from as the field name 
 * observed 	 -- the number of times the collocate occurs in the window
 * expected 	 -- the number of times the collocate would occur in the window given smooth distribution
 * significance  -- the statistic [NOT PRESENT IF IT'S FREQ ONLY]
 * freq 		 -- the freq of that word or tag in the entire corpus (or subcorpus, etc)
 * text_id_count -- the number of texts in which the collocation occurs  
 */

function create_statistic_sql_query($stat, $soloform = '')
{
	global $Config;
	global $Corpus;
	
	/* TODO these should be parameters (OR: one parameter,pass in config values as array or stdClass?) instead of globals. */
	/* TODO see run_script_for_solo_collocation. The values that these funcs share == the configuation vals.  */
	global $dbname;
	global $att_for_calc;
	global $calc_range_begin;
	global $calc_range_end;
	global $calc_minfreq_collocalone;
	global $calc_minfreq_together;
	global $tag_filter;
// 	global $primary_annotation;
	global $freq_table_to_use;
	global $download_mode;

	global $begin_at;

	/* abbreviate the name for nice-ness in this function */
	$freq_table = $freq_table_to_use;
	
	/* table-field-cluase shorthand combos */
	
	/* the column in the db that is being collocated on */
	$item = "$dbname.`$att_for_calc`";
	
	$tag_clause = colloc_tagclause_from_filter($dbname, $att_for_calc, $Corpus->primary_annotation, $tag_filter);

	/* number to show on one page */
	$limit_string = ($download_mode ? '' : "LIMIT $begin_at, 50");	
// TODO shouldn't this be "per page"????


	/* the condition for including only the collocates within the window */
	if ($calc_range_begin == $calc_range_end)
		$range_condition = "dist = $calc_range_end";
	else
		$range_condition = "dist between $calc_range_begin and $calc_range_end";


	/* $sql_endclause -- a block at the end which is the same regardless of the statistic */
	if ($soloform === '')
	{
		/* the normal case */
		$sql_endclause = "where $item = $freq_table.item
			and $range_condition
			$tag_clause
			and $freq_table.freq >= $calc_minfreq_collocalone
			group by $item
			having observed >= $calc_minfreq_together
			order by significance desc 
			$limit_string
			";
	}
	else
	{
		/* if we are getting the formula for a solo form */
		$sql_endclause = "where $item = $freq_table.item
			and $range_condition
			$tag_clause
			and $item = '$soloform'
			group by $item
			";		
	}



	/* shorthand variables for contingency table */
	$N   = calculate_total_basis($freq_table_to_use);
	$R1  = calculate_words_in_window($dbname, $calc_range_begin, $calc_range_end);
	$R2  = $N - $R1;		
	$C1  = "($freq_table.freq)";
	$C2  = "($N - $C1)";
	$O11 = "1e0 * COUNT($item)";
	$O12 = "($R1 - $O11)";
	$O21 = "($C1 - $O11)";
	$O22 = "($R2 - $O21)";
	$E11 = "($R1 * $C1 / $N)";
	$E12 = "($R1 * $C2 / $N)";
	$E21 = "($R2 * $C1 / $N)";
	$E22 = "($R2 * $C2 / $N)";

	/*
	
	2-by-2 contingency table
	
	--------------------------------
	|        | Col 1 | Col 2 |  T  |
	--------------------------------
	| Row 1  | $O11  | $O12  | $R1 |
	|        | $E11  | $E12  |     |
	--------------------------------
	| Row 2  | $O21  | $O22  | $R2 |
	|        | $E21  | $E22  |     |
	--------------------------------
	| Totals | $C1   | $C2   | $N  |
	--------------------------------
	
	N   = total words in corpus (or the section)
	C1  = frequency of the collocate in the whole corpus
	C2  = frequency of words that aren't the collocate in the corpus
	R1  = total words in window
	R2  = total words outside of window
	O11 = how many of collocate there are in the window 
	O12 = how many words other than the collocate there are in the window (calculated from row total)
	O21 = how many of collocate there are outside the window
	O22 = how many words other than the collocate there are outside the window
	E11 = expected values (proportion of collocate that would belong in window if collocate were spread evenly)
	E12 =     "    "      (proportion of collocate that would belong outside window if collocate were spread evenly)
	E21 =     "    "      (proportion of other words that would belong in window if collocate were spread evenly)
	E22 =     "    "      (proportion of other words that would belong outside window if collocate were spread evenly)
	
	*/
	
	switch ($stat)
	{
	case 0:		/* Rank by frequency */
		$sql = "select $item, $O11 as observed,  $E11 as expected,
			$freq_table.freq, count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table 
			$sql_endclause";
		/* for rank by freq, we need to sort by something other than frequency */
		$sql = str_replace('order by significance', 'order by observed', $sql);
		break;
	
	case 1:		/* Mutual information */
		$sql = "select $item, count($item) as observed, $E11 as expected,
			log2($O11 / $E11) as significance, $freq_table.freq,
			count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table
			$sql_endclause";
		break;
		
	case 2:		/* MI3 (Cubic mutual information) */
		$sql = "select $item, count($item) as observed, $E11 as expected,
			3 * log2($O11) - log2($E11) as significance, $freq_table.freq, 
			count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table 
			$sql_endclause";
		break;
		
	case 3:		/* Z-score  (with Yates' continuity correction as of v3.0.8) */
		$sql = "select $item, count($item) as observed, $E11 as expected,
			sign($O11 - $E11) * if(abs($O11 - $E11) > 0.5, abs($O11 - $E11) - 0.5, abs($O11 - $E11) / 2) / sqrt($E11) as significance, $freq_table.freq,
			count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table
			$sql_endclause";
		break;
		
	case 4:		/* T-score */
		$sql = "select $item, count($item) as observed, $E11 as expected,
			($O11 - $E11) / sqrt($O11) as significance, $freq_table.freq,
			count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table
			$sql_endclause";
		break;
/*	case 5:		/* Chi-squared with Yates' correction * /
//turned off by Stefan (in the BNCweb code this was copied from)
		$sql = "select $item, count($item) as observed, $E11 as expected,
			sign($O11 - $E11) * $N * pow(abs($O11 * $O22 - $O12 * $O21) -  ($N / 2), 2) /
			(pow($R1*$R2,1) * pow($C1*$C2,1)) as significance,
			$freq_table.freq, count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table
			$sql_endclause";
		
		$sql = "select $item, count($item) as observed, $E11 as expected,
			sign($O11 - $E11) * $N *
			((abs($O11 * $O22 - $O12 * $O21) - ($N / 2)) / ($R1 * $R2)) *
			((abs($O11 * $O22 - $O12 * $O21) - ($N / 2)) / ($C1 * $C2))
			as significance,
			$freq_table.freq, count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table
			$sql_endclause";
		break;
	*/
	
	case 6:		/* Log likelihood */
		$sql = "select $item, count($item) as observed, $E11 as expected,
			sign($O11 - $E11) * 2 * (
				IF($O11 > 0, $O11 * log($O11 / $E11), 0) +
				IF($O12 > 0, $O12 * log($O12 / $E12), 0) +
				IF($O21 > 0, $O21 * log($O21 / $E21), 0) +
				IF($O22 > 0, $O22 * log($O22 / $E22), 0)
			) as significance,
			$freq_table.freq, count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table
			$sql_endclause";
		break;
	
	case 7:		/* Dice coefficient */
		/* this one uses extra variables, so get these first */
		$result = do_mysql_query("SELECT COUNT(DISTINCT refnumber) from $dbname WHERE $range_condition");
		list($DICE_NODE_F) = mysql_fetch_row($result);
		$P_COLL_NODE = "(COUNT(DISTINCT refnumber) / $DICE_NODE_F)";
		$P_NODE_COLL = "(COUNT($item) / ($freq_table.freq))";
		
		$sql = "select $item, count($item) as observed, $E11 as expected,
			2 / ((1 / $P_COLL_NODE) + (1 / $P_NODE_COLL)) as significance, 
			$freq_table.freq, count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table 
			$sql_endclause";
		break;

	case 8:		/* Log Ratio filtered by log likelihood */
		/* 
		 * Before getting to the actual SQL, we need to add the LL filter to the end clause;
		 * use a base alpha of 0.05 and adjust to number of types we are testing. 
		 * Nothing is ever easy!
		 */
		/* make the LL cutoff globally available, for the infobox. Bugger me this code is ugly! */
		global $LL_CUTOFF;
		$n_comparisons = calculate_types_in_window($sql_endclause, array('order by significance desc', 
																		$limit_string,
																		"and $freq_table.freq >= $calc_minfreq_collocalone",
																		"having observed >= $calc_minfreq_together"
																		));
		$alpha = correct_alpha_for_familywise(0.05, $n_comparisons, 'Šidák');
		$r = new RFace($Config->path_to_r);
		list($LL_CUTOFF) = $r->read_execute(sprintf("qchisq(%E, df=1, lower.tail=FALSE)", $alpha));
		unset($r);
		$sql_endclause = str_replace('having observed', "having LogLikelihood >= $LL_CUTOFF and observed", $sql_endclause);
		/* NB note that this means that the LL filter does not apply in colloc-solo mode. */
	
		$sql = "select $item, count($item) as observed, $E11 as expected,
			log2( ($O11 / $R1) / (IF($O21 > 0, $O21, 0.5) / $R2) ) as significance ,
			sign($O11 - $E11) * 2 * (
				IF($O11 > 0, $O11 * log($O11 / $E11), 0) +
				IF($O12 > 0, $O12 * log($O12 / $E12), 0) +
				IF($O21 > 0, $O21 * log($O21 / $E21), 0) +
				IF($O22 > 0, $O22 * log($O22 / $E22), 0)
			) as LogLikelihood,
			$freq_table.freq, count(distinct(text_id)) as text_id_count
			from $dbname, $freq_table
			$sql_endclause";
		break;
	
	
	default:
		exiterror_arguments($stat, "Collocation script specified an unrecognised statistic!");
	}

	return $sql;
}


/* next two functions support the "create statistic" function */


function colloc_tagclause_from_filter($dbname, $att_for_calc, $primary_annotation, $tag_filter)
{
	/* there may or may not be a primary_annotation filter; $tag_filter is from _GET, so check it */
	if (isset($tag_filter) && $tag_filter != false && $att_for_calc != $primary_annotation)
	{
		/* as of v2.11, tag restrictions are done with REGEXP, not = as the operator 
		 * if there are non-Word characters in the restriction; since tags usually
		 * are alphanumeric, defaulting to = may save procesing time.
		 * As with CQP, anchors are automatically added. */
		if (preg_match('/\W/', $tag_filter))
		{
			$tag_filter = regex_add_anchors($tag_filter);
			$tag_clause_operator = 'REGEXP';
		}
		else
			$tag_clause_operator = '=';
		
		/* tag filter is set and applies to a DIFFERENT attribute than the one being calculated */
		
		return "and $dbname.`$primary_annotation` $tag_clause_operator '"
			. mysql_real_escape_string($tag_filter)
			. "' ";
	}
	else
		return '';
}





function calculate_total_basis($basis_table)
{
	static $total_basis_cache;
	
	if ( ! isset($total_basis_cache[$basis_table]))	
	{
		$sql = 'select sum(freq) from ' . mysql_real_escape_string($basis_table);
		list($total_basis_cache[$basis_table]) = mysql_fetch_row(do_mysql_query($sql));
	}
	
	return $total_basis_cache[$basis_table];
}





/**
 * Calculates the total number of word tokens in the collocation window
 * described by the paramters $calc_range_begin, $calc_range_end
 * for the specified $dbname.
 */
function calculate_words_in_window($dbname, $calc_range_begin, $calc_range_end)
{
	$sql = "SELECT COUNT(*) from $dbname";
	
	if ($calc_range_begin == $calc_range_end)
		$sql .= " where dist = $calc_range_end";
	else
		$sql .= " where dist between $calc_range_begin and $calc_range_end";
		
	/* note that MySQL 'BETWEEN' is inclusive of the limit-values */
	
	$r = mysql_fetch_row(do_mysql_query($sql));

	return $r[0];
}



/**
 * Calculates the total number of word/annotation types in the collocation window
 * using a fragment of the main query (that designed as $sql_endclause).
 * 
 * Uses (some of the) same global values as the calling function.
 * 
 * Not to be called by any function other than create_statistic_sql_query()!
 */
function calculate_types_in_window($sql_endclause, $strings_to_remove_from_endclause = array() )
{
	global $dbname;
	global $freq_table_to_use;
	global $att_for_calc;
	
	$item = "$dbname.`$att_for_calc`";
	
	/* this is a dirty, dirty hack. The gods of modular programming will frown upon me. */
	foreach($strings_to_remove_from_endclause as $s)
		$sql_endclause = str_replace($s, '', $sql_endclause);
	/* note: we need to filter out based on the RANGE limits, but NOT based on the frequency cutoffs. */
	
	$sql_query = "select $item, count($item) as observed
			from $dbname, $freq_table_to_use
			$sql_endclause";
	
	return mysql_num_rows(do_mysql_query($sql_query));

//	global $dbname;
//	global $att_for_calc;
//	list($n) = mysql_fetch_row(do_mysql_query("select count(distinct($att_for_calc)) from $dbname"));
//	return $n;
}











// prob don't need this function - can use corpus_annotation_taglist()

function colloc_table_taglist($field, $dbname)
{
	/* shouldn't be necessary...  but hey */
	$field = mysql_real_escape_string($field);
	$dbname = mysql_real_escape_string($dbname);
	
	/* this function WILL NOT RUN on word - the results would be huge & unwieldy */
	if ($field == 'word')
		return array();
	/* this does not block it running on p-atts other than word that are equally huge, but we can't head off every problem. */
	
	$sql = "select distinct(`$field`) from `$dbname` limit 1000";
	$result = do_mysql_query($sql);
	
	$tags = array();
	while ( ($r = mysql_fetch_row($result)) !== false )
		$tags[] = $r[0];
	
	sort($tags);
	return $tags;
}












function run_script_for_solo_collocation()
{
	/* note, this function is really just a moved-out-of-the-way chunk of the script */
	/* it assumes all the globals of collocation.inc.php and won't run anywhere else */
	global $Config;
	global $Corpus;
	
	/* Globalling-in all the variables of the Collocations script. */
	global $statistic;
	global $soloform;
	global $tag_filter;
	global $calc_range_begin;
	global $calc_range_end;
	global $att_for_calc;
	global $query_record;
	global $dbname;
// 	global $primary_annotation;
	
	/* bdo tags ensure that l-to-r goes back to normal after an Arabic (etc) string */
	$bdo_tag1 = ($Corpus->main_script_is_r2l ? '<bdo dir="ltr">' : '');
	$bdo_tag2 = ($Corpus->main_script_is_r2l ? '</bdo>' : '');
	
	$soloform_sql = mysql_real_escape_string($soloform);
	$soloform_html = escape_html($soloform);

	foreach ($statistic as $s => $info)
	{
		$sql = create_statistic_sql_query($s, $soloform_sql);
		$result = mysql_query($sql);
				
		$counts = mysql_fetch_assoc($result);
		
		/* adjust number formatting : expected -> 3dp, significance -> 4dp, freq-> thousands*/
		if ( empty($counts['significance']) )
			$statistic[$s]['value'] = 'n/a';
		else
			$statistic[$s]['value'] = round($counts['significance'], 3);
	
	}

	/* this lot don't need doing on every iteration; they pick up their values from its last loop */
	$observed_to_show = number_format((float)$counts['observed']);
	$observed_for_calc = $counts['observed'];
	$expected_to_show = round($counts['expected'], 3);
	$basis_to_show = number_format((float)$counts['freq']);
	$number_of_texts_to_show = number_format((float)$counts['text_id_count']);
	
// 	if (empty($query_record->subcorpus) && $query_record->restrictions == '')
	if (QueryScope::TYPE_WHOLE_CORPUS == $query_record->qscope->type)
		$basis_point = 'the whole corpus';
	else
		$basis_point = 'the current subcorpus';
	
	echo print_html_header($Corpus->title . ' -- CQPweb collocation results', $Config->css_path)
	
	?>

	<table class="concordtable" width="100%">
		<tr>

	<?php	
	/* check that soloform actually occurs at all */
	if (empty($basis_to_show))
	{
		echo '<td class="concordgeneral"><strong>'
			, "<em>$soloform_html</em> does not collocate with &ldquo;"
	 		, escape_html($query_record->cqp_query)
			, "&rdquo within a window of $calc_range_begin to $calc_range_end."
			, '</strong></td></tr></table>'
 			;
		return;
	}

	echo '<th class="concordtable" colspan="2">';
	
	$tag_description = (empty($tag_filter) ? '' : " with tag restriction <em>$tag_filter</em>");
	
	echo "Collocation information for the node &ldquo;"
 		, escape_html($query_record->cqp_query)
		, "&rdquo; collocating with &ldquo;$soloform_html&rdquo; $tag_description $bdo_tag1($basis_to_show occurrences in $basis_point)$bdo_tag2 "
 		;

	echo "
 			</th>
		</tr>
		<tr>
			<th class=\"concordtable\" width=\"50%\">Type of statistic</th>
			<th class=\"concordtable\" width=\"50%\">
				Value (for window span $calc_range_begin to $calc_range_end)
			</th>
		</tr>"
 		;
	

	
	foreach ($statistic as $s => $info)
	{
		/* skip "rank by frequency" */
		if ($s == 0)
			continue;
			
		echo "
			<tr>
				<td class=\"concordgrey\">{$info['desc']}</td>
				<td class=\"concordgeneral\" align=\"center\">{$info['value']}</td>
			</tr>";
	}
	echo '</table>
		';
	
	echo '<table class="concordtable" width="100%">
		';
	
	echo "
		<tr>
			<th class=\"concordtable\" colspan=\"4\">
				Within the window $calc_range_begin to $calc_range_end, <em>$soloform_html</em> occurs
				$observed_to_show times in 
				$number_of_texts_to_show different texts 
				(expected frequency: $expected_to_show)
			</th>
		</tr>
		<tr>
			<th class=\"concordtable\" align=\"left\">Distance</th>
			<th class=\"concordtable\">No. of occurrences</th>
			<th class=\"concordtable\">In no. of texts</th>
			<th class=\"concordtable\">Percent</th>
		</tr>
		";
		
	for ($i = $calc_range_begin ; $i <= $calc_range_end ; $i++)
	{
		if ($i == 0)
		{
			?><tr><td colspan="4"></td></tr><?php
			continue;
		}

		$tag_clause = colloc_tagclause_from_filter($dbname, $att_for_calc, $Corpus->primary_annotation, $tag_filter);

		$sql = "SELECT count(`$att_for_calc`) as n_hits, count(distinct(text_id)) as n_texts
			FROM $dbname
			WHERE `$att_for_calc` = '$soloform_sql'
			$tag_clause
			AND dist = $i
			";

		$counts = mysql_fetch_object(do_mysql_query($sql));

		if ($counts->n_hits == 0)
		{
			$link = $i;
			$n_hits  = 0;
			$n_texts = 0;
			$percent = 0;
		}
		else
		{
			$link = "<a href=\"concordance.php?qname={$query_record->qname}&newPostP=coll"
				. "&newPostP_collocDB=$dbname&newPostP_collocDistFrom=$i&newPostP_collocDistTo=$i"
				. "&newPostP_collocAtt=$att_for_calc&newPostP_collocTarget="
				. urlencode($soloform)
				. "&newPostP_collocTagFilter="
				. urlencode($tag_filter)
				. "&uT=y\" onmouseover=\"return escape('Show solutions collocating with "
				. "<B>" . addslashes($soloform_html) . "</B> at position <B>$i</B>')\">$i</a>";
			$n_hits  = number_format((float)$counts->n_hits);
			$n_texts = number_format((float)$counts->n_texts);
			$percent = round(($counts->n_hits/$observed_for_calc)*100.0, 1);	
		}
		echo "
			<tr>
				<td class=\"concordgrey\">$link</td>
				<td class=\"concordgeneral\" align=\"center\">$n_hits</td>
				<td class=\"concordgeneral\" align=\"center\">$n_texts</td>
				<td class=\"concordgeneral\" align=\"center\">$percent%</td>
			</tr>";
	}

	echo "</table>";
}








function collocation_write_download(
	$att_for_calc, 
	$calc_stat, 
	$att_desc, 
	$basis_desc, 
	$stat_desc, 
	$description, 
	&$result			//TODO: why is this a reference? I don't see why it needs to be....
	)
{
	global $User;
	$eol = get_user_linefeed($User->username);
	
	$description = preg_replace('/&([lr]dquo|quot);/', '"', $description);
	$description = preg_replace('/<span .*>/', '', $description);

	header("Content-Type: text/plain; charset=utf-8");
	header("Content-disposition: attachment; filename=collocation_list.txt");
	echo "$description$eol";
	echo "__________________$eol$eol";
	$sighead = ($calc_stat == 0 ? '' : "\t$stat_desc value");
	echo "No.\t$att_desc\tTotal no. in $basis_desc\tExpected collocate frequency\t"
		. "Observed collocate frequency\tIn no. of texts$sighead";
	echo "$eol$eol";


	for ($i = 1; ($row = mysql_fetch_assoc($result)) !== false ; $i++ )
	{
		/* adjust number formatting : expected -> 3dp, significance -> 4dp */
		if ( empty($row['significance']) )
			$row['significance'] = 'n/a';
		else
			$row['significance'] = round($row['significance'], 3);
		$row['expected'] = round($row['expected'], 3);
		
		$sig = ($calc_stat == 0 ? '' : "\t{$row['significance']}");
		
		echo "$i\t{$row[$att_for_calc]}\t{$row['freq']}\t{$row['expected']}\t{$row['observed']}";
		echo "\t{$row['text_id_count']}$sig$eol";
	}
}


