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







function printquery_corpusoptions()
{
	global $Corpus;

// TODO move actions into corpus-admin.php? then this could be purely display.
// TODO change this to use execute.php maybe??? bad separation of concerns ot ahve actions at the top of a render scirpt.
	if (isset($_GET['settingsUpdateURL']))
		update_corpus_external_url($_GET['settingsUpdateURL']);
	if (isset($_GET['settingsUpdatePrimaryClassification']))
		update_corpus_primary_classification_field($_GET['settingsUpdatePrimaryClassification']);
	if (isset($_GET['settingsUpdateContextScope']))
	{
		if ($_GET['settingsUpdateContextUnit'] == '*words*')
			$_GET['settingsUpdateContextUnit'] = '';
		update_corpus_conc_scope($_GET['settingsUpdateContextScope'], $_GET['settingsUpdateContextUnit']);
	}


	$classifications = metadata_list_classifications();
	$class_options = '';
		
	foreach ($classifications as $class)
	{
		$class_options .= "<option value=\"{$class['handle']}\"";
		$class_options .= ($class['handle'] === $Corpus->primary_classification_field ? 'selected="selected"' : '');
		$class_options .= '>' . $class['description'] . '</option>';
	}
	
	/* convenience vars */
	$r2l = $Corpus->main_script_is_r2l;
	$case_sensitive = $Corpus->uses_case_sensitivity;
	



	?>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Corpus options</th>
		</tr>
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="3">Core corpus settings</th>
		</tr>
		<form action="execute.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					Corpus title:
				</td>
				<td class="concordgeneral" align="center">
					<input type="text" name="args" value="<?php echo escape_html($Corpus->title); ?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="locationAfter" value="index.php?thisQ=corpusSettings&uT=y" />
			<input type="hidden" name="function" value="update_corpus_title" />
			<input type="hidden" name="uT" value="y" />
		</form>
		<form action="execute.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					Directionality of main corpus script:
				</td>
				<td class="concordgeneral" align="center">
					<select name="args">
						<!-- note, false = left-to-right -->
						<option value="0" <?php echo ($r2l ? '' : 'selected="selected"'); ?>>Left-to-right</option>
						<option value="1" <?php echo ($r2l ? 'selected="selected"' : ''); ?>>Right-to-left</option>
					</select>
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="locationAfter" value="index.php?thisQ=corpusSettings&uT=y" />
			<input type="hidden" name="function" value="update_corpus_main_script_is_r2l" />
			<input type="hidden" name="uT" value="y" />
		</form>
		<form action="execute.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					Corpus requires case-sensitive collation for string comparison and searches
					<br/>&nbsp;<br/>
					<em>
						(note: the default, and recommended, value is &ldquo;No&rdquo;; if you change this  
						<br/>
						setting, you must delete and recreate all frequency lists and delete cached databases)
					</em> 
				</td>
				<td class="concordgeneral" align="center">
					<select name="args">
						<!-- note, 0 (false) = set to false -->
						<option value="0" <?php echo ($case_sensitive ? '' : 'selected="selected"'); ?>>No</option>
						<option value="1" <?php echo ($case_sensitive ? 'selected="selected"' : ''); ?>>Yes</option>
					</select>
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="locationAfter" value="index.php?thisQ=corpusSettings&uT=y" />
			<input type="hidden" name="function" value="update_corpus_uses_case_sensitivity" />
			<input type="hidden" name="uT" value="y" />
		</form>
		
		

		<!-- ***************************************************************************** -->

		<tr>
			<th class="concordtable" colspan="3">Display settings</th>
		</tr>
		<form action="execute.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					Stylesheet address
					(<a href="<?php echo $Corpus->css_path; ?>">click here to view</a>):
				</td>
				<td class="concordgeneral" align="center">
					<input type="text" name="args" value="<?php echo $Corpus->css_path; ?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="locationAfter" value="index.php?thisQ=corpusSettings&uT=y" />
			<input type="hidden" name="function" value="update_corpus_css_path" />
			<input type="hidden" name="uT" value="y" />
		</form>
		
		<form action="index.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					How many words/elements of context should be shown in concordances?
					<br/>&nbsp;<br/>
					<em>Note: context of a hit is counted <strong>each way</strong>.
				</td>
				<td class="concordgeneral" align="center">
					show
					<input type="text" name="settingsUpdateContextScope" size="3"
						value="<?php echo $Corpus->conc_scope; ?>" />
					of
					<select name="settingsUpdateContextUnit">
						<?php

						$current_scope_unit = $Corpus->conc_s_attribute;

						echo '<option value="*words*"' 
							. ( is_null($current_scope_unit) ? ' selected="selected"' : '' ) 
							. '>words</option>';

						foreach (list_xml_elements($Corpus->name) as $element => $element_desc)
							echo "<option value=\"$element\""
								, ($element == $current_scope_unit ? ' selected="selected"' : '')
								, ">XML element: "
								, escape_html($element_desc)
								, " ($element)</option>"
								;

						?>
						
					</select>
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="thisQ" value="corpusSettings" />
			<input type="hidden" name="uT" value="y" />
		</form>

		<form action="execute.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					Initial words to show (each way) in extended context:
				</td>
				<td class="concordgeneral" align="center">
					<input type="text" name="args" value="<?php echo $Corpus->initial_extended_context; ?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="locationAfter" value="index.php?thisQ=corpusSettings&uT=y" />
			<input type="hidden" name="function" value="update_corpus_initial_extended_context" />
			<input type="hidden" name="uT" value="y" />
		</form>

		<form action="execute.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					Maximum words to show (each way) in extended context:
				</td>
				<td class="concordgeneral" align="center">
					<input type="text" name="args" value="<?php echo $Corpus->max_extended_context; ?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="locationAfter" value="index.php?thisQ=corpusSettings&uT=y" />
			<input type="hidden" name="function" value="update_corpus_max_extended_context" />
			<input type="hidden" name="uT" value="y" />
		</form>

		<form action="execute.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					Word annotation to make available as alternative view in extended context:
				</td>
				<td class="concordgeneral" align="center">
					<select name="args">

						<?php 
						echo '<option value=""'; 
						if (empty($Corpus->alt_context_word_att))
							echo ' selected="selected"';
						echo '>Do not make alternative view available</option>';
						
						foreach (get_corpus_annotations() as $att=>$desc)
							echo '<option value="'
 								, $att, '"'
 								, ($Corpus->alt_context_word_att == $att ? ' selected="selected">' : '">')
 								, escape_html($desc)
 								, '</option>'
 								;
						?>

					</select>
					
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="locationAfter" value="index.php?thisQ=corpusSettings&uT=y" />
			<input type="hidden" name="function" value="update_corpus_alt_context_word_att" />
			<input type="hidden" name="uT" value="y" />
		</form>




		<tr>
			<th class="concordtable" colspan="3">General options</th>
		</tr>
		<form action="execute.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					The corpus is currently in the following category:
				</td>
				<td class="concordgeneral" align="center">
					<select name="args">
						<?php
// 						$this_corpus_cat = get_corpus_metadata('corpus_cat');

						foreach (list_corpus_categories() as $i => $c)
							echo "<option value=\"$i\"", ( ($Corpus->corpus_cat == $i) ? ' selected="selected"': ''), ">$c</option>\n\t\t";
						?>
					
					</select>
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="locationAfter" value="index.php?thisQ=corpusSettings&uT=y" />
			<input type="hidden" name="function" value="update_corpus_category" />
			<input type="hidden" name="uT" value="y" />
		</form>
		
		<form action="corpus-admin.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					Visibility of the corpus is currently set to:
					<br/>&nbsp;<br/>
					<em>
						(note: &ldquo;Visible&rdquo; means the corpus is accessible through the main menu.
						<br/>
						Invisible corpora can still be accessed by direct URL entry by people who know the address.
					</em>
				</td>
		
				<td align="center" class="concordgeneral">
					<select name="newVisibility">
						<?php
						if ($Corpus->visible)
							echo "<option value=\"1\" selected=\"selected\">Visible</option><option value=\"0\">Invisible</option>\n";
						else
							echo "<option value=\"1\">Visible</option><option value=\"0\" selected=\"selected\">Invisible</option>\n";
						?>
					</select>
				</td>
				
				<td align="center" class="concordgeneral">
					<input type="submit" value="Update!">
				</td>
				<input type="hidden" name="caAction" value="updateVisibility" />
				<input type="hidden" name="uT" value="y" />
			</tr>
		</form>
		
		<form action="index.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					The external URL (for documentation/help links) is:
				</td>
				<td class="concordgeneral" align="center">
					<input type="text" name="settingsUpdateURL" maxlength="200" value="<?php 
						echo get_corpus_metadata('external_url'); 
					?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
			</tr>
			<input type="hidden" name="thisQ" value="corpusSettings" />
			<input type="hidden" name="uT" value="y" />
		</form>
		<form action="index.php" method="get">
			<tr>
				<td class="concordgrey" align="center">
					The primary text categorisation scheme is currently:
				</td>
				<td class="concordgeneral" align="center">
					<select name="settingsUpdatePrimaryClassification">
						<?php
						if (empty($class_options))
						{
							$button = '&nbsp;';
							echo '<option selected="selected">There are no classification schemes for this corpus.</option>';
						}
						else
						{
							$button = '<input type="submit" value="Update" />';
							echo $class_options;
						}
						?>
						
					</select>
					
				</td>
				<td class="concordgeneral" align="center">
					<?php echo $button; ?>
					
				</td>
			</tr>
			<input type="hidden" name="thisQ" value="corpusSettings" />
			<input type="hidden" name="uT" value="y" />
		</form>			
	</table>
	<?php
}





function printquery_manageaccess()
{
	global $Corpus;

	$options_groups_to_add = '';

	$short_priv_desc = array(
		PRIVILEGE_TYPE_CORPUS_FULL       => 'Full',
		PRIVILEGE_TYPE_CORPUS_NORMAL     => 'Normal',
		PRIVILEGE_TYPE_CORPUS_RESTRICTED => 'Restricted'
		);
	
	$all_users_allowed = array();
	
	$corpus_privileges = get_all_privileges_info(array('corpus'=>$Corpus->name));
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">Corpus access control panel</th>
		</tr>
		<tr>
			<td class="concordgrey" align="center" colspan="4">
				&nbsp;<br/>
				The following privileges control access to this corpus:
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<th class="concordtable">ID</th>
			<th class="concordtable">Description</th>
			<th class="concordtable">Access level</th>
			<th class="concordtable">Granted to...</th>
		</tr>
		
		<?php
		
		foreach ($corpus_privileges as $p)
		{
			$users_with = list_users_with_privilege($p->id);
			natcasesort($users_with);
			$grant_string_u = (empty($users_with) ? '&nbsp;' : "<b>Users:</b> " . implode(', ',$users_with));
			$all_users_allowed = array_merge($all_users_allowed, $users_with);
			
			
			$groups_with = list_groups_with_privilege($p->id);
			natcasesort($groups_with);
			$grant_string_g = (empty($groups_with) ? '&nbsp;' : "<b>Groups:</b> " . implode(', ',$groups_with));
			foreach($groups_with as $gw)
				$all_users_allowed = array_merge($all_users_allowed, list_users_in_group($gw));
			
			echo "\t\t<tr>\n"
				, "\t\t\t<td class=\"concordgeneral\" align=\"center\">{$p->id}</td>\n"
				, "\t\t\t<td class=\"concordgeneral\">{$p->description}</td>\n"
				, "\t\t\t<td class=\"concordgeneral\">{$short_priv_desc[$p->type]}</td>\n"
				, "\t\t\t<td class=\"concordgeneral\">$grant_string_g<br/>$grant_string_u</td>\n"
				;
		}
		
		
		?>
		
		<tr>
			<td class="concordgrey" align="center" colspan="4">
				&nbsp;<br/>
				<a class="menuItem" href="../adm/index.php?thisF=userGrants&uT=y">Manage individual privileges</a>
				|
				<a class="menuItem" href="../adm/index.php?thisF=groupGrants&uT=y">Manage group privileges</a>
				| 
				<a class="menuItem" href="../adm/index.php?thisF=groupMembership&uT=y">Manage group membership</a>
				<br/>&nbsp;
			</td>
		</tr>

		<tr>
			<th class="concordtable" colspan="4">Full list of users with access </th>
		</tr>
		<tr>
			<td class="concordgrey" align="center" colspan="4">
				&nbsp;<br/>
				The following users have access to this corpus (any level), individually or via a group membership:
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<td class="concordgeneral" align="center" colspan="4">
				&nbsp;<br/>
				
				<table class="basicbox">
					<tr>
					<?php
					
					$all_users_allowed = array_values(array_unique($all_users_allowed));
					natcasesort($all_users_allowed);
					
					for($i = 0, $n = count($all_users_allowed); $i < $n ; $i++)
					{
						echo "\n\t\t\t\t\t\t<td class=\"basicbox\">"
							, "<a href=\"../adm/index.php?thisF=userView&username={$all_users_allowed[$i]}&uT=y\">{$all_users_allowed[$i]}</a></td>";
						if ( 0 == (($i+1) % 8) && ($i+1) != $n )
							echo "\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>";
					}
					
					?>
					
					</tr>
				</table>
				
				<br/>&nbsp;
			</td>
		</tr>
	</table>
	
	<?php
}


function printquery_managemeta()
{
	global $Config;
	global $Corpus;

	/* this is used for the top table, but also for the corpus-level metadata table (below) */
	$result_variable = do_mysql_query("select * from corpus_metadata_variable where corpus = '{$Corpus->name}'");	
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Admin tools for managing corpus metadata</th>
		</tr>
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">Metadata status summary</th>
		</tr>
		<tr>
			<td  class="concordgrey">
				Text metadata table has been loaded:
			</td>
			<td class="concordgeneral" align="center">
				<?php echo text_metadata_table_exists() ? 'Yes' : 'No'; ?>
			</td>
		</tr>
		<tr>
			<td  class="concordgrey">
				Number of corpus-level metadata items:
				<br>
				(listed below)
			</td>
			<td class="concordgeneral" align="center">
				<?php echo mysql_num_rows($result_variable); ?>
			</td>
		</tr>
<!--
		<tr>
			<td  class="concordgrey" >
			
			</td>
			<td class="concordgeneral" align="center">
			
			</td>
		</tr>
-->
	</table>
	
	
	<?php
	
	if (! text_metadata_table_exists())
	{
		/* we need to create a text metadata table for this corpus */
		?>
		
		<table class="concordtable" width="100%">
			<tr>
				<td class="concordgrey">
					&nbsp;<br/>
					The text metadata table for this corpus has not yet been set up. You must create it,
					using the controls below, before you can search this corpus.
					<br/>&nbsp;
				</td>
			</tr>
		</table>
		
		<?php
		
		/* first, test for the "alternate" form. */
		if (isset($_GET['createMetadataFromXml']) && $_GET['createMetadataFromXml'] == '1')
		{
			?>
			
			<table class="concordtable" width="100%">
		
			<tr>
				<th class="concordtable" colspan="5" >Create metadata table from corpus XML annotations</th>
			</tr>
			<?php
			$possible_xml = get_xml_all_info($Corpus->name);
			/* remove the two we know cannot be used for this: */
			unset($possible_xml['text'], $possible_xml['text_id']);
			
			if (empty($possible_xml))
			{
				?>
				<tr>
					<td class="concordgrey" colspan="5" align="center">
						&nbsp;<br/>
						No usable XML elements/attributes are available in this corpus.
						<br/>&nbsp;
					</td>
				</tr>
				<?php
			}
			else
			{
				?>
				<form action="metadata-admin.php" method="get">
				
					<tr>
						<td class="concordgrey" colspan="5">
							&nbsp;<br/>
							
							The following XML annotations are indexed in the corpus.
							Select the ones which you wish to use as text-metadata fields.
							
							<br/>&nbsp;<br/>
							
							<em>Note: you must only select annotations that occur <strong>at or above</strong>
							the level of &lt;text&gt; in the XML hierarchy of your corpus; doing otherwise may 
							cause a CQP error, and will in any case not give you the expected results.</em> 
							
							<br/>&nbsp;<br/>
							
							The descriptions of the XML elements/attributes can be altered from their original values for
							their use as metadata fields. However, the datatype cannot be changed. 
							
							<br/>&nbsp;<br/>

						</td>
					</tr>
					<tr>
						<th class="concordtable">Use?</th>
						<th class="concordtable">Field handle</th>
						<th class="concordtable">Description for this field</th>
						<th class="concordtable">Datatype of this field</th>
						<th class="concordtable">Which field is the primary classification?</th>
					</tr>
				<?php
				
				foreach($possible_xml as $x)
				{
					if (METADATA_TYPE_NONE == $x->datatype)
						continue;
					
					$x->description = escape_html(trim($x->description));
					if (empty($x->description))
						$x->description = $x->handle;
						
					echo "\n\n<tr>"
						, '<td class="concordgeneral" align="center">'
						, '<input name="createMetadataFromXmlUse_'
						, $x->handle
						, '" type="checkbox" value="1" /> '
						, '</td>'
						, '<td class="concordgeneral">' , $x->handle , '</td>'
						;
					echo '<td class="concordgeneral" align="center">' 
						, '<input name="createMetadataFromXmlDescription_' 
						, $x->handle
						, '" type="text" value="' , $x->description, '"/> '
						, '</td>'
						;
					echo '<td class="concordgeneral" align="center">'
						, $Config->metadata_type_descriptions[$x->datatype]
//						, '<select name="fieldType_'
//						, $x->handle
//						, '"><option value="', METADATA_TYPE_CLASSIFICATION, '" selected="selected">Classification</option>'
//						, '<option value="', METADATA_TYPE_FREETEXT, '">Free text</option></select>'
						, '</td>'
						;
					if (METADATA_TYPE_CLASSIFICATION == $x->datatype)
						echo '<td class="concordgeneral" align="center">'
							, '<input type="radio" name="primaryClassification" value="'
							, $x->handle 
							/* nb this form, unlike t'other, has primaryClassification as a handle, not a row ix */
							, '"/></td>'
							;
					else
						echo '<td class="concordgeneral" align="center">(not a classication)</td>';
						
					echo "</tr>\n\n\n";
				}
				
				?> 
					<tr>
						<th class="concordtable" colspan="5">
							Do you want to automatically run frequency-list setup?
						</th>
					</tr>
					<tr>
						<td class="concordgeneral" colspan="5">
							<table align="center">
								<tr>
									<td class="basicbox">
										<input type="radio" name="createMetadataRunFullSetupAfter" value="1"/>
										<strong>Yes please</strong>, run this automatically (ideal for relatively small corpora)
									</td>
								</tr>
								<tr>
									<td class="basicbox">
										<input type="radio" name="createMetadataRunFullSetupAfter" value="0"  checked="checked"/>
										<strong>No thanks</strong>, I'll run this myself (safer for very large corpora)
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td align="center" class="concordgeneral" colspan="5">
							<input type="submit" value="Create metadata table from XML using the settings above" />
						</td>
					</tr>
					<tr>
						<td align="center" class="concordgrey" colspan="5">
							&nbsp;<br/>
							<a href="index.php?thisQ=manageMetadata&uT=y">
								Click here to go back to the normal metadata setup form.</a>
							<br/>&nbsp;
						</td>
					</tr>
					<input type="hidden" name="mdAction" value="createMetadataFromXml" />
					<input type="hidden" name="corpus" value="<?php echo $Corpus->name; ?>" />
					<input type="hidden" name="uT" value="y" />
				</form>
				<?php
			}
		
			/* to avoid wrapping the whole of the rest of the function in an else */
			echo '</table>';
			return;
			
		} /* end if (create metadata from XML) */
		
		
		/* OK, print the usual (non-XML) metadata setup page. */
		
//		$number_of_fields_in_form = ( isset($_GET['metadataFormFieldCount']) ? (int)$_GET['metadataFormFieldCount'] : 8);
		
		?>

		
		<!-- i want a form with more slots!  - no longer needed since we have an embiggener

		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable" colspan="3">I need more fields!</th>
			</tr>
			<form action="index.php" method="get">
				<tr>
					<td class="concordgeneral">
						Do you need more metadata fields? Use this control:
					</td>
					<td class="concordgeneral">						
						I want a metadata form with 
						<select name="metadataFormFieldCount">
							<option>9</option>
							<option>10</option>
							<option>11</option>
							<option>12</option>
							<option>14</option>
							<option>16</option>
							<option>20</option>
							<option>25</option>
							<option>30</option>
							<option>40</option>
						</select>
						slots!
					</td>
					<td class="concordgeneral">
						<input type="submit" value="Create bigger form!" />
					</td>
				</td>
				<input type="hidden" name="thisQ" value="manageMetadata" />
				<input type="hidden" name="uT" value="y" />
			</form>
		</table>
		-->
		
		<form action="metadata-admin.php" method="get">
		
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable" colspan="5">Choose the file containing the metadata</th>
			</tr>

			<tr>
				<th class="concordtable">Use?</th>
				<th colspan="2" class="concordtable">Filename</th>
				<th class="concordtable">Size (K)</th>
				<th class="concordtable">Date modified</th>
			</tr>

			<?php
			echo print_uploaded_file_selector();
			?>

	
			<tr>
				<th class="concordtable" colspan="5">Describe the contents of the file you have selected</th>
			</tr>
			
			<tr>
				<td class="concordgeneral" colspan="5">
					<table align="center" width="100%">
						<tr>
							<td class="basicbox" width="50%">
								Choose template for text metadata structure
								<br/>
								<i>(or select "Custom metadata structure" and specify annotations in the boxes below)</i>
							</td>
							<td class="basicbox" width="50%">
								<select name="useMetadataTemplate">
									<option value="~~customMetadata" selected="selected">Custom metadata structure</option>
									
									<?php
									
									foreach(list_metadata_templates() as $t)
										echo "\n\t\t\t\t\t\t\t\t\t"
											, '<option value="'
											, $t->id
											, '">'
											, escape_html($t->description)
											, ' (containing ', count($t->fields), ' defined fields)' 
											, "</option>\n"
											;
									
									?>
									
								</select>
							</td>
						</tr>
					</table>
				</td>
			</tr>
				
			<tr>
				<td class="concordgrey" colspan="5">
					Note: you should not specify the text identifier (text_id), which must be the first field. 
					This is inserted automatically.
					
					<br/>&nbsp;<br/>
					
					<em>Classification</em> fields contain one of a set number of handles indicating text categories. 
					<em>Free-text metadata</em> fields can contain anything, and don't indicate categories of texts.
				</td>
			</tr>

			<?php
			
			echo print_embiggenable_metadata_form(5);

			?>
			
			<tr>
				<th class="concordtable" colspan="5">
					Do you want to automatically run frequency-list setup?
				</th>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="5">
					<table align="center">
						<tr>
							<td class="basicbox">
								<input type="radio" name="createMetadataRunFullSetupAfter" value="1"/>
								<strong>Yes please</strong>, run this automatically (ideal for relatively small corpora)
							</td>
						</tr>
						<tr>
							<td class="basicbox">
								<input type="radio" name="createMetadataRunFullSetupAfter" value="0"  checked="checked"/>
								<strong>No thanks</strong>, I&rsquo;ll run this myself (safer for very large corpora)
							</td>
						</tr>
					</table>
				</td>
			</tr>
		
			<tr>
				<td align="center" class="concordgeneral" colspan="5">
					<input type="submit" value="Install metadata table using the settings above" />
				</td>
			</tr>
			
		</table>
		
			<input type="hidden" name="mdAction" value="createMetadataFromFile" /> 
			<input type="hidden" name="fieldCount" id="fieldCount" value="5" />
			<input type="hidden" name="corpus" value="<?php echo $Corpus->name; ?>" />
			<input type="hidden" name="uT" value="y" />
		</form>

		<table class="concordtable" width="100%">
		
		
			<!-- minimalist metadata -->
		
			<tr>
				<th class="concordtable">My corpus has no metadata!</th>
			</tr>
			<tr>
				<form action="metadata-admin.php" method="get">
					<td class="concordgeneral" align="center">
						&nbsp;<br/>
						
						Use this tool to automatically generate a &ldquo;dummy&rdquo; metadata table,
						containing only text IDs, for a corpus with no other metadata.
						<br/>&nbsp;<br/>
						Do you want to automatically run frequency-list setup for your corpus?						
						<br/>&nbsp;<br/>

						<table align="center">
							<tr>
								<td class="basicbox">
									<input type="radio" name="createMetadataRunFullSetupAfter" value="1"/>
									<strong>Yes please</strong>, run this automatically (ideal for relatively small corpora)
								</td>
							</tr>
							<tr>
								<td class="basicbox">
									<input type="radio" name="createMetadataRunFullSetupAfter" value="0"  checked="checked"/>
									<strong>No thanks</strong>, I'll run this myself (safer for very large corpora)
								</td>
							</tr>
						</table>

						
						<input type="submit" value="Create minimalist metadata table"/>
						
						<br/>&nbsp;
					</td>
					<input type="hidden" name="mdAction" value="createTextMetadataMinimalist"/>
					<input type="hidden" name="uT" value="y"/>
				</form>	
			</tr>
		</table>
		
		
		
		<!-- pre-encoded metadata:link to alt page -->
			
		<table class="concordtable" width="100%">
		
			<tr>
				<th class="concordtable" >My metadata is embedded in the XML of my corpus!</th>
			</tr>
			<?php
			/* check for less-than 2, because text_id always exists. */
			if (2 > count(list_xml_with_values($Corpus->name)))
			{
				?>
				<tr>
					<td class="concordgrey" colspan="5" align="center">
						&nbsp;<br/>
						No XML annotations found for this corpus.
						<br/>&nbsp;
					</td>
				</tr>
				<?php
			}
			else
			{
				?>
					<tr>
						<td class="concordgrey" align="center">
							&nbsp;<br/>
							
							<a href="index.php?thisQ=manageMetadata&createMetadataFromXml=1&uT=y">
								Click here to install metadata from within-corpus XML annotation.
							</a>
							
							<br/>&nbsp;<br/>
						</td>
					</tr>

				<?php
			}
			?>
			
		</table>

		<?php
	
	}  /* endif text metadata table does not already exist */
	else
	{
		/* table exists, so allow other actions */
		
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th colspan="2" class="concordtable">Reset the metadata table for this corpus</th>
			</tr>
			<tr>
				<td colspan="2" class="concordgrey" align="center">
					Are you sure you want to do this?
				</td>
			</tr>
			<form action="metadata-admin.php" method="get">
				<tr>
					<td class="concordgeneral" align="center">
						<input type="checkbox" name="clearMetadataAreYouReallySure" value="yesYesYes"/>
						Yes, I'm really sure and I know I can't undo it.
					</td>
					<td class="concordgeneral" align="center">
						<input type="submit" value="Delete metadata table for this corpus" />
					</td>
					<input type="hidden" name="mdAction" value="clearMetadataTable" />
					<input type="hidden" name="corpus" value="<?php echo $Corpus->name; ?>" />
					<input type="hidden" name="uT" value="y" />
				</tr>
			</form>
		</table>
		
		<table class="concordtable" width="100%">
			<tr>
				<th colspan="2" class="concordtable">Corpus-level metadata</th>
			</tr>
			<tr>
				<td class="concordgrey" align="center" colspan="2" />
					<p class="spacer">&nbsp;</p>
					<p>
						The corpus-level metadata is a set of freeform attribute/value pairs that will become
						visible in the user interface (under &ldquo;Corpus info &gt; View corpus metadata&rdquo;).
					</p>
					<p class="spacer">&nbsp;</p>
				</td>
			</tr>
			<tr>
				<td class="concordgrey" align="center" width="50%">Attribute</td>
				<td class="concordgrey" align="center">Value</td>
			</tr>
			<form action="../adm/index.php" method="get">
				<tr>
					<td class="concordgeneral" align="center">
						<input type="text" maxlength="200" name="variableMetadataAttribute" />
					</td>
					<td class="concordgeneral" align="center">
						<input type="text" maxlength="200" name="variableMetadataValue" />
					</td>
					<input type="hidden" name="admFunction" value="variableMetadata" />
					<input type="hidden" name="corpus" value="<?php echo $Corpus->name; ?>" />
				</tr>
				<tr>
					<td class="concordgeneral" align="center" colspan="2" />
						&nbsp;<br/>
						<input type="submit" value="Add a new item to the corpus metadata" />
						<br/>&nbsp;
					</td>
				</tr>
				<input type="hidden" name="uT" value="y" />
			</form>
			<tr>
				<td class="concordgrey" align="center" colspan="2" />
					<p class="spacer">&nbsp;</p>
					<p>
						<em>
						
							<?php
						
							echo mysql_num_rows($result_variable) != 0
								? 'Existing items of variable corpus-level metadata (as attribute-value pairs):' 
								: 'No items of variable corpus-level metadata have been set.'
								;
							?>
	
						</em>
					</p>
					<p class="spacer">&nbsp;</p>
				</td>
			</tr>
			<?php
			while (($metadata = mysql_fetch_assoc($result_variable)) != false)
			{
				$del_link = 'execute.php?function=delete_variable_corpus_metadata&args='
					. urlencode("{$Corpus->name}#{$metadata['attribute']}#{$metadata['value']}")
					. '&locationAfter=' . urlencode('index.php?thisQ=manageMetadata&uT=y') . '&uT=y';
				?>
				<tr>
					<td class="concordgeneral" align="center">
						<?php 
							echo "Attribute [<strong>{$metadata['attribute']}</strong>] 
									with value [<strong>{$metadata['value']}</strong>]"; 
						?>
					</td>
					<td class="concordgeneral" align="center">
						<a class="menuItem" href="<?php echo $del_link; ?>">[Delete]</a>
					</td>
				</tr>
				<?php
			}
			?>
		</table>

		<?php
	
	} /* end else (IE case where metadasta table exists) */
}


function printquery_managefreqlists()
{
	global $Config;
	global $Corpus;
	
	?>
	<table class="concordtable" width="100%">
	
		<tr>
			<th class="concordtable" colspan="2">Corpus frequency list controls</th>
		</tr>
	
		<?php
	
		if (! text_metadata_table_exists())
		{
			$message = 'The text metadata table <strong>does not yet exist</strong> - you must crfeate it (on the <b>Manage metadata</b> page
							before you can setup text begin/end positions. end offset positions. Use the button below to refresh 
								this data.';
			$make_a_button = false;
			$button_label = 'Update CWB text-position records';
		}
		else
		{
			$make_a_button = true;
		
			list($n) = mysql_fetch_row(do_mysql_query("select count(*) from text_metadata_for_{$Corpus->name} where words > 0"));
			
			if ($n > 0)
			{
				$message = 'The text metadata table <strong>has already been populated</strong> 
									with begin/end offset positions. Use the button below to refresh 
									this data.';
				$button_label = 'Update CWB text-position records';
			}
			else
			{
				$message = 'The text metadata table <strong>has not yet been populated</strong> 
									with begin/end offset positions. Use the button below to generate 
									this data.';
				$button_label = 'Generate CWB text-position records';
			}
		}
		
		?>
	
		<tr>
			<td class="concordgrey" width="20%" valign="center">Text begin/end positions</td>
			<td class="concordgeneral" align="center">
				&nbsp;<br/>
				<?php echo $message; ?>
				<br/>&nbsp;
				<?php 
				if ($make_a_button)
				{
					?>
					<form action="execute.php" method="get">
						<input type="submit" value="<?php echo $button_label; ?>"/>
						<br/>
						<input type="hidden" name="function" value="populate_corpus_cqp_positions" />
						<input type="hidden" name="args" value="<?php echo $Corpus->name; ?>" />
						<input type="hidden" name="locationAfter" value="index.php?thisQ=manageFreqLists&uT=y" />
						<input type="hidden" name="uT" value="y" />
					</form>
					<?php
				}
				?>
			</td>
		</tr>

		<?php
		
		$n = mysql_num_rows(
				do_mysql_query("select handle from text_metadata_fields 
					where corpus = '{$Corpus->name}' and datatype = " . METADATA_TYPE_CLASSIFICATION)
				);
		if ($n == 0)
		{
			?>
			<tr>
				<td class="concordgrey" width="20%" valign="center">Text category wordcounts</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					There are no text classification systems in this corpus; wordcounts are therefore not relevant.
					<br/>&nbsp;
				</td>
			</tr>
			<?php
		}
		else
		{
			if ( 0 < mysql_num_rows(
						do_mysql_query("select handle from text_metadata_values where corpus = '{$Corpus->name}' and category_num_words IS NOT NULL")
						) )
			{
				$button_label = 'Update word and file counts';
				$message = 'The word count tables for the different text classification categories 
								in this corpus <strong>have already been generated</strong>. Use the button below 
								to regenerate them.';
			}
			else
			{
				$button_label = 'Populate word and file counts';
				$message = 'The word count tables for the different text classification categories 
								in this corpus <strong>have not yet been populated</strong>. Use the button below  
								to populate them.';
			}
			
			?>
			<tr>
				<td class="concordgrey" width="20%" valign="center">Text category wordcounts</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<?php echo $message; ?>
					<br/>&nbsp;
					<form action="execute.php" method="get">
						<input type="submit" value="<?php echo $button_label; ?>"/>
						<br/>
						<input type="hidden" name="function" value="metadata_calculate_category_sizes" />
						<input type="hidden" name="args" value="<?php echo $Corpus->name; ?>" />
						<input type="hidden" name="locationAfter" value="index.php?thisQ=manageFreqLists&uT=y" />
						<input type="hidden" name="uT" value="y" />
					</form>
				</td>
			</tr>
			<?php
		}
		
		
		$corpus_cqp_name_lower = strtolower($Corpus->cqp_name);
		
		if (file_exists("{$Config->dir->registry}/{$corpus_cqp_name_lower}__freq"))
		{
			$message = 'The text-by-text list for this corpus <strong>has already been created</strong>. Use the button below to delete and recreate it.';
			$button_label = 'Recreate CWB frequency table';
		}
		else
		{
			$message = 'The text-by-text list for this corpus <strong>has not yet been created</strong>. Use the button below to generate it.';
			$button_label = 'Create CWB frequency table';
		}
		
		?>
		<tr>
			<td class="concordgrey" width="20%" valign="center">Text-by-text freq-lists</td>
			<td class="concordgeneral" align="center">
				&nbsp;<br/>
				CWB text-by-text frequency lists are used to generate subcorpus frequency lists (important for keywords, collocations etc.)
				<br/>&nbsp;<br/>
				<?php echo $message; ?>
				<br/>&nbsp;
				<form action="execute.php" method="get">
					<input type="submit" value="<?php echo $button_label; ?>"/>
					<br/>
					<input type="hidden" name="function" value="make_cwb_freq_index" />
					<input type="hidden" name="args" value="<?php echo $Corpus->name; ?>" />
					<input type="hidden" name="locationAfter" value="index.php?thisQ=manageFreqLists&uT=y" />
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>

		<?php
		
		if (0 < mysql_num_rows(do_mysql_query("show tables like 'freq_corpus_{$Corpus->name}_word'")))
		{
			$message = 'Word and annotation frequency tables for this corpus <strong>have already been created</strong>. 
							Use the button below to delete and recreate them.';
			$button_label = 'Recreate frequency tables';
		}
		else
		{
			$message = 'Word and annotation frequency tables for this corpus <strong>have not yet been created</strong>. 
							Use the button below to generate them.';
			$button_label = 'Create frequency tables';
		}
		?>
		
		<tr>
			<td class="concordgrey" width="20%" valign="center">Frequency tables</td>
			<td class="concordgeneral" align="center">
				&nbsp;<br/>
				<?php echo $message; ?>
				<br/>&nbsp;<br/>
				<form action="execute.php" method="get">
					<input type="submit" value="<?php echo $button_label; ?>"/>
					<br/>
					<input type="hidden" name="function" value="corpus_make_freqtables" />
					<input type="hidden" name="args" value="<?php echo $Corpus->name; ?>" />
					<input type="hidden" name="locationAfter" value="index.php?thisQ=manageFreqLists&uT=y" />
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>

	
		<?php

		/* Is the corpus public on the system? */
		$public_freqlist_desc = get_corpus_metadata('public_freqlist_desc');
		if ( ! is_null($public_freqlist_desc))  /* cos NULL in MySQL comes back as NULL */
		{
			?>
			<tr>
				<td class="concordgrey" width="20%" valign="center">Public freq-lists</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					This corpus's frequency list is publicly available across the system (for
					keywords, etc), identified as <strong><?php echo $public_freqlist_desc;?></strong>.
					Use the button below to undo this!
					<br/>&nbsp;<br/>
					<form action="execute.php" method="get">
						<input type="submit" value="Make this corpus's frequency list private again!"/>
						<br/>
						<input type="hidden" name="function" value="unpublicise_this_corpus_freqtable" />
						<input type="hidden" name="locationAfter" value="index.php?thisQ=manageFreqLists&uT=y" />
						<input type="hidden" name="uT" value="y" />
					</form>
				</td>
			</tr>
			<?php
		}
		else /* corpus is not public on the system */
		{
			?>
			<tr>
				<td class="concordgrey" width="20%" valign="center">Public freq-lists</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					Use this control to make the frequency list for this corpus public on the
					system, so that anyone can use it for calculation of keywords, etc.
					<br/>&nbsp;<br/>
					<form action="execute.php" method="get">

						The frequency list will be identified by this descriptor 
						(you may wish to modify):
						<br/>&nbsp;<br/>
						<input type="text" name="args" value="<?php echo escape_html($Corpus->title); ?>" size="40" maxlength="100" />
						<br/>&nbsp;<br/>
						<input type="submit" value="Make this frequency table public"/>

						&nbsp;<br/>
						<input type="hidden" name="function" value="publicise_this_corpus_freqtable" />
						<input type="hidden" name="locationAfter" value="index.php?thisQ=manageFreqLists&uT=y" />
						<input type="hidden" name="uT" value="y" />
					</form>
				</td>
			</tr>
			<?php
		}
		?>

	</table>
	<?php
}


function printquery_managecategories()
{
	global $Corpus;
	
	$classification_list = metadata_list_classifications();

	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Insert or update text category descriptions</th>
		</tr>
		<?php

		if (empty($classification_list))
			echo '<tr><td class="concordgrey" align="center">&nbsp;<br/>
				No text classification schemes exist for this corpus.
				<br/>&nbsp;</td></tr>';

		foreach ($classification_list as $scheme)
		{
			?>
			<tr>
				<td class="concordgrey" align="center">
					Categories in classification scheme <em><?php echo $scheme['handle'];?></em> 
					(&ldquo;<?php echo escape_html($scheme['description']); ?>&rdquo;)
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" align="center">
					<form action="metadata-admin.php" method="get">
						<table>
							<tr>
								<td class="basicbox" align="center"><strong>Scheme = Category</strong></td>
								<td class="basicbox" align="center"><strong>Category description</strong></td>
							</tr>
							<?php
							
							$category_list = metadata_category_listdescs($scheme['handle']);
				
							foreach ($category_list as $handle => $description)
							{
								echo '<tr><td class="basicbox">' . "{$scheme['handle']} = $handle" . '</td>';
								echo '<td class="basicbox">
									<input type="text" name="' . "desc-{$scheme['handle']}-$handle"
									. '" value="' . $description . '"/>
									</td>
								</tr>';
							}
							
							?>
							<tr>
								<td class="basicbox" align="center" colspan="2">
									<input type="submit" value="Update category descriptions" />
								</td>
							</tr>
						</table>
						<input type="hidden" name="mdAction" value="updateMetadataCategoryDescriptions" />
						<input type="hidden" name="uT" value="y" />
					</form>
				</td>
			</tr>
			<?php
		}
	echo '</table>';
}



function printquery_managexml()
{
	global $Config;
	global $Corpus;
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Manage corpus XML</th>
		</tr>
	</table>
		
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="5">Available XML elements/attributes (s-attributes)</th>
		</tr>
		<tr>
			<th class="concordtable">Handle</th>
			<th class="concordtable">Dependent of...</th>
			<th class="concordtable">Description</th>
			<th class="concordtable" colspan="2">Datatype</th>
		</tr>	

		<?php
		
		$classifications = array();
		$idlinks = array();
		
		foreach (get_xml_all_info($Corpus->name) as $x)
		{
			if (METADATA_TYPE_CLASSIFICATION == $x->datatype)
				$classifications[] = $x;
			if (METADATA_TYPE_IDLINK == $x->datatype)
				$idlinks[] = $x;
			$id = "desc-{$x->corpus}-{$x->handle}";
			$x->description = escape_html($x->description); /* because it is always going to be rendered. */
			
			$descform = '&nbsp;
				<form action="execute.php" method="get"
					onSubmit="var t = $(\'#' . $id . '\').css(\'visibility\', \'hidden\').val(); 
 							  $(\'#' . $id . '\').val(\'' . $x->corpus . '#' . $x->handle . '#' . '\' + t); 
 							  return true;"
				>
					<input type="text" name="args" id="' . $id . '" maxlength="255" value="' . $x->description . '" />
					<input type="submit" value="Update!" />
					<input type="hidden" name="function" value="update_xml_description" />
					<input type="hidden" name="locationAfter" value="index.php?thisQ=manageXml&uT=y" />
					<input type="hidden" name="uT" value="y" />
				</form>
			';
			
			$typeopts = '';
			foreach($Config->metadata_type_descriptions as $const=>$desc)
				if ($x->datatype != $const)
					if (METADATA_TYPE_NONE != $const)
						$typeopts .= '<option value="' . $const . '">' . $desc . '</option>';
			$typeform = '&nbsp;
				<form action="metadata-admin.php" method="get">
					<select name="newDatatype">
						<option value="~~NULL" selected="selected">Change datatype to...</option>
						' . $typeopts . '
					</select>
					<input type="submit" value="Change" />
					<input type="hidden" name="mdAction" value="xmlChangeDatatype" />
					<input type="hidden" name="handle" value="' . $x->handle . '" />
					<input type="hidden" name="uT" value="y" />
				</form>
			';
			
			echo "\n\t\t<tr>"
				, "\n\t\t\t<td class=\"concordgeneral\"><b>{$x->handle}</b></td>"
				, "\n\t\t\t<td class=\"concordgeneral\">", ($x->handle == $x->att_family ? '<i>None</i>' : $x->att_family), "</td>"
				, "\n\t\t\t<td class=\"concordgeneral\" align=\"center\">\n$descform\n\t\t\t</td>"
				, "\n\t\t\t<td "
				, (METADATA_TYPE_NONE == $x->datatype ? 'colspan="2" ' : '') 
				,"class=\"concordgeneral\">{$Config->metadata_type_descriptions[$x->datatype]}" 
// TODO add either (linked table exists) or (linked table does not exist) after "ID link" -- by conditionalising the line below.
//TODO,cleanup!!!!
				, (METADATA_TYPE_IDLINK == $x->datatype ? (' (linked table '. (xml_idlink_table_exists($Corpus->name, $x->handle) ? 'exists)' : 'does not exist)')) : '') 
				, '</td>'
				, (METADATA_TYPE_NONE != $x->datatype ? "\n\t\t\t<td class=\"concordgeneral\">$typeform</td>": '') 
				, "\n\t\t</tr>\n"
				;
		}
		?>
		<tr>
			<td colspan="5" class="concordgeneral">
				<p class="spacer">&nbsp;</p>
				<p align="center">Important note: if you change the datatype of a Classification, any customised category descriptions will be lost.</p>
				<p class="spacer">&nbsp;</p>
				<p align="center">Important note: similarly, if you change the datatype of an ID link, the entire linked data-table will be lost.</p>
				<p class="spacer">&nbsp;</p>
			</td>
		</tr>
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">XML Region Categories (for Classification-type attributes)</th>
		</tr>
		
		<?php
		
		if (empty($classifications))
			echo "\n\t\t\t<tr>\n\t\t\t\t"
				, '<td colspan="" class="concordgrey">No classification-type XML attributes exist.</td>'
				, "\n\t\t\t<tr>\n"
				;
					
		foreach ($classifications as $x)
		{
			echo "\n\t\t\t<tr>\n\t\t\t\t"
				, '<td colspan="2" class="concordgrey">Categories of classification attribute <b>', $x->handle
				, '</b> (<em>', $x->description, '</em>), classifying <b>', $x->att_family
				, '</b></td>'
				;
			$cats = xml_category_listdescs($Corpus->name, $x->handle); 

			
			if (empty($cats))
				echo "\n\t\t\t<tr>\n\t\t\t\t"
					, '<td colspan="2" class="concordgeneral"><p>The categories have not been set up yet. Click below to generate them.</p>'
					, '<form action="metadata-admin.php" method="get" align="center">'
					, '<input type="submit" value="Generate categories for &rdquo;', $x->handle, '&ldquo;" />'
					, '<input type="hidden" name="mdAction" value="runXmlCategorySetup"/>'
					, '<input type="hidden" name="xmlClassification" value="' , $x->handle, '"/>'
					, '<input type="hidden" name="uT" value="y"/>'
					, '</form>'
					, '</td>'
					;
				/* the above button will be needed for old corpora (pre 3.2). 
				 * In later versions, it should always be run automatically where needed. */
			else
			{
				?>
				<tr>
					<td class="concordgeneral" align="center">
					<form action="metadata-admin.php" method="get">
							<table>
								<tr>
									<td class="basicbox" align="center"><strong>Category handle</strong></td>
									<td class="basicbox" align="center"><strong>Category description</strong></td>
								</tr>
								<?php
								foreach($cats as $handle=>$desc)
								{
									?>
									<tr>
										<td class="basicbox">
											<?php echo $x->handle, ' = ', $handle; ?>
										</td>
										<td class="basicbox">
											<input type="text" name="desc-<?php echo $x->handle, '-', $handle; ?>" value="<?php echo $desc; ?>"/>
										</td>
									</tr>
									<?php
								}
								?>

								<tr>
									<td class="basicbox" align="center" colspan="2">
										<input type="submit" value="Update category descriptions" />
									</td>
								</tr>
							</table>
							<input type="hidden" name="mdAction" value="updateXmlCategoryDescriptions" />
							<input type="hidden" name="uT" value="y" />
						</form>
					</td>
				</tr>
				<?php
			}
		}
		
		?>

	</table>
	
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">ID link metadata tables (for ID link-type attributes)</th>
		</tr>
		
		<?php
		
		if (empty($idlinks))
			echo "\n\t\t\t<tr>\n\t\t\t\t"
				, '<td colspan="" class="concordgrey">No IDlink-type XML attributes exist.</td>'
				, "\n\t\t\t<tr>\n"
				;
					
		foreach ($idlinks as $x)
		{
			echo "\n\t\t\t<tr>\n\t\t\t\t"
				, '<td colspan="2" class="concordgrey">IDlink attribute <b>', $x->handle
				, '</b> (<em>', $x->description, '</em>), providing ID codes for XML element <b>', $x->att_family
				, '</b></td>'
				;
			// TODO - one set of rows per CLASSIFICATION SCHEME, listing its categories, from xml_metadata_values; option to set descriptions.

			

	// TODO - table to create an IDLINK table. 
	
	// TODO - info on the available IDLINK tables. Option to delete. (w/ are you sure)
	
	// TODO generic function(in "modal.js") for an "are you sure" overlay question which submits or does nto submit.
	
	
	//TODO
	//TODO
	//TODO
	//TODO
	//TODO
	//TODO This is another major TODO locus.
	//TODO
	//TODO
	//TODO
	//TODO
	//TODO
	//TODO
	//TODO
	
	///TODO UP TO HERE
			if (! xml_idlink_table_exists($Corpus->name, $x->handle))
			{
				echo "\n\t\t\t<tr>\n\t\t\t\t"
					, '<td colspan="2" class="concordgeneral"><p>The IDlink metadata table does not yet exist.</p>'
					, "</td>\n\t\t\t<tr>\n"
					;
				?>
				<form action="metadata-admin.php" method="get">
				
					<tr>
						<th class="concordtable" colspan="5">Choose the file containing the metadata</th>
					</tr>
		
					<tr>
						<th class="concordtable">Use?</th>
						<th colspan="2" class="concordtable">Filename</th>
						<th class="concordtable">Size (K)</th>
						<th class="concordtable">Date modified</th>
					</tr>
		
					<?php
	//TODO col headers into the print uploaded file sdelector??
					echo print_uploaded_file_selector();
					?>
		
			
					<tr>
						<th class="concordtable" colspan="5">Describe the contents of the file you have selected</th>
					</tr>
					
					<tr>
						<td class="concordgeneral" colspan="5">
							<table align="center" width="100%">
								<tr>
									<td class="basicbox" width="50%">
										Choose template for idlink metadata structure
										<br/>
										<i>(or select "Custom metadata structure" and specify annotations in the boxes below)</i>
									</td>
									<td class="basicbox" width="50%">
										<select name="useMetadataTemplate">
											<option value="~~customMetadata" selected="selected">Custom metadata structure</option>
											
											<?php
											
											foreach(list_metadata_templates() as $t)
												echo "\n\t\t\t\t\t\t\t\t\t"
													, '<option value="'
													, $t->id
													, '">'
													, escape_html($t->description)
													, ' (containing ', count($t->fields), ' defined fields)' 
													, "</option>\n"
													;
											
											?>
											
										</select>
									</td>
								</tr>
							</table>
						</td>
					</tr>
						
					<tr>
						<td class="concordgrey" colspan="5">
							Note: you should not specify the identifier (matches the contents of <b><?php echo $x->handle; ?></b>), 
							which must be the first field. 
							This is inserted automatically.
							
							<br/>&nbsp;<br/>
							
							<em>Classification</em> fields contain one of a set number of handles indicating text categories. 
							<em>Free-text metadata</em> fields can contain anything, and don't indicate categories of texts.
						</td>
					</tr>
	
				<?php
				
				//TODO might some of the above be putable-into-funcs?
				
				echo print_embiggenable_metadata_form();
		
				?>
					<tr>
						<td align="center" class="concordgeneral" colspan="5">
							<input type="submit" value="Install ID-link data table using the settings above" />
						</td>
					</tr>
					
				
					<input type="hidden" name="mdAction" value="createXmlIdlinkTable" /> 
					<input type="hidden" name="fieldCount" id="fieldCount" value="5" />
					<input type="hidden" name="corpus" value="<?php echo $Corpus->name; ?>" />
					<input type="hidden" name="xmlAtt" value="<?php echo $x->handle; ?>" />
					<input type="hidden" name="uT" value="y" />
				</form>
				
				<?php
			}
			else
			{
// temp code for when meta table exists.
// proper UI needed TODO
echo '<tr>					<td class="concordgeneral" align="center">hello<br/>';
echo dump_mysql_result(do_mysql_query("select * from idlink_fields where corpus = '{$Corpus->name}' and att_handle = '{$x->handle}'"));
echo dump_mysql_result(do_mysql_query("select * from idlink_values where corpus = '{$Corpus->name}' and att_handle = '{$x->handle}'"));
echo dump_mysql_result(do_mysql_query("select * from idlink_xml_{$Corpus->name}_{$x->handle}"));
echo '</td></tr>';
				$junk = '?>
				<tr>
					<td class="concordgeneral" align="center">
					<form action="metadata-admin.php" method="get">
							<table>
								<tr>
									<td class="basicbox" align="center"><strong>Category handle</strong></td>
									<td class="basicbox" align="center"><strong>Category description</strong></td>
								</tr>
								<?php
								foreach($cats as $handle=>$desc)
								{
									?>
									<tr>
										<td class="basicbox">
											<?php echo $x->handle, \' = \', $handle; ?>
										</td>
										<td class="basicbox">
											<input type="text" name="desc-<?php echo $x->handle, '-', $handle; ?>" value="<?php echo $desc; ?>"/>
										</td>
									</tr>
									<?php
								}
								?>

								<tr>
									<td class="basicbox" align="center" colspan="2">
										<input type="submit" value="Update category descriptions" />
									</td>
								</tr>
							</table>
							<input type="hidden" name="mdAction" value="updateXmlCategoryDescriptions" />
							<input type="hidden" name="uT" value="y" />
						</form>
					</td>
				</tr>
				<?php';
			}
		}
		
		?>

	</table>
	
	<?php
	
}



function printquery_manageannotation()
{
	global $Corpus;
	
	
	// TODO move actions into corpus-admin.php? then this could be purely display.
	if (isset($_GET['updateMe']))
	{
		if ( $_GET['updateMe'] == 'CEQL')
		{
			/* we have incoming values from the CEQL table to update */
			$changes = array();
			if (isset($_GET['setPrimaryAnnotation']))
				$changes['primary_annotation'] = ($_GET['setPrimaryAnnotation'] == '~~UNSET' ? NULL : $_GET['setPrimaryAnnotation']);
			if (isset($_GET['setSecondaryAnnotation']))
				$changes['secondary_annotation'] = ($_GET['setSecondaryAnnotation'] == '~~UNSET' ? NULL : $_GET['setSecondaryAnnotation']);
			if (isset($_GET['setTertiaryAnnotation']))
				$changes['tertiary_annotation'] = ($_GET['setTertiaryAnnotation'] == '~~UNSET' ? NULL : $_GET['setTertiaryAnnotation']);
			if (isset($_GET['setMaptable']))
				$changes['tertiary_annotation_tablehandle'] = ($_GET['setMaptable'] == '~~UNSET' ? NULL : $_GET['setMaptable']);
			if (isset($_GET['setComboAnnotation']))
				$changes['combo_annotation'] = ($_GET['setComboAnnotation'] == '~~UNSET' ? NULL : $_GET['setComboAnnotation']);
			
			update_corpus_annotation_info($changes, $Corpus->name);
		}
		else if ($_GET['updateMe'] == 'annotation_metadata')
		{
			/* we have incoming annotation metadata to update */
			if (! isset($_GET['annotationHandle']))
				exiterror("Couldn't update $handle_to_change - not a real annotation!");
			
			update_all_annotation_info( $Corpus->name, 
										$_GET['annotationHandle'], 
										isset($_GET['annotationDescription']) ? $_GET['annotationDescription'] : NULL, 
										isset($_GET['annotationTagset'])      ? $_GET['annotationTagset']      : NULL, 
										isset($_GET['annotationURL'])         ? $_GET['annotationURL']         : NULL
										);
		}
	}
// TODO, having done the above, we need to tell the global $Corpus object to update tiself!
// or, better, mnove these into a corpus-admin script, which then LOCATIONS to this page.


	$annotation_list = get_corpus_annotations();

	/* set variables */
	
	$select_for_primary = '<select name="setPrimaryAnnotation">';
	$selector = ($Corpus->primary_annotation === NULL ? 'selected="selected"' : '');
	$select_for_primary .= '<option value="~~UNSET"' . $selector . '>Not in use in this corpus</option>';
	foreach ($annotation_list as $handle=>$desc)
	{
		$selector = ($Corpus->primary_annotation === $handle ? 'selected="selected"' : '');
		$select_for_primary .= "<option value=\"$handle\" $selector>$desc</option>";
	}
	$select_for_primary .= "</select>\n";

	$select_for_secondary = '<select name="setSecondaryAnnotation">';
	$selector = ($Corpus->secondary_annotation === NULL ? 'selected="selected"' : '');
	$select_for_secondary .= '<option value="~~UNSET"' . $selector . '>Not in use in this corpus</option>';
	foreach ($annotation_list as $handle=>$desc)
	{
		$selector = ($Corpus->secondary_annotation === $handle ? 'selected="selected"' : '');
		$select_for_secondary .= "<option value=\"$handle\" $selector>$desc</option>";
	}
	$select_for_secondary .= "</select>\n";

	$select_for_tertiary = '<select name="setTertiaryAnnotation">';
	$selector = ($Corpus->tertiary_annotation === NULL ? 'selected="selected"' : '');
	$select_for_tertiary .= '<option value="~~UNSET"' . $selector . '>Not in use in this corpus</option>';
	foreach ($annotation_list as $handle=>$desc)
	{
		$selector = ($Corpus->tertiary_annotation === $handle ? 'selected="selected"' : '');
		$select_for_tertiary .= "<option value=\"$handle\" $selector>$desc</option>";
	}
	$select_for_tertiary .= "</select>\n";

	$select_for_combo = '<select name="setComboAnnotation">';
	$selector = ($Corpus->combo_annotation === NULL ? 'selected="selected"' : '');
	$select_for_combo .= '<option value="~~UNSET"' . $selector . '>Not in use in this corpus</option>';
	foreach ($annotation_list as $handle=>$desc)
	{
		$selector = ($Corpus->combo_annotation === $handle ? 'selected="selected"' : '');
		$select_for_combo .= "<option value=\"$handle\" $selector>$desc</option>";
	}
	$select_for_combo .= "</select>\n";


	/* and the mapping table */
	
	$mapping_table_list = get_list_of_tertiary_mapping_tables();
	$select_for_maptable = '<select name="setMaptable">';
	$selector = ($Corpus->tertiary_annotation_tablehandle === NULL ? 'selected="selected"' : '');
	$select_for_maptable .= '<option value="~~UNSET"' . $selector . '>Not in use in this corpus</option>';
	foreach ($mapping_table_list as $handle=>$desc)
	{
		$selector = ($Corpus->tertiary_annotation_tablehandle === $handle ? 'selected="selected"' : '');
		$select_for_maptable .= "<option value=\"$handle\" $selector>$desc</option>";
	}
	$select_for_maptable .= "</select>\n";


	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Manage annotation
			</th>
		</tr>
	</table>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				Annotation setup for CEQL queries for <?php echo $Corpus->name; ?>
			</th>
		</tr>
		<form action="index.php" method="get">
			<tr>
				<td class="concordgrey">
					<b>Primary annotation</b>
					- used for tags given after the underscore character (typically POS)
				</td>
				<td class="concordgeneral">
					<?php echo $select_for_primary;?>
				</td>
			<tr>
				<td class="concordgrey">
					<b>Secondary annotation</b>
					- used for searches like <em>{...}</em> (typically lemma)	
				</td>
				<td class="concordgeneral">
					<?php echo $select_for_secondary;?>
				</td>
			<tr>
				<td class="concordgrey">
					<b>Tertiary annotation</b>
					- used for searches like <em>_{...}</em> (typically simplified POS tag)	
				</td>
				<td class="concordgeneral">
					<?php echo $select_for_tertiary;?>
				</td>
			<tr>
				<td class="concordgrey">
					<b>Tertiary annotation mapping table</b>
					- handle for the list of aliases used in the tertiary annotation
				</td>
				<td class="concordgeneral">
					<?php echo $select_for_maptable;?>
				</td>
			<tr>
				<td class="concordgrey">
					<b>Combination annotation</b>
					- typically lemma_simpletag, used for searches in the form <em>{.../...}</em>
				</td>
				<td class="concordgeneral">
					<?php echo $select_for_combo;?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Update annotation settings"/>
					<br/>&nbsp;
				</td>
			<input type="hidden" name="updateMe" value="CEQL"/>
			<input type="hidden" name="thisQ" value="manageAnnotation"/>
			<input type="hidden" name="uT" value="y"/>
		</form>
	</table>

	<table class="concordtable" width="100%">
		<tr>
			<th colspan="5" class="concordtable">
				Annotation metadata
			</th>
		</tr>
		<tr>
			<th class="concordtable">Handle</th>
			<th class="concordtable">Description</th>
			<th class="concordtable">Tagset name</th>
			<th class="concordtable">External URL</th>
			<th class="concordtable">Update?</th>
		</tr>
		
		<?php
		
		$result = do_mysql_query("select * from annotation_metadata where corpus='{$Corpus->name}'");
		if (mysql_num_rows($result) < 1)
			echo '<tr><td colspan="5" class="concordgrey" align="center">&nbsp;<br/>This corpus has no annotation.<br/>&nbsp;</td></tr>';
		
		while ( false !== ($tag = mysql_fetch_object($result)) )
		{
			echo '<form action="index.php" method= "get"><tr>';
			
			echo '<td class="concordgrey"><strong>' . $tag->handle . '</strong></td>'; 
			echo '<td class="concordgeneral" align="center">
				<input name="annotationDescription" maxlength="230" type="text" value="'
				. $tag->description	. '"/></td>
				'; 
			echo '<td class="concordgeneral" align="center">
				<input name="annotationTagset" maxlength="230" type="text" value="'
				. $tag->tagset	. '"/></td>
				'; 
			echo '<td class="concordgeneral" align="center">
				<input name="annotationURL" maxlength="230" type="text" value="'
				. $tag->external_url	. '"/></td>
				';
			?>
			
					<td class="concordgeneral" align="center">
						<input type="submit" value="Go!" />			
					</td>
				</tr>
				<input type="hidden" name="annotationHandle" value="<?php echo $tag->handle; ?>" />
				<input type="hidden" name="updateMe" value="annotation_metadata" />
				<input type="hidden" name="thisQ" value="manageAnnotation" />
				<input type="hidden" name="uT" value="y" />
			</form>
			
			<?php
		}
	
		?>
		<tr>
			<td colspan="5" class="concordgeneral">&nbsp;<br/>&nbsp;</td>
		</tr> 
	</table>
	
	
	<?php

}




function printquery_visualisation()
{
	global $Corpus;
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th  colspan="2" class="concordtable">
				Query result and context-view visualisation
			</th>
		</tr>
	</table>
	<?php

	/* FIRST SECTION --- GLOSS VISUALIASATION */
	/* process incoming */
	
	$annotations = get_corpus_annotations();
	
	if (isset($_GET['settingsUpdateGlossAnnotation']))
	{
		/* we overwrite the values in the global object too so that after
		 * we update the database, the global object still matches it */
		switch($_GET['settingsUpdateGlossShowWhere'])
		{
		case 'both':
			$Corpus->visualise_gloss_in_context = true;
			$Corpus->visualise_gloss_in_concordance = true;
			break;
		case 'concord':
			$Corpus->visualise_gloss_in_context = false;
			$Corpus->visualise_gloss_in_concordance = true;
			break;
		case 'context':
			$Corpus->visualise_gloss_in_context = true;
			$Corpus->visualise_gloss_in_concordance = false;
			break;
		default:
			$Corpus->visualise_gloss_in_context = false;
			$Corpus->visualise_gloss_in_concordance = false;
			break;			
		}
		if ($_GET['settingsUpdateGlossAnnotation'] == '~~none~~')
			$_GET['settingsUpdateGlossAnnotation'] = NULL;
		if (array_key_exists($_GET['settingsUpdateGlossAnnotation'], $annotations) 
				|| $_GET['settingsUpdateGlossAnnotation'] == NULL)
		{
			$Corpus->visualise_gloss_annotation = $_GET['settingsUpdateGlossAnnotation'];
			update_corpus_visualisation_gloss($Corpus->visualise_gloss_in_concordance, $Corpus->visualise_gloss_in_context, 
												$Corpus->visualise_gloss_annotation);
		}
		else
			exiterror_parameter("A non-existent annotation was specified to be used for glossing.");
	}
	
	/* set up option strings for first form  */
	
	$opts = array(	'neither'=>'Don\'t show anywhere', 
					'concord'=>'Concordance only', 
					'context'=>'Context only', 
					'both'=>'Both concordance and context'
					);
	if ($Corpus->visualise_gloss_in_concordance)
		if ($Corpus->visualise_gloss_in_context)
			$show_gloss_curr_opt = 'both';
		else
			$show_gloss_curr_opt = 'concord';
	else
		if ($Corpus->visualise_gloss_in_context)
			$show_gloss_curr_opt = 'context';
		else
			$show_gloss_curr_opt = 'neither';
	
	$show_gloss_options = '';
	foreach ($opts as $o => $d)
		$show_gloss_options .= "\t\t\t\t\t\t<option value=\"$o\""
							. ($o == $show_gloss_curr_opt ? ' selected="selected"' : '')
							. ">$d</option>\n";

	$gloss_annotaton_options = "\t\t\t\t\t\t<option value=\"~~none~~\""
								. (isset($Corpus->visualise_gloss_annotation) ? '' : ' selected="selected"')
								. ">No annotation selected</option>";		
	foreach($annotations as $h => $d)
		$gloss_annotaton_options .= "\t\t\t\t\t\t<option value=\"$h\""
							. ($h == $Corpus->visualise_gloss_annotation ? ' selected="selected"' : '')
							. ">$d</option>\n";
		
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th  colspan="2" class="concordtable">
				(1) Interlinear gloss
			</th>
		</tr>
		<tr>
			<td  colspan="2" class="concordgrey">
				&nbsp;<br/>
				You can select an annotation to be treated as the "gloss" and displayed in
				query results and/or extended context display.
				<br/>&nbsp;
			</td>
		</tr>
		<form id="formSetGlossOptions" action="index.php" method="get">
			<tr>
				<td class="concordgrey">Use annotation:</td>
				<td class="concordgeneral">
					<select name="settingsUpdateGlossAnnotation">
						<?php echo $gloss_annotaton_options; ?>
					</select>
				</td>
			</tr>
			<tr>
				<!-- at some point, it might be nice to allow users to set this for themselves. -->
				<td class="concordgrey">Show gloss in:</td>
				<td class="concordgeneral">
					<select name="settingsUpdateGlossShowWhere">
						<?php echo $show_gloss_options; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center" class="concordgeneral">
					<input type="submit" value="Update settings" />
					<input type="hidden" name="thisQ" value="manageVisualisation" />
					<input type="hidden" name="uT" value="y" />
				</td>
			</tr>
		</form>
	</table>
	
	<?php
	/* SECOND SECTION --- TRANSLATION VISUALIASATION */
	/* process incoming */
	
	global $Corpus;
	
	$s_attributes = list_xml_all($Corpus->name);
	/* the descriptions will be printed more than once, so escape now. */
	foreach ($s_attributes as &$v)
		$v = escape_html($v);
	
	if (isset($_GET['settingsUpdateTranslateXML']))
	{	
		/* see note above re overwrite of global object */
		switch($_GET['settingsUpdateTranslateShowWhere'])
		{
		case 'both':
			$Corpus->visualise_translate_in_context = true;
			$Corpus->visualise_translate_in_concordance = true;
			break;
		case 'concord':
			$Corpus->visualise_translate_in_context = false;
			$Corpus->visualise_translate_in_concordance = true;
			break;
		case 'context':
			$Corpus->visualise_translate_in_context = true;
			$Corpus->visualise_translate_in_concordance = false;
			break;
		default:
			$Corpus->visualise_translate_in_context = false;
			$Corpus->visualise_translate_in_concordance = false;
			break;			
		}
		if ($_GET['settingsUpdateTranslateXML'] == '~~none~~')
			$_GET['settingsUpdateTranslateXML'] = NULL;
		if (array_key_exists($_GET['settingsUpdateTranslateXML'], $s_attributes) 
			|| empty($_GET['settingsUpdateTranslateXML']))
		{
			$Corpus->visualise_translate_s_att = $_GET['settingsUpdateTranslateXML'];
			update_corpus_visualisation_translate($Corpus->visualise_translate_in_concordance, $Corpus->visualise_translate_in_context, 
												  $Corpus->visualise_translate_s_att);
		}
		else
			exiterror_parameter("A non-existent s-attribute was specified to be used for translation.");
	}
	
	/* set up option string for second form */

	/* note that $opts array already exists */
	if ($Corpus->visualise_translate_in_concordance)
		if ($Corpus->visualise_translate_in_context)
			$show_translate_curr_opt = 'both';
		else
			$show_translate_curr_opt = 'concord';
	else
		if ($Corpus->visualise_translate_in_context)
			$show_translate_curr_opt = 'context';
		else
			$show_translate_curr_opt = 'neither';
	
	$show_translate_options = '';
	foreach ($opts as $o => $d)
		$show_translate_options .= "\t\t\t\t\t\t<option value=\"$o\""
							. ($o == $show_translate_curr_opt ? ' selected="selected"' : '')
							. ">$d</option>\n";
	$translate_XML_options = "\t\t\t\t\t\t<option value=\"~~none~~\""
								. (isset($Corpus->visualise_translate_s_att) ? '' : ' selected="selected"')
								. ">No XML element-attribute selected</option>";		
	foreach($s_attributes as $s=>$s_desc)
		$translate_XML_options .= "\t\t\t\t\t\t<option value=\"$s\""
							. ($s == $Corpus->visualise_translate_s_att ? ' selected="selected"' : '')
							. ">$s_desc ($s)</option>\n";
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th  colspan="2" class="concordtable">
				(2) Free translation
			</th>
		</tr>
		<tr>
			<td  colspan="2" class="concordgrey">
				&nbsp;<br/>
				You can select an XML element/attribute to be used to provide whole-sentence or
				whole-utterance translation.
				<br/>&nbsp;<br/>
				Note that if this setting is enabled, it <b>overrides</b> the context setting.
				The context is automatically set to "one of whatever XML attribute you are using".
				<br/>&nbsp;
			</td>
		</tr>
		<form id="formSetTranslateOptions" action="index.php" method="get">
			<tr>
				<td class="concordgrey">Select XML element/attribute to get the translation from:</td>
				<td class="concordgeneral">
					<select name="settingsUpdateTranslateXML">
						<?php echo $translate_XML_options; ?>
					</select>
				</td>
			</tr>
			<tr>
				<!-- at some point, it might be nice to allow users to set this for themselves. -->
				<td class="concordgrey">Show free translation in:</td>
				<td class="concordgeneral">
					<select name="settingsUpdateTranslateShowWhere">
						<?php echo $show_translate_options; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center" class="concordgeneral">
					<input type="submit" value="Update settings" />
					<input type="hidden" name="thisQ" value="manageVisualisation" />
					<input type="hidden" name="uT" value="y" />
				</td>
			</tr>
		</form>
	</table>

	<?php

	/* THIRD SECTION --- POSITION LABELS */
	/* process incoming; rewrite global $Corpus object members as previously */
	
	/* and we can re-use $s_attributes from above */

	if (isset($_GET['settingsUpdatePositionLabelAttribute']))
	{
		$Corpus->visualise_position_labels = true;
		$Corpus->visualise_position_label_attribute = $_GET['settingsUpdatePositionLabelAttribute'];
		
		if ($Corpus->visualise_position_label_attribute == '~~none~~')
		{
			$Corpus->visualise_position_labels = false;
			$Corpus->visualise_position_label_attribute = NULL;
		}
		else if ( ! array_key_exists($Corpus->visualise_position_label_attribute, $s_attributes) )
		{
			exiterror_parameter("A non-existent s-attribute was specified for position labels.");
		}
		/* so we know at this point that $Corpus->visualise_position_label_attribute contains an OK s-att */ 
		update_corpus_visualisation_position_labels($Corpus->visualise_position_labels, $Corpus->visualise_position_label_attribute);
	}
	
	$position_label_options = "\t\t\t\t\t\t<option value=\"~~none~~\""
								. ($Corpus->visualise_position_labels ? '' : ' selected="selected"')
								. ">No position labels will be shown in the concordance</option>";		
	foreach($s_attributes as $s=>$s_desc)
		$position_label_options .= "\t\t\t\t\t\t<option value=\"$s\""
							. ($s == $Corpus->visualise_position_label_attribute ? ' selected="selected"' : '')
							. ">$s_desc ($s) will used for position labels</option>\n";

	?>
	<table class="concordtable" width="100%">
		<tr>
			<th  colspan="2" class="concordtable">
				(4) Position labels
			</th>
		</tr>
		<tr>
			<td  colspan="2" class="concordgrey">
				&nbsp;<br/>
				You can select an XML element/attribute to be used to indicate the position <em>within</em> its text
				where each concordance result appears. A typical choice for this would be sentence or utterance number.
				<br/>&nbsp;<br/>
				<strong>Warning</strong>: If you select an element/attribute pair that does not cover the entire corpus, no
				position label will be shown next to a result at a corpus position with no value for the selected attribute!
				<br/>&nbsp;
			</td>
		</tr>
		<form id="formSetPositionLabelAttribute" action="index.php" method="get">
			<tr>
				<td class="concordgrey">Select XML element/attribute to use for position labels:</td>
				<td class="concordgeneral">
					<select name="settingsUpdatePositionLabelAttribute">
						<?php echo $position_label_options; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center" class="concordgeneral">
					<input type="submit" value="Update setting" />
					<input type="hidden" name="thisQ" value="manageVisualisation" />
					<input type="hidden" name="uT" value="y" />
				</td>
			</tr>
		</form>
	</table>

	
	<?php
//	TODO from here on down.....
//	
//	
//	note, way down the road, it would be nice if auto-transliteration
//	could affect database-derived tables as well
//	- and, of course, be configurable on a per-user basis.
	
	// for now, don't display
	return;
	
	/* FOURTH SECTION --- TRANSLITERATION VISUALIASATION */
	/* process incoming */

	
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th  colspan="2" class="concordtable">
				(3) Transliteration    [NOT WORKING YET!!]
			</th>
		</tr>
		<tr>
			<td  colspan="2" class="concordgrey">
				&nbsp;<br/>
				You can have the "word" attribute automatically transliterated into the Latin
				alphabet, as long as you have added an appropriate transliterator plugin to CQPweb
				(or are happy to use the default).
				<br/>&nbsp;
			</td>
		</tr>
		<form action="" method="get">
			<tr>
				<td class="concordgrey">Select transliterator:</td>
				<td class="concordgeneral">
					
				</td>
			</tr>
			<tr>
				<!-- at some point, it might be nice to allow users to set this for themselves. -->
				<td class="concordgrey">Autotransliterate in:</td>
				<td class="concordgeneral">
					<select>
						<option>Concordance only</option>
						<option>Context only</option>
						<option>Both concordance and context</option>
					</select>
				</td>
			</tr>
			<tr>
				<!-- at some point, it might be nice to allow users to set this for themselves. -->
				<td class="concordgrey">Show:</td>
				<td class="concordgeneral">
					<select>
						<option>Original script only</option>
						<option>Autotransliterated text only</option>
						<option>Original and autotransliterated text</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center" class="concordgeneral">
					<input type="submit" value="Update settings" />
					<input type="hidden" name="uT" value="y" />
				</td>
			</tr>
		</form>
	</table>

	<?php

}





function printquery_xmlvisualisation()
{
	global $Corpus;
	
	/* PROCESS INCOMING */
	
	/* process incoming NEW or UPDATE */
	if (isset($_GET['xmlTheElement']))
	{
		/* change the update form's "select" to the two-value pair of variables used in the create form... */
		if (isset($_GET['xmlUseInSelector']))
		{
			$_GET['xmlUseInConc']    = ( $_GET['xmlUseInSelector'] == 'in_conc'    || $_GET['xmlUseInSelector'] == 'both' );
			$_GET['xmlUseInContext'] = ( $_GET['xmlUseInSelector'] == 'in_context' || $_GET['xmlUseInSelector'] == 'both' );
		}
		xml_visualisation_create(	$Corpus->name, 
									$_GET['xmlTheElement'], 
									$_GET['xmlVisCode'],
									$_GET['xmlCondAttribute'],
									$_GET['xmlCondRegex'],
									(bool) $_GET['xmlIsStartTag'], 
									(bool) $_GET['xmlUseInConc'], 
									(bool) $_GET['xmlUseInContext'] );
	}
	/* process incoming DELETE */
	if (isset($_GET['xmlDeleteVisualisation']))
		xml_visualisation_delete(	$Corpus->name, 
									$_GET['xmlDeleteVisualisation'],
									$_GET['xmlCondAttribute'],
									$_GET['xmlCondRegex'] );
	
	/* 
	 * OK, now the processing is done, let's render the form 
	 */
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th  colspan="6" class="concordtable">
				(5) XML visualisation
			</th>
		</tr>
		<tr>
			<td  colspan="6" class="concordgrey">
				&nbsp;<br/>
				XML visualisations are commands stored in the database which describe how an indexed
				XML element (or, in CWB terms, an &ldquo;s-attribute&rdquo;) is to appear in the concordance.
				<br/>&nbsp;<br/>
				By default, all XML elements are invisible. You must create and enable a visualisation for
				each XML element in each corpus that you wish to display to the user.  
				<br/>&nbsp;<br/>
				An XML visualisation can be unconditional, in which case it will always apply. Or, it can have
				a condition attached to it - a regular expresion that will be matched against an attribute on
				the XML tag, with the visualisation only displayed if the regular expression matches. This allows
				you to have different visualisations for &lt;element type="A"&gt; and &lt;element type="B"&gt;.
				<br/>&nbsp;<br/>
				You can define an unconditional visualisation for the same element as one or more conditional
				visualisations, in which case, the unconditional visualisation applies in any cases where none of the 
				conditional visualisations apply. In addition, note that conditions are only possible on start tags, 
				not end tags.
				<br/>&nbsp;<br/>
				You can use the forms below to manage your visualisations.
				<br/>&nbsp;
			</td>
		</tr>
		
		
		<!-- display current visualisations for this corpus -->
		<!-- note we use the SAME FORM for updates as for creates -->
		<tr>
			<th colspan="6" class="concordtable">
				Existing XML visualisation commands
			</th>
		</tr>
		<tr>
			<th class="concordtable">Applies to ... </th>
			<th class="concordtable">Visualisation code</th>
			<th class="concordtable">Show</th>
			<th class="concordtable">Used where?</th>
			<th class="concordtable" colspan="2">Actions</th>
		</tr>
		
		<?php
		
		/* show each existing visualisation for this corpus */
		
		$where_values = array(
			'in_conc' => "In concordance displays only",
			'in_context' => "In extended context displays only",
			'both' => "In concordance AND context displays",
			'neither' => "Nowhere (visualisation disabled)"
			);

		$result = do_mysql_query("select * from xml_visualisations where corpus = '{$Corpus->name}'"); 
		
		if (mysql_num_rows($result) == 0)
			echo '<tr><td colspan="6" class="concordgrey" align="center">'
				, '&nbsp;<br/>There are currently no XML visualisations in the database.<br/>&nbsp;'
				, "</td></tr>\n"
				;
		
		while (false !== ($v = mysql_fetch_object($result)))
		{
			echo '
				<form action="index.php" method="get">
				<tr>
				';
			
			list($tag, $startend) = explode('~', $v->element);
			$startend = ($startend=='end' ? '/' : ''); 
			$cond_regex_print = escape_html($v->cond_regex);
			
			echo '
				<td class="concordgeneral">&lt;' , $startend , $tag , '&gt;'
				, (empty($v->cond_attribute) ? '' 
					: "<br/>where <em>{$v->cond_attribute}</em> matches <em>$cond_regex_print</em>\n")  
				, '</td>
				';
			
			echo '
				<td class="concordgeneral" align="center"><textarea cols="40" rows="2" name="xmlVisCode">' 
				, $v->bb_code 
				, '</textarea></td>
				';
			
			echo '<td class="concordgeneral" align="center">'
				, '<span onmouseover="return escape(\'' 
				  /* note we need double-encoding to get the actual code to show up in a tooltip ! */
				, htmlspecialchars(htmlspecialchars($v->html_code, ENT_QUOTES, 'UTF-8', true) , ENT_QUOTES, 'UTF-8', true) 
				, '\')">[HTML]</span>'
				, '</td>
				';
			
			switch (true)
			{
				case ( $v->in_context &&  $v->in_concordance):		$checked = 'both';			break; 
				case (!$v->in_context && !$v->in_concordance):		$checked = 'neither';		break; 
				case (!$v->in_context &&  $v->in_concordance):		$checked = 'in_conc';		break; 
				case ( $v->in_context && !$v->in_concordance):		$checked = 'in_context';	break; 
			}
			$options = "\n";
			foreach ($where_values as $val=>$label)
			{
				$blob = ($checked == $val ? ' selected="selected"' : '');
				$options .= "\n\t\t\t\t\t<option value=\"$val\"$blob>$label</option>\n";
			}
			
			echo '
				<td class="concordgeneral" align="center">
				<select name="xmlUseInSelector">'
				, $options
				, '
				</select>
				</td>
				';

			echo '
				<td class="concordgeneral" align="center">'
				, '<input type="submit" value="Update" />' 
				, '</td>';
						
			echo '
				<td class="concordgeneral" align="center">'
				, '<a class="menuItem" href="index.php?thisQ=manageVisualisation&xmlDeleteVisualisation='
				, $tag , ($startend=='/' ? '~end' : '~start')
				, '&xmlCondAttribute=' , $v->cond_attribute
				, '&xmlCondRegex=' , urlencode($v->cond_regex)
				, '&uT=y">[Delete]</a>'
				, '</td>
				';
						
			echo '
				</tr>
				<input type="hidden" name="xmlTheElement" value="' , $tag , '" />
				<input type="hidden" name="xmlIsStartTag" value="' , ($startend=='/' ? '0' : '1') , '" />
				<input type="hidden" name="xmlCondAttribute" value="' , $v->cond_attribute , '" />
				<input type="hidden" name="xmlCondRegex" value="' , $v->cond_regex , '" />
				<input type="hidden" name="thisQ" value="manageVisualisation" />
				<input type="hidden" name="uT" value="y" />
				</form>
				';

		}
		?>

	</table>
	
	<table class="concordtable" width="100%">		
		<!-- form to create new visualisation -->
		<form action="index.php" method="get">
			<tr>
				<th colspan="2" class="concordtable">
					Create new XML visualisation command
				</th>
			</tr>
			
			<tr>
				<td class="concordgrey">
					Select one of the available XML elements:
				</td>
				<td class="concordgeneral">
					<select name="xmlTheElement">
					
						<?php
						foreach (list_xml_all() as $x=>$x_desc)
							echo "<option>$x</option>\n\t\t\t\t\t\t";
						?>
						
					</select>					
				</td>
			</tr>
			<tr>
				<td class="concordgrey">Create visualisation for start or end tag?</td>
				<td class="concordgeneral">
					<input type="radio" checked="checked" name="xmlIsStartTag" value="1" /> Start tag
					<input type="radio" name="xmlIsStartTag" value="0" /> End tag
				</td>
			</tr>
			<tr>
				<td align="center" colspan="2" class="concordgrey">
					<em>Note: if you choose an element start/end for which a visualisation 
					already exists, the existing visualisation will be overwritten UNLESS 
					there are different conditions.</em>
				</td>
			</tr>
			<tr>
				<td class="concordgrey">
					Enter the code for the visualisation you want to create.
					<br/>&nbsp;<br/>
					See <a target="_blank" href="../doc/CQPweb-visualisation-manual.html">this file</a> for more
					information.
				</td>
				<td class="concordgeneral">
					<textarea cols="40" rows="12" name="xmlVisCode"></textarea>
				</td>
			</tr>		
			<tr>
				<td class="concordgrey">Use this visualisation in concordances?</td>
				<td class="concordgeneral">
					<input type="radio" checked="checked" name="xmlUseInConc" value="1" /> Yes
					<input type="radio" name="xmlUseInConc" value="0" /> No
				</td>
			</tr>
			<tr>
				<td class="concordgrey">Use this visualisation in extended context display?</td>
				<td class="concordgeneral">
					<input type="radio" checked="checked" name="xmlUseInContext" value="1" /> Yes
					<input type="radio" name="xmlUseInContext" value="0" /> No
				</td>
			</tr>
			<tr>
				<td class="concordgrey">
					Specify a condition?
					<br/>&nbsp;<br/>
					<em>(Leave blank for an unconditional visualisation.)</em></td>
				<td class="concordgeneral">
					The attribute 
					<input type="text" name="xmlCondAttribute" />
					must have a value which contains
					<br/> 
					a match for the regular expression
					<input type="text" name="xmlCondRegex" />
					.
				</td>
			</tr>
			<tr>
				<td class="concordgrey">Click here to store this visualisation</td>
				<td class="concordgeneral">
					<input type="submit" value="Create XML visualisation" />
				</td>
			</tr>
			
			<input type="hidden" name="thisQ" value="manageVisualisation" />
			<input type="hidden" name="uT" value="y" />
			
		</form>
	</table>
	
	
	<?php	
}





function printquery_showquerycache()
{
	global $Corpus;
	global $Config;
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				Showing query cache for corpus <?php echo $Corpus->name;?>
			</th>
		</tr>
		<tr>
			<th colspan="2" class="concordtable">
				<i>Admin controls over query cache and query-history log</i>
			</th>
		</tr>
		<tr>
	<?php
	
	$return_to_url = urlencode('index.php?' . url_printget());

	echo '<th width="50%" class="concordtable">'
		, '<a onmouseover="return escape(\'This function affects <b>all</b> corpora in the CQPweb database\')"'
		, 'href="execute.php?function=delete_cache_overflow&locationAfter='
		, $return_to_url
		, '&uT=y">Delete cache overflow</a></th>'
 		;

	echo '<th width="50%" class="concordtable">Discard old query history<br/>(function removed)</th>';

	echo '</tr> <tr>';
			
	echo '<th width="50%" class="concordtable">'
		, '<a onmouseover="return escape(\'This function affects <b>all</b> corpora in the CQPweb database\')"'
		, 'href="execute.php?function=clear_cache&locationAfter='
		, $return_to_url
		, '&uT=y">Clear entire cache<br/>(but keep saved queries)</a></th>'
 		;
		
	echo '<th width="50%" class="concordtable">Clear entire cache<br/>(clear all saved queries)<br/>(function removed)</th>';
// 	echo '<th width="50%" class="concordtable">'
// 		. '<a onmouseover="return escape(\'This function affects <b>all</b> corpora in the CQPweb database\')"'
// 		. 'href="execute.php?function=clear_cache&args=0&locationAfter='
// 		. $return_to_url
// 		. '&uT=y">Clear entire cache<br/>(clear all saved queries)</a></th>';
		
	echo '</td></tr></table>';


	if (isset($_GET['beginAt']))
		$begin_at = $_GET['beginAt'];
	else
		$begin_at = 1;

	if (isset($_GET['pp']))
		$per_page = $_GET['pp'];
	else
		$per_page = $Config->default_history_per_page;

	echo print_cache_table($begin_at, $per_page, '~~ALL', true, true);
}



function printquery_showfreqtables()
{
	global $Corpus;
	global $Config;
	
	list($size) = mysql_fetch_row(do_mysql_query("select sum(ft_size) from saved_freqtables where corpus='{$Corpus->name}'"));
	if (empty($size))
		$size = 0;
	$percent = round(((float)$size / (float)$Config->freqtable_cache_size_limit) * 100.0, 2);
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				Showing frequency table cache for corpus <em><?php echo $Corpus->name;?></em>
			</th>
		</tr>
		<tr>
			<td colspan="2" class="concordgeneral">
				<p class="spacer">&nbsp;</p>
				<p>
					The currently saved frequency tables <b>for this corpus</b> have a total size of 
					<?php echo number_format((float)$size/1024.0) , " kilobytes, $percent%"; ?>
					of the maximum frequency-table cache.
				</p>
				<p>
					<a href="../adm/index.php?thisF=freqtableCacheControl">
						Click here for systemwide frequency-table control.
					</a>
				</p>
				<p class="spacer">&nbsp;</p>
			</td>
		</tr>
<!-- 		<tr> -->
<!-- 			<th colspan="2" class="concordtable"> -->
<!-- 				<i>Admin controls over cached frequency tables</i> -->
<!-- 			</th> -->
<!-- 		</tr> -->
<!-- 		<tr> -->
	<?php
	
// 	$return_to_url = urlencode('index.php?' . url_printget());

// 	echo '<th width="50%" class="concordtable">'
// 		, '<a onmouseover="return escape(\'This function affects <b>all</b> corpora in the CQPweb database\')"'
// 		, 'href="execute.php?function=delete_freqtable_overflow&locationAfter='
// 		, $return_to_url
// 		, '&uT=y">Delete frequency table cache overflow</a></th>'
//  		;


// 	echo '<th width="50%" class="concordtable">'
// 		, '<a onmouseover="return escape(\'This function affects <b>all</b> corpora in the CQPweb database\')"'
// 		, 'href="execute.php?function=clear_freqtables&locationAfter='
// 		, $return_to_url
// 		, '&uT=y">Clear entire frequency table cache</a></th>'
//  		;
// 	echo '<th width="50%" class="concordtable">Clear entire frequency table cache<br/>(function disabled)</th>';
	
	
	
	?>
<!-- 		</tr> -->
	</table>
	
	
	
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">No.</th>
			<th class="concordtable">FT name</th>
			<th class="concordtable">User</th>
			<th class="concordtable">Size (bytes)</th>
			<th class="concordtable">Corpus section</th>
			<th class="concordtable">Created</th>
			<th class="concordtable">Public?</th>
			<th class="concordtable">Delete</th>
		</tr>


	<?php
	
	$result = do_mysql_query("SELECT * FROM saved_freqtables WHERE corpus = '{$Corpus->name}' order by create_time desc");


	if (isset($_GET['beginAt']))
		$begin_at = (int)$_GET['beginAt'];
	else
		$begin_at = 1;

	if (isset($_GET['pp']))
		$per_page = (int)$_GET['pp'];
	else
		$per_page = $Config->default_history_per_page;


	$toplimit = $begin_at + $per_page;
	$alt_toplimit = mysql_num_rows($result);
	
	if (($alt_toplimit + 1) < $toplimit)
		$toplimit = $alt_toplimit + 1;
	
	$name_trim_factor = strlen($Corpus->name) + 9;

	for ( $i = 1 ; $i < $toplimit ; $i++ )
	{
		$public_control = false;
		$row = mysql_fetch_assoc($result);
		if (!$row)
			break;
		if ($i < $begin_at)
			continue;
		
		echo "<tr>\n<td class='concordgeneral' align='center'>$i</td>";
		echo "<td class='concordgeneral' align='center'>" , substr($row['freqtable_name'], $name_trim_factor) . '</td>';
		echo "<td class='concordgeneral' align='center'>" , $row['user'] , '</td>';
		echo "<td class='concordgeneral' align='center'>" , number_format($row['ft_size']) , '</td>';
		switch(true)
		{
		case empty($row['query_scope']):
			/* ought not actually to be possible: but hey */
			$qs = '-';
			break;
		case (bool) preg_match('/^\d+$/', $row['query_scope']):
			$qs = 'Subcorpus id # '. $row['query_scope'];
			$public_control = true;
			break;
		case $row['query_scope'] == QueryScope::$DELETED_SUBCORPUS:
			/* this should not happen except in case of a mid-delete glitch of some kind */
			$qs = '[a deleted subcorpus]';
			break;
		default:
			$qs = $row['query_scope'];
			break;
		}
		
		echo "<td class='concordgeneral'>$qs</td>";
		
		echo "<td class='concordgeneral' align='center'>" , date(CQPWEB_UI_DATE_FORMAT, $row['create_time']), '</td>';
		
		if ( $public_control )
		{
			if ((bool)$row['public'])
				echo '<td class="concordgeneral" align="center"><span 
					onmouseover="return escape(\'This frequency list is public on the system!\')">Yes</span>
					<a class="menuItem" href="execute.php?function=unpublicise_freqtable&args='
					, $row['freqtable_name'] , "&locationAfter=$return_to_url&uT=y"
					, '" onmouseover="return escape(\'Make this frequency list unpublic\')">[&ndash;]</a>
					</td>';
			else
				echo '<td class="concordgeneral" align="center"><span
					onmouseover="return escape(\'This frequency list is not publicly accessible\')">No</span>
					<a class="menuItem" href="execute.php?function=publicise_freqtable&args='
					, $row['freqtable_name'] , "&locationAfter=$return_to_url&uT=y"
					, '" onmouseover="return escape(\'Make this frequency list public\')">[+]</a>
					</td>'
					;
		}
		else
			/* only freqtables from subcorpora can be made public, not freqtables from restrictions*/
			echo '<td class="concordgeneral" align="center">N/A</td>';

		echo '<td class="concordgeneral"><center><a class="menuItem" href="execute.php?function=delete_freqtable&args='
			, $row['freqtable_name'] , "&locationAfter=$return_to_url&uT=y"
			, '" onmouseover="return escape(\'Delete this frequency table\')">[x]</a></center></td>'
			;
	}
	$navlinks = '<table class="concordtable" width="100%"><tr><td class="basicbox" align="left';

	if ($begin_at > 1)
	{
		$new_begin_at = $begin_at - $per_page;
		if ($new_begin_at < 1)
			$new_begin_at = 1;
		$navlinks .=  '"><a href="index.php?' . url_printget(array(array('beginAt', "$new_begin_at")));
	}
	$navlinks .= '">&lt;&lt; [Newer frequency tables]';
	if ($begin_at > 1)
		$navlinks .= '</a>';
	$navlinks .= '</td><td class="basicbox" align="right';
	
	if (mysql_num_rows($result) > $i)
		$navlinks .=  '"><a href="index.php?' . url_printget(array(array('beginAt', "$i + 1")));
	$navlinks .= '">[Older frequency tables] &gt;&gt;';
	if (mysql_num_rows($result) > $i)
		$navlinks .= '</a>';
	$navlinks .= '</td></tr></table>';
	
	echo $navlinks;

}




function printquery_showdbs()
{
	global $Corpus;
	global $Config;
	
	list($size) = mysql_fetch_row(do_mysql_query("select sum(db_size) from saved_dbs"));
	if (!isset($size))
		$size = 0;
	$percent = round(((float)$size / (float)$Config->mysql_db_size_limit) * 100.0, 2);
	
	$subc_mapper = get_subcorpus_name_mapper($Corpus->name);
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				Showing database cache for corpus <em><?php echo $Corpus->name;?></em>
			</th>
		</tr>
		<tr>
			<td colspan="2" class="concordgeneral">
				&nbsp;<br/>
				The currently saved databases for all corpora have a total size of 
				<?php echo number_format((float)$size) . " bytes, $percent%"; ?>
				of the maximum cache.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<th colspan="2" class="concordtable">
				<i>Admin controls over cached databases</i>
			</th>
		</tr>
		<tr>
	<?php
	
	$return_to_url = urlencode('index.php?' . url_printget());

	echo '<th width="50%" class="concordtable">'
		, '<a onmouseover="return escape(\'This function affects <b>all</b> corpora in the CQPweb database\')"'
		, 'href="execute.php?function=delete_saved_dbs&locationAfter='
		, $return_to_url
		, '&uT=y">Delete DB cache overflow</a></th>'
 		;

	echo '<th width="50%" class="concordtable">'
		, '<a onmouseover="return escape(\'This function affects <b>all</b> corpora in the CQPweb database\')"'
		, 'href="execute.php?function=clear_dbs&locationAfter='
		, $return_to_url
		, '&uT=y">Clear entire DB cache</a></th>'
 		;
	
	
	?>
		</tr>
	</table>
	
	
	
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">No.</th>
			<th class="concordtable">User</th>
			<th class="concordtable">DB name</th>
			<th class="concordtable">DB type</th>
			<th class="concordtable">DB size</th>
			<th class="concordtable">Matching query...</th>
			<th class="concordtable">Restrictions/Subcorpus</th>
			<th class="concordtable">Created</th>
			<th class="concordtable">Delete</th>	
		</tr>


	<?php
	
	$result = do_mysql_query("SELECT * FROM saved_dbs WHERE corpus = '{$Corpus->name}' order by create_time desc");

	if (isset($_GET['beginAt']))
		$begin_at = (int)$_GET['beginAt'];
	else
		$begin_at = 1;

	if (isset($_GET['pp']))
		$per_page = (int)$_GET['pp'];
	else
		$per_page = $Config->default_history_per_page;


	$toplimit = $begin_at + $per_page;
	$alt_toplimit = mysql_num_rows($result);
	
	if (($alt_toplimit + 1) < $toplimit)
		$toplimit = $alt_toplimit + 1;
	

	for ( $i = 1 ; $i < $toplimit ; $i++ )
	{
		$row = mysql_fetch_assoc($result);
		if (!$row)
			break;
		if ($i < $begin_at)
			continue;
		
		echo "<tr>\n<td class='concordgeneral'><center>$i</center></td>";
		echo "<td class='concordgeneral'><center>" , $row['user'] , '</center></td>';
		echo "<td class='concordgeneral'><center>" , $row['dbname'] , '</center></td>';
		echo "<td class='concordgeneral'><center>" , $row['db_type'] , '</center></td>';
		echo "<td class='concordgeneral'><center>" , $row['db_size'] , '</center></td>';
		echo "<td class='concordgeneral'><center>" , $row['cqp_query'] , '</center></td>';
		
		if (empty($row['query_scope']))
			echo "<td class='concordgeneral' align='center'>-</td>";
		else if (preg_match('/^\d+$/', $row['query_scope']))
			echo "<td class='concordgeneral' align='center'>Subcorpus: ", $subc_mapper[$row['query_scope']], '</td>';
		else
			echo "<td class='concordgeneral' align='center'>", $row['query_scope'], '</td>';

		echo "<td class='concordgeneral'><center>" , date(CQPWEB_UI_DATE_FORMAT, $row['create_time']) 
			, '</center></td>';
			
		echo '<td class="concordgeneral"><center><a class="menuItem" href="execute.php?function=delete_db&args='
			, $row['dbname'] , "&locationAfter=$return_to_url&uT=y"
			, '" onmouseover="return escape(\'Delete this table\')">[x]</a></center></td>';
	}
	$navlinks = '<table class="concordtable" width="100%"><tr><td class="basicbox" align="left';

	if ($begin_at > 1)
	{
		$new_begin_at = $begin_at - $per_page;
		if ($new_begin_at < 1)
			$new_begin_at = 1;
		$navlinks .=  '"><a href="index.php?' . url_printget(array(array('beginAt', "$new_begin_at")));
	}
	$navlinks .= '">&lt;&lt; [Newer databases]';
	if ($begin_at > 1)
		$navlinks .= '</a>';
	$navlinks .= '</td><td class="basicbox" align="right';
	
	if (mysql_num_rows($result) > $i)
		$navlinks .=  '"><a href="index.php?' . url_printget(array(array('beginAt', "$i + 1")));
	$navlinks .= '">[Older databases] &gt;&gt;';
	if (mysql_num_rows($result) > $i)
		$navlinks .= '</a>';
	$navlinks .= '</td></tr></table>';
	
	echo $navlinks;
}


