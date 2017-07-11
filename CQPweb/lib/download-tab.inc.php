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



/* initialise variables from settings files  */
require('../lib/environment.inc.php');

/* include function library files */
require('../lib/library.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/metadata.inc.php');
require('../lib/xml.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/cqp.inc.php');


cqpweb_startup_environment();





/* ----------------- *
 * Permissions check *
 * ----------------- */

if (PRIVILEGE_TYPE_CORPUS_RESTRICTED >= $Corpus->access_level)
{
	/*
	 * Tabulation download is only available to those with a NORMAL or FULL level privilege for the present corpus.
	 */
	echo print_html_header("{$Corpus->title} -- CQPweb tabulate query", $Config->css_path);
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Restricted access: tabulation download is not available.
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p class="spacer">&nbsp;</p>
				<p>
					You only have <b>restricted-level</b> access to this corpus. You cannot download a data tabulation.
					This is usually for copyright or licensing reasons.
				</p>
				<p class="spacer">&nbsp;</p>
			</td>
		</tr>
	</table>
	<?php

	echo print_html_footer('hello');
	cqpweb_shutdown_environment();
	exit;
}







/* variables needed by both versions of this script */

$qname = safe_qname_from_get();

$possible_atts = array_merge(get_corpus_annotations(), list_xml_with_values($Corpus->name));




if ( isset($_GET['downloadGo']) && $_GET['downloadGo'] === 'yes')
{
	/* ----------------------------- *
	 * create and send the text file *
	 * ----------------------------- */

	/* parse download variables. */

	/* the filename for the output */
	$filename = (isset($_GET['downloadFilename']) ? preg_replace('/\W/', '', $_GET['downloadFilename']) : '' );
	if ($filename == '')
		$filename = 'tabulation';
	$filename .= '.txt';


	/* linebreak */
	if (isset($_GET['downloadLinebreak']))
	{
		$eol = preg_replace('/[^da]/', '', $_GET['downloadLinebreak']);
		$eol = strtr($eol, "da", "\r\n");
	}
	else
		$eol = get_user_linefeed($User->username);

	/* now on to the descriptor for the table... */

	if (empty($_GET['tabSpecial']))
	{
		$column_specs = array();

		/* a variable used in the following loop */
		$anchors = array('match', 'matchend', 'target', 'keyword'); // TODO prescreen for absence of target and keyword. See alo note below.

		for ( $i = 1 ; isset($_GET["c{$i}_att"]) ; $i++ )
		{
			switch ($_GET["c{$i}_att"])
			{
			case '~~nothing':
				continue 2;
			case '~~cpos':
				$att = '';
				break;
			case 'word':
				$att = 'word';
				break;
			default:
				if (array_key_exists($_GET["c{$i}_att"], $possible_atts))
					$att = $_GET["c{$i}_att"];
				else
					exiterror_general("The attribute you specified for column $i is not available.");
				break;
			}
			
			/* what position do we want to use? */
			
			/* begin point */
			if (! isset($_GET["c{$i}_beginAnch"]))
				exiterror_parameter("Missing begin anchor for column $i.");
			if (in_array($_GET["c{$i}_beginAnch"], $anchors))
				$begin_anchor = $_GET["c{$i}_beginAnch"];
			else
				exiterror_parameter("Invalid begin anchor for column $i.");
			$begin_offset = ( isset($_GET["c{$i}_beginOff"]) ? (int)$_GET["c{$i}_beginOff"] : 0);
			/* note, this is a heuristic, since max_extended_context is meant to apply to the beginning/end
			 * of the match. But this will not have any effect unless max_extended_context is very low, as
			 * it only budges what is possible by one or two tokens, in most cases. */
			if (abs($begin_offset) > $Corpus->max_extended_context)
				exiterror_general("In this corpus, you are not permitted to tabulate positions with an offset greater than {$Corpus->max_extended_context}.");
			
			/* end point! */
			if (! isset($_GET["c{$i}_endAnch"]))
				exiterror_parameter("Missing end anchor for column $i.");
			if (in_array($_GET["c{$i}_endAnch"], $anchors))
				$end_anchor = $_GET["c{$i}_endAnch"];
			else
				exiterror_parameter("Invalid end anchor for column $i.");
			$end_offset = ( isset($_GET["c{$i}_endOff"]) ? (int)$_GET["c{$i}_endOff"] : 0);
			if (abs($end_offset) > $Corpus->max_extended_context)
				exiterror_general("In this corpus, you are not permitted to tabulate positions with an offset greater than {$Corpus->max_extended_context}.");
			
			/* build range spec */
			$range = ($begin_offset == 0 ? "$begin_anchor" : "{$begin_anchor}[$begin_offset]");
			if ($end_anchor == $begin_anchor && $end_offset == $begin_offset)
				;
			else
				$range .= ($end_offset == 0 ? " .. $end_anchor" : "..{$end_anchor}[$end_offset]");
			

			$flags  = ( (isset($_GET["c{$i}_ncase"]) && $_GET["c{$i}_ncase"] == '1')? '%c' : '');
			$flags .= ( (isset($_GET["c{$i}_ndiac"]) && $_GET["c{$i}_ndiac"] == '1')? '%d' : '');
			
			/* build full column spec and add to list */
			$column_specs[] = "$range $att $flags";
		}
		if (empty($column_specs))
			exiterror_general("No columns have been defined for the tabulation!");
		else
			$descriptor = implode(', ', $column_specs);
	}
	else
	{
		/* built in tabulation formats */
		switch ($_GET['tabSpecial'])
		{
		case 'FirstWordOnly':
			$descriptor = 'match word %c';
			break;
		case 'FullHitWordOnly':
			$descriptor = 'match .. matchend word %c';
			break;
		case 'FirstCposOnly':
			$descriptor = 'match';
			break;
		case 'FirstCposAndTags':
			$descriptor = 'match, match word %c';
			foreach(get_corpus_annotations() as $k =>$v)
				$descriptor .= ", match $k";
			break;
		default:
			exiterror_general("You haev requested a standard tabulation format which was not recognised.");
			break;
		}
	}




	/*
	 * OK, we are now ready to start calling CQP.
	 */

	list($num_of_solutions) = $cqp->execute("size $qname");
	/* before running the loop, unlimit in case of big query */
	if ($num_of_solutions > 100)
		php_execute_time_unlimit();

	/* send the HTTP header */
	header("Content-Type: text/plain; charset=utf-8");
	header("Content-disposition: attachment; filename=$filename");
	
	for ($batch_start = 0; $batch_start < $num_of_solutions; $batch_start += 100) 
	{
		$batch_end = $batch_start + 99;
		if ($batch_end >= $num_of_solutions)
			$batch_end = $num_of_solutions - 1; 
			
		$cqp_command = "tabulate $qname $batch_start $batch_end $descriptor";

		$result = $cqp->execute($cqp_command);
		
		foreach ($result as &$line)
			echo $line, $eol;
	}

	/* just in case ... */
	if ($num_of_solutions > 100)
		php_execute_time_relimit();
	
	
	
} /* end of if ($_GET['downloadGo'] === 'yes') */

else

{
	/* --------------------------------------- *
	 * write an HTML page with all the options *
	 * --------------------------------------- */

	/* --------------------------------------------
	 * First, set up variables for use in the HTML.
	 * -------------------------------------------- */

	/* enable the user setting to be auto-selected for linebreak type */
	$da_selected = array('d' => '', 'a' => '', 'da' => '');
	if ($User->linefeed == 'au')
		$User->linefeed = guess_user_linefeed($User->username);
	$da_selected[$User->linefeed] = ' selected="selected" ';
	
	
	if (isset($_GET['columnCount']))
		$n_output_columns = (int)$_GET['columnCount'];
	else
		$n_output_columns = 5;
	
	$selector_anchor = <<<END
	
		<option>match</option>
		<option>matchend</option>
		<option>target</option>
		<option>keyword</option>
	
END;
// TODO, is there a way of finding out if a query has a target / keyword and only showing if they are defined?
// for now just allow access.

	$selector_attribute = <<<END
		<option value="~~nothing">(Column not in use)</option>
		<option value="~~cpos">Corpus position number</option>
		<option>word</option>
END;
	foreach($possible_atts as $k => $v)
		$selector_attribute .= "\t\t<option>$k</option>\n";

	$selector_offset = '';
	for ($i = -5 ; $i <= 5 ; $i++)
		if ($i == 0)
			$selector_offset .= "\t\t<option value=\"0\" selected=\"selected\">no offset</option>\n";
		else
			$selector_offset .= "\t\t<option>$i</option>\n";


	/* --------------------------------------------------
	 * Now, write the page with the tabulate-define form.
	 * -------------------------------------------------- */
	
	/* before anything else */
	
	echo print_html_header($Corpus->title . ' -- CQPweb tabulate query', $Config->css_path, array('cword'));
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="9">Download Query Tabulation</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="9">

			<p>&nbsp;</p>
			
			<p>A "Tabulation" is a plain-text table containing, for each result in a query, a series of related items of data from the underlying corpus index.</p>
			
			<p>Each column contains one particular item of data, specified for either a given position or for a range of positions, relative to the query result.</p>
			
			<p>You can select a commonly-used tabulation, or define the contents of each column, using the controls below.</p> 
			
			<p>
				For technical details of Tabulation, see 
				<a href="http://cwb.sourceforge.net/files/CQP_Tutorial/node39.html" target="_blank">the CQP tutorial section 6.3</a>.
			</p> 
			
			<p>&nbsp;</p>
			
			</td>
		</tr>

		<tr>
			<th class="concordtable" colspan="9">Frequently-used tabulations</th>
		</tr>
		
		<tr>
			<td class="concordgeneral" colspan="9" align="center">

				&nbsp;<br/>
				<form action="redirect.php" method="get">
					<input type="submit" 
						value="Table with first word of each hit" />
					<br/>
					<input type="hidden" name="redirect" value="download-tab" />
					<input type="hidden" name="qname" value="<?php echo $qname; ?>" />
					<input type="hidden" name="downloadGo" value="yes" />
					<input type="hidden" name="tabSpecial" value="FirstWordOnly" />
					<input type="hidden" name="uT" value="y" />
				</form>
				<form action="redirect.php" method="get">
					<input type="submit" 
						value="Table of all words (use for multi-word queries)" />
					<br/>
					<input type="hidden" name="redirect" value="download-tab" />
					<input type="hidden" name="qname" value="<?php echo $qname; ?>" />
					<input type="hidden" name="downloadGo" value="yes" />
					<input type="hidden" name="tabSpecial" value="FullHitWordOnly" />
					<input type="hidden" name="uT" value="y" />
				</form>
				<form action="redirect.php" method="get">
					<input type="submit" 
						value="Table with corpus position of first word of each hit" />
					<br/>
					<input type="hidden" name="redirect" value="download-tab" />
					<input type="hidden" name="qname" value="<?php echo $qname; ?>" />
					<input type="hidden" name="downloadGo" value="yes" />
					<input type="hidden" name="tabSpecial" value="FirstCposOnly" />
					<input type="hidden" name="uT" value="y" />
				</form>
				<form action="redirect.php" method="get">
					<input type="submit" 
						value="Table with corpus position and all tags of initial word" />
					<br/>
					<input type="hidden" name="redirect" value="download-tab" />
					<input type="hidden" name="qname" value="<?php echo $qname; ?>" />
					<input type="hidden" name="downloadGo" value="yes" />
					<input type="hidden" name="tabSpecial" value="FirstCposAndTags" />
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
		<tr>
			<th class="concordtable" colspan="9">Specify custom tabulation</th>
		</tr>	
		<tr>
			<th class="concordtable" rowspan="2">Col. no.</th>
			<th class="concordtable" colspan="2">Begin at</th>
			<th class="concordtable" colspan="2">End at</th>
			<th class="concordtable" rowspan="2">Attribute</th>
			<th class="concordtable" colspan="2">Normalise?</th>
		</tr>
		<tr>
			<th class="concordtable" width="8%">Anchor</th>
			<th class="concordtable" width="8%">Offset</th>
			<th class="concordtable" width="8%">Anchor</th>
			<th class="concordtable" width="8%">Offset</th>
			<th class="concordtable" width="8%">Case</th>
			<th class="concordtable" width="8%">Diacritics</th>
		</tr>
		
		<form action="redirect.php" method="get">

		<?php
		
			for ($i = 1 ; $i <= $n_output_columns ; $i++)
			{
				?>
				<tr>
					<td class="concordgrey" align="center">
						<?php echo $i; ?>
					</td>
					<td class="concordgeneral" align="center">
						<select name="c<?php echo $i; ?>_beginAnch">
							<?php echo $selector_anchor; ?>
						</select>
					</td>
					<td class="concordgeneral" align="center">
						<select name="c<?php echo $i; ?>_beginOff">
							<?php echo $selector_offset; ?>
						</select>
					</td>
					<td class="concordgeneral" align="center">
						<select name="c<?php echo $i; ?>_endAnch">
							<?php echo $selector_anchor; ?>
						</select>
					</td>
					<td class="concordgeneral" align="center">
						<select name="c<?php echo $i; ?>_endOff">
							<?php echo $selector_offset; ?>
						</select>
					</td>
					<td class="concordgeneral"  align="center">
						<select name="c<?php echo $i; ?>_att">
							<?php echo $selector_attribute; ?>
						</select>
					</td>
					<td class="concordgeneral" align="center">
						<input type="checkbox" value="1" name="c<?php echo $i; ?>_ncase" />
					</td>
					<td class="concordgeneral" align="center">
						<input type="checkbox" value="1" name="c<?php echo $i; ?>_ndiac" />
					</td>
				</tr>
				<?php
			}
		?>

			<!-- $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ -->

			<!--
			<tr>
				<th class="concordtable" colspan="9">General options for download</th>
			</tr>
			-->
			<tr>

				<td class="concordgeneral" colspan="5">
					&nbsp;<br/>
					Choose operating system on which you will be working with the file:
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" colspan="4">
					&nbsp;<br/>
					<select name="downloadLinebreak">
						<option value="d"  <?php echo $da_selected['d']; ?>>Macintosh (OS 9 and below)</option>
						<option value="da" <?php echo $da_selected['da'];?>>Windows</option>
						<option value="a"  <?php echo $da_selected['a']; ?>>UNIX (incl. OS X)</option>
					</select>
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="5">
					&nbsp;<br/>
					Enter name for the downloaded file:
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" colspan="4">
					&nbsp;<br/>
					<input type="text" name="downloadFilename" value="tabulation" />
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="9" align="center">
					&nbsp;<br/>
					<input type="submit" value="Download query tabulation with settings above" />
					<br/>&nbsp;
				</td>
			<input type="hidden" name="redirect" value="download-tab" />
			<input type="hidden" name="downloadGo" value="yes" />
			<input type="hidden" name="qname" value="<?php echo $qname; ?>" />
			<input type="hidden" name="uT" value="y" />
			</tr>
		</form>
	
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="3">I need more output columns!</th>
		</tr>
		<form action="redirect.php" method="get">
			<tr>
				<td class="concordgeneral">
					Do you need more tabulation columns? Use this control:
				</td>
				<td class="concordgeneral">
					I want a tabulation with (up to) 
					<select name="columnCount">
						<option>9</option>
						<option>10</option>
						<option>11</option>
						<option>12</option>
						<option>14</option>
						<option>16</option>
						<option>20</option>
					</select>
					columns!
				</td>
				<td class="concordgeneral">
					<input type="submit" value="Create bigger form!" />
				</td>
			</td>
			<input type="hidden" name="redirect" value="download-tab" />
			<input type="hidden" name="qname" value="<?php echo $qname; ?>" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Switch download type</th>
		</tr>
		<form action="redirect.php" method="get">
			<tr>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Download query as plain-text concordance" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="redirect" value="download-conc" />
			<input type="hidden" name="qname" value="<?php echo $qname; ?>" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	<?php
	
	echo print_html_footer('downloadtab');

} /* end of the huge determining if-else */


/* disconnect CQP child process and mysql */
cqpweb_shutdown_environment();

/* end of script */

