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
 * 
 * This file contains the user interfaces for each of the primary corpus query entry points.  
 * Most of these functions (as in all "indexforms" files) print a table for the right-hand side interface.
 * Some are support functions, providing reusable chunks of HTML.
 */


/**
 * Builds and returns an HTML string containing the search-box and associated UI elements 
 * used in the Standard and Restricted Query forms. 
 * 
 * @param string $qstring               A search pattern that will be inserted into the query textbox Or NULL.
 * @param string $qmode                 The query-mode to pre-set in the query control. Or NULL.
 * @param string $qsubcorpus            String: preset subcorpus. Only works if $show_mini_restrictions is true. 
 * @param bool $show_mini_restrictions  Set to true if you want the "simple restriction" control for Standard Query.
 */
function printquery_build_search_box($qstring, $qmode, $qsubcorpus, $show_mini_restrictions)
{
	global $Config;
	global $Corpus;
	global $User;
	
	/* GET VARIABLES READY: contents of query box */
	$qstring = ( ! empty($qstring) ? escape_html(prepare_query_string($qstring)) : '' );
	

	/* GET VARIABLES READY: the query mode. */
	$modemap = array(
		'cqp'       => 'CQP syntax',
		'sq_nocase' => 'Simple query (ignore case)',
		'sq_case'   => 'Simple query (case-sensitive)',
		);
	if (! array_key_exists($qmode, $modemap) )
		$qmode = ($Corpus->uses_case_sensitivity ? 'sq_case' : 'sq_nocase');
		/* includes NULL, empty */
	
	$mode_options = '';
	foreach ($modemap as $mode => $modedesc)
		$mode_options .= "\n\t\t\t\t\t\t\t<option value=\"$mode\"" . ($qmode == $mode ? ' selected="selected"' : '') . ">$modedesc</option>";

	/* GET VARIABLES READY: hidden attribute help */
	$style_display = ('cqp' != $qmode ? "display: none" : '');
	$mode_js       = ('cqp' != $qmode ? 'onChange="if ($(\'#qmode\').val()==\'cqp\') $(\'#searchBoxAttributeInfo\').slideDown();"' : '');
	
	$p_atts = "\n";
	foreach(get_corpus_annotation_info() as $p)
	{
		$p->tagset = escape_html($p->tagset);
		$p->description = escape_html($p->description);
		$tagset = (empty($p->tagset) ? '' : "(using {$p->tagset})");
		$p_atts .= "\t\t\t<tr>\t<td><code>{$p->handle}</code></td>\t<td>{$p->description}$tagset</td>\t</tr>\n";
	}
	
	$s_atts = "\n";
	foreach(list_xml_all($Corpus->name) as $s=>$s_desc)
		$s_atts .= "\t\t\t\t\t<tr>\t<td><code>&lt;{$s}&gt;</code></td>\t<td>" . escape_html($s_desc) . "</td>\t</tr>\n";


	/* GET VARIABLES READY: hits per page select */
	$pp_options = '';
	foreach (array (10,50, 100, 250, 350, 500, 1000) as $val)
		$pp_options .= "\n\t\t\t\t\t\t\t<option value=\"$val\""
			. ($Config->default_per_page == $val ? ' selected="selected"' : '')
			. ">$val</option>"
			;
	
	if ($User->is_admin())
		$pp_options .=  "\n\t\t\t\t\t\t\t<option value=\"all\">show all</option>";



	/* ASSEMBLE THE RESTRICTIONS MINI-CONTROL TOOL */
	if ( ! $show_mini_restrictions)
		$restrictions_html = '';
	else
	{
		/* create options for the Primary Classification */
		/* first option is always whole corpus */
		$restrict_options = "\n\t\t\t\t\t\t\t<option value=\"\"" 
			. ( empty($subcorpus) ? ' selected="selected"' : '' )
			. '>None (search whole corpus)</option>'
			; 
		
		$field = $Corpus->primary_classification_field;
		foreach (metadata_category_listdescs($field, $Corpus->name) as $h => $c)
			$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"-|$field~$h\">".(empty($c) ? $h : escape_html($c))."</option>";
		
		/* list the user's subcorpora for this corpus, including the last set of restrictions used */
		
		$result = do_mysql_query("select * from saved_subcorpora where corpus = '{$Corpus->name}' and user = '{$User->username}' order by name");
	
		while (false !== ($sc = Subcorpus::new_from_db_result($result)))
		{
			if ($sc->name == '--last_restrictions')
				$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"--last_restrictions\">Last restrictions ("
					. $sc->print_size_tokens() . ' words in ' 
					. $sc->print_size_items()  . ')</option>'
					;
			else
				$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"~sc~{$sc->id}\""
					. ($qsubcorpus == $sc->id ? ' selected="selected"' : '')
					. '>Subcorpus: ' . $sc->name . ' ('
					. $sc->print_size_tokens() . ' words in ' 
					. $sc->print_size_items()  . ')</option>'
					;
		}
		
		/* we now have all the subcorpus/restrictions options, so assemble the HTML */
		$restrictions_html = <<<END_RESTRICT_ROW

				<tr>
					<td class="basicbox">Restriction:</td>
					<input type="hidden" name="del" size="-1" value="begin" />
					<td class="basicbox">
						<select name="t">
							$restrict_options
						</select>
					</td>
				</tr>
				<input type="hidden" name="del" size="-1" value="end" />

END_RESTRICT_ROW;

	} /* end of $show_mini_restrictions */



	/* ALL DONE: so assemble the HTML from the above variables && return it. */

	return <<<END_OF_HTML


			&nbsp;<br/>
			
			<textarea 
				name="theData" 
				rows="5" 
				cols="65" 
				style="font-size: 16px"  
				spellcheck="false" 
			>$qstring</textarea>
			
			<div id="searchBoxAttributeInfo" style="$style_display">
				<table>
					<tr>
						<td colspan="2"><b>P-attributes in this corpus:</b></td>
					</tr>
					<tr>
						<td width="40%"><code>word</code></td>
						<td><p>Main word-token attribute</p></td>
					</tr>
			
					$p_atts
		
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2"><b>S-attributes in this corpus:</b></td>
					</tr>
		
					$s_atts
					
				</table>
				<p>
					<a target="_blank" href="http://cwb.sourceforge.net/files/CQP_Tutorial/"
						onmouseover="return escape('Detailed help on CQP syntax')">
						Click here to open the full CQP-syntax tutorial
					</a>
				</p>
			</div>

			&nbsp;<br/>
			&nbsp;<br/>


			<table>	
				<tr>
					<td class="basicbox">Query mode:</td>
				
					<td class="basicbox">
						<select id="qmode" name="qmode" $mode_js>
							$mode_options
						</select>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a target="_blank" href="../doc/cqpweb-simple-syntax-help.pdf"
							onmouseover="return escape('How to compose a search using the Simple Query language')">
							Simple query language syntax
						</a>
					</td>
				</tr>
			
				<tr>
					<td class="basicbox">Number of hits per page:</td>
					<td class="basicbox">	
						<select name="pp">
							<option value="count">count hits</option>
							
							$pp_options
							
						</select>
					</td>
				</tr>

				$restrictions_html

				<tr>
					<td class="basicbox">&nbsp;</td>
					<td class="basicbox">				
						<input type="submit" value="Start Query"/>
						<input type="reset" value="Reset Query"/>
					</td>
				</tr>
			</table>


END_OF_HTML;
	
}



function printquery_search()
{
	/* most of the hard work of this function is done by the inner "print search box" function
	 * and thisd function merely wraps it, yanks vars from GET, and begins/ends the form. */


	?>
	<table class="concordtable" width="100%">
	
	<tr>
		<th class="concordtable">Standard Query</th>
	</tr>
	
	<tr>
		<td class="concordgeneral">
		
			<form action="concordance.php" accept-charset="UTF-8" method="get"> 
		
				<?php
				echo printquery_build_search_box(
					isset($_GET['insertString'])    ? $_GET['insertString']    : NULL,
					isset($_GET['insertType'])      ? $_GET['insertType']      : NULL,
					isset($_GET['insertSubcorpus']) ? $_GET['insertSubcorpus'] : NULL,
					true
				);
				?>
				
				<input type="hidden" name="uT" value="y"/>
			</form>
		</td>
	</tr>
	
	</table>
	<?php
}




function printquery_restricted()
{
	/* insert restrictions as checked tickboxes lower down */
// 	$checkarray = array();
// 	if (isset($_GET['insertRestrictions']))
// 	{
// 		/* note that, counter to what one might expect, the parameter here is given as a serialisation, not URL-format */
// 		if (false === ($restriction = Restriction::new_by_unserialise($_GET['insertRestrictions'])))
// 			/* it can't be read: so don't populate $checkarray. */
// 			;
// 		else
// 			foreach ($restriction->get_form_check_pairs() as $pair)
// 				$checkarray[$pair[0]][$pair[1]] = 'checked="checked" ';
// // old method:
// // 		preg_match_all('/\W+(\w+)=\W+(\w+)\W/', $_GET['insertRestrictions'], $matches, PREG_SET_ORDER);
// // 		foreach($matches as $m)
// // 			$checkarray[$m[1]][$m[2]] = 'checked="checked" ';
// 	}
	if (isset($_GET['insertRestrictions']))
		$insert_r = Restriction::new_by_unserialise($_GET['insertRestrictions']);
	else
		$insert_r = NULL;

	?>
	<table class="concordtable" width="100%">
	
		<tr>
			<th class="concordtable" colspan="3">Restricted Query</th>
		</tr>
	
		<form action="concordance.php" accept-charset="UTF-8" method="get"> 
			<tr>
				<td class="concordgeneral" colspan="3">
			
					<?php
					echo printquery_build_search_box(
						isset($_GET['insertString']) ? $_GET['insertString']  : NULL,
						isset($_GET['insertType'])   ? $_GET['insertType']    : NULL,
						NULL,
						false
					);
					?>	
				</td>
			</tr>
						
			<?php
			echo printquery_build_restriction_block($insert_r, 'query');
			?>
			
			<input type="hidden" name="uT" value="y"/>
		</form>
	</table>
	
	<?php
}





/**
 * This provides the metadata restrictions block that is used for queries and for subcorpora.
 * 
 * @param Restriction $insert_restriction  If not empty, contains a Restriction to be rendered in the form.
 * @param string $thing_to_produce         String to rpint labelling the thing the form will produce: "query", "subcorpus"
 * checkarray is an array of categories / classes that are to be checked;
 */
// function printquery_build_restriction_block($checkarray, $thing_to_produce)
function printquery_build_restriction_block($insert_restriction, $thing_to_produce)
{
	global $Corpus;
	
	$block = '
		<tr>
			<th colspan="3" class="concordtable">
				Select the text-type restrictions for your '. $thing_to_produce . ':
			</th>
		</tr>
		';
		
	
	/* TEXT METADATA */
	
	/* get a list of classifications and categories from mysql; print them here as tickboxes */
	
	$block .= '<tr><input type="hidden" name="del" size="-1" value="begin" />';

	$classifications = metadata_list_classifications();
	
	$header_row = array();
	$body_row = array();
	$i = 0;
	
	foreach ($classifications as $c)
	{
		$header_row[$i] = '<td width="33%" class="concordgrey" align="center">' .escape_html($c['description']) . '</td>';
		$body_row[$i] = '<td class="concordgeneral" valign="top" nowrap="nowrap">';
		
		$catlist = metadata_category_listdescs($c['handle']);
		
		foreach ($catlist as $handle => $desc)
		{
			$t_value = '-|' . $c['handle'] . '~' . $handle;
			$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked="checked" ' : '');
			$body_row[$i] .= '<input type="checkbox" name="t" value="' . $t_value . '" ' . $check 
				. '/> ' . ($desc == '' ? $handle : escape_html($desc)) . '<br/>';
		}
		

		/* whitespace is gratuitous for readability */
		$body_row[$i] .= '
			&nbsp;
			</td>';
		
		$i++;
		/* print three columns at a time */
		if ( $i == 3 )
		{
			$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
				<tr>
				' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
				<tr>
				';
			$i = 0;
		}
	}
	
	if ($i > 0) /* not all cells printed */
	{
		while ($i < 3)
		{
			$header_row[$i] = '<td class="concordgrey" align="center">&nbsp;</td>';
			$body_row[$i] = '<td class="concordgeneral">&nbsp;</td>';
			$i++;
		}
		$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
			<tr>
			' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
			<tr>
			';
	}

	
	if (empty($classifications))
		$block .= '<tr><td colspan="3" class="concordgrey" align="center">
			&nbsp;<br/>
			There are no text classification schemes set up for this corpus.
			<br/>&nbsp;
			</td></tr>';


	$classification_elements_matrix = array();
	$idlink_elements_matrix = array();
	
	$xml = get_xml_all_info($Corpus->name);
	

	foreach ($xml as $x)
		if ($x->datatype == METADATA_TYPE_NONE)
			$classification_elements_matrix[$x->handle] = array();

	foreach ($xml as $x)
	{
		if ($x->datatype == METADATA_TYPE_CLASSIFICATION)
			$classification_elements_matrix[$x->att_family][] = $x->handle;
		else if ($x->datatype == METADATA_TYPE_IDLINK)
		{
			foreach (get_all_idlink_info($Corpus->name, $x->handle) as $k=> $field)
				if ($field->datatype == METADATA_TYPE_CLASSIFICATION)
					$idlink_elements_matrix[$x->handle][$k] = $field;
		}
	}

	foreach($classification_elements_matrix as $k=>$c)
		if (empty($c))
			unset($classification_elements_matrix[$k]);
// show_Var($idlink_elements_matrix);
//show_Var($classification_elements_matrix);

	/* we now know which elements we need a display for. */

	foreach ($classification_elements_matrix as $el => $class_atts)
	{
		$block .= <<<END_HTML
			<tr>
				<th colspan="3" class="concordtable">
					Select sub-text restrictions for your $thing_to_produce -- for <em>{$xml[$el]->description}</em> regions:
				</th>
			</tr>
END_HTML;
					
		$header_row = array();
		$body_row = array();
		$i = 0;
		
		foreach($class_atts as $c)
		{
			$header_row[$i] = '<td width="33%" class="concordgrey" align="center">' . $xml[$c]->description . '</td>';
			$body_row[$i] = '<td class="concordgeneral" valign="top" nowrap="nowrap">';
			
			$catlist = xml_category_listdescs($Corpus->name, $c);

			$t_base_c = preg_replace("/^{$el}_/",  '', $c);
			
			foreach ($catlist as $handle => $desc)
			{
				$t_value = $el . '|'. $t_base_c . '~' . $handle;
				$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked="checked" ' : '');
				$body_row[$i] .= '<input type="checkbox" name="t" value="' . $t_value . '" ' . $check 
					. '/> ' . ($desc == '' ? $handle : escape_html($desc)) . '<br/>';
			}
			
			/* whitespace is gratuitous for readability */
			$body_row[$i] .= '
				&nbsp;
				</td>';
						
			$i++;
			/* print three columns at a time */
			if ( $i == 3 )
			{
				$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
				<tr>
				' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
				<tr>
				';
				$i = 0;
			}
		}

		if ($i > 0) /* not all cells printed */
		{
			while ($i < 3)
			{
				$header_row[$i] = '<td class="concordgrey" align="center">&nbsp;</td>';
				$body_row[$i] = '<td class="concordgeneral">&nbsp;</td>';
				$i++;
			}
			$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
			<tr>
			' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
			<tr>
			';
		}
	}
	
	//TODO
	// a lot of stuff is now repeated 3 times, for text metadata, xml classification, and idlink classifications. Look at factoring some of it out. 
	


	foreach ($idlink_elements_matrix as $el => $idlink_classifications)
	{
		$block .= <<<END_HTML
			<tr>
				<th colspan="3" class="concordtable">
					Select restrictions on <em>{$xml[$el]->description}</em> 
					for your $thing_to_produce -- affects <em>{$xml[$xml[$el]->att_family]->description}</em> regions:
				</th>
			</tr>
END_HTML;
		
		$header_row = array();
		$body_row = array();
		$i = 0;

		foreach ($idlink_classifications as $field_h => $field_o)
		{
			
			$header_row[$i] = '<td width="33%" class="concordgrey" align="center">' . $field_o->description . '</td>';
			$body_row[$i] = '<td class="concordgeneral" valign="top" nowrap="nowrap">';
			
			$catlist = idlink_category_listdescs($Corpus->name, $field_o->att_handle, $field_h);

			$t_base = preg_replace("/^{$xml[$el]->att_family}_/",  '', $el) . '/' . $field_h;
			
			foreach ($catlist as $handle => $desc)
			{
				$t_value = $xml[$el]->att_family . '|'. $t_base . '~' . $handle;
				$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked="checked" ' : '');
				$body_row[$i] .= '<input type="checkbox" name="t" value="' . $t_value . '" ' . $check 
					. '/> ' . ($desc == '' ? $handle : escape_html($desc)) . '<br/>';
			}
			
			/* whitespace is gratuitous for readability */
			$body_row[$i] .= '
				&nbsp;
				</td>';
						
			$i++;
			/* print three columns at a time */
			if ( $i == 3 )
			{
				$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
				<tr>
				' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
				<tr>
				';
				$i = 0;
			}
		}

		if ($i > 0) /* not all cells printed */
		{
			while ($i < 3)
			{
				$header_row[$i] = '<td class="concordgrey" align="center">&nbsp;</td>';
				$body_row[$i] = '<td class="concordgeneral">&nbsp;</td>';
				$i++;
			}
			$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
			<tr>
			' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
			<tr>
			';
		}
	}	
	
	$block .= '</tr>
		<input type="hidden" name="del" size="-1" value="end" />
		';
	
	return $block;
}





function printquery_lookup()
{
	/* much of this is the same as the form for freq list, but simpler */
	
	/* do we want to allow an option for "showing both words and tags"? */
	$primary_annotation = get_corpus_metadata('primary_annotation');
	
	$annotation_available = ( empty($primary_annotation) ? false : true );

?>
<table class="concordtable" width="100%">

	<tr>
		<th class="concordtable" colspan="2">Word lookup</th>
	</tr>
	
	<tr>
		<td class="concordgrey" colspan="2">
			&nbsp;<br/>
			You can use this search to find out how many words matching the form you look up
			occur in the corpus, and the different tags that they have.
			<br/>&nbsp;
		</td>
	</tr>
	
	<form action="redirect.php" method="get">
		<tr>
			<td class="concordgeneral">Enter the word-form you want to look up</td>
			<td class="concordgeneral">
				<input type="text" name="lookupString" size="32" />
				<br/>
				<em>(NB. you can use the normal wild-cards of Simple Query language)</em>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">Show only words ...</td>
			<td class="concordgeneral">
				<table>
					<tr>
						<td class="basicbox">
							<p>
								<input type="radio" name="lookupType" value="begin" checked="checked" />
								starting with
							</p>
							<p>
								<input type="radio" name="lookupType" value="end" />
								ending with
							</p>
							<p>
								<input type="radio" name="lookupType" value="contain"/>
								containing
							</p>
							<p>
								<input type="radio" name="lookupType" value="exact"  />
								matching exactly
							</p>
						</td>
						<td class="basicbox" valign="center">
							... the pattern you specified
						</td>							
					</tr>
				</table>
				<!--
				<select name="lookupType">
					<option value="begin" selected="selected">starting with</option>
					<option value="end">ending with</option>
					<option value="contain">containing</option>
					<option value="exact">matching exactly</option>
				</select>
				the pattern you specified
				-->
			</td>
		</tr>
		
		<?php		
		if ($annotation_available)
		{
			echo '
			<tr>
				<td class="concordgeneral">List results by word-form, or by word-form AND tag?</td>
				<td class="concordgeneral">
					<select name="lookupShowWithTags">
						<option value="1" selected="selected">List by word-form and tag</option>
						<option value="0">Just list by word-form</option>
					</select>
				</td>
			</tr>';
		}
		?>

		<tr>
			<td class="concordgeneral">Number of items shown per page:</td>
			<td class="concordgeneral">
				<select name="pp">
					<option>10</option>
					<option selected="selected">50</option>
					<option>100</option>
					<option>250</option>
					<option>350</option>
					<option>500</option>
					<option>1000</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="2" align="center">
				&nbsp;<br/>
				<input type="submit" value="Lookup " />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="reset" value="Clear the form" />
				<br/>&nbsp;
			</td>
		</tr>
		<input type="hidden" name="redirect" value="lookup" />
		<input type="hidden" name="uT" value="y" />
	</form>

</table>
<?php


}




function printquery_keywords()
{
	global $Config;
	global $Corpus;
	
	/* create the options for frequency lists to compare */
	
	/* needed for both local and public subcorpora */
	$subc_mapper = get_subcorpus_name_mapper();	
	
	/* subcorpora belonging to this user that have freqlists compiled (list of IDs returned) */
	$subcorpora = list_freqtabled_subcorpora();

	/* public freqlists - corpora */
	$public_corpora = list_public_whole_corpus_freqtables();

	/* public freqlists - subcorpora (function returns associative array) */
	$public_subcorpora = list_public_freqtables();

	
	$list_options = "<option value=\"__entire_corpus\">Whole of " . escape_html($Corpus->title) ."</option>\n";
	
	foreach ($subcorpora as $s)
		$list_options .= "\t\t\t\t\t<option value=\"sc~$s\">Subcorpus: {$subc_mapper[$s]}</option>\n";
	
	$list_options_list2 = $list_options;
	/* only list 2 has the "public" options */
	
	foreach ($public_corpora as $pc)
		$list_options_list2 .= 
			( $pc['corpus'] == $Corpus->name ? '' : 
				( "\t\t\t\t\t<option value=\"pc~{$pc['corpus']}\">Public frequency list:  " 
 					. escape_html($pc['public_freqlist_desc']) 
					. "</option>\n" )
			);
	
	foreach ($public_subcorpora as $ps)
		$list_options_list2 .= "\t\t\t\t\t<option value=\"ps~{$ps['freqtable_name']}\">
			Public frequency list: subcorpus {$subc_mapper[$ps['query_scope']]} from corpus {$ps['corpus']}
			</option>\n";
	
	/* and the options for selecting an attribute */
	
	$attribute = get_corpus_annotations();
	
	$att_options = '<option value="word">Word forms</option>
		';
	
	foreach ($attribute as $k => $a)
	{
		$a = escape_html($a);
		$att_options .= "<option value=\"$k\">$a</option>\n";
	}
		

?>
<table class="concordtable" width="100%">

	<tr>
		<th class="concordtable" colspan="4">Keywords and key tags</th>
	</tr>
	
	<tr>
		<td class="concordgrey" colspan="4" align="center">
			&nbsp;<br/>
			Keyword lists are compiled by comparing frequency lists you have created for different subcorpora. 
			<a href="index.php?thisQ=subcorpus&uT=y">Click here to create/view frequency lists</a>.
			<br/>&nbsp;
		</td>
	</tr>
	
	<form action="keywords.php" method="get">
		<tr>
			<td class="concordgeneral">Select frequency list 1:</td>
			<td class="concordgeneral">
				<select name="kwTable1">
					<?php echo $list_options; ?>
				</select>
			</td>
			<td class="concordgeneral">Select frequency list 2:</td>
			<td class="concordgeneral">
				<select name="kwTable2">
					<?php echo $list_options_list2; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">Compare:</td>
			<td class="concordgeneral" colspan="3">
				<select name="kwCompareAtt">
					<?php echo $att_options; ?>
				</select>
			</td>
		</tr>
		
		<tr>
			<th class="concordtable" colspan="4">Options for keyword analysis:</th>
		</tr>


		<tr>
			<td class="concordgeneral" rowspan="2">Show:</td>
			<td class="concordgeneral" rowspan="2">
				<select name="kwWhatToShow">
					<option value="allKey" >All keywords</option>
					<option value="onlyPos">Positive keywords</option>
					<option value="onlyNeg">Negative keywords</option>
					<option value="lock"   >Lockwords</option>
				</select>
			</td>
			<td class="concordgeneral">Comparison statistic:</td>
			<td class="concordgeneral">
				<select name="kwStatistic">
					<option value="LL"   selected="selected">Log-likelihood</option>
					<option value="LR_LL">Log Ratio with Log-likelihood filter</option>
					<option value="LR_CI">Log Ratio with Confidence Interval filter</option>
					<option value="LR_UN">Log Ratio (unfiltered)</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<td class="concordgeneral">
				Significance cut-off point:
				<br>(or confidence interval width)
				</td>
			<td class="concordgeneral">
				<select name="kwAlpha">
					<option value="0.05"      >5%</option>
					<option value="0.01"      >1%</option>
					<option value="0.001"     >0.1%</option>
					<option value="0.0001" selected="selected">0.01%</option>
					<option value="0.00001"   >0.001%</option>
					<option value="0.000001"  >0.0001%</option>
					<option value="0.0000001" >0.00001%</option>
					<option value="1.0"       >No cut-off</option>
				</select>
				<br>
				<input name="kwFamilywiseCorrect" value="Y" type="checkbox" checked="checked" />
				Use Šidák correction?
			</td>
		</tr>
		
		
		<tr>
			<td class="concordgeneral">Min. frequency (list 1):</td>
			<td class="concordgeneral">
				<select name="kwMinFreq1">
					<option>1</option>
					<option>2</option>
					<option selected="selected">3</option>
					<option>4</option>
					<option>5</option>
					<option>6</option>
					<option>7</option>
					<option>8</option>
					<option>9</option>
					<option>10</option>
					<option>15</option>
					<option>20</option>
					<option>50</option>
					<option>100</option>
					<option>500</option>
					<option>1000</option>
				</select>
			</td>
			<td class="concordgeneral">Min. frequency (list 2):</td>
			<td class="concordgeneral">
				<select name="kwMinFreq2">
					<option>0</option>
					<option>1</option>
					<option>2</option>
					<option selected="selected">3</option>
					<option>4</option>
					<option>5</option>
					<option>6</option>
					<option>7</option>
					<option>8</option>
					<option>9</option>
					<option>10</option>
					<option>15</option>
					<option>20</option>
					<option>50</option>
					<option>100</option>
					<option>500</option>
					<option>1000</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="4" align="center">
				&nbsp;<br>
				<input type="submit" name="kwMethod" value="Calculate keywords" />
				<br>&nbsp;
			</td>
		</tr>
		
		<tr>
			<th class="concordtable" colspan="4">
				View unique words or tags on one frequency list:
			</th>
		</tr>
		
		<tr>
			<td class="concordgeneral" colspan="2">Display items that occur in:</td>
			<td class="concordgeneral" colspan="2">
				<select name="kwEmpty">
					<option value="f1">Frequency list 1 but NOT frequency list 2</option>
					<option value="f2">Frequency list 2 but NOT frequency list 1</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="4" align="center">
				&nbsp;<br>
				<input type="submit" name="kwMethod" value="Show unique items on list" />
				<br>&nbsp;
			</td>
		</tr>

		<input type="hidden" name="uT" value="y" />
	</form>

</table>
<?php

}





function printquery_freqlist()
{
	/* much of this is the same as the form for keywords, but simpler */
	global $Corpus;
	
	/* create the options for frequency lists to compare */
	
	/* subcorpora belonging to this user that have freqlists compiled (list of IDs returned) */
	$subcorpora = list_freqtabled_subcorpora();
	/* public freqlists - corpora */
	
	$list_options = "<option value=\"__entire_corpus\">Whole of ". escape_html($Corpus->title) . "</option>\n";
	
	$subc_mapper = get_subcorpus_name_mapper();	
	foreach ($subcorpora as $s)
		$list_options .= "<option value=\"$s\">Subcorpus: {$subc_mapper[$s]}</option>\n";
	
	/* and the options for selecting an attribute */
	
	$attribute = get_corpus_annotations();
	
	$att_options = '<option value="word">Word forms</option>
		';
	
	foreach ($attribute as $k => $a)
		$att_options .= "<option value=\"$k\">$a</option>\n";
	
	

?>
<table class="concordtable" width="100%">

	<tr>
		<th class="concordtable" colspan="2">Frequency lists</th>
	</tr>
	
	<tr>
		<td class="concordgrey" colspan="2" align="center">
			You can view the frequency lists of the whole corpus and frequency lists for 
			subcorpora you have created. <a href="index.php?thisQ=subcorpus&uT=y">Click 
			here to create/view subcorpus frequency lists</a>.
		</td>
	</tr>
	
	<form action="freqlist.php" method="get">
		<tr>
			<td class="concordgeneral">View frequency list for ...</td>
			<td class="concordgeneral">
				<select name="flTable">
					<?php echo $list_options; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">View a list based on ...</td>
			<td class="concordgeneral">
				<select name="flAtt">
					<?php echo $att_options; ?>
				</select>
			</td>
		</tr>
		
		<tr>
			<th class="concordtable" colspan="2">Frequency list option settings</th>
		</tr>

		<tr>
			<td class="concordgeneral">Filter the list by <em>pattern</em> - show only words/tags ...</td>
			<td class="concordgeneral">
				<select name="flFilterType">
					<option value="begin" selected="selected">starting with</option>
					<option value="end">ending with</option>
					<option value="contain">containing</option>
					<option value="exact">matching exactly</option>
				</select>
				&nbsp;&nbsp;
				<input type="text" name="flFilterString" size="32" />
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">Filter the list by <em>frequency</em> - show only words/tags ...</td>
			<td class="concordgeneral">
				with frequency between 
				<input type="text" name="flFreqLimit1" size="8" />
				and
				<input type="text" name="flFreqLimit2" size="8" />
			</td>
		</tr>


		<tr>
			<td class="concordgeneral">Number of items shown per page:</td>
			<td class="concordgeneral">
				<select name="pp">
					<option>10</option>
					<option selected="selected">50</option>
					<option>100</option>
					<option>250</option>
					<option>350</option>
					<option>500</option>
					<option>1000</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">List order:</td>
			<td class="concordgeneral">
				<select name="flOrder">
					<option value="desc" selected="selected">most frequent at top</option>
					<option value="asc">least frequent at top</option>
					<option value="alph">alphabetical order</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="2" align="center">
				&nbsp;<br/>
				<input type="submit" value="Show frequency list" />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="reset" value="Clear the form" />
				<br/>&nbsp;
			</td>
		</tr>
		<input type="hidden" name="uT" value="y" />
	</form>

</table>
<?php


}

/* not really a "query", but closer to belonging in this file than any other. */
function printquery_corpusmetadata()
{
	global $Corpus;

	?>
	<table class="concordtable" width="100%">
	
		<tr>
			<th colspan="2" class="concordtable">Metadata for <?php echo escape_html($Corpus->title); ?> 
			</th>
		</tr>

	<?php
	
	/* set up the data we need */
		
	/* number of files in corpus */
	list($num_texts) = mysql_fetch_row(do_mysql_query("select count(text_id) from text_metadata_for_{$Corpus->name}"));
	$num_texts = number_format((float)$num_texts);
	
	/* now get tokens / types */
	$tokens = get_corpus_wordcount();
	$types  = get_corpus_n_types();
	$words_in_all_texts = empty($tokens) ? 'Cannot be calculated (wordcount not cached)'        : number_format((float)$tokens);
	$types_in_corpus    = empty($types)  ? 'Cannot be calculated (frequency tables not set up)' : number_format((float)$types);
	$type_token_ratio   = (empty($tokens)||empty($types)) 
							? 'Cannot be calculated (type or token count not available)' 
							: number_format( ((float)$types / (float)$tokens) , 4) . ' types per token';
	

	/* create a placeholder for the primary annotation's description */
	$primary_annotation_string = $Corpus->primary_annotation;
	/* the description itself will be grabbed when we scroll through the full list of annotations */

	
	?>
		<tr>
			<td width="50%" class="concordgrey">Corpus title</td>
			<td width="50%" class="concordgeneral"><?php echo escape_html($Corpus->title); ?></td>
		</tr>
		<tr>
			<td class="concordgrey">CQPweb's short handles for this corpus</td>
			<td class="concordgeneral"><?php echo "{$Corpus->name} / {$Corpus->cqp_name}"; ?></td>
		</tr>
		<tr>
			<td class="concordgrey">Total number of corpus texts</td>
			<td class="concordgeneral"><?php echo $num_texts; ?></td>
		</tr>
		<tr>
			<td class="concordgrey">Total words in all corpus texts</td>
			<td class="concordgeneral"><?php echo $words_in_all_texts; ?></td>
		</tr>
		<tr>
			<td class="concordgrey">Word types in the corpus</td>
			<td class="concordgeneral"><?php echo $types_in_corpus; ?></td>
		</tr>
		<tr>
			<td class="concordgrey">Type:token ratio</td>
			<td class="concordgeneral"><?php echo $type_token_ratio; ?></td>
		</tr>

	<?php
	
	
	/* VARIABLE METADATA */

	$result_variable = do_mysql_query("select * from corpus_metadata_variable where corpus = '{$Corpus->name}'");

	while (false !== ($metadata = mysql_fetch_assoc($result_variable)) )
	{
		/* if it looks like a URL, linkify it */
		if (0 < preg_match('|^https?://\S+$|', $metadata['value']))
			$metadata['value'] = "<a href=\"{$metadata['value']}\" target=\"_blank\">" . escape_html($metadata['value']) . "</a>";
		else
			$metadata['value'] = escape_html($metadata['value']);
		?>
		
		<tr>
			<td class="concordgrey"><?php echo escape_html($metadata['attribute']); ?></td>
			<td class="concordgeneral"><?php echo $metadata['value']; ?></td>
		</tr>
		
		<?php
	}
	
	?>
	
		<tr>
			<th class="concordtable" colspan="2">Text metadata and word-level annotation</th>
		</tr>

	<?php
	
	
	/* TEXT CLASSIFICATIONS */

	$result_textfields = do_mysql_query("select handle from text_metadata_fields where corpus = '{$Corpus->name}'");
	$num_rows = mysql_num_rows($result_textfields);

	?>
	
		<tr>
			<td rowspan="<?php echo $num_rows; ?>" class="concordgrey">
				The database stores the following information for each text in the corpus:
			</td>
			
	<?php
	$i = 1;
	while (($metadata = mysql_fetch_row($result_textfields)) != false)
	{
		echo '<td class="concordgeneral">';
		echo escape_html(metadata_expand_field($metadata[0]));
		echo '</td></tr>';
		if (($i) < $num_rows)
			echo '<tr>';
		$i++;
	}
	if ($i == 1)
		echo '<td class="concordgeneral">There is no text-level metadata for this corpus.</td></tr>';
	?>
		<tr>
			<td class="concordgrey">The <b>primary</b> classification of texts is based on:</td>
			<td class="concordgeneral">
				<?php 
				echo (empty($Corpus->primary_classification_field)
					? 'A primary classification scheme for texts has not been set.'
					: escape_html(metadata_expand_field($Corpus->primary_classification_field)))
					;
				?>
			</td>
		</tr>
	<?php	
	
	
	/* ANNOTATIONS */
	/* get a list of annotations */
	$result_annotations = do_mysql_query("select * from annotation_metadata where corpus = '{$Corpus->name}'");

	$num_rows = mysql_num_rows($result_annotations);
	?>
		<tr>
			<td rowspan="<?php echo $num_rows; ?>" class="concordgrey">
				Words in this corpus are annotated with:
			</td>
	<?php
	$i = 1;
	
	while (($annotation = mysql_fetch_assoc($result_annotations)) != false)
	{
		echo '<td class="concordgeneral">';
		if ($annotation['description'] != "")
		{
			echo escape_html($annotation['description']);
			
			/* while we're looking at the description, save it for later if this
			 * is the primary annotation */
			if ($primary_annotation_string == $annotation['handle'])
				$primary_annotation_string  = escape_html($annotation['description']);
		}
		else
			echo $annotation['handle'];
		if ($annotation['tagset'] != "")
		{
			echo ' (';
			if ($annotation['external_url'] != "")
				echo '<a target="_blank" href="' . $annotation['external_url'] 
					. '">' . $annotation['tagset'] . '</a>';
			else
				echo $annotation['tagset'];
			echo ')';
		}	
			
		echo '</td></tr>';
		if (($i) < $num_rows)
			echo '<tr>';
		$i++;
	}
	/* if there were no annotations.... */
	if ($i == 1)
		echo '<td class="concordgeneral">There is no word-level annotation in this corpus.</td></tr>';
	?>
		<tr>
			<td class="concordgrey">The <b>primary</b> word-level annotation scheme is:</td>
			<td class="concordgeneral">
				<?php 
				echo empty($primary_annotation_string) 
					? 'No primary word-level annotation scheme has been set' 
					: $primary_annotation_string; 
				?>
			</td>
		</tr>
	<?php		
	
	
	/* EXTERNAL URL */
	if ( ! empty($Corpus->external_url) )
	{
		?>
		<tr>
			<td class="concordgrey">
				Further information about this corpus is available on the web at:
			</td>
			<td class="concordgeneral">
				<a target="_blank" href="<?php echo escape_html($Corpus->external_url); ?>">
					<?php echo escape_html($Corpus->external_url); ?>
				</a>
			</td>
		</tr>
		<?php
	}
		
	?>	
	</table>
	<?php
}


