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



/* print a form to collect the options for running collocations */

/* initialise variables from settings files  */
require('../lib/environment.inc.php');


/* include function library files */
require("../lib/library.inc.php");
require("../lib/html-lib.inc.php");
require("../lib/user-lib.inc.php");
require("../lib/exiterror.inc.php");
require("../lib/cqp.inc.php");
require("../lib/xml.inc.php");
require("../lib/cache.inc.php");
require("../lib/subcorpus.inc.php");
require("../lib/freqtable.inc.php");
require("../lib/concordance-lib.inc.php");

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP);


/* check parameters - only one we really need is qname */

$qname = safe_qname_from_get();


// $query_record = check_cache_qname($qname);
$query_record = QueryRecord::new_from_qname($qname);
if ($query_record === false)
	exiterror_general("The specified query $qname was not found in cache!");



echo print_html_header("{$Corpus->title} -- CQPweb Collocation Options", $Config->css_path, array('cword', 'colloc-options'));

/* now print the options form */
?>


<table width="100%" class="concordtable" id="tableCollocProximity">
	<form action="collocation.php" method="get">
		<tr>
			<th colspan="3" class="concordtable">
				Choose settings for proximity-based collocations:
			</th>
		</tr>
		<tr>
			<?php
			/* get a list of annotations && the primary && count them for this corpus */
			$result_annotations = do_mysql_query("select * from annotation_metadata where corpus = '{$Corpus->name}'");
			
			$num_annotation_rows = mysql_num_rows($result_annotations);
			
// 			$sql_query = "select primary_annotation from corpus_info where corpus = '{$Corpus->name}'";
// 			$result_fixed = do_mysql_query($sql_query);
// 			/* this will only contain a single row */
// 			list($primary_att) = mysql_fetch_row($result_fixed);
			$primary_att = $Corpus->primary_annotation;

			?>
			
			<td rowspan="<?php echo $num_annotation_rows; ?>" class="concordgrey">
				Include annotation:
			</td>

			<?php
			$i = 1;
			while (false !== ($annotation = mysql_fetch_assoc($result_annotations)) )
			{
				echo '<td class="concordgeneral" align="left">';
				if ($annotation['description'] != '')
					echo $annotation['description'];
				else
					echo $annotation['handle'];

				if ($annotation['handle'] == $primary_att) 
				{
					$vc_include = 'value="1" checked="checked"';
					$vc_exclude = 'value="0"';
				}
				else
				{
					$vc_include = 'value="1"';
					$vc_exclude = 'value="0" checked="checked"';
				}
					
				echo "</td>
					<td class=\"concordgeneral\" align=\"center\">
						<input type=\"radio\" name=\"collAtt_{$annotation['handle']}\" $vc_include />
						Include
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type=\"radio\" name=\"collAtt_{$annotation['handle']}\" $vc_exclude />
						Exclude				
					</td>
					</tr>
					";
				if ($i < $num_annotation_rows)
					echo '  <tr>';
				$i++;
			}
			?>

		<tr>
			<td class="concordgrey">Maximum window span:</td>
			<td class="concordgeneral" align="center" colspan="2">
				+ / -
				<select name="maxCollocSpan">
					<?php
					
					$span_option = array();
					for ($i = 4 ; $i < 11 ; $i++)
						$span_option[$i] = "<option>$i</option>";
						
					$user_pref_span = max($User->coll_from, $User->coll_to);
					
					/* note, we don't allow spans greater than 10. 
					 * HOWEVER, just in case a bigger number has snuck inot the user_info table.... */ 
					if ( ! ($user_pref_span > 5 && $user_pref_span < 11) )
						$user_pref_span = 5;
					$span_option[$user_pref_span] = "<option selected=\"selected\">$user_pref_span</option>";

					echo "\n";
					foreach($span_option as $s_o)
						echo "\t\t\t\t\t", $s_o, "\n";
					/* Above code replaces the hard-coded dropdown in earlier versions:
					<option>4</option>
					<option selected="selected">5</option>
					<option>6</option>
					<option>7</option>
					<option>8</option>
					<option>9</option>
					<option>10</option>
					*/
					?>
				</select>
			</td>
		</tr>
		<?php 


		
		/*
		Other potential options: 
		s-attributes: the option of crossing or not crossing their boundaries
		(but this is way, way down the list of TODO)
		foreach xml annotation that is not a member of a family: two radio buttons: cross/don't cross. All default to dont cross.
		*/
		
		
		echo print_warning_cell($query_record);
		
		
		?>
		
		<tr>
			<th colspan="3" class="concordtable">
				<input type="submit" value="Create collocation database"/>
			</th>
		</tr>
		<?php 
			echo "\n<input type=\"hidden\" name=\"qname\" value=\"$qname\" />\n";
		?>
		<input type="hidden" name="uT" value="y" />
	</form>	
</table>

<?php 



/* ---------------------------------------------------- *
 * end of proximity control; start of syntactic control *
 * ---------------------------------------------------- */



// if false: I don't want this switched on just yet!
if (false) 
{
	/* ultimate intention: this if will check whether any syntactic collocations are actually available */
	?> 


<table width="100%" class="concordtable" id="tableCollocSyntax">
	<form action="collocation.php" method="get">
		<tr>
			<th colspan="3" class="concordtable">
				Syntactic collocations - choose settings:
			</th>
		</tr>
		<tr>
			<?php
			/* get a list of annotations && the primary && count them for this corpus */
			$sql = "select * from annotation_metadata where corpus = '{$Corpus->name}'";
			$result_annotations = do_mysql_query($sql);
			
			$num_annotation_rows = mysql_num_rows($result_annotations);
			
			$sql = "select primary_annotation from corpus_info 
				where corpus = '{$Corpus->name}'";
			$result_fixed = do_mysql_query($sql);
			/* this will only contain a single row */
			list($primary_att) = mysql_fetch_row($result_fixed);

			?>
			
			<td rowspan="<?php echo $num_annotation_rows; ?>" class="concordgrey">
				Include annotation:
			</td>

			<?php
			$i = 1;
			while (($annotation = mysql_fetch_assoc($result_annotations)) != false)
			{
				echo '<td class="concordgeneral" align="left">';
				if ($annotation['description'] != '')
					echo $annotation['description'];
				else
					echo $annotation['handle'];

				if ($annotation['handle'] == $primary_att) 
				{
					$vc_include = 'value="1" checked="checked"';
					$vc_exclude = 'value="0"';
				}
				else
				{
					$vc_include = 'value="1"';
					$vc_exclude = 'value="0" checked="checked"';
				}
					
				echo "</td>
					<td class=\"concordgeneral\" align=\"center\">
						<input type=\"radio\" name=\"collAtt_{$annotation['handle']}\" $vc_include />
						Include
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type=\"radio\" name=\"collAtt_{$annotation['handle']}\" $vc_exclude />
						Exclude				
					</td>
					</tr>
					";
				if ($i < $num_annotation_rows)
					echo '  <tr>';
				$i++;
			}
			?>

		<tr>
			<td class="concordgrey">Maximum window span:</td>
			<td class="concordgeneral" align="center" colspan="2">
				+ / -
				<select name="maxCollocSpan">
					<option>4</option>
					<option selected="selected">5</option>
					<!-- shouldn't this be related to the default option? -->
					<option>6</option>
					<option>7</option>
					<option>8</option>
					<option>9</option>
					<option>10</option>
				</select>
			</td>
		</tr>
		<?php 
		/*
		Other potential options: 
		the one about crossing/not crossing s-attributes that was mentioned above prob does not apply here, since it is dependencybased....
		*/
		
		// TODO. Work out what kind of warning, if any, is needed here.
		//echo print_warning_cell($query_record);
		
		
		?>
		
		<tr>
			<th colspan="3" class="concordtable">
				<input type="submit" value="Create database of syntactic collocations"/>
			</th>
		</tr>
		<?php   echo "\n<input type=\"hidden\" name=\"qname\" value=\"$qname\" />\n";  ?> 
		<input type="hidden" name="uT" value="y" />
	</form>	
</table>
<?php

	/* end if syntactic collocations are available */
}

if (false) // also temp, the next html block will be unconditional
{
?>



<table width="100%" class="concordtable">
	<tr>
		<td class="concordgrey" align="center">
			&nbsp;<br/>
			<a href="" class="menuItem" id="linkSwitchControl">
				<!-- no inner HTML, assigned via JavaScript -->
			</a>
			<br/>&nbsp;
		</td>
	</tr>
</table>


<?php
}

echo print_html_footer('collocopt');


cqpweb_shutdown_environment();


/* ------------- *
 * end of script *
 * ------------- */




/**
 * 
 * @param QueryRecord $query_record
 */
function print_warning_cell($query_record)
{
	global $Config;
//	global $collocation_warning_cutoff;
//	global $collocation_disallow_cutoff;
	
	$issue_warning = false;


// 	/* if there is a subcorpus / restriction, check whether it has frequency lists */
// 	if (QueryScope::TYPE_SUBCORPUS == $query_record->qscope->type)
// 	{
// 		$sc = Subcorpus::new_from_id($query_record->subcorpus);
// 		if (false === $sc || false === ($freqtable_record = $sc->get_freqtable_record()))
// // 		if ( ($freqtable_record = check_freqtable_subcorpus($query_record->subcorpus)) == false )
// 			$issue_warning = true;
// 	}
// 	else if (QueryScope::TYPE_RESTRICTION == $query_record->qscope->type)
// 	{
// 		if ( false == ($freqtable_record = check_freqtable_restriction($query_record->qscope->serialise())) == false )
// 			$issue_warning = true;
// 	}
	if (QueryScope::TYPE_WHOLE_CORPUS != $query_record->qscope->type) 
	{
		$freqtable_record = $query_record->qscope->get_freqtable_record();
		if (false === $freqtable_record)
			$issue_warning = true;
	}
	

	/* if either (a) it's the whole corpus or (b) a freqtable was found */
	if ( ! $issue_warning)
		return '';

	$words = $query_record->get_tokens_searched_reduced();
	if ( $words >= $Config->collocation_disallow_cutoff )
		/* we need to point out that the main corpus WILL be used */
		$s = '
			<tr>
				<td class="concorderror" colspan="3">
					The current set of hits was retrieved from a large subpart of the corpus 
					(' . number_format((float)$words) . ' words). No cached frequency data
					was found, and this is too much text for frequency lists to be compiled 
					on the fly in order to provide accurate measures of collocational strength. 
					<br/>&nbsp;<br/>
					The frequency lists for the main corpus will be used instead (less precise
					results, but probably reliable if word-frequencies are 
					relatively homogenous across the corpus).

					<input type="hidden" name="freqtableOverride" value="1" />
				</td>
			</tr>
			';
	else if ( $words >= $Config->collocation_warning_cutoff )
		/* we need a major warning */
		$s = '
			<tr>
				<td class="concorderror" colspan="2">
					The current set of hits was retrieved from a large subpart of the corpus 
					(' . number_format((float)$words) . ' words). No cached frequency data
					was found and frequency lists for the relevant part of the corpus will have to 
					be compiled in order to provide accurate measures of collocational strength. 
					Depending on the size of the subcorpus this may take several minutes and may
					use a lot of disk space.
					<br/>&nbsp;<br/>
					Alternatively, you can use the frequency lists for the main corpus (less precise
					results, but will run faster and is a valid option if word-frequencies are 
					relatively homogenous across the corpus).
				</td>
				<td class="concordgeneral">
					<select name="freqtableOverride">
						<option value="1" selected="selected">Use main corpus frequency lists</option>
						<option value="0">Compile accurate frequency lists</option>
					</select>
				</td>
			</tr>
			';
	else
		/* a minor warning will do */
		$s = '
			<tr>
				<td class="concorderror" colspan="3">
					<strong>Note:</strong> The current set of hits was retrieved from a subpart 
					of the corpus (' . number_format((float)$words) . ' words). No cached frequency data 
					was found and frequency lists for the relevant part of the corpus will have to 
					be compiled in order to provide accurate measures of collocational strength. 
			 		This will increase the time needed for the calculation - please be patient.
				</td>
			</tr>
			';

	return $s;
}



