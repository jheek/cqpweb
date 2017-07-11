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


// TODO A lot of the functions in this file could do with renaming in a way that at least AIMS to be systematic.





/**
 * Standardises the whitespace within a query string, and checks for its being empty.  
 * 
 * @param  string  $s  Query string submitted to concordance.php via GET.
 * @return string      Standardised string.
 */
function prepare_query_string($s)
{
// 	if (!preg_match('/\S/', $s))
// 		exiterror('You are trying to search for nothing!');

	/* remove extra whitespace and internal line breaks / tabs */
// 	$s = trim($s);
// 	/*  */
// 	$s = str_replace("\r", ' ', $s);
// 	$s = str_replace("\n", ' ', $s);
// 	$s = str_replace("\t", ' ', $s);
// 	$s = str_replace('  ', ' ', $s);
// 	$s = strtr($s, "\r\n\t", "   ");
	/* note we do NOT use %0D, %0A etc. because PHP htmldecodes for us. */
	$s = trim(preg_replace('/\s+/', ' ', $s));
	if ('' == $s)
		exiterror('You are trying to search for nothing!');
	
	return $s;
}


/**
 * This function gets one of the allowed query-mode strings from $_GET.
 * 
 * If no valid query-mode is specified, it (a) causes CQPweb to abort if
 * $strict is true; OR (b) returns NULL if $strict is false. 
 */
function prepare_query_mode($s, $strict)
{
	$s = strtolower($s);
	
	switch($s)
	{
	case 'sq_case':
	case 'sq_nocase':
	case 'cqp':
		return $s;
	default:
		if ($strict)
			exiterror('Invalid query mode specified!');
		else
			return NULL;
	}
}



/**
 * Translates a MySQL sort database field-name(*) to an integer sort position.
 * 
 * (* - not including the "tag", i.e. just node, beforeX, afterX)
 */
function integerise_sql_position($sql_position)
{
	if ( $sql_position == 'node' )
		return 0;
	
	/* failsafe to node ==> 0 */
	if (1 > preg_match('/^(before|after)(\d+)/', $sql_position, $m)) 
		return 0;
	
	return ($m[1]==='before' ? -1 : 1) * (int)$m[2] ;
}


/**
 * Translates an integer sort position to the equivalent MySQL sort database field-name.
 * 
 * @see integerise_sql_position
 */
function sqlise_integer_position($int_position)
{
	$ip = (int) $int_position;
	
	switch (true)
	{
	case (0 == $ip):
		return "node";
	case (0 >  $ip):
		return 'after' . abs($ip);
	case (0 <  $ip):
		return 'before' . abs($ip);
	}
}

/**
 * Translates an integer sort position to a string suitable to print in the UI.
 * 
 * @see integerise_sql_position
 */
function stringise_integer_position($int_position)
{
	global $Corpus;
	
	$ip = (int) $int_position;
	
	if ($ip == 0)
		return "at the node";
	
	$sign = ($ip < 0 ? -1 : 1);	
	
	if ($Corpus->main_script_is_r2l)
		return abs($ip) . ($sign == -1 ? ' before the node' : ' after the node');
	else
		return abs($ip) . ($sign == -1 ?  ' to the Left' : ' to the Right'); 
}





/** 
 * Returns the maximum number of hits that a user should get in a concordance if they only have restricted access,
 * which is calculated based on the size of the corpus (should be supplied!)
 * 
 * Broadly, the number of hits allowed in this situation is half the square root of the corpus size.
 * 
 * The precise number is calculated as follows: is by the following formula:
 * 		- N tokens in corpus
 *      - square root
 *      - divide by two thousand
 *      - round up to integer; ALWAYS upwards so it can never be 0. 
 *      - times by one thousand
 * In other words, the limit is half of square root N, rounded upwards always, and never less than 1 K.
 * 
 * Examples:
 *     - 100,000,000   => 5,000
 *     - 2,000,000,000 => 23,000
 *     - 1,000,000     => 1,000
 */
function max_hits_when_restricted($tokens_in_corpus_or_section)
{
	return 1000 * (int)ceil(sqrt($tokens_in_corpus_or_section)/2000.0); 
}







function format_time_string($time_taken, $not_from_cache = true)
{
	$str = '';
	if (isset($time_taken) )
		$str .= " <span class=\"concord-time-report\">[$time_taken seconds"
			. ($not_from_cache ? '' : ' - retrieved from cache') . ']</span>';
	else if ( ! $not_from_cache )
		$str .= ' <span class="concord-time-report">[data retrieved from cache]</span>';

	return $str;
}




/**
 * Builds HTML of concordance screen control row. Cache record needed for forms.  
 * 
 * @param QueryRecord $cache_record
 */
function print_control_row($cache_record)
{
	// TODO ugh globals. two down, but get rid of rest when poss.
// 	global $qname;
	global $page_no;
	global $per_page;
	global $num_of_pages;
	global $reverseViewMode;
	global $reverseViewButtonText;
	
// 	global $postprocess;
	global $program;
	
	global $Corpus;

	/* this is the variable to which everything is printed */
	$final_string = '<tr>';


	/* ----------------------------------------- *
	 * first, create backards-and-forwards-links *
	 * ----------------------------------------- */
	
	$marker = array( 'first' => '|&lt;', 'prev' => '&lt;&lt;', 'next' => "&gt;&gt;", 'last' => "&gt;|" );
	
	/* work out page numbers */
	$nav_page_no['first'] = ($page_no == 1 ? 0 : 1);
	$nav_page_no['prev']  = $page_no - 1;
	$nav_page_no['next']  = ($num_of_pages == $page_no ? 0 : $page_no + 1);
	$nav_page_no['last']  = ($num_of_pages == $page_no ? 0 : $num_of_pages);
	/* all page numbers that should be dead links are now set to zero  */
	

	foreach ($marker as $key => $m)
	{
		$final_string .= '<td align="center" class="concordgrey"><b><a class="page_nav_links" ';
		$n = $nav_page_no[$key];
		if ( $n != 0 )
			/* this should be an active link */
			$final_string .= 'href="concordance.php?'
				. url_printget(array(
					array('uT', ''), array('pageNo', "$n"), array('qname', $cache_record->qname)
					) )
				. '&uT=y"';
		$final_string .= ">$m</b></a></td>";
	}

	/* ----------------------------------------- *
	 * end of create backards-and-forwards-links *
	 * ----------------------------------------- */



	/* --------------------- *
	 * create show page form *
	 * --------------------- */
	$final_string .= "<form action=\"concordance.php\" method=\"get\"><td width=\"20%\" class=\"concordgrey\" nowrap=\"nowrap\">&nbsp;";
	
	$final_string .= '<input type="submit" value="Show Page:"/> &nbsp; ';
	
	$final_string .= '<input type="text" name="pageNo" value="1" size="8" />';
		
	$final_string .= '&nbsp;</td>';

	$final_string .= url_printinputs(array(
		array('uT', ''), array('pageNo', ""), array('qname', $cache_record->qname)
		));
	
	$final_string .= '<input type="hidden" name="uT" value="y"/></form>';
	
	
	
	/* ----------------------- *
	 * create change view form *
	 * ----------------------- */
	if ($Corpus->visualise_translate_in_concordance)
	{
		$final_string .= "<td align=\"center\" width=\"20%\" class=\"concordgrey\" nowrap=\"nowrap\">No KWIC view available</td>";
	}
	else
	{
		$final_string .= "<form action=\"concordance.php\" method=\"get\"><td align=\"center\" width=\"20%\" class=\"concordgrey\" nowrap=\"nowrap\">&nbsp;";
		
		$final_string .= "<input type=\"submit\" value=\"$reverseViewButtonText\"/>";
			
		$final_string .= '&nbsp;</td>';
		
		$final_string .= url_printinputs(array(
			array('uT', ''), array('viewMode', "$reverseViewMode"), array('qname', $cache_record->qname)
			));
		
		$final_string .= '<input type="hidden" name="uT" value="y"/>
				</form>';
	}
	


	/* ----------------*
	 * interrupt point *
	 * --------------- */
	if ($program == 'categorise')
		/* return just with two empty cells */
		return $final_string . '<td class="concordgrey" width="25%">&nbsp;</td> <td class="concordgrey" width="25%">&nbsp;</td></tr>';


	/* ------------------------ *
	 * create random order form *
	 * ------------------------ */
	if ($cache_record->last_postprocess_is_rand())
	{
		/* current display is randomised */
		$newPostP_value = 'unrand';
		$randomButtonText = 'Show in corpus order';
	}
	else
	{
		/* current display is not randomised */
		$newPostP_value = 'rand';
		$randomButtonText = 'Show in random order';
	}
		
	$final_string .= "
		<form action=\"concordance.php\" method=\"get\">
			<td align=\"center\" width=\"20%\" class=\"concordgrey\" nowrap=\"nowrap\">
				&nbsp;<input type=\"submit\" value=\"$randomButtonText\"/>&nbsp;
			</td>
			";	

	$final_string .= url_printinputs(array(
		array('uT', ''), array('qname', $cache_record->qname), array('newPostP', $newPostP_value)
		));

	$final_string .= '
					<input type="hidden" name="uT" value="y"/>
		</form>
		';


	/* -------------------------- *
	 * create action control form *
	 * -------------------------- */

	$custom_options = '';
	foreach (list_plugins_of_type(PLUGIN_TYPE_POSTPROCESSOR) as $record)
	{
		$obj = new $record->class($record->path);
		$label = $obj->get_label();
		$custom_options .= "<option value=\"CustomPost:{$record->class}\">$label</option>\n\t\t\t";
		unset($obj);
	}
	
	$final_string .= '<form action="redirect.php" method="get"><td class="concordgrey" nowrap="nowrap">&nbsp;
		<select name="redirect">	
			<option value="newQuery" selected="selected">New query</option>
			<option value="thin">Thin...</option>
			<option value="breakdown">Frequency breakdown</option>
			<option value="distribution">Distribution</option>
			<option value="sort">Sort</option>
			<option value="collocations">Collocations...</option>
			<option value="download-conc">Download...</option>
			<option value="categorise">Categorise...</option>
			<option value="saveHits">Save current set of hits...</option>
			' . $custom_options . '
		</select>
		&nbsp;
		<input type="submit" value="Go!"/>
		';
	
	$final_string .= url_printinputs(array(
		array('uT', ''), array('redirect', ''), array('qname', $cache_record->qname)
		));
	
	$final_string .= '<input type="hidden" name="uT" value="y"/>&nbsp;</td></form>';

	
	/* finish off and return */
	$final_string .= '</tr>';

	return $final_string;
}


/**
 * Alters a control row string so that it contains the sort position in such a way
 * that it can be successfully passed through to breakdown.inc.php
 */
function add_sortposition_to_control_row($html, $sort_pos)
{	
	/* NOTE: this string **must** match the hidden input generate by print_control_row() */
	$search = '<input type="hidden" name="uT" value="y"/>';
	
	$add = '<input type="hidden" name="concBreakdownAt" value="' . $sort_pos . '"/>';
	
	return str_replace($search, $add.$search, $html);
}





/**
 * Prints and returns a series of <option> elements with values -5 to 5
 * (the standard posiitons available in a sort database). This is used in
 * both the sort control and in the freq breakdown control box.
 * 
 * Parameter: the integer value of the option to be pre-selected.
 */
function print_sort_position_options($current_position = 0)
{
	global $Corpus;

	$s = '';
	
	foreach(array(5,4,3,2,1) as $i)
	{
		$s .= "\n\t<option value=\"-$i\""
			. (-$i == $current_position ? ' selected="selected"' : '')
			. ">$i Left</option>";
	}
	
	$s .= "\n\t<option value=\"0\""
		. (0 == $current_position ? ' selected="selected"' : '')
		. ">Node</option>";
		
	foreach(array(1,2,3,4,5) as $i)
	{
		$s .= "\n\t<option value=\"$i\""
			. ($i == $current_position ? ' selected="selected"' : '')
			. ">$i Right</option>\n";
	}
	
	if ($Corpus->main_script_is_r2l)
	{
		$s = str_replace('Left',  'Before', $s);
		$s = str_replace('Right', 'After',  $s);
	}

	return $s;
}



function print_sort_control($primary_annotation, $postprocess_string, &$sort_position_out)
{
	/* get current sort settings : from the current query's postprocess string */
	/* ~~sort[position~thin_tag~thin_tag_inv~thin_str~thin_str_inv] */
	$command = array_pop(explode('~~', $postprocess_string));

	if (substr($command, 0, 4) == 'sort')
	{
		list($current_settings_position, 
			$current_settings_thin_tag, $current_settings_thin_tag_inv,
			$current_settings_thin_str, $current_settings_thin_str_inv)
			=
			explode('~', trim(substr($command, 4), '[]'));
		if ($current_settings_thin_tag == '.*')
			$current_settings_thin_tag = '';
		if ($current_settings_thin_str == '.*')
			$current_settings_thin_str = '';
	}
	else
	{
		$current_settings_position = 1;
		$current_settings_thin_tag = '';
		$current_settings_thin_tag_inv = 0;
		$current_settings_thin_str = '';
		$current_settings_thin_str_inv = 0;
	}

	/* create a select box: the "position" dropdown */
	$position_select = '<select name="newPostP_sortPosition">'
		. print_sort_position_options($current_settings_position)
		. '</select>
		';
	
	


	/* create a select box: the "tag restriction" dropdown */
	if (!empty($primary_annotation))
		$taglist = corpus_annotation_taglist($primary_annotation);
	else
		$taglist = array();

	$tag_restriction_select = '<select name="newPostP_sortThinTag">
		<option value=""' . ('' === $current_settings_thin_tag ? ' selected="selected"' : '') 
		. '>None</option>';
	
	foreach ($taglist as &$tag)
		$tag_restriction_select .= '<option' . ($tag == $current_settings_thin_tag ? ' selected="selected"' : '')
				. ">$tag</option>\n\t";
	
	$tag_restriction_select .= '</select>';



	/* list of inputs with all the ones set by this form cleared */
	$forminputs = url_printinputs(array(
				array('pageNo', '1'),
				array('uT', ''),
				array('newPostP_sortThinString', ''),
				array('newPostP_sortThinStringInvert', ''),
				array('newPostP_sortThinTag', ''),
				array('newPostP_sortThinTagInvert', ''),
				array('newPostP_sortPosition', ''),
				) );

	/* stash sort position in an out parameter... */
	$sort_position_out = $current_settings_position;

	/* all is now set up so we are ready to return the final string */
	return '
	<tr>
		<form action="concordance.php" method="get">
			<td colspan="4" class="concordgrey"><strong>Sort control:</td>
			<td class="concordgrey">
				Position:
				' . $position_select . '
			</td>
			<td class="concordgrey" nowrap="nowrap">
				Tag restriction:
				' . $tag_restriction_select . '
				<br/>
				<input type="checkbox" name="newPostP_sortThinTagInvert" value="1"'
				. ($current_settings_thin_tag_inv ? ' checked="checked"' : '')
				. ' /> exclude
			</td>
			<td class="concordgrey" nowrap="nowrap">
				Starting with:
				<input type="text" name="newPostP_sortThinString" value="'
				. $current_settings_thin_str 
				. '" />
				<br/>
				<input type="checkbox" name="newPostP_sortThinStringInvert" value="1"'
				. ($current_settings_thin_str_inv ? ' checked="checked"' : '')
				. ' /> exclude
			</td>
			<td class="concordgrey">
				&nbsp;
				<input type="submit" value="Update sort" />
			</td>
			' . $forminputs	. '
			<input type="hidden" name="newPostP_sortRemovePrevSort" value="1"/>
			<input type="hidden" name="newPostP" value="sort"/>
			<input type="hidden" name="uT" value="y"/>
		</form>
	</tr>';
}




/* creates the control bar at the bottom of "categorise" */

function print_categorise_control()
{
	global $viewMode;
	
	$final_string = '<tr><td class="concordgrey" align="right" colspan="'
		. ($viewMode == 'kwic' ? 6 : 4)
		.'">
			<select name="categoriseAction">
				<option value="updateQueryAndLeave">Save values and leave categorisation mode</option>
				<option value="updateQueryAndNextPage" selected="selected">Save values for this page and go to next</option>
				<option value="noUpdateNewQuery">New Query (does not save changes to category values!)</option>
			</select>
			<input type="submit" value="Go!"/>
		</td></tr>'
		."\n"
		;
	
	return $final_string;
}












/**
 * Processes a line of CQP output for display in the CQPweb concordance table.
 * 
 * This is done with regard to certain rendering-control variables esp. related to gloss
 * visualisation.
 * 
 * Returns a line of 3 or 5 td's that can be wrapped in a pair of tr's, or have other
 * cells added (e.g. for categorisation).
 * 
 * Note no tr's are added at this point.
 * 
 * In certain display modes, these td's may have other smaller tables within them.
 * 
 * @param string $cqp_line			A line of output from CQP.
 * @param $position_table		    Not used.
 * @param int $line_number			The line number to be PRINTED (counted from 1)
 * @param int $highlight_position	The entry in left or right context to be highlit. 
 * 								    Set to a ridiculously large number (such as 10000000)
 * 								    to get no highlight.
 * @param bool $highlight_show_pos	Boolean: show the primary annotation of the highlit
 * 								    item in-line.  
 * @return string                   The built-up line.
 */
function print_concordance_line($cqp_line, $position_table, $line_number, $highlight_position, $highlight_show_pos = false)
{
	global $qname;
	global $viewMode;
	// TODO weird use of globals.

	global $Corpus;

	/* get URL of the extra-context page right at the beginning, because we don't know when we may need it;
	 * create as string that is easily embeddable into the <a> of the node-link (or empty string if not permitted) */
	if (PRIVILEGE_TYPE_CORPUS_RESTRICTED < $Corpus->access_level)
		$context_href = ' href="context.php?batch=' . ($line_number-1) . '&qname=' . $qname . '&uT=y" ';
	else
		$context_href = '';
		
	if ($Corpus->visualise_translate_in_concordance)
	{
		/* extract the translation content, which will be BEFORE the text_id */
		preg_match("/<{$Corpus->visualise_translate_s_att} (.*?)><text_id/", $cqp_line, $m);
		$translation_content = $m[1];
		$cqp_line = preg_replace("/<{$Corpus->visualise_translate_s_att} .*?><text_id/", '<text_id', $cqp_line);
	}

	/* extract the text_id and delete that first bit of the line */
	$text_id = $position_label = false;
	extract_cqp_line_position_labels($cqp_line, $text_id, $position_label);
	
	/* divide up the CQP line */
	list($kwic_lc, $kwic_match, $kwic_rc) = explode('--%%%--', $cqp_line);

	/* left context string */
	list($lc_string, $lc_tool_string) 
		= concordance_line_blobprocess($kwic_lc, 'left', $highlight_position, $highlight_show_pos);

	list($node_string, $node_tool_string) 
		= concordance_line_blobprocess($kwic_match, 'node', $highlight_position, $highlight_show_pos, $context_href);

	/* right context string */
	list($rc_string, $rc_tool_string) 
		= concordance_line_blobprocess($kwic_rc, 'right', $highlight_position, $highlight_show_pos);

	/* if the corpus is r-to-l, this function call will spot it and handle things for us */
	right_to_left_adjust($lc_string, $lc_tool_string, $node_string, $node_tool_string, $rc_string,$rc_tool_string); 



	/* create final contents for putting in the cells */
	if ($Corpus->visualise_gloss_in_concordance)
	{
		$lc_final   = build_glossbox('left', $lc_string, $lc_tool_string);
		$node_final = build_glossbox('node', $node_string, $node_tool_string);
		$rc_final   = build_glossbox('right', $rc_string, $rc_tool_string);
	}
	else
	{
		$lc_final = $lc_string;
		$rc_final = $rc_string;
		
		/* the untidy HTML here is inherited from BNCweb. */
		$full_tool_tip = "onmouseover=\"return escape('"
			. str_replace('\'', '\\\'', $lc_tool_string . '<font color=&quot;#DD0000&quot;>'
				. $node_tool_string . '</font> ' . $rc_tool_string)	
			. "')\"";
		$node_final = '<b><a class="nodelink"' . $context_href . $full_tool_tip . '>' . $node_string . '</a></b>';
	}


	/* print cell with line number */
	$final_string = "<td class=\"text_id\"><b>$line_number</b></td>";
	
	$final_string .= "<td class=\"text_id\"><a href=\"textmeta.php?text=$text_id&uT=y\" "
		. metadata_tooltip($text_id) . '>' . $text_id . ($position_label === '' ? '' : " $position_label") . '</a></td>';

	
	if ($viewMode == 'kwic')
	{
		/* print three cells - kwic view */

		$final_string .= '<td class="before" nowrap="nowrap"><div class="before">' . $lc_final   . '</div></td>';

		$final_string .= '<td class="node"   nowrap="nowrap">'                     . $node_final . '</td>';
		
		$final_string .= '<td class="after"  nowrap="nowrap"><div class="after">'  . $rc_final   . '</div></td>';
	}
	else
	{
		/* print one cell - line view */
		
		/* glue it all together, then wrap the translation if need be */
		$subfinal_string =  $lc_final . ' ' . $node_final . ' ' . $rc_final;
		if ($Corpus->visualise_translate_in_concordance)
			$subfinal_string = concordance_wrap_translationbox($subfinal_string, $translation_content);
		
		/* and add to the final string */
		$final_string .= '<td class="lineview">' . $subfinal_string . '</td>';
	}

	$final_string .= "\n";

	return $final_string;
}




/**
 * Converts a node-or-right-or-left context string from CQP output into
 * two strings ready for printing in CQPweb.
 * 
 * The FIRST string is the "main" string; the one that is the principle
 * readout. The SECOND string is the "other" string: either for a 
 * tag-displaying tooltip, or for the gloss-line when the gloss is visible.
 * 
 * This function gets called 3 times per hit, obviously.
 * 
 * Note: we do not apply links here in normal mode, but if we are visualising
 * a gloss, then we have to (because the node gets buried in the table
 * otherwise). Thus $context_href must be passed in.
 */
function concordance_line_blobprocess($lineblob, $type, $highlight_position, $highlight_show_pos = false, $context_href = '')
{
	global $Corpus;
	
	/* all string literals (other than empty strings or spacers) must be here so they can be conditionally set. */
	if ($type == 'node')
	{
		$main_begin_high = '';
		$main_end_high = '';
		$other_begin_high = '';
		$other_end_high = '';
		$glossbox_nodelink_begin = '<b><a class="nodelink"' . $context_href . '>';
		$glossbox_nodelink_end = '</a></b>';
	}
	else
	{
		$main_begin_high = '<span class="contexthighlight">';
		$main_end_high = '</span> ';
		$other_begin_high = '<b>';
		$other_end_high = '</b> ';
		$glossbox_nodelink_begin = '';
		$glossbox_nodelink_end = '';
	}
	/* every glossbox will contain a bolded link, if it is a node; otherwise not */
	$glossbox_line1_cell_begin = "<td class=\"glossbox-$type-line1\" nowrap=\"nowrap\">$glossbox_nodelink_begin";
	$glossbox_line2_cell_begin = "<td class=\"glossbox-$type-line2\" nowrap=\"nowrap\">$glossbox_nodelink_begin";
	$glossbox_end = $glossbox_nodelink_end . '</td>';
	/* end of string-literals-into-variables section */

	
	/* the "trim" is just in case of unwanted spaces (there will deffo be some on the left) ... */
	/* this regular expression puts tokens in $m[4]; xml-tags-before in $m[1]; xml-tags-after in $m[5] . */
	preg_match_all('|((<\S+?( \S+?)?>)*)([^ <]+)((</\S+?>)*) ?|', trim($lineblob), $m, PREG_PATTERN_ORDER);
	/* note, this is prone to interference from literal < in the index.
	 * TODO: 
	 * Will be fixable when we have XML concordance output in CQP v 4.0 */
	$token_array = $m[4];
	$xml_before_array = $m[1];
	$xml_after_array = $m[5];

	$n = (empty($token_array[0]) ? 0 : count($token_array));
	
	/* if we are in the left string, we need to translate the highlight position from
	 * a negative number to a number relative to 0 to $n... */
	if ($type == 'left')
		$highlight_position = $n + $highlight_position + 1;
	
	/* these are the strings we will build up */
	$main_string = '';
	$other_string = '';
	
	for ($i = 0; $i < $n; $i++) 
	{
/* TODO replace with actual function to render the XML viz, rather than just HTML speciallchars */
$xml_before_string = escape_html($xml_before_array[$i]) . ' ';
$xml_after_string =  ' ' . escape_html($xml_after_array[$i]);
		list($word, $tag) = extract_cqp_word_and_tag($token_array[$i]);

		if ($type == 'left' && $i == 0 && preg_match('/\A[.,;:?\-!"]\Z/', $word))
			/* don't show the first word of left context if it's just punctuation */
			continue;
	
		if (!$Corpus->visualise_gloss_in_concordance)
		{
			/* the default case: we are buiilding a concordance line and a tooltip */
			if ($highlight_position == $i+1) /* if this word is the word being sorted on / collocated etc. */
			{
				$main_string .= "$xml_before_string$main_begin_high$word"
					. ($highlight_show_pos ? $tag : '') 
					. "$main_end_high$xml_after_string" ;
				$other_string .= "$other_begin_high$word$tag$other_end_high";
			}
			else
			{
				$main_string .= "$xml_before_string$word$xml_after_string ";
				$other_string .= "$word$tag ";
			}
		}
		else
		{
			/* build a gloss-table instead;
			 * other_string will be the second line of the gloss table instead of a tooltip */
			if ($highlight_position == $i+1)
			{
				$main_string .= "$glossbox_line1_cell_begin$xml_before_string$main_begin_high$word$main_end_high$xml_after_string$glossbox_end";
				$other_string .= "$glossbox_line2_cell_begin$main_begin_high$tag$main_end_high$glossbox_end";
			}
			else
			{
				$main_string .= "$glossbox_line1_cell_begin$xml_before_string$word$xml_after_string$glossbox_end";
				$other_string .= "$glossbox_line2_cell_begin$tag$glossbox_end";
			}	
		}
	}
	if ($main_string == '' && !$Corpus->visualise_gloss_in_concordance)
		$main_string = '&nbsp;';
	

	/* extra step needed because otherwise a space may get linkified */
	if ($type == 'node')
		$main_string = trim($main_string);

	return array($main_string, $other_string);
}


/**
 * Returns an onmouseover string for links to the specified text_id
 */
function metadata_tooltip($text_id)
{
	global $Corpus;
	
	static $stored_tts = array();
	
	/* avoid re-running the queries / string building code for a text whose tooltip has already been created;
	 * worth doing because we KNOW a common use-case is to have lots of concordances from the same text visible at once */
	if (isset($stored_tts[$text_id]))
		return $stored_tts[$text_id]; 
	
	$result = do_mysql_query("select * from text_metadata_for_{$Corpus->name} where text_id = '$text_id'");
	if (mysql_num_rows($result) == 0)
		return "";
	$text_data = mysql_fetch_assoc($result);

	$result = do_mysql_query("select handle from text_metadata_fields 
										where corpus = '{$Corpus->name}' and datatype = ".METADATA_TYPE_CLASSIFICATION);
	if (mysql_num_rows($result) == 0)
		return "";
	
	$tt = 'onmouseover="return escape(\'Text <b>' . $text_id . '</b><BR>'
		. '<i>(length = ' . number_format((float)$text_data['words']) 
		. ' words)</i><BR>--------------------<BR>';
	
	while (false !== ($field_handle = mysql_fetch_row($result)) )
	{
		$item = metadata_expand_attribute($field_handle[0], $text_data[$field_handle[0]]);
		
		if (!empty($item['value']))
			$tt .= str_replace('\'', '\\\'', '<i>' . escape_html($item['field']) 
						. ':</i> <b>' 
						. escape_html($item['value']) . '</b><BR>');
	}
	
	$tt .= '\')"';
	
	/* store for later use */
	$stored_tts[$text_id] = $tt;
	
	return $tt;
}



/** 
 * Switches around the contents of the left/right strings, if necessary, 
 * to support L2R scripts. 
 * 
 * All parameters are passed by reference.
 */
function right_to_left_adjust(&$lc_string,   &$lc_tool_string, 
                              &$node_string, &$node_tool_string, 
                              &$rc_string,   &$rc_tool_string)
{
	global $Corpus;
	global $viewMode;

	if ($Corpus->main_script_is_r2l)
	{
		/* ther are two entirely different styles of reordering.
		 * (1) if we are using glosses (strings of td's all over the shop)
		 * (2) if we have the traditional string-o'-words
		 */ 
		if ($Corpus->visualise_gloss_in_concordance)
		{
			/* invert the order of table cells in each string. */
			$lc_string        = concordance_invert_tds($lc_string);
			$lc_tool_string   = concordance_invert_tds($lc_tool_string);
			$node_string      = concordance_invert_tds($node_string);
			$node_tool_string = concordance_invert_tds($node_tool_string);
			$rc_string        = concordance_invert_tds($rc_string);
			$rc_tool_string   = concordance_invert_tds($rc_tool_string);
			/* note this is done regardless of whether we are in kwic or line */	
			/* similarly, regardless of whether we are in kwic or line, we need to flip lc and rc */
			$temp_r2l_string = $lc_string;
			$lc_string = $rc_string;
			$rc_string = $temp_r2l_string;
			/* we need ot do the same with the tooltips too */
			$temp_r2l_string = $lc_tool_string;
			$lc_tool_string = $rc_tool_string;
			$rc_tool_string = $temp_r2l_string;
		}
		else
		{
			/* we only need to worry in kwic. In line mode, the flow of the
			 * text and the normal text-directionality systems in the browser
			 * will deal wit it for us.*/
			if ($viewMode == 'kwic')
			{
				$temp_r2l_string = $lc_string;
				$lc_string = $rc_string;
				$rc_string = $temp_r2l_string;
			}
		}
	}
	/* else it is an l-to-r script, so do nothing. */
}

/**
 * Build a two-line (or three line?) glossbox table from
 * two provided sequences of td's.
 * 
 * $type must be left, node, or right (as a string). Anything
 * else will be treated as if it was "node".
 */
function build_glossbox($type, $line1, $line2, $line3 = false)
{
	global $Corpus;
	global $viewMode;
	if ($viewMode =='kwic')
	{
		switch($type)
		{
			case 'left':	$align = 'right';	break;
			case 'right':	$align = 'left';	break;
			default:		$align = 'center';	break;
		}
	}
	else
		$align = ($Corpus->main_script_is_r2l ? 'right' : 'left');
	
	if (empty($line1) && empty($line2))
		return '';
		
	return 	'<table class="glossbox" align="' . $align . '"><tr>'
			. $line1
			. '</tr><tr>' 
			. $line2
			. '</tr>'
			. ($line3 ? '' : '')
			. '</table>';
}

function concordance_wrap_translationbox($concordance, $translation)
{
	return 
		'<table class="transbox"><tr><td class="transbox-top">'
		. $concordance
		. '</td></tr><tr><td class="transbox-bottom">'
		. $translation
		. "\n</td></tr></table>\n"
		;
}

/**
 * Takes a string consisting of a sequence of td's.
 * 
 * Returns the same string of td's, in the opposite order.
 * Note - if there is material outside the td's, results may
 * be unexpected. Should not be used outside the concordance
 * line rendering module.
 */
function concordance_invert_tds($string)
{
	$stack = explode('</td>', $string);
	
	$newstring = '';
	
	while (! is_null($popped = array_pop($stack)))
	{
		/* 
		 * there will prob be an empty string at the end,
		 * from after the last end-td. We don't want to add this.
		 * But all the other strings in $stack should be "<td>...".
		 */ 
		if (!empty($popped))
			$newstring .= $popped . '</td>';	
	}
	
	return $newstring;
}


/**
 * Function used by print_concordance_line. 
 * 
 * Also used in context.inc.php.
 * 
 * It takes a single word/tag string from the CQP concordance line, and
 * returns an array of 0 => word, 1 => tag 
 * 
 * TODO important design problem in this function: it always uses 
 * the visualise_gloss_in_concordance variable for the active corpus,
 * when in fact visualise_gloss_in_context should be used in context.inc.php
 * So a rethink, to some degree, is needed to fix this.
 * 
 * note also it uses globals .... so is fundamentally a dirty hack just on that basis!!!
 * 
 */
function extract_cqp_word_and_tag($cqp_source_string)
{
	global $Corpus;
	
	static $word_extraction_pattern = NULL;
	
	if (is_null($word_extraction_pattern))
	{ 
		/* on the first call only: only deduce the pattern once per run of the script */
		global $primary_tag_handle;
		
		/* OK, this is how it works: if EITHER a primary tag is set, OR we are visualising 
		 * glosses, then we must split the token into word and tag using a regex.
		 * 
		 * If NEITHER of these things is the case, then we don't use a regex.
		 * 
		 * Note that we assume that forward slash can be part of a word, but not part of a 
		 * (primary) tag.
		 * 
		 * [TODO: note this in the manual] 
		 */
		$word_extraction_pattern = 
			( (empty($primary_tag_handle)&&!$Corpus->visualise_gloss_in_concordance) ? false : '/\A(.*)\/([^\/]*)\z/' );
	}
	
	if ($word_extraction_pattern)
	{
		preg_match($word_extraction_pattern, escape_html($cqp_source_string), $m);
		if (!isset($m[1], $m[2]))
		{
			/* a fallback case, for if the regular expression is derailed;
			 * should only happen with badly-encoded XML tags. */
			$word = '[UNREADABLE]';
			$tag = $Corpus->visualise_gloss_in_concordance ? '[UNREADABLE]' : '_';
		}
		else
		{
			/* this will nearly always be the case. */
			$word = $m[1];
			$tag = ($Corpus->visualise_gloss_in_concordance ? '' : '_') . $m[2];
		}
	}
	else
	{
		$word = escape_html($cqp_source_string);
		$tag = '';
	}
	return array($word, $tag);
}

/**
 * Extracts the position inidicators (text_id and, optionally, one other) and place them
 * in the given variables; scrub them from the CQP line and put the new CQP line
 * back in the variable the old one came from.
 * 
 * Returns nothing; modifies all its parameters.
 * 
 * Note that if the corpus is set up to not use a position label, that argument will be
 * set to an empty string.
 */ 
function extract_cqp_line_position_labels(&$cqp_line, &$text_id, &$position_label)
{
	global $Corpus;
	if ($Corpus->visualise_position_labels)
	{
		/* if a position label is to be used, it is extracted from between <text_id ...> and the colon. */
		if (0 < preg_match("/\A\s*\d+: <text_id (\w+)><{$Corpus->visualise_position_label_attribute} ([^>]+)>:/", $cqp_line, $m) )
		{
			$text_id = $m[1];
			$position_label = escape_html($m[2]);
			$cqp_line = preg_replace("/\A\s*\d+: <text_id \w+><$Corpus->visualise_position_label_attribute [^>]+>:/", '', $cqp_line);
		}
		else
		{
			/* Position label could not be extracted, sojust extract text_id */
			preg_match("/\A\s*\d+: <text_id (\w+)><$Corpus->visualise_position_label_attribute>:/", $cqp_line, $m);
			$text_id = $m[1];
			$position_label = '';
			$cqp_line = preg_replace("/\A\s*\d+: <text_id \w+><$Corpus->visualise_position_label_attribute>:/", '', $cqp_line);
			/* note it IS NOT THE SAME as the "normal" case below: the s-att still prints, just wihtout a value */		
		}
	}
	else
	{
		/* If we have no position label, just extract text_id */
		preg_match("/\A\s*\d+: <text_id (\w+)>:/", $cqp_line, $m);
		$text_id = $m[1];
		$position_label = '';
		$cqp_line = preg_replace("/\A\s*\d+: <text_id \w+>:/", '', $cqp_line);
	}
}


/** print a sorry-no-solutions page, shut down CQPweb, and end */
function say_sorry($sorry_input = "no_solutions")
{
	global $Config;
	
	switch ($sorry_input)
	{
	case 'empty_postproc':
		$error_text = "No results were left after performing that operation!";
		break;
// 	case 'no_files':
// 		$error_text = "There are no texts in the corpus that match your restrictions.";
// 		break;
	case 'no_scope':
		$error_text = "Nowhere to search! There are no sub-parts of the corpus that satisfy all of the restrictions you specified.";
		break;
	case 'no_solutions':
		$error_text = "Your query had no results.";
		break;
	default:
		$error_text = "Something went wrong!";
		break;
	}
	
	echo print_html_header('Query error!', $Config->css_path);

	?>
		<table width="100%" class="concordtable">
			<tr>
				<th class="concordtable">
					<?php echo $error_text; ?>
				</th>
			</tr>
			<tr>
				<td class="concorderror" align="center">
					<p>
						<b>Press [Back] and try again.</b>
					</p>
				</td>
			</tr>
		</table>
	<?php

	echo print_html_footer('hello');
	cqpweb_shutdown_environment();
	exit(0);
}




