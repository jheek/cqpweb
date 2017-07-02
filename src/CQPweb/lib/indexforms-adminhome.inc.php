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
 * 
 * @file
 * 
 * 
 * Function library for adminhome interface screen.
 * 
 */


function printquery_showcorpora()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">Showing list of currently installed corpora</th>
		</tr>
		<!-- 
		<tr>
			<td class="concordgrey" colspan="8">
				&nbsp;<br/>
				<em>Visible</em> means the corpus is accessible through the main menu. Invisible
				corpora can still be accessed by direct URL entry by people who know the web address.
				<br/>&nbsp;
			</td>
		</tr>
		-->
		<tr>
			<th class="concordtable">Corpus</th>
			<th class="concordtable">Indexing date</th>
			<th class="concordtable">Size (tokens)</th>
			<!--
			<th class="concordtable" colspan="2">Visibility</th>
			<th class="concordtable" colspan="3">Manage...</th>
			<th class="concordtable" colspan="2">Actions</th>
			-->
			<th class="concordtable">Actions</th>
		</tr>
	<?php
	
	foreach (get_all_corpora_info() as $curr_corpus => $r)
	{
// 		if ($r->visible)
// 			$visible_options = '<option value="1" selected="selected">Visible</option>
// 				<option value="0">Invisible</option>';
// 		else
// 			$visible_options = '<option value="1">Visible</option>
// 				<option value="0" selected="selected">Invisible</option>';

		
		$javalinks = ' onmouseover="corpus_box_highlight_on(\'' . $curr_corpus 
			. '\')" onmouseout="corpus_box_highlight_off(\'' . $curr_corpus 
			. '\')" ';

//TODO: change tooltip below to the Title of the corpus, once that is in the database (or have as column?)

		?>
		<tr>
			<td class="concordgeneral" <?php echo "id=\"corpusCell_$curr_corpus\""; ?>>
				<a class="menuItem" onmouseover="return escape('<?php echo $curr_corpus; ?>')" href="../<?php echo $curr_corpus; ?>">
					<strong><?php echo $curr_corpus; ?></strong>
				</a>
			</td>

			<td class="concordgeneral" align="center">
				<?php echo $r->date_of_indexing, "\n"; ?>
			</td>			

			<td class="concordgeneral" align="right">
				<?php echo number_format($r->size_tokens), "\n"; ?>
			</td>			

			<!--
			<form action="index.php" method="get">
			
				<td align="center" class="concordgeneral">
					<select name="updateVisible"><?php echo $visible_options; ?></select>
				</td>
				
				<td align="center" class="concordgeneral">
					<input <?php echo $javalinks; ?> type="submit" value="Update!">
				</td>
				
				<input type="hidden" name="corpus" value="<?php echo $curr_corpus; ?>" />
				<input type="hidden" name="admFunction" value="updateCorpusMetadata" />
				<input type="hidden" name="uT" value="y" />
			
			</form>
			
			<td class="concordgeneral" align="center">
				<a class="menuItem" 
				<?php echo $javalinks . ' href="../' . $curr_corpus; ?>/index.php?thisQ=userAccess&uT=y">
					[Access]
				</a>
			</td>
			
			<td class="concordgeneral" align="center">
				<a class="menuItem" 
				<?php echo $javalinks . ' href="../' . $curr_corpus; ?>/index.php?thisQ=manageMetadata&uT=y">
					[Metadata]
				</a>
			</td>

			<td class="concordgeneral" align="center">
				<a class="menuItem" 
				<?php echo $javalinks . ' href="../' . $curr_corpus; ?>/index.php?thisQ=manageAnnotation&uT=y">
					[Annotation]
				</a>
			</td>
			
			<td class="concordgeneral" align="center">
				<a class="menuItem" 
				<?php echo $javalinks . ' href="../' .$curr_corpus; ?>/index.php?thisQ=corpusSettings&uT=y">
					[Goto corpus settings]
				</a>
			</td>
			-->

			<td class="concordgeneral" align="center">
				<a class="menuItem"
				<?php echo $javalinks . ' href="index.php?thisF=deleteCorpus&corpus=' . $curr_corpus; ?>&uT=y">
					[Delete corpus]
				</a>
			</td>		
		
		</tr>
		<?php
	}
	?></table>
	
	<?php
}




function printquery_installcorpus_indexed()
{
	global $Config;
	
	
	?>
	<form action="index.php" method="GET">
		<table class="concordtable" width="100%">
			<tr>
				<th colspan="2" class="concordtable">
					Install a corpus pre-indexed in CWB
				</th>
			</tr>
			<tr>
				<td colspan="2" class="concordgrey">
					&nbsp;<br/>
					<a href="index.php?thisF=installCorpus&uT=y">
						Click here to install a completely new corpus from files in the upload area.
					</a>
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">Specify the CWB name (lowercase format)<br/>(will be used as CQPweb's internal short-handle)</td>
				<td class="concordgeneral">
					<input type="text" name="corpus_cwb_name" onKeyUp="check_c_word(this)" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">Enter the full descriptive name of the corpus</td>
				<td class="concordgeneral">
					<input type="text" name="corpus_description" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" rowspan="2">Where is the registry file?</td>
				<td class="concordgeneral">
					<input type="radio" name="corpus_useDefaultRegistry" value="1" checked="checked" />
					In CQPweb's usual registry directory 
					<a class="menuItem" onmouseover="return escape('<?php echo $Config->dir->registry; ?>/')">
						[?]
					</a>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					<input type="radio" name="corpus_useDefaultRegistry" value="0" />
					In the directory specified here:
					<br/>
					<input type="text" name="corpus_cwb_registry_folder" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">Tick here if the main script in the corpus is right-to-left</td>
				<td class="concordgeneral">
					<input type="checkbox" name="corpus_scriptIsR2L" value="1"/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Tick here if the corpus is encoded in Latin1 (iso-8859-1)
					<br/>
					<em>
						(note that the character set in CQPweb is assumed to be UTF8 unless otherwise specifed)
					</em> 
				</td>
				<td class="concordgeneral">
					<input type="checkbox" name="corpus_encodeIsLatin1" value="1"/>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="concordgrey">
					&nbsp;<br/>
					P-attributes (annotation) are read automatically from the registry file.
					Use "Manage annotation" to add descriptions, tagset names/links, etc. 
					<br/>&nbsp;
				</td>
			</tr>
		<?php printquery_installcorpus_stylesheetrows(); ?>
		</table>
				
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">Install corpus</th>
			</tr>
			<tr>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Install corpus with settings above" />
					<br/>&nbsp;<br/>
					<input type="reset" value="Clear this form" />
				</td>
			</tr>
		</table>
		
		<input type="hidden" name="admFunction" value="installCorpusIndexed" />
		<input type="hidden" name="uT" value="y" />
	</form>
	
	<?php
	
}


/**
 * Returns string containing a form chunk that has in it the P-attribute definition form.
 * 
 * Works in tandem with clientside JS functions, q.v.
 *   
 * @param $input_name_base  Prefix for HTML-form-field "names".
 * @param $init_n           Initial number of rows to print. Minimum is 1. Default is 6.
 * @return                  A string containing the HTML of the form-chunk.

 */
function print_embiggenable_p_attribute_form($input_name_base, $init_n = 6)
{
	$html = <<<END

			<tr id="p_att_row_1">
				<td class="concordgrey" align="center">Primary?</td>
				<td class="concordgrey" align="center">Handle</td>
				<td class="concordgrey" align="center">Description</td>
				<td class="concordgrey" align="center">Tagset</td>
				<td class="concordgrey" align="center">External URL</td>
				<td class="concordgrey" align="center">Feature set?</td>
			</tr>
END;
	for ($q = 1 ; $q <= $init_n; $q++)
	{
		$html .= "
			<tr>
				<td align=\"center\" class=\"concordgeneral\">
					<input type=\"radio\" name=\"{$input_name_base}PPrimary\" value=\"$q\" />
				</td>
				<td align=\"center\" class=\"concordgeneral\">
					<input type=\"text\" maxlength=\"15\" name=\"{$input_name_base}PHandle$q\" onKeyUp=\"check_c_word(this)\" />
				</td>
				<td align=\"center\" class=\"concordgeneral\">
					<input type=\"text\" maxlength=\"150\" name=\"{$input_name_base}PDesc$q\" />
				</td>
				<td align=\"center\" class=\"concordgeneral\">
					<input type=\"text\" maxlength=\"150\" name=\"{$input_name_base}PTagset$q\" />
				</td>
				<td align=\"center\" class=\"concordgeneral\">
					<input type=\"text\" maxlength=\"150\" name=\"{$input_name_base}Purl$q\" />
				</td>
				<td align=\"center\" class=\"concordgeneral\">
					<input type=\"checkbox\" name=\"{$input_name_base}Pfs$q\"  value=\"1\"/>
				</td>
			</tr>\n";
	}
	$html .= <<<END
			<tr id="p_embiggen_button_row">
				<td colspan="6" class="concordgrey" align="center">
					&nbsp;<br/>
					<a onClick="add_p_attribute_row()" class="menuItem">[Embiggen form]</a>
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="pNumRows" id="pNumRows" value="6"/>
			<input type="hidden" name="inputNameBaseP" id="inputNameBaseP" value="$input_name_base"/>
END;

	return $html;
}


/**
 * Creates a chunk of an HTML form containing a dynamically-growable form for use in XML templates
 * and installation of corpus XML.
 * 
 * Works in tandem with two clientside JS functions, q.v.
 *   
 * @param $input_name_base  Prefix for HTML-form-field "names".
 * @param $init_n           Initial number of rows to print. Minimum is 1. Default is 4.
 * @return                  A string containing the HTML of the form-chunk.
 */
function print_embiggenable_s_attribute_form($input_name_base, $init_n = 4)
{

	/* some chunks using constants that we need to create first, then embed. */
	$text_id_type = METADATA_TYPE_UNIQUE_ID;
	/* note, we *manully* list the datatypes, rather than using some sort of array. This may merit changing later. */
	$optblock = '
				<option value="' . METADATA_TYPE_FREETEXT . '" selected="selected">Free text</option>
				<option value="' . METADATA_TYPE_CLASSIFICATION . '">Classification</option>
				<option value="' . METADATA_TYPE_IDLINK . '">ID link</option>
				<option value="' . METADATA_TYPE_UNIQUE_ID . '">Unique ID</option>
		';
	// TODO use $Config->metadata_type_descriptions once we allow DATE too.
	
	/* now compose the main returnable */ 
	$html = <<<END

			<!-- hidden block of select options for the attribute datatypes -->
			<select id="getDataTypeOptionsFromHere" style="display:none">
$optblock
			</select>
			<!-- the above is never rendered, but is used as a template by the clientside JavaScript -->

			<tr id="s_att_row_1">
				<td rowspan="2" class="concordgrey" align="center">Element tag</td>
				<td rowspan="2" class="concordgrey" align="center">Description</td>
				<td colspan="3" class="concordgrey" align="center">Attributes</td>
			</tr>
			<tr>
				<td class="concordgrey" align="center">Att tag</td>
				<td class="concordgrey" align="center">Description</td>
				<td class="concordgrey" align="center">Datatype</td>
			</tr>
			
			<!-- first content row is the text element: special -->
			<tr id="row_for_S_1">
				<td id="cell{$input_name_base}SHandle1" rowspan="2" align="center" class="concordgeneral">
					<i>text</i>
				</td>
				<td id="cell{$input_name_base}SDesc1" rowspan="2" align="center" class="concordgeneral">
					<i>The text division markers<br/>are automatic and compulsory.</i>
				</td>
				<td align="center" class="concordgeneral">
					<i>id</i>
				</td>
				<td colspan="2" align="center" class="concordgeneral">
					<i>automatic and compulsory</i>
				</td>
			</tr>
			<tr>
				<td id="addXmlAttributeButtonCellFor1" colspan="3" align="center" class="concordgeneral">
					<a class="menuItem" onClick="add_xml_attribute_to_s(1)">[Add attribute slot]</a>
				</td>
			</tr>
			<input type="hidden" name="nOfAttsFor{$input_name_base}Xml1" id="nOfAttsFor{$input_name_base}Xml1" value="1" />
			
			<!-- hidden variables that == the inputs that **would** exist for text/text_id -->
			<input type="hidden" name="{$input_name_base}SHandle1" value="text" />
			<input type="hidden" name="{$input_name_base}SDesc1" value="Text" />
			<input type="hidden" name="{$input_name_base}SHandleAtt1_1" value="id" />
			<input type="hidden" name="{$input_name_base}SDescAtt1_1" value="Text ID" />
			<input type="hidden" name="{$input_name_base}STypeAtt1_1" value="$text_id_type" />
			<!-- end special variant row for text/text_id -->
			
END;


	for ($q = 2 ; $q <= $init_n; $q++)
	{
		$html .= "
			<tr id=\"row_for_S_$q\">
				<td id=\"cell{$input_name_base}SHandle$q\" align=\"center\" class=\"concordgeneral\">
					<input type=\"text\" maxlength=\"64\" name=\"{$input_name_base}SHandle$q\" onKeyUp=\"check_c_word(this)\" />
				</td>
				<td id=\"cell{$input_name_base}SDesc$q\" align=\"center\" class=\"concordgeneral\">
					<input type=\"text\" maxlength=\"255\" name=\"{$input_name_base}SDesc$q\" />
				</td>
				<td id=\"addXmlAttributeButtonCellFor$q\" colspan=\"3\" align=\"center\" class=\"concordgeneral\">
					<a class=\"menuItem\" onClick=\"add_xml_attribute_to_s($q)\">[Add attribute slot]</a>
				</td>
			</tr>
			<input type=\"hidden\" name=\"nOfAttsFor{$input_name_base}Xml$q\" id=\"nOfAttsFor{$input_name_base}Xml$q\" value=\"0\" />\n";
	}

	$html .= <<<END
	
			<tr id="s_embiggen_button_row">
				<td colspan="5" class="concordgrey" align="center">
					&nbsp;<br/>
					<a onClick="add_s_attribute_row()" class="menuItem">[Embiggen form]</a>
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="sNumRows" id="sNumRows" value="$init_n"/>
			<input type="hidden" name="inputNameBaseS" id="inputNameBaseS" value="$input_name_base"/>
END;

	return $html;
}



function printquery_installcorpus_unindexed()
{
	global $Config;
	
	// TODO: add other 8-bit encodings.
	
	?>
	<form action="index.php" method="GET">
		<table class="concordtable" width="100%">
			<tr>
				<th colspan="2" class="concordtable">
					Install new corpus
				</th>
			</tr>
			<tr>
				<td colspan="2" class="concordgrey">
					&nbsp;<br/>
					<a href="index.php?thisF=installCorpusIndexed&uT=y">
						Click here to install a corpus you have already indexed in CWB.</a>
					<br/>&nbsp;
				</td>
			</tr>
			<!--
			<tr>
				<td class="concordgeneral">Specify the MySQL name of the corpus you wish to create</td>
				<td class="concordgeneral">
					<input type="text" name="corpus_mysql_name" onKeyUp="check_c_word(this)"/>
				</td>
			</tr>
			-->
			<tr>
				<td class="concordgeneral">Specify a CWB/MySQL name for the corpus you wish to create<br/>(will be used as CQPweb's internal short-handle)</td>
				<td class="concordgeneral">
					<input type="text" name="corpus_cwb_name" onKeyUp="check_c_word(this)"/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">Enter the full descriptive name of the corpus</td>
				<td class="concordgeneral">
					<input type="text" name="corpus_description" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">Tick here if the main script in the corpus is right-to-left</td>
				<td class="concordgeneral">
					<input type="checkbox" name="corpus_scriptIsR2L" value="1"/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Tick here if the corpus is encoded in Latin1 (iso-8859-1)
					<br/>
					<em>
						(note that the character set in CQPweb is assumed to be UTF-8 unless otherwise specifed)
					</em>
				</td>
				<td class="concordgeneral">
					<input type="checkbox" name="corpus_encodeIsLatin1" value="1"/>
				</td>
			</tr>
		</table>
		
		<table class="concordtable" width="100%">
			<tr>
				<th colspan="4" class="concordtable">
					Select files
				</th>
			</tr>
			<tr>
				<td class="concordgrey" colspan="4">
					The following files are available (uncompressed) in the upload area. Put a tick next to
					the files you want to index into CWB format.
				</td>
			</tr>
			<tr>
				<th class="concordtable">Include?</th>
				<th class="concordtable">Filename</th>
				<th class="concordtable">Size (K)</th>
				<th class="concordtable">Date modified</th>
			</tr>
			<?php
			$file_list = scandir($Config->dir->upload);
			natcasesort($file_list);
			
	
			foreach ($file_list as &$f)
			{
				$file = "{$Config->dir->upload}/$f";
				
				if (!is_file($file)) continue;
				
				if (substr($f,-3) === '.gz') continue;
	
				$stat = stat($file);
				?>
				
				<tr>
					<td class="concordgeneral" align="center">
						<?php 
						echo '<input type="checkbox" name="includeFile" value="' . urlencode($f) . '" />'; 
						?>
					</td>
					
					<td class="concordgeneral" align="left"><?php echo $f; ?></td>
					
					<td class="concordgeneral" align="right";>
						<?php echo number_format(round($stat['size']/1024, 0)); ?>
					</td>
				
					<td class="concordgeneral" align="center">
						<?php echo date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']); ?>
					</td>		
				</tr>
				<?php
			}
			?>
		</table>
		<table class="concordtable" width="100%" id="annotation_table_second">
			<tr>
				<th  colspan="7" class="concordtable">
					Define corpus annotation
				</th>
			</tr>
			<tr>
				<td  colspan="7" class="concordgrey">
					You do not need to specify the <em>word</em> as a P-attribute or the <em>text</em> as
					an S-attribute. Both are assumed and added automatically.
				</td>
			</tr>
		</table>
		<!--
		<table class="concordtable" width="100%" id="annotation_table_old">
			<tr>
				<th colspan="2" class="concordtable">S-attributes (XML elements)</th>
			</tr>
			<tr id="s_att_row_1">
				<td rowspan="6" class="concordgeneral" id="s_instruction_cell">
					<input type="radio" name="withDefaultSs" value="1" checked="checked"/>
					<label for="withDefaultSs:1">Use default setup for S-attributes (only &lt;s&gt;)</label>
					<br/>
					<input type="radio" id="withDefaultSs:0" name="withDefaultSs" value="0"/>
					<label for="withDefaultSs:0">Use custom setup (specify attributes in the boxes opposite)</label>
					
					<br/>&nbsp<br/>
					<a onClick="add_s_attribute_row()" class="menuItem">
						[Embiggen form]
					</a>
				</td>
				<?php 
				foreach(array(1,2,3,4,5,6) as $q)
				{
					if ($q != 1) echo '<tr>';
					echo "<td align=\"center\" class=\"concordgeneral\">
							<input type=\"text\" name=\"customS$q\"  onKeyUp=\"check_c_word(this)\"/>
						</td>
					</tr>
					";
				}
				?>
				
		</table>
		-->
		<table class="concordtable" width="100%" id="annotation_table">
			<tr>
				<th colspan="5" class="concordtable">S-attributes (corpus XML)</th>
			</tr>

			<tr>
				<td colspan="5" class="concordgeneral" align="center">
					<table width="100%">
						<tr>
							<td class="basicbox" width="50%">
								&nbsp;<br/>
								Choose XML template
								<br/>
								<i>
									(or select &ldquo;Custom XML structure&rdquo; and specify  
									XML elements and attributes in the form below)
								</i>
								<br/>&nbsp;
							</td>
							<td class="basicbox" width="50%" align="center">
							
								<select name="useXmlTemplate">
									<option value='~~customSs' selected="selected">Custom XML structure</option>
									
									<?php
									foreach (list_xml_templates() as $t)
										echo "\n\t\t\t\t\t\t\t\t\t<option value=\"{$t->id}\">{$t->description}</option>";
									?>
									
								</select>
							
							</td>
						</tr>
					</table>
				</td>
			</tr>

			<?php echo print_embiggenable_s_attribute_form('custom'); ?>	

		</table>
		
		<table class="concordtable" width="100%" id="annotation_table_third">
			<tr id="p_att_header_row">
				<th colspan="6" class="concordtable">P-attributes (word annotation)</th>
			</tr>
			
			<tr>
				<td colspan="6" class="concordgeneral" align="center">
					<table width="100%">
						<tr>
							<td class="basicbox" width="50%">
								&nbsp;<br/>
								Choose annotation template
								<br/>
								<i>(or select "Custom annotation" and specify annotations in the boxes below)</i>
								<br/>&nbsp;
							</td>
							<td class="basicbox" width="50%" align="center">
							
								<select name="useAnnotationTemplate">
									<option value='~~customPs' selected="selected">Custom annotation</option>
									
									<?php
									foreach (list_annotation_templates() as $t)
										echo "\t\t\t\t\t\t<option value=\"{$t->id}\">{$t->description}</option>\n";
									?>
									
								</select>
							
							</td>
						</tr>
					</table>
				</td>
			</tr>
			
		<?php

		echo print_embiggenable_p_attribute_form('custom');
		
		?>

		</table>
		
		<table class="concordtable" width="100%">
		<?php printquery_installcorpus_stylesheetrows(); ?>
		</table>
				
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">Install corpus</th>
			</tr>
			<tr>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Install corpus with settings above" />
					<br/>&nbsp;<br/>
					<input type="reset" value="Clear this form" />
				</td>
			</tr>
		</table>
		
		<input type="hidden" name="admFunction" value="installCorpus" />
		<input type="hidden" name="uT" value="y" />
	</form>
	
	<?php
}


function printquery_installcorpusdone()
{
	/* addslashes shouldn't be necessary here, but paranoia never hurts */
	$corpus = urlencode(addslashes($_GET['newlyInstalledCorpus']));
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Your corpus has been successfully installed!
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p>You can now:</p>
				<ul>
					<li>
						<a href="../<?php echo $corpus; ?>/index.php?thisQ=manageMetadata&uT=y">Design and 
						insert a text-metadata table for the corpus</a> (searches won't work till you do)<br/>
					</li>
					<li>
						<a href="index.php?thisF=installCorpus&uT=y">Install another corpus</a>
					</li>
					<li>
						<a onClick="$('#installedCorpusIndexingNotes').slideDown();">View the indexing notes</a>
				</ul>
				<p>&nbsp;</p>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral" id="installedCorpusIndexingNotes" style="display:none">
				<pre>
				
					<?php echo "\n", get_corpus_info($_GET['newlyInstalledCorpus'])->indexing_notes, "\n"; ?>
				
				</pre>
			</td>
	</table>
	<?php
}

function printquery_installcorpus_stylesheetrows()
{
	?>
	
			<tr>
				<th colspan="2" class="concordtable">Select a stylesheet</th>
			</tr>
			<tr>
				<td class="concordgeneral" align="left">
					<input type="radio" id="cssCustom:0" name="cssCustom" value="0" checked="checked"/>
					<label for="cssCustom:0">Choose a built in stylesheet:</label>
				</td>
				<td class="concordgeneral" align="left">
					<select name="cssBuiltIn">
						<?php
							$list = scandir('../css');
							foreach($list as &$l)
							{
								if (substr($l, -4) !== '.css')
									continue;
								else
									echo "<option>$l</option>";
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" align="left">
					<input type="radio" id="cssCustom:1" name="cssCustom" value="1" />
					<label for="cssCustom:1">Use the stylesheet at this URL:</label>
				</td>
				<td class="concordgeneral" align="left">
					<input type="text" maxlength="255" name="cssCustomUrl" />
				</td>
			</tr>
	<?php
}



function printquery_deletecorpus()
{
	$corpus = $_GET['corpus'];
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				You have requested deletion of the corpus "<?php echo $corpus; ?>" from the CQPweb system.
			</th>
		</tr>
		<tr>
			<td class="concordgrey" align="center">Are you sure you want to do this?</td>
		</tr>
		<tr>
			<td class="concordgeneral" align="center">
				<form action="index.php" method="get">
					<br/>
					<input type="checkbox" name="sureyouwantto" value="yes"/>
					Yes, I'm sure I want to do this.
					<br/>&nbsp;<br/>
					<input type="submit" value="I am definitely sure I want to delete this corpus." />
					<br/>
					<input type="hidden" name="admFunction" value="deleteCorpus" />
					<input type="hidden" name="corpus" value="<?php echo $corpus; ?>" />
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
	</table>					
		
	<?php
}


function printquery_corpuscategories()
{
	global $Config;
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">
				Manage corpus categories
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="6">
				Corpus categories are used to organise links to corpora on CQPweb's home page.
				<br/>&nbsp;<br/>
				This behaviour can be turned on or off using the setting 
					<code>$homepage_use_corpus_categories</code>
				in your configuration file.
				<br/>&nbsp;<br/>
				Currently, it is turned <strong><?php echo ($Config->homepage_use_corpus_categories?'on':'off'); ?></strong>.
				<br/>&nbsp;<br/>
				Categories are displayed on the home page in the defined <em>sort order</em>, with low numbers shown first
				(in the case of a numerical tie, categories are sorted alphabetically).
				<br/>&nbsp;<br/>
				The available categories are listed below. Use the form at the bottom to ad a new category.
				<br/>&nbsp;<br/>
				Important note: you cannot have two categories with the same name, and you cannot delete 
				<em>&ldquo;Uncategorised&rdquo;</em>, which is the default category of a new corpus.
			</td>
		</tr>
		<tr>
			<th class="concordtable">
				Category label
			</th>
			<th class="concordtable">
				No. corpora
			</th>
			<th class="concordtable">
				Sort order
			</th>
			<th class="concordtable" colspan="3">
				Actions
			</th>
		</tr>
		
		<?php
		/* this function call is a bit wasteful, but it makes sure "Uncategorised" exists... */
		list_corpus_categories();
		
		$result = do_mysql_query("select id, label, sort_n from corpus_categories order by sort_n asc, label asc");
		$sort_key_max = 0;
		$sort_key_min = 0; 
		while (false !== ($r = mysql_fetch_object($result)))
		{
			list($n) = mysql_fetch_row(do_mysql_query("select count(*) from corpus_info where corpus_cat={$r->id}"));
			echo '<tr><td class="concordgeneral">', $r->label, '</td>',
				'<td class="concordgeneral" align="center">', $n, '</td>',
				'<td class="concordgeneral" align="center">', $r->sort_n, '</td>',
				'<td class="concordgeneral" align="center">',
					'<a class="menuItem" href="index.php?admFunction=execute&function=update_corpus_category_sort&args=',
					$r->id, urlencode('#'), $r->sort_n - 1, 
					'&locationAfter=', urlencode('index.php?thisF=manageCorpusCategories&uT=y'), '&uT=y">',
					'[Move up]</a></td>',
				'<td class="concordgeneral" align="center">',
					'<a class="menuItem" href="index.php?admFunction=execute&function=update_corpus_category_sort&args=',
					$r->id, urlencode('#'), $r->sort_n + 1, 
					'&locationAfter=', urlencode('index.php?thisF=manageCorpusCategories&uT=y'), '&uT=y">',
					'[Move down]</a></td>',
				'<td class="concordgeneral" align="center">',
					'<a class="menuItem" href="index.php?admFunction=execute&function=delete_corpus_category&args=',
					$r->id, '&locationAfter=', urlencode('index.php?thisF=manageCorpusCategories&uT=y'), '&uT=y">',
					'[Delete]</a></td>',
				"</tr>\n";
			if ($sort_key_max < $r->sort_n)
				$sort_key_max = $r->sort_n;
			if ($sort_key_min > $r->sort_n)
				$sort_key_min = $r->sort_n;
		}
		?>
		
	</table>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="5">
				Create a new category
			</th>
		</tr>
		<form action="index.php" method="GET">
			<tr>
				<td class="concordgrey" align="center">
					&nbsp;<br/>
					Specify a category label
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input name="newCategoryLabel" size="50" type="text" maxlength="255"/>
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgrey" align="center">
					&nbsp;<br/>
					Initial sort key for this category
					<br/>
					<em>(lower numbers appear higher up)</em>
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<select name="newCategoryInitialSortKey">
					
						<?php
						/* give options for intial sort key of zero to existing range, plus one */
						for ($sort_key_min--; $sort_key_min < 0; $sort_key_min++)
							echo "\t\t<option>$sort_key_min</option>\n";
						echo "\t\t<option selected=\"selected\">0</option>\n";
						for ($sort_key_max++, $i = 1; $i <= $sort_key_max; $i++)
							echo "\t\t<option>$i</option>\n";
						?>
						 
					</select>
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="3" align="center">
					&nbsp;<br/>
					<input type="submit" value="Click here to create the new category" />
					<br/>&nbsp;
				</td>
				<input type="hidden" name="admFunction" value="newCorpusCategory" />
				<input type="hidden" name="uT" value="y" />
			</tr>
		</form>
	</table>
	
	<?php
}


function printquery_annotationtemplates()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="8">
				Manage annotation templates
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="8">
				&nbsp;<br/>
				An annotation template is a description of a predefined set of word-level annotations (p-attributes).
				<br/>&nbsp;<br/>
				You can use templates when indexing corpora instead of specifying the p-attribute information every time.
				<br/>&nbsp;<br/>
				Use the controls below to create and manage annotation templates.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<th class="concordtable" colspan="8">
				Currently-defined annotation templates
			</th>
		</tr>		
		<tr>
			<th class="concordtable">
				ID
			</th>
			<th class="concordtable">
				Description
			</th>
			<th class="concordtable" colspan="5">
				Attributes (in order of columns left-to-right; [*] = primary)
			</th>
			<th class="concordtable">
				Delete
			</th>
		</tr>
		
		<?php
			
		foreach(list_annotation_templates() as $template)
		{
			$rowspan = 1 + count($template->attributes);
			echo "\n\t\t<tr>"
				, "\n\t\t\t<td class=\"concordgeneral\" align=\"center\" rowspan=\"$rowspan\">{$template->id}</td>"
				, "\n\t\t\t<td class=\"concordgeneral\" rowspan=\"$rowspan\">{$template->description}</td>\n"
				, "\n\t\t\t", '<td class="concordgrey" align="center">N</td>'
				, '<td class="concordgrey" align="center">Handle</td><td class="concordgrey" align="center">Description</td>'
				, '<td class="concordgrey" align="center">Feature set?</td><td class="concordgrey" align="center">Tagset</td>'
				, "\n\t\t\t<td class=\"concordgeneral\" align=\"center\" rowspan=\"$rowspan\">"
				, "<a class=\"menuItem\" href=\"index.php?admFunction=deleteAnnotationTemplate&toDelete={$template->id}&uT=y\">[x]</a></td>"
				, "\n\t\t</tr>"
				;
			
			foreach($template->attributes as $k=>$att)
			{
				$star = ($att->handle == $template->primary_annotation ? ' [*] ' : '');
				
				$link = (empty($att->external_url) ? "{$att->tagset}" :"<a href=\"{$att->external_url}\" target=\"_blank\">{$att->tagset}</a>");
					
				echo "\n\t\t\t<td class=\"concordgeneral\" align=\"center\">{$att->order_in_template}</td>"
					, "\n\t\t\t<td class=\"concordgeneral\">{$att->handle}$star</td>\n"
					, "\n\t\t\t<td class=\"concordgeneral\">{$att->description}</td>\n"
					, "\n\t\t\t<td class=\"concordgeneral\" align=\"center\">", ($att->is_feature_set ? 'Y' : 'N'), "</td>\n"
					, "\n\t\t\t<td class=\"concordgeneral\">$link</td>\n"
					, "\n\t\t</tr>"
					;	
			}
		}
			
		?>
		
	</table>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">
				Add new annotation template
			</th>
		</tr>		
		
		<form action="index.php" method="get">

			<tr>
				<td colspan="6" class="concordgeneral" align="center">
					<table width="100%">
						<tr>
							<td class="basicbox" width="50%" align="center">
								&nbsp;<br/>
								Enter a description for your new template:
								<br/>&nbsp;
							</td>
							<td class="basicbox" width="50%" align="center">
							
								<input type="text" name="newTemplateDescription" size="60" maxlength="255">
							
							</td>
						</tr>
					</table>
				</td>
			</tr>

			<?php echo print_embiggenable_p_attribute_form('template'); ?>

			<tr>
				<td class="concordgeneral" colspan="6" align="center">
					&nbsp;<br/>
					<input type="submit" value="Click here to create annotation template"/>
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="newAnnotationTemplate" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">
				Install default templates
			</th>
		</tr>		
		<tr>
			<td class="concordgrey">
				&nbsp;<br/>
				The default annotation templates describe commonly-used corpus annotation patterns 
				(especially those generated by annotation tools created or used by the CWB/CQPweb developers).
					<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<form action="index.php" method="get">
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Load built-in annotation templates" />
					<br/>&nbsp;
				</td>
				<input type="hidden" name="admFunction" value="loadDefaultAnnotationTemplates" />
				<input type="hidden" name="uT" value="y" />
			</form>
		</tr>
	</table>
	
	<?php
}


function printquery_metadatatemplates()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="8">
				Manage metadata templates
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="8">
				&nbsp;<br/>
				A metadata template is a description of a series of columns of metadata (data about either corpus texts, or some other
				relevent entity in the strucutre of the corpus.) Each column has (1) a handle, (2) a description, and (3) a datatype.
				<br/>&nbsp;<br/>
				You can use templates when setting up metadata tables instead of entering the field-structure every time.
				<br/>&nbsp;<br/>
				Use the controls below to create and manage metadata templates.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<th class="concordtable" colspan="7">
				Currently-defined metadata templates
			</th>
		</tr>
		<tr>
			<th class="concordtable">
				ID
			</th>
			<th class="concordtable">
				Description
			</th>
			<th class="concordtable" colspan="4">
				Fields
			</th>
			<th class="concordtable">
				Delete
			</th>
		</tr>
		
		<?php
		
		/* for datatype descriptions */
		global $Config;
		
		$all_templates = list_metadata_templates();
		if (empty($all_templates))
			echo '<tr><td class="concordgrey" colspan="8">'
				, '<p class="spacer">&nbsp;</p><p>No metadata templates are defined.</p><p class="spacer">&nbsp;</p>'
				, '</td></tr>';
		
		foreach($all_templates as $template)
		{
			$rowspan = 1 + count($template->fields);
			echo "\n\t\t<tr>"
				, "\n\t\t\t<td class=\"concordgeneral\" align=\"center\" rowspan=\"$rowspan\">{$template->id}</td>"
				, "\n\t\t\t<td class=\"concordgeneral\" rowspan=\"$rowspan\">{$template->description}</td>\n"
				, "\n\t\t\t"
				, '<td class="concordgrey" align="center">N</td>'
				, '<td class="concordgrey" align="center">Handle</td>'
				, '<td class="concordgrey" align="center">Description</td>'
				, '<td class="concordgrey" align="center">Datatype</td>'
				, "\n\t\t\t<td class=\"concordgeneral\" align=\"center\" rowspan=\"$rowspan\">"
				, "<a class=\"menuItem\" href=\"index.php?admFunction=deleteMetadataTemplate&toDelete={$template->id}&uT=y\">[x]</a></td>"
				, "\n\t\t</tr>"
				;
			
			foreach($template->fields as $k=>$field)
			{
				echo "\n\t\t\t<td class=\"concordgeneral\" align=\"center\">{$field->order_in_template}</td>"
					, "\n\t\t\t<td class=\"concordgeneral\">{$field->handle}</td>\n"
					, "\n\t\t\t<td class=\"concordgeneral\">{$field->description}"
					, ($field->handle == $template->primary_classification ? ' <em>(primary classification)</em>' : '')
					, "</td>\n"
					, "\n\t\t\t<td class=\"concordgeneral\">{$Config->metadata_type_descriptions[$field->datatype]}</td>\n"
					, "\n\t\t</tr>"
					;
			}
		}
		?>

	</table>
	
	<form action="index.php" method="get">
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable" colspan="6">
					Add new metadata template
				</th>
			</tr>		
		
			<tr>
				<td colspan="6" class="concordgeneral">
					<table width="100%" align="center">
						<tr>
							<td class="basicbox" width="50%" align="center">
								&nbsp;<br/>
								Enter a description for your new template:
								<br/>&nbsp;
							</td>
							<td class="basicbox" width="50%" align="center">
							
								<input type="text" name="newTemplateDescription" size="60" maxlength="255">
							
							</td>
						</tr>
					</table>
					<p>
						Important note: when you specify the metadata fields below, you <b>must not</b> include the identifier column
						- which is implicit, and must be the first column of every file that is used with this template.
					</p>
					<p class="spacer">&nbsp;</p>
				</td>
			</tr>

			<?php echo print_embiggenable_metadata_form(); ?> 

			<tr>
				<td class="concordgeneral" colspan="6" align="center">
					&nbsp;<br/>
					<input type="submit" value="Click here to create metadata template"/>
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="newMetadataTemplate" />
			<input type="hidden" name="fieldCount" id="fieldCount" value="5" />
			<input type="hidden" name="uT" value="y" />
		</table>
	</form>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">
				Install default templates
			</th>
		</tr>		
		<tr>
			<td class="concordgrey">
				&nbsp;<br/>
				The default metadata templates describe commonly-used patterns for metadata files.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<form action="index.php" method="get">
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Load built-in metadata templates" />
					<br/>&nbsp;
				</td>
				<input type="hidden" name="admFunction" value="loadDefaultMetadataTemplates" />
				<input type="hidden" name="uT" value="y" />
			</form>
		</tr>
	</table>	

	<?php
}


function printquery_xmltemplates()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="8">
				Manage XML templates
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="8">
				&nbsp;<br/>
				An XML template is a description of a predefined set of XML elements/attributes (s-attributes).
				<br/>&nbsp;<br/>
				You can use templates when indexing corpora instead of specifying the s-attribute information every time.
				<br/>&nbsp;<br/>
				Use the controls below to create and manage XML templates.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<th class="concordtable" colspan="7">
				Currently-defined XML templates
			</th>
		</tr>		
		<tr>
			<th class="concordtable">
				ID
			</th>
			<th class="concordtable">
				Description
			</th>
			<th class="concordtable" colspan="4">
				Elements and attributes
			</th>
			<th class="concordtable">
				Delete
			</th>
		</tr>
		
		<?php
		
		/* for datatype descriptions */
		global $Config;
		
		foreach(list_xml_templates() as $template)
		{
			$rowspan = 1 + count($template->attributes);
			echo "\n\t\t<tr>"
				, "\n\t\t\t<td class=\"concordgeneral\" align=\"center\" rowspan=\"$rowspan\">{$template->id}</td>"
				, "\n\t\t\t<td class=\"concordgeneral\" rowspan=\"$rowspan\">{$template->description}</td>\n"
				, "\n\t\t\t", '<td class="concordgrey" align="center">N</td>'
				, '<td class="concordgrey" align="center">Handle</td><td class="concordgrey" align="center">Description</td>'
				, '<td class="concordgrey" align="center">Datatype</td>'
				, "\n\t\t\t<td class=\"concordgeneral\" align=\"center\" rowspan=\"$rowspan\">"
				, "<a class=\"menuItem\" href=\"index.php?admFunction=deleteXmlTemplate&toDelete={$template->id}&uT=y\">[x]</a></td>"
				, "\n\t\t</tr>"
				;
			
			foreach($template->attributes as $k=>$att)
			{
				if ($att->handle != $att->att_family)
				{
					$a_handle = preg_replace("/^{$att->att_family}_/", '', $att->handle);
					
					$att->description .= ' (<i>' . $a_handle . '</i> attribute on <i>' . $att->att_family . '</i>)'; 
				}
				echo "\n\t\t\t<td class=\"concordgeneral\" align=\"center\">{$att->order_in_template}</td>"
					, "\n\t\t\t<td class=\"concordgeneral\">{$att->handle}</td>\n"
					, "\n\t\t\t<td class=\"concordgeneral\">{$att->description}</td>\n"
					, "\n\t\t\t<td class=\"concordgeneral\">{$Config->metadata_type_descriptions[$att->datatype]}</td>\n"
					, "\n\t\t</tr>"
					;
			}
		}
		?>
		
	</table>

	<form action="index.php" method="get">
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable" colspan="6">
					Add new XML template
				</th>
			</tr>
		

			<tr>
				<td colspan="6" class="concordgeneral" align="center">
					<table width="100%">
						<tr>
							<td class="basicbox" width="50%" align="center">
								&nbsp;<br/>
								Enter a description for your new template:
								<br/>&nbsp;
							</td>
							<td class="basicbox" width="50%" align="center">
							
								<input type="text" name="newTemplateDescription" size="60" maxlength="255">
							
							</td>
						</tr>
					</table>
				</td>
			</tr>

			<?php echo print_embiggenable_s_attribute_form('template'); ?> 

			<tr>
				<td class="concordgeneral" colspan="6" align="center">
					&nbsp;<br/>
					<input type="submit" value="Click here to create XML template"/>
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="newXmlTemplate" />
			<input type="hidden" name="uT" value="y" />
		</table>
	</form>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">
				Install default templates
			</th>
		</tr>		
		<tr>
			<td class="concordgrey">
				&nbsp;<br/>
				The default annotation templates describe commonly-used corpus XML patterns.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<form action="index.php" method="get">
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Load built-in XML templates" />
					<br/>&nbsp;
				</td>
				<input type="hidden" name="admFunction" value="loadDefaultXmlTemplates" />
				<input type="hidden" name="uT" value="y" />
			</form>
		</tr>
	</table>
	
	<?php
}


function printquery_newupload()
{
	// TODO this form could be aesthetically much nicer. I improved it a bit in v3.1.5, but a better layout could be achieved.
	// re-use the upload interface that users have? (once they have it)
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">
				Add a file to the upload area
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="2">
				&nbsp;<br/>
				Files uploaded to CQPweb can be used as the input to indexing, or as database inputs.
				<br/>&nbsp;
			</td>
		</tr>
		<form enctype="multipart/form-data" action="index.php" method="POST">
			<tr>
				<td class="concordgeneral" align="center">
					Choose a file to upload: 
				</td>
				<td class="concordgeneral" align="center">
					<input type="file" name="uploadedFile" />
				</td>
			</tr>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Upload file" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="reset"  value="Clear form" />
				</td>
			</tr>
		</form>
	</table>
	<?php
}


function printquery_uploadarea()
{
	global $Config;
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="7" class="concordtable">
				List of files currently in upload area
			</th>
		</tr>
		<tr>
			<th class="concordtable">Filename</th>
			<th class="concordtable">Size (K)</th>
			<th class="concordtable">Date modified</th>
			<th colspan="4" class="concordtable">Actions</th>
		</tr>
		<?php
		
		$file_list = scandir($Config->dir->upload);
		natcasesort($file_list);
		
		$total_files = 0;
		$total_bytes = 0;

		foreach ($file_list as &$f)
		{
			$file = "{$Config->dir->upload}/$f";
			
			if (!is_file($file)) continue;
			
			$file_is_compressed = ( (substr($f,-3) === '.gz') ? true : false);

			$stat = stat($file);
			
			$total_files++;
			$total_bytes += $stat['size'];
			
			echo '';

			?>
			<tr>
			<td class="concordgeneral" align="left"><?php echo $f; ?></td>
			
			<td class="concordgeneral" align="right";>
				<?php echo number_format(round($stat['size']/1024, 0)); ?>
			</td>
			
			<td class="concordgeneral" align="center"><?php echo date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']); ?></td>
			
			<td class="concordgeneral" align="center">
				<?php 
					if ($file_is_compressed)
						echo '&nbsp;';
					else
						echo '<a class="menuItem" href="index.php?admFunction=fileView&filename=' 
							. urlencode($f) . '&uT=y">[View]</a>';
				?>
			</td>
			
			<td class="concordgeneral" align="center">
				<a class="menuItem" href="index.php?admFunction=<?php 
					if ($file_is_compressed)
					{
						echo 'fileDecompress&filename=' .urlencode($f);
						$compress_label = '[Decompress]';
					}
					else
					{
						echo 'fileCompress&filename=' .urlencode($f);
						$compress_label = '[Compress]';
					}
				?>&uT=y"><?php echo$compress_label; ?></a>
			</td>
			
			<td class="concordgeneral" align="center">
				<?php 
				if ($file_is_compressed)
					echo '&nbsp;';
				else
					echo '<a class="menuItem" href="index.php?admFunction=fileFixLinebreaks&filename=' 
						. urlencode($f) . '&uT=y">[Fix linebreaks]</a>'; 
				?>
			</td>
			
			<td class="concordgeneral" align="center">
				<a class="menuItem" href="index.php?admFunction=fileDelete&filename=<?php 
					echo urlencode($f);
				?>&uT=y">[Delete]</a>
			</td>
			</tr>
			<?php

		}
		
		echo '<tr><td align="left" class="concordgrey" colspan="7">'
			, $total_files , ' files (' , number_format(round($total_bytes/1024, 0)) , ' K)'
			, '</td></tr>';
		
		?>
		
	</table>
	<?php
}


function printquery_useroverview()
{
	$n_users_by_status = array(
		USER_STATUS_UNVERIFIED => 0,
		USER_STATUS_ACTIVE => 0,
		USER_STATUS_SUSPENDED => 0,
		USER_STATUS_PASSWORD_EXPIRED => 0,
		);

	$result = do_mysql_query("select acct_status, count(acct_status) as N from user_info group by acct_status");
	
	while (false !== ($o = mysql_fetch_object($result)))
		$n_users_by_status[$o->acct_status] = $o->N;

	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				Manage Users
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" width="60%"><b>Total users on the system:</b></td>
			<td class="concordgeneral"><b><?php echo number_format(array_sum($n_users_by_status)); ?></b></td>
		</tr>
		<tr>
			<td class="concordgeneral">Number of accounts validated and active:</td>
			<td class="concordgeneral"><?php echo number_format($n_users_by_status[USER_STATUS_ACTIVE]); ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Number of unverfied accounts (*):</td>
			<td class="concordgeneral"><?php echo number_format($n_users_by_status[USER_STATUS_UNVERIFIED]); ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Number of suspended accounts:</td>
			<td class="concordgeneral"><?php echo number_format($n_users_by_status[USER_STATUS_SUSPENDED]); ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Number of accounts with expired passwords:</td>
			<td class="concordgeneral"><?php echo number_format($n_users_by_status[USER_STATUS_PASSWORD_EXPIRED]); ?></td>
		</tr>
		<tr>
			<td colspan="2" class="concordgrey"> 
				(*) <a class="menuItem" href="index.php?thisF=userUnverified&uT=y">Click here to go to list of unverified accounts</a>
			</td>
		</tr>
		
	</table>

	<?php
}

function printquery_usersearchform()
{
	/* putting username data for responsive quicksearch into the page reduces need for Ajax */
	$result = do_mysql_query("select username from user_info order by username collate utf8_general_ci");
	$username_list = '';
	while (false !== ($r = mysql_fetch_row($result)))
		$username_list .= "|{$r[0]}";
	$username_list = ltrim($username_list, '|');
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">
				Search for user account details
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" colspan="3">
				&nbsp;
				<form>
					Quick username search: 
					<input id="userQuicksearch" type="text" autocomplete="off" tabindex="1" />
					<input id="userQuicksearchData" type="hidden" value="<?php echo $username_list; ?>"/>
				</form>
				<!-- empty element, anchor for results -->
				<div id="userQuicksearchResultsAnchor"></div>
				<ul id="userQuicksearchResults" class="userQuickSearchList"></ul>
				<!-- ad hoc (non-global-stylesheet-based) style for quicksearch popup -->
				<style>
					ul.userQuickSearchList 
					{
						position: absolute;
						float: left;
						vertical-align: top;
						text-align:left;
						margin: 0px;
						padding: 0px;
						list-style: none;
						margin: 0px;
						padding: 0px;
					}					
					.userQuickSearchList li 
					{ 
						padding: 0px;
						display: block;
					}
					.userQuickSearchList  a, .userQuickSearchList  a:link, .userQuickSearchList  a:visited
					{
						display: block;
						background-color: #f2f2e0;
						padding: 10px;
						margin: 0px;
						color: #000099;
						text-decoration: none;
						font-size: 11pt;
						border: 1px solid #cccccc;	
					}
					.userQuickSearchList  a:hover
					{
						background-color: #dfdfff;
						color: #ff0000;
					}
				</style>
				&nbsp;
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				&nbsp;<br>
				Full search (username, realname, or email):
				<br>&nbsp;
			</td>
			<form action="index.php?thisF=userSearch&uT=y" method="get">
				<td class="concordgeneral" align="center">
					<input tabindex="29" name="searchterm" size="50" type="text" />
				</td>
				<td class="concordgeneral" align="center" width="20%">
					<input tabindex="30" type="submit" value="Search" />
				</td>
				<input type="hidden" name="thisF" value="userSearch" />
				<input type="hidden" name="uT" value="y" />
			</form>
			
		</tr>

	</table>

	<?php
}

function printquery_usersearch()
{
	if (! isset($_GET['searchterm']))
		exiterror("No search term was supplied.");
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="7" class="concordtable">
				User search results
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="7">
				&nbsp;
				Your search term: <b><?php echo escape_html($_GET['searchterm']); ?></b>
				&nbsp;
			</td>
		</tr>
		<tr>			
			<th class="concordtable">&nbsp;</th>
			<th class="concordtable">Username</th>
			<th class="concordtable">Realname</th>
			<th class="concordtable">Email</th>
			<th class="concordtable">Affiliation</th>
			<th class="concordtable" colspan="2">Actions</th>
		</tr>

		<?php
		
		$i = 0;
		
		$term = mysql_real_escape_string($_GET['searchterm']);
		
		$result = do_mysql_query("select username, realname, email, affiliation from user_info 
									where username collate utf8_general_ci like '%$term%' 
									or email collate utf8_general_ci like '%$term%' 
									or realname collate utf8_general_ci like '%$term%' ");
		if (1 > mysql_num_rows($result))
			echo "\n\t\t<tr><td colspan=\"6\" class=\"concordgrey\">No results found.</td></tr>\n";
			
		while (false !== ($r = mysql_fetch_object($result)))
			echo "\n\t\t<tr>"
				, "\n\t\t\t<td class=\"concordgeneral\">", ++$i, "</td>"
				, "\n\t\t\t<td class=\"concordgeneral\">"
					, "<b><a class=\"menuItem\" href=\"index.php?thisF=userView&username=", $r->username, "&uT=y\">", $r->username, "</a></b></td>"
				, "\n\t\t\t<td class=\"concordgeneral\">", escape_html($r->realname), "</td>"
				, "\n\t\t\t<td class=\"concordgeneral\">", escape_html($r->email), "</td>"
				, "\n\t\t\t<td class=\"concordgeneral\">", escape_html($r->affiliation), "</td>"
				, "\n\t\t\t<td class=\"concordgeneral\"align=\"center\"><a class=\"menuItem\" href=\"index.php?thisF=userView&username="
					, $r->username, "&uT=y\">[View full details]</a></td>"
				, "\n\t\t\t<td class=\"concordgeneral\"align=\"center\"><a class=\"menuItem\" href=\"index.php?thisF=userDelete&checkUserDelete="
					, $r->username, "&uT=y\">[Delete account]</a></td>"
				, "\n\t\t</tr>\n"
				;
		?>

	</table>

	<?php
}

function printquery_userview()
{
	global $Config;
	include('../lib/user-iso31661.inc.php');
	
	/* allow this view to be accessed either by username or by an ID. 
	 * n.b. Do not confuse local var $user with global var $User!!!! */
	if (isset($_GET['username']))
		$user = get_user_info($_GET['username']);
	else 
	{
		if (!isset($_GET['userID']))
			exiterror("User view function accessed, but no user ID specified.");
		else 
			$user = get_user_info(user_id_to_name((int)$_GET['userID']));
	}
	
	if (false === $user)
		exiterror("Invalid username or user ID.");
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				Viewing User Profile
			</th>
		</tr>
		<tr>
			<td class="concordgeneral"><b>Username:</b></td>
			<td class="concordgeneral"><b><?php echo $user->username; ?></b></td>
		</tr>
		<tr>
			<td class="concordgeneral">Account ID:</td>
			<td class="concordgeneral"><?php echo $user->id; ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Email address linked to account:</td>
			<td class="concordgeneral"><?php echo escape_html($user->email); ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Account status:</td>
			<td class="concordgeneral"><?php echo $Config->user_account_status_description_map[$user->acct_status]; ?></td>
		</tr>
		<?php
		
		if (USER_STATUS_UNVERIFIED == $user->acct_status)
		{
			?>
			<tr>
				<td colspan="2" class="concorderror">
					&nbsp;<br>
					This user's account has not yet been verified via the link sent to their email address. 
					You can manually verify it using the button below.
					<br> 
					This will allow them to log on, but circumvents the check on the correctness of their email address!
					<br>&nbsp;
					<div align="center">
						<form action="index.php" method="get">
							<input type="submit" value="Manually verify this user's account" />
							<input type="hidden" name="admFunction" value="execute" />
							<input type="hidden" name="function" value="verify_user_account" />
							<input type="hidden" name="args" value="<?php echo $user->username; ?>" />
							<input type="hidden" name="locationAfter" value="index.php?thisF=userView&username=<?php echo $user->username; ?>&uT=y" />
							<input type="hidden" name="uT" value="y" />
						</form>
					</div>
				</td>
			</tr>
			<?php
		} 
		
		?>

		<!-- ****************************************************************** -->
		
		<tr>
			<td colspan="2" class="concordgrey">
				&nbsp;<br>
				The user has entered their personal details as follows:
				<br>&nbsp;
			</td>
		</tr>		<tr>
			<td class="concordgeneral">Real name:</td>
			<td class="concordgeneral"><?php echo escape_html($user->realname); ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Stated affiliation:</td>
			<td class="concordgeneral"><?php echo escape_html($user->affiliation); ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Country:</td>
			<td class="concordgeneral"><?php echo $Config->iso31661[$user->country]; ?></td>
		</tr>
		
		<!-- ****************************************************************** -->
		
		<tr>
			<td colspan="2" class="concordgrey">
				&nbsp;<br>
				User activity on this account:
				<br>&nbsp;
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">Account originally created:</td>
			<td class="concordgeneral"><?php echo escape_html($user->acct_create_time);  ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Number of queries in history:</td>
			<td class="concordgeneral">
				<?php
				list($n_of_queries) = mysql_fetch_row(do_mysql_query("select count(*) from query_history where user = '{$user->username}'"));
				echo number_format($n_of_queries); 
				?>
			</td>
		</tr>
		
		<tr>
			<td class="concordgeneral">Time of last visit to CQPweb:</td>
			<td class="concordgeneral"><?php echo escape_html($user->last_seen_time);  ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Account expires:</td>
			<td class="concordgeneral"><?php echo 0==$user->expiry_time ? 'Not set' : escape_html($user->expiry_time); ?></td>
		</tr>
		<tr>
			<td class="concordgeneral">Password expires:</td>
			<td class="concordgeneral"><?php echo 0==$user->password_expiry_time ? 'Not set' : escape_html($user->password_expiry_time); ?></td>
		</tr>

		<tr>
			<th colspan="2" class="concordtable">
				Actions
			</th>
		</tr>
	</table>

	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				This user's corpus-query usage stats
			</th>
		</tr>
		<tr>
			<th class="concordtable" width="60%">
				Corpus
			</th>
			<th class="concordtable">
				N of queries
			</th>
		</tr>
		
		<?php
		
		$result = do_mysql_query("select corpus, count(corpus) as N from query_history where user='{$user->username}' group by corpus order by N desc");
		if (1 > mysql_num_rows($result))
			echo "\n\t\t<tr>"
				, "\n\t\t\t<td colspan=\"2\" align=\"center\" class=\"concordgrey\">This user has performed no queries.</td>"
				, "\n\t\t</tr>\n"
				;
		while (false !== ($nq = mysql_fetch_object($result)))
			echo  "\n\t\t<tr>"
				, "\n\t\t\t<td class=\"concordgeneral\">", $nq->corpus, "</td>"
				, "\n\t\t\t<td class=\"concordgeneral\">", number_format($nq->N), "</td>"
				, "\n\t\t</tr>\n"
				;
		?>
		
	</table>
	
	<hr>
	<!-- TODO: NB, this is temporary: to be handled by the new privilege system at some point...
	           All the forms under this line require a sort-out in terms of their layout, they were judged bodged togerther for the moment. 
	-->
	
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="4" class="concordtable">
				Set user's maximum database size
			</th>
		</tr>
		<tr>
			<td colspan="4" class="concordgrey">
				&nbsp;<br/>
				This limit allows you to control the amount of disk space that MySQL operations - such as 
				calculating distributions or collocations - can take up at one go from each user.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<th class="concordtable">Username</th>
			<th class="concordtable">Current limit</th>
			<th class="concordtable">New limit</th>
			<th class="concordtable">Update</th>
		</tr>
		
		<?php
		$limit_options = "<option value=\"{$user->username}#max_dbsize#100\" selected=\"selected\">100</option>\n";
		for ($n = 100, $i = 1; $i < 8; $i++)
		{
			$n *= 10;
			$w = number_format((float)$n);
			$limit_options .= "<option value=\"{$user->username}#max_dbsize#$n\">$w</option>\n";
		}
		?>
		<form action="index.php" method="get">
			<tr>
				<td class="concordgeneral"><strong><?php echo $user->username;?></strong></td>
				<td class="concordgeneral" align="center">
					<?php echo number_format((float)$user->max_dbsize); ?>
				</td>
				<td class="concordgeneral" align="center">
					<select name="args">
						<?php echo $limit_options; ?>
					</select>
				</td>
				<td class="concordgeneral" align="center"><input type="submit" value="Go!" /></td>
			</tr>
			<input type="hidden" name="admFunction" value="execute"/>
			<input type="hidden" name="function" value="update_user_setting"/>
			<input type="hidden" name="locationAfter" value="index.php?thisF=userView&username=<?php echo $user->username; ?>&uT=y"/>
			<input type="hidden" name="uT" value="y" />
		</form>
		
	</table>
	
	
	<!-- ****************************************************************** -->
	
	
	<table class="concordtable" width="100%">

		<tr>
			<th colspan="3" class="concordtable">
				Reset the user's password
			</th>
		</tr>

		<form action="index.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Enter new password for user <b><?php echo $user->username; ?></b>:
				</td>
				<td class="concordgeneral">
					<input type="text" name="newPassword" width="50" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="concordgeneral" align="center">
					<input type="submit" value="Reset this user's password" />
				</td>
			</tr>
			<input type="hidden" name="userForPasswordReset" value="<?php echo $user->username; ?>" />
			<input type="hidden" name="admFunction" value="resetUserPassword"/>
			<input type="hidden" name="locationAfter" value="index.php?thisF=userView&username=<?php echo $user->username; ?>&uT=y"/>
			<input type="hidden" name="uT" value="y" />
			<?php
			// TODO add JavaScript Are You Sure? Pop up to the submission button of this form 
			?>
		</form>

	</table>



	<?php
	
	// TODO: add privileges; add groups && group privileges ;  add indication of user files / user corpora
	// individual user privileges should have [x] boxes (as should group memeberships)
	
	// TODO add delete user w. JavaScript "are you sure" (zee useradmin_old for old v of form.)
	// TODO add reset user password button
	
	// TODO add the expiry functionality for both acft and password. 
	
}

function printquery_useradmin()
{
	printquery_useroverview();
	printquery_usersearchform();

	//TODO functionalise create user form; make the javascript less wasteful 
	//TODO add mass-logout button (delete cookie tokens)
	//TODO add link to list of unverified/inactive accounts (which should ahve "delete" buttons next to them, for easy clearout of the trash) 
	
	global $Config;
	
	/* before we start, add the javascript function that inserts password candidates */
	echo print_javascript_for_password_insert();
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">
				Create new user
			</th>
		</tr>
		<form action="index.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Enter the username you wish to create:
				</td>
				<td class="concordgeneral">
					<input type="text" name="newUsername" tabindex="31" width="30" onKeyUp="check_c_word(this)" />
				</td>
				<td class="concordgeneral" rowspan="4" align="center">
					<input type="submit" value="Create user account" tabindex="35" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter a new password for the specified user:
				</td>
				<td class="concordgeneral">
					<input type="text" id="passwordField" name="newPassword" tabindex="32" width="50" />
					<a class="menuItem" tabindex="33" onmouseover="return escape('Suggest a password')" onclick="insertPassword()">
						[+]
					</a>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter the user's email address:
				</td>
				<td class="concordgeneral">
					<input type="text" name="newEmail" tabindex="34" width="30" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Send verification email?
				</td>
				<td class="concordgeneral">
					<select name="verifyType">
						<?php echo ($Config->cqpweb_no_internet ? '' : '<option value="yes">Yes, send a verification email</option>'); ?>
						 
						<option value="no:Verify" selected="selected">No, auto-verify the account</option>
						<option value="no:DontVerify">No, and leave the account unverified</option>
					</select>
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="newUser"/>
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
		
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">
				Create a batch of user accounts
			</th>
		</tr>
		<form action="index.php" method="GET">
			<tr>
				<td class="concordgeneral">
					Enter the root for the batch of usernames:
				</td>
				<td class="concordgeneral">
					<input type="text" name="newUsername" width="30" onKeyUp="check_c_word(this)" />
				</td>
				<td class="concordgeneral" rowspan="4">
					<input type="submit" value="Create batch of users" />
					<br/>&nbsp;<br/>
					<input type="reset" value="Clear form" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter the number of accounts in the batch:
					<br/>
					<em>(Usernames will have the numbers 1 to N appended to them)</em>
				</td>
				<td class="concordgeneral">
					<input type="text" name="sizeOfBatch" width="30" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter a password for all accounts in the batch:
				</td>
				<td class="concordgeneral">
					<input type="text" name="newPassword" width="30" />
				</td>
			</tr>

			<tr>
				<td class="concordgeneral">
					Select a group for the new users to be assigned to:
				</td>
				<td class="concordgeneral">
					<select name="batchAutogroup">
						<option value="" selected="selected">Do not assign new users to a group</option>
						<?php 
						foreach(get_list_of_groups() as $g)
							if ($g != 'everybody' && $g != 'superusers')
								echo '<option>', $g, "\n";
						?>
					</select>
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="newBatchOfUsers"/>
			<input type="hidden" name="uT" value="y" />
		</form>
		
		<tr>
			<td colspan="3" class="concordgrey">
				<b>Note</b>: Use this function with caution, as it is a potential security hole (password known to more than one person)!
				<br>&nbsp;<br>
				A typical use-case would be to create a set of accounts for a demonstration, then delete them (automatable via commandline
				but not currently in the web interface - sorry).
				<br>&nbsp;<br>
				In future it will be possible to set an expiry date on user accounts: batch-created accounts will then have a nearby
				expiry set on them. However, this is not possible yet, so rememebr to delete the accounts.
			</td>
		</tr>
	</table>
	
	<?php
}

function printquery_useradmin_old()
{
	global $Config;
	
	$array_of_users = get_list_of_users();
	
	$user_list_as_options = '';
	foreach ($array_of_users as $a)
		$user_list_as_options .= "<option>$a</option>\n";
	
	/* before we start, add the javascript function that inserts password candidates */
	
	echo print_javascript_for_password_insert();
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">
				Create new user
			</th>
		</tr>
		<form action="index.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Enter the username you wish to create:
				</td>
				<td class="concordgeneral">
					<input type="text" name="newUsername" tabindex="1" width="30" onKeyUp="check_c_word(this)" />
				</td>
				<td class="concordgeneral" rowspan="4">
					<input type="submit" value="Create user account" tabindex="5" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter a new password for the specified user:
				</td>
				<td class="concordgeneral">
					<input type="text" id="passwordField" name="newPassword" tabindex="2" width="50" />
					<a class="menuItem" tabindex="3"
						onmouseover="return escape('Suggest a password')" onclick="insertPassword()">
						[+]
					</a>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter the user's email address:
				</td>
				<td class="concordgeneral">
					<input type="text" name="newEmail" tabindex="4" width="30" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Send verification email?
				</td>
				<td class="concordgeneral">
					<select name="verifyType">
						<?php echo ($Config->cqpweb_no_internet ? '' : '<option value="yes">Yes, send a verification email</option>'); ?>
						 
						<option value="no:Verify" selected="selected">No, auto-verify the account</option>
						<option value="no:DontVerify">No, and leave the account unverified</option>
					</select>
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="newUser"/>
			<input type="hidden" name="uT" value="y" />
		</form>
		
		
		<tr>
			<th colspan="3" class="concordtable">
				Reset a user's password
			</th>
		</tr>

		<form action="index.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Select the user for password reset:
				</td>
				<td class="concordgeneral">
					<select name="userForPasswordReset">
						<option>Select user ....</option>
						<?php echo $user_list_as_options; ?>
					</select>
				</td>
				<td class="concordgeneral" rowspan="2">
					<input type="submit" value="Reset this user's password" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter new password:
				</td>
				<td class="concordgeneral">
					<input type="text" name="newPassword" width="50" />
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="resetUserPassword"/>
			<input type="hidden" name="uT" value="y" />
			<?php
			// TODO add JavaScript Are You Sure? Pop up to the submission button of this form 
			?>
		</form>

		
		<tr>
			<th colspan="3" class="concordtable">
				Delete a user account
			</th>
		</tr>
		<form action="index.php" method="GET">
			<tr>
				<td class="concordgeneral">
					Select a user to delete:
				</td>
				<td class="concordgeneral">
					<select name="userToDelete">
						<option>Select user ....</option>
						<?php echo $user_list_as_options; ?>
					</select>
				</td>
				<td class="concordgeneral">
					<input type="submit" value="Delete this user's account" />
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="deleteUser"/>
			<input type="hidden" name="uT" value="y" />
			<?php
			// TODO add JavaScript Are You Sure? Pop up to the submission button of this form 
			?>
		</form>
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="4" class="concordtable">
				Set user's maximum database size
			</th>
		</tr>
		<tr>
			<td colspan="4" class="concordgrey">
				&nbsp;<br/>
				This limit allows you to control the amount of disk space that MySQL operations - such as 
				calculating distributions or collocations - can take up at one go from each user.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<th class="concordtable">Username</th>
			<th class="concordtable">Current limit</th>
			<th class="concordtable">New limit</th>
			<th class="concordtable">Update</th>
		</tr>
		
		<?php
		$result = do_mysql_query("SELECT username, max_dbsize from user_info");
		
		while (($r = mysql_fetch_assoc($result)) !== false)
		{
			$limit_options 
				= "<option value=\"{$r['username']}#max_dbsize#100\" selected=\"selected\">100</option>\n";
			for ($n = 100, $i = 1; $i < 8; $i++)
			{
				$n *= 10;
				$w = number_format((float)$n);
				$limit_options .= "<option value=\"{$r['username']}#max_dbsize#$n\">$w</option>\n";
			}
			?>
			<form action="index.php" method="get">
				<tr>
					<td class="concordgeneral"><strong><?php echo $r['username'];?></strong></td>
					<td class="concordgeneral" align="center">
						<?php echo number_format((float)$r['max_dbsize']); ?>
   					</td>
					<td class="concordgeneral" align="center">
						<select name="args">
							<?php echo $limit_options; ?>
						</select>
					</td>
					<td class="concordgeneral" align="center"><input type="submit" value="Go!" /></td>
				</tr>
				<input type="hidden" name="admFunction" value="execute"/>
				<input type="hidden" name="function" value="update_user_setting"/>
				<input type="hidden" name="locationAfter" value="index.php?thisF=userAdmin&uT=y"/>
				<input type="hidden" name="uT" value="y" />
			</form>
			<?php
		}
		?>
		
	</table>

	<?php
}


function printquery_userdelete()
{
	?>
	<table class="concordtable" width="100%">
		<?php 
		
		/* we expect this form to be accessed from another form which populates this member of $_GET */
		if (empty($_GET['checkUserDelete']))
			exiterror("No ID specified for account to delete!");
		else
			$checkname = cqpweb_handle_enforce($_GET['checkUserDelete']);
		
		if (false === ($user = get_user_info($checkname)))
			exiterror("User $checkname doesn't exist: account can't be deleted.");
			
		if (user_is_superuser($checkname))
			exiterror("It's not possible to delete superuser accounts.");
		
		/* all is OK, so print the form. */
		
		?>
		<tr>
			<th class="concordtable">
				Totally delete account with username &ldquo;<?php echo $checkname; ?>&rdquo;? 
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" align="center">
				Real name:  <em><?php echo empty($user->realname)    ? '[unset]' : escape_html($user->realname);    ?></em>
				|
				Affilation: <em><?php echo empty($user->affiliation) ? '[unset]' : escape_html($user->affiliation); ?></em>
				|
				Email:      <em><?php echo empty($user->email)       ? '[unset]' : escape_html($user->email);       ?></em>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" align="center">
				Are you sure you want to do this?
				<br>&nbsp;<br>
				Deleting a user account also deletes <b>all</b> their saved data and uploaded files.
			</td>
		</tr>
		<tr>
			<td class="concordgeneral" align="center">
				<form action="index.php" method="get">
					<br/>
					<input type="checkbox" name="sureyouwantto" value="yes"/>
					Yes, I'm sure I want to do this.
					<br/>&nbsp;<br/>
					<input type="submit" value="I am definitely sure I want to delete this user's account." />
					<br/>
					<input type="hidden" name="admFunction" value="deleteUser" />
					<input type="hidden" name="userToDelete" value="<?php echo $checkname; ?>" />
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
	</table>					
		
	<?php	
}


function printquery_groupadmin()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="7" class="concordtable">
				Manage user groups
			</th>
		</tr>
		<tr>
			<th class="concordtable">ID</th>
			<th class="concordtable">Group</th>
			<th class="concordtable">Description</th>
			<th class="concordtable">Auto-add regex</th>
			<th class="concordtable">Update</th>
			<th class="concordtable">Delete</th>
		</tr>
	<?php

	foreach (get_all_groups_info() as $group)
	{
		echo "\n\t\t\t<tr>\n";
		?>
		<form action="index.php" method="GET">
			<td class="concordgeneral" align="center">
				<strong><?php echo $group->id; ?></strong>
			</td>
			<td class="concordgeneral" align="center">
				<strong><?php echo $group->group_name; ?></strong>
			</td>
			<td class="concordgeneral"  align="center">
				<?php
				if ($group->group_name == 'everybody')
					echo '<em>Group to which all users automatically belong.</em>';
				else if ($group->group_name == 'superusers')
					echo '<em>Only admin accounts belong to this group.</em>';
				else
					echo '<input type="text" maxlength="255" size="50" name="newGroupDesc" value="'
						, escape_html($group->description)
						, '" />';
				?>
			</td>
			
			<?php
			if ($group->group_name == 'superusers' || $group->group_name == 'everybody')
				echo '<td class="concordgeneral" colspan="3">&nbsp;</td>', "\n";				
			else
			{

				?>
				<td class="concordgeneral"  align="center">
					<input type="text" maxlength="255" size="50" name="newGroupAutojoinRegex" value="<?php
						echo escape_html($group->autojoin_regex);
					?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Click to update" />
				</td>
				<?php
			}
			?>
			
			<input type="hidden" name="admFunction" value="updateGroupInfo" />
			<input type="hidden" name="groupToUpdate" value="<?php echo $group->group_name; ?>" />
			<input type="hidden" name="uT" value="y" />
		</form>
	
		<?php 
		if ( ! ($group->group_name == 'superusers' || $group->group_name == 'everybody') )
		{
			?>
			<td class="concordgeneral" align="center">
				<a class="menuItem" href="index.php?admFunction=execute&function=delete_group&args=<?php
				echo $group->group_name, '&locationAfter=', urlencode('index.php?thisF=groupAdmin&uT=y');
				?>&uT=y">
					[x]
				</a>
			</td>
			<?php
		}
		echo "\n\t\t\t</tr>\n";
	}
	?>
	<tr>
		<td class="concordgrey" colspan="6">
			&nbsp;<br/>
			The &ldquo;description&rdquo; will be visible in various places in the user interface (to users as well
			as to system administrators).
			<br/>&nbsp;<br/>
			The &ldquo;auto-add regex&rdquo; determines which users will be added automatically to this group at time of
			account creation.
			<br/>&nbsp;<br/>
			Any new user whose email address matches the regular expression given here will automatically be added to
			the group in question. For example, if you set the regex to <b>(\.edu|\.ac\.uk)$</b> then all users with
			email addresses that end in .edu or .ac.uk (i.e. US and UK academic addresses) will be added to the group
			automatically. Regexes use <a href="" target="_blank">PCRE syntax</a>.
			<br/>&nbsp;<br/>
			(Note this only affects <em>new</em> user accounts, i.e. if you add or change a regex, existing accounts
			will <em>not</em> be added to the group. You can perform a 
			<em><a href="index.php?thisF=groupMembership&uT=y">bulk add</a></em> 
			to accomplish that.)
			<br/>&nbsp;
		</td>
	</tr>
	</table>
	
	<table class="concordtable" width="100%">
		<form action="index.php" method="get">
			<tr>
				<th colspan="3" class="concordtable">
					Add new group
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<br/>
					Enter the name for the new group:
					<br/>
					&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					<br/>
					<input type="text" maxlength="20" name="args" onKeyUp="check_c_word(this)" >
					<br/>
					&nbsp;
				<td class="concordgeneral" align="center">
					<br/>
					<input type="submit" value="Add this group to the system"/>
					<br/>
					&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="execute" />
			<input type="hidden" name="function" value="add_new_group" />
			<input type="hidden" name="locationAfter" value="index.php?thisF=groupAdmin&uT=y" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	
	<?php
}


function printquery_groupmembership()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="7" class="concordtable">
				Manage user groups
			</th>
		</tr>
		<tr>
			<th class="concordtable">Group</th>
			<th class="concordtable">Members</th>
			<th class="concordtable" colspan="2">Add member</th>			
			<th class="concordtable" colspan="2">Remove member</th>	
		</tr>
	<?php
	
	$group_list = get_list_of_groups();
	
	foreach ($group_list as $group)
	{
		echo '<tr>';
		echo '<td class="concordgeneral"><strong>' . $group . '</strong></td>';

		$member_list = list_users_in_group($group);
		sort($member_list);
		echo "\n<td class=\"concordgeneral\">";
		$i = 0;
		if ($group == 'everybody')
			echo '<em>All users are members of this group.</em>';
		else
		{
			foreach ($member_list as &$member)
			{
				echo $member . ' ';
				$i++;
				if ($i == 5)
				{
					echo "<br/>\n";
					$i = 0;
				}
			}
		}
		if (empty($member_list))echo '&nbsp;';
		echo '</td>';
		
		if ($group == 'superusers' || $group == 'everybody')
		{
			echo '<td class="concordgeneral" colspan="4">&nbsp;</td>';
			continue;
		}
		
		$members_not_in_group = array_diff(get_list_of_users(), $member_list);
		$options = "<option>[Select user from list]</option>\n";
		foreach ($members_not_in_group as &$m)
			$options .= "<option>$m</option>\n";		
		echo "<form action=\"index.php\" method=\"GET\">
			<td class=\"concordgeneral\" align=\"center\">
			<select name=\"userToAdd\">$options</select></td>\n";
		echo "<td class=\"concordgeneral\" align=\"center\">
			<input type=\"submit\" value=\"Add user to group\" /></td>\n";
		echo "<input type=\"hidden\" name=\"admFunction\" value=\"addUserToGroup\" />
			<input type=\"hidden\" name=\"groupToAddTo\" value=\"$group\" />
			<input type=\"hidden\" name=\"uT\" value=\"y\" /></form>\n";
		
		$options = "<option>[Select user from list]</option>\n";
		foreach ($member_list as &$m)
			$options .= "<option>$m</option>\n";
		echo "<form action=\"index.php\" method=\"GET\">\n
			<td class=\"concordgeneral\" align=\"center\">
			<select name=\"userToRemove\">$options</select></td>\n";
		echo "<td class=\"concordgeneral\" align=\"center\">
			<input type=\"submit\" value=\"Remove user from group\" /></td>\n";
		echo "<input type=\"hidden\" name=\"admFunction\" value=\"removeUserFromGroup\" />
			<input type=\"hidden\" name=\"groupToRemoveFrom\" value=\"$group\" />
			<input type=\"hidden\" name=\"uT\" value=\"y\" /></form>\n";
				
		echo '</tr>';
	}
	?>
	</table>

	<?php
	
	$g_opts = '';
	
	foreach ($group_list as $g)
		if ($g != 'superusers' && $g != 'everybody')
			$g_opts .= "\n\t\t\t\t\t\t<option value=\"$g\">$g</option>\n";
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				Bulk Add:
				<br/>
				<em>Add users to group by email address pattern-match</em>
			</th>
		<tr>
			<form action="index.php" method="get">
				<td class="concordgrey" width="50%">
					<p>&nbsp;</p>
					
					<p>
						Apply group's stored pattern-match to existing users
						<br/>&nbsp;<br/>
						<i>by default, the group auto-add regex only applies to <u>new</u>
						accounts; this function adds any existing users whose emails match
						that regex to the group in question.</i>
					</p>
					
					<p>&nbsp;</p>
				</td>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					
					<p>Select group:</p>
					
					<select name="group">
						<option value="">[Select a group...]</option>
						<?php echo $g_opts; ?>

					</select>
					
					<br/>&nbsp;<br/>
					
					<input type="submit" value="Click here to run group regex against existing users" />
					
					<p>&nbsp;</p>
				</td>
				<input type="hidden" name="admFunction" value="groupRegexRerun" />
				<input type="hidden" name="uT" value="y" />
			</form>
		</tr>
		<tr>
			<form action="index.php" method="get">
				<td class="concordgrey">
					<p>&nbsp;</p>
					
					<p>Apply one-off custom regex to all existing users:</p>
					
					<p>&nbsp;</p>
				</td>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					
					<p>Select group:</p>
					
					<select name="group">
						<option value="">[Select a group...]</option>
						<?php echo $g_opts; ?>
											
					</select>
					
					<p>Enter the regex to apply:</p>
					
					<input type="text" maxlength="255" size="50" name="regex" />
					
					<br/>&nbsp;<br/>
					
					<input type="submit" value="Click here to add all users matching this regex to the group specified" />
					
					<p>&nbsp;</p>
				</td>
				<input type="hidden" name="admFunction" value="groupRegexApplyCustom" />
				<input type="hidden" name="uT" value="y" />
			</form>
		</tr>
		<!--
		<tr>
			<td class="concordgrey">
				<p>&nbsp;</p>
				
				<p>This functionality is coming soon.</p>
				
				<p>&nbsp;</p>
			</td>
		</tr>
		-->
	</table>
	<?php
	
	//TODO : bulk add users
}





function printquery_privilegeadmin()
{
	global $Config;
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="5">
				Manage privileges
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="5">
				&nbsp;<br/>
				&ldquo;Privileges&rdquo; are rights to use different aspects of the CQPweb system: corpora,
				plugins, and so on. Once defined, privileges can be assigned (&ldquo;granted&rdquo;)
				individually to users and/or collectively to groups of users.
				<br/>&nbsp;<br/>
				What users are able to do when logged on to CQPweb is defined by the privileges that have
				been granted to them.
				<br/>&nbsp;
			</td>
		</tr>
		<tr>
			<th class="concordtable" colspan="5">
				Existing privileges
			</th>
		</tr>
		<tr>
			<th class="concordtable">
				ID
			</th>
			<th class="concordtable">
				Description
			</th>
			<th class="concordtable">
				Type
			</th>
			<th class="concordtable">
				Scope
			</th>
			<th class="concordtable">
				Actions
			</th>
		</tr>
		
		<?php
		foreach (get_all_privileges_info() as $p)
		{
			$scope_cell_string = print_privilege_scope_as_html($p->type, $p->scope_object);
			
			echo "<tr>"
				, "<td class=\"concordgeneral\" align=\"center\">{$p->id}</td>"
				, "<td class=\"concordgeneral\"><em>{$p->description}</em></td>"
				, "<td class=\"concordgeneral\">{$Config->privilege_type_descriptions[$p->type]}</td>"
				, "<td class=\"concordgeneral\">$scope_cell_string</td>"
				, "<td class=\"concordgeneral\" align=\"center\">"
					, "<a class=\"menuItem\" href=\"index.php?admFunction=deletePrivilege&privilege={$p->id}&uT=y\">[Delete]</a>"
				, "</td>"
				, "</tr>\n";
		}
		?>
		
	</table>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">
				Create a new privilege
			</th>
		</tr>
			<td class="concordgrey" colspan="2">
				&nbsp;<br/>
				Adding a customised privilege will be added soon, for now use "generate default privileges" below.
				<br/>&nbsp;
			</td>
		<tr>
			<th class="concordtable" colspan="2">
				Generate default privileges
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="2">
				&nbsp;<br/>
				The &ldquo;default&rdquo; privileges are:
				<ul>
					<li>A <em>full access</em> privilege for each corpus;</li>
					<li>A <em>normal access</em> privilege for each corpus;</li>
					<li>A <em>restricted access</em> privilege for each corpus.</li>
				</ul>
				Generating default privileges creates these three privileges for each corpus on the system,
				if those privileges do not exist already. Existing privileges are not affected.
				<br/>&nbsp;<br/>
				In addition, four levels of privilege are generated for frequency-list creation.
				Users can only build frequency lists for subcorpora if they have a privilege that
				covers a subcorpus of that size. The automatically-created levels are one, ten, 
				twenty-five and one hundred million tokens. 
				<br/>&nbsp;<br/>
				(At least one such privilege should be granted to the "everybody" group, 
				or some users may not be able to create frequency lists at all.)  
				<br/>&nbsp;
			</td>
		</tr>
		<form action="index.php" method="GET">
			<tr>
				<td class="concordgeneral">
					&nbsp;<br/>
					<b>Generate default privileges for corpus...</b>
					<select name="corpus">
					
						<option selected="selected">[Select a corpus...]</option>
						<?php
						foreach(list_corpora() as $c)
							echo "\t\t\t\t\t\t<option value=\"$c\">$c</option>\n";
						?>
						
					</select>
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Generate default privileges for this corpus" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="generateDefaultPrivileges" />
			<input type="hidden" name="uT" value="y" />
		</form>
		<form action="index.php" method="GET">
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<input type="submit" value="Generate all default privileges" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="generateDefaultPrivileges" />
			<input type="hidden" name="corpus" value="~~all~~" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	<?php
}


function printquery_usergrants()
{
	$priv_desc = get_all_privilege_descriptions();
	$user_list = get_list_of_users();
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Manage grants of privileges to users
			</th>
		</tr>
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="3">
				Grant new privilege to user
			</th>
		</tr>
		<form action="index.php" method="GET">
			<tr>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					Select user:
					<select name="user">
						<option value="">[Select a user...]</option>
						<?php
						foreach ($user_list as $u)
							echo "\n\t\t\t\t\t\t<option value=\"$u\">$u</option>\n";
						?> 
					</select>
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					Select privilege:
					<select name="privilege">
						<option value="">[Select a privilege...]</option>
						<?php
						foreach ($priv_desc as $id => $desc)
							echo "\n\t\t\t\t\t\t<option value=\"$id\">$id: $desc</option>\n";
						?> 
					</select>
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Grant privilege to user!" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="newGrantToUser" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">
				Existing grants to individual users
			</th>
		</tr>
		<tr>
			<th class="concordtable">
				Username
			</th>
			<th class="concordtable">
				Privilege
			</th>
			<th class="concordtable">
				Expiry time
			</th>
			<th class="concordtable">
				Delete
			</th>
		</tr>
		
		<?php
		
		$at_least_one_row_written = false;
				
		foreach($user_list as $user)
		{
			$grants = list_user_grants($user);
			
			$nrows = count($grants);
			
			$firstgrant = true;
			
			foreach($grants as $g)
			{
				$at_least_one_row_written = true;
				echo "<tr>"
					, ($firstgrant ? "<td class=\"concordgeneral\" align=\"center\" rowspan=\"$nrows\">$user</td>" : '')
					, "<td class=\"concordgeneral\" align=\"center\"><b>{$g->privilege_id}</b>: {$priv_desc[$g->privilege_id]}</td>"
					, "<td class=\"concordgeneral\" align=\"center\">"
 						, ($g->expiry_time < 1 ? 'Never' : date(CQPWEB_UI_DATE_FORMAT, $g->expiry_time))
 					, "</td>"
					, "<td class=\"concordgeneral\" align=\"center\">"
					, "<a class=\"menuItem\" href=\"index.php?admFunction=removeUserGrant&user=$user&privilege={$g->privilege_id}&uT=y\">[x]</a>"
					, "</td>"
					, "</tr>";
				$firstgrant = false;
			}
		}
		
		if ( ! $at_least_one_row_written)
			echo "<tr><td class=\"concordgrey\" colspan=\"4\" align=\"center\">"
				, "&nbsp;<br/>There are currently no individual-user grants.<br/>&nbsp;</td></tr>";
		
		?>
		
	</table>

	<?php
}


function printquery_groupgrants()
{
	$priv_desc = get_all_privilege_descriptions();
	$group_list = get_list_of_groups();
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Manage grants of privileges to groups
			</th>
		</tr>
	</table>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="3">
				Grant new privilege to group
			</th>
		</tr>
		<form action="index.php" method="GET">
			<tr>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					Select group:
					<select name="group">
						<option value="">[Select a group...]</option>
						<?php
						foreach ($group_list as $g)
							if ($g != 'superusers')
								echo "\n\t\t\t\t\t\t<option value=\"$g\">$g</option>\n";
						?> 
					</select>
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					Select privilege:
					<select name="privilege">
						<option value="">[Select a privilege...]</option>
						<?php
						foreach ($priv_desc as $id => $desc)
							echo "\n\t\t\t\t\t\t<option value=\"$id\">$id: $desc</option>\n";
						?> 
					</select>
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Grant privilege to group!" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="newGrantToGroup" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>


	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">
				Clone a group&rsquo;s granted privileges
			</th>
		</tr>
		
		<tr>
			<td colspan="3" class="concordgrey">
				&nbsp;<br/>
				If you "clone" privilege grants from Group A to Group B, you overwrite all the current privileges
				of Group B; it will have exactly the same set of privileges as Group A.
				<br/>&nbsp;
			</td>
		</tr>
		
		<?php
		
		$clone_group_options = '<option value="">[Select a group...]</option>';
		foreach ($group_list as $group)
		{
			if ($group == 'superusers')
				continue;
			$clone_group_options .= "<option>$group</option>\n";
		}
		
		?>
		
		<form action="index.php" method="get">
		
			<tr>
				<td class="concordgeneral">
					&nbsp;<br/>
					Clone from:
					<select name="groupCloneFrom">
						<?php echo $clone_group_options; ?>
					</select>
					<br/>&nbsp;
				</td>
				<td class="concordgeneral">
					&nbsp;<br/>
					Clone to:
					<select name="groupCloneTo">
						<?php echo $clone_group_options; ?>
					</select>
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					&nbsp;<br/>
					<input type="submit" value="Clone access rights!" />
					<br/>&nbsp;
				</td>
			</tr>
			
			<input type="hidden" name="admFunction" value="cloneGroupGrants"/>
			<input type="hidden" name="uT" value ="y" />
			
		</form>

	</table>		

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">
				Existing grants to user groups
			</th>
		</tr>
		<tr>
			<th class="concordtable">
				Group
			</th>
			<th class="concordtable">
				Privilege
			</th>
			<th class="concordtable">
				Expiry time
			</th>
			<th class="concordtable">
				Delete
			</th>
		</tr>
		
		<?php
		
		foreach($group_list as $group)
		{
			$grants = list_group_grants($group);
			
			if ($group == 'superusers')
				echo "\t\t<tr>\n\t\t\t<td class=\"concordgeneral\" align=\"center\" rowspan=\"1\"><b>superusers</b></td>\n"
					, "\t\t\t<td class=\"concordgrey\" align=\"center\" colspan=\"3\"><em>This group always has all privileges.</em></td>\n"
					, "\t\t</tr>";
			else
			{
				if (empty($grants))
				echo "\t\t<tr>\n\t\t\t<td class=\"concordgeneral\" align=\"center\" rowspan=\"1\"><b>$group</b></td>\n"
					, "\t\t\t<td class=\"concordgrey\" align=\"center\" colspan=\"3\"><em>This group currently has no granted privileges.</em></td>\n"
					, "\t\t</tr>";
				else
				{
					if (0 == ($nrows = count($grants)))
						++$nrows;
					$firstgrant = true;	

					foreach($grants as $g)
					{
						echo "<tr>"
							, ($firstgrant ? "<td class=\"concordgeneral\" align=\"center\" rowspan=\"$nrows\"><b>$group</b></td>" : '')
							, "<td class=\"concordgeneral\" align=\"center\"><b>{$g->privilege_id}</b>: {$priv_desc[$g->privilege_id]}</td>"
							, "<td class=\"concordgeneral\" align=\"center\">"
								, ($g->expiry_time < 1 ? 'Never' : date(CQPWEB_UI_DATE_FORMAT, $g->expiry_time))
							, "</td>"
							, "<td class=\"concordgeneral\" align=\"center\">"
							, "<a class=\"menuItem\" href=\"index.php?admFunction=removeGroupGrant&group=$group&privilege={$g->privilege_id}&uT=y\">[x]</a>"
							, "</td>"
							, "</tr>"
							;
						$firstgrant = false;
					}
				}
			}
		}
	
		?>
	</table>	
	

	<?php
}


function printquery_skins()
{
	global $Config;
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">
				Skins and colour schemes
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" colspan="4">
				&nbsp;<br/>
				Use the button below to re-generate built-in colour schemes:
				<br/>
				<form action="index.php" method="GET">
					<center>
						<input type="submit" value="Regenerate colour schemes!" />
					</center>
					<input type="hidden" name="admFunction" value="regenerateCSS"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" colspan="4">
				&nbsp;<br/>
				Listed below are the CSS files currently present in the upload area which do 
				<em>not</em> already appear in the main <em>css</em> directory.
				Select a file and click &ldquo;Import!&rdquo; 
				to create a copy of the file in the <em>css</em> directory.  
				<br/>&nbsp;
			</td>
		<tr>
			<th class="concordtable">Transfer?</th>
			<th class="concordtable">Filename</th>
			<th class="concordtable">Size (K)</th>
			<th class="concordtable">Date modified</th>
		</tr>
		<form action="index.php" method="GET">
			<?php
			$file_list = scandir($Config->dir->upload);
			
			foreach ($file_list as &$f)
			{
				$file = "{$Config->dir->upload}/$f";
				$target = "../css/$f";
				
				if (!is_file($file)) continue;	
				if (substr($f,-4) !== '.css') continue;
				if (is_file($target)) continue;
	
				$stat = stat($file);
				?>
				
				<tr>
					<td class="concordgeneral" align="center">
						<?php 
						echo '<input type="radio" name="cssFile" value="' . urlencode($f) . '" />'; 
						?>
					</td>
					
					<td class="concordgeneral" align="left"><?php echo $f; ?></td>
					
					<td class="concordgeneral" align="right";>
						<?php echo number_format(round($stat['size']/1024, 0)); ?>
					</td>
				
					<td class="concordgeneral" align="center">
						<?php echo date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']); ?>
					</td>		
				</tr>
				<?php
			}
			?>
			<tr>
				<td class="concordgrey" align="center" colspan="4">
					&nbsp;<br/>
					<input type="submit" value="Transfer" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="transferStylesheetFile" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	<?php
}



function printquery_mappingtables()
{
	$show_existing = ( isset($_GET['showExisting']) ? (bool)$_GET['showExisting'] : false );
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="3">
				Mapping tables
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="3">
				&nbsp;<br/>
				
				&ldquo;Mapping tables&rdquo; are used in the Common Elementary Query Language (CEQL)
				system (aka &ldquo;Simple query&rdquo;).
				
				<br/>&nbsp;<br/>
				
				They transform <em>the tag the user searches for</em> (referred to as an 
				<strong>alias</strong>) into <em>the tag that actually occurs in the corpus</em>, or 
				alternatively into <em>a regular expression covering a group of tags</em> (referred to
				as the <strong>search term</strong>.
				
				<br/>&nbsp;<br/>
				
				Each alias-to-search-term mapping has the form "ALIAS" => "SEARCH TERM".  
					
				<br/>&nbsp;<br/>
				
				<?php
				
				echo '<a href="index.php?thisF=mappingTables&showExisting='
					. ($show_existing ? '0' : '1')
					. '&uT=y">Click here '
					. ($show_existing ? 'to add a new mapping table' : 'to view all stored mapping tables')
					. "</a>.\n\n";
				?>
				<br/>&nbsp;
			</td>
		</tr>
		<?php
		if ($show_existing)
		{
			/* show existing mapping tables */
			?>
			<tr>
				<th class="concordtable" colspan="3">
					Currently stored mapping tables
				</th>
			</tr>
			<tr>
				<th class="concordtable">Name (and <em>handle</em>)</th>
				<th class="concordtable">Mapping table</th>
				<th class="concordtable">Actions</th>
			</tr>
			
			<?php
			foreach(get_all_tertiary_mapping_tables() as $table)
			{
				echo '<tr>'
					. '<td class="concordgeneral">' . $table->name . ' <br/>&nbsp;<br/>(<em>' . $table->handle . '</em>)</td>'
					. '<td class="concordgeneral"><font size="-2" face="courier new, monospace">' 
					. strtr($table->mappings, array("\n"=>'<br/>', "\t"=>'&nbsp;&nbsp;&nbsp;') )
					. '</font></td>'
					. '<td class="concordgeneral" align="center">'
					. '<a class="menuItem" href="index.php?admFunction=execute&function=drop_tertiary_mapping_table&args=' 
					. $table->handle . '&locationAfter=' . urlencode('index.php?thisF=mappingTables&showExisting=1&uT=y') 
					. '&uT=y">[Delete]</a></td>'
					. "</tr>\n\n";	
			}

		}
		else
		{
			/* add new mapping table */
			?>
			<tr>
				<th class="concordtable" colspan="3">
					Create a new mapping table
				</th>
			</tr>
			<tr>
				<td class="concordgrey" colspan="3">
					Your mapping table must start and end in a brace <strong>{ }</strong> ; each 
					alias-to-search-term mapping but the last must be followed by a comma. 
					Use perl-style escapes for quotation marks where necessary.
					
					<br/>&nbsp;<br/>
					
					You are strongly advised to save an offline copy of your mapping table,
					as it is a lot of work to recreate if it accidentally gets deleted from
					the database.
				</td>
			</tr>
			<form action="index.php" method="get">
				<tr>
					<td class="concordgeneral" align="center" valign="top">
						Enter an ID code
						<br/> 
						(letters, numbers, and _ only)
						<br/>&nbsp;<br/>
						<input type="text" size="30" name="newMappingTableId" onKeyUp="check_c_word(this)" />
					</td>
					<td class="concordgeneral" align="center" valign="top">
						Enter the name of the mapping table:
						<br/>&nbsp;<br/>&nbsp;<br/>
						<input type="text" size="30" name="newMappingTableName"/>
					</td>
					<td class="concordgeneral" align="center" valign="top">
						Enter the mapping table code here:
						<br/>&nbsp;<br/>&nbsp;<br/>
						<textarea name="newMappingTableCode" cols="60" rows="25"></textarea>					
					</td>				
				</tr>
				<tr>
					<td class="concordgeneral" colspan="3" align="center">
						<input type="submit" value="Create mapping table!"/>
					</td>				
				</tr>
				<input type="hidden" name="admFunction" value="newMappingTable" />
				<input type="hidden" name="uT" value="y" />
			</form>
			
			
			
			<?php
		}
		?>
		<tr>
			<th class="concordtable" colspan="3">
				Built-in mapping tables
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" colspan="3" align="center">
				CQPweb contains a number of built-in mapping tables, including the Oxford Simplified Tagset 
				devised for the BNC (highly recommended).
				<br/>&nbsp;<br/>
				Use the button below to insert them into the database.
				<br/>&nbsp;<br/>

				<form action="index.php" method="get">
					<input type="submit" value="Click here to regenerate built-in mapping tables."/>
					<br/>
					<input type="hidden" name="admFunction" value="execute" />
					<input type="hidden" name="function" value="regenerate_builtin_mapping_tables" />
					<input type="hidden" name="locationAfter" 
						value="index.php?thisF=mappingTables&showExisting=1&uT=y" />
					<input type="hidden" name="uT" value="y" />
				</form>					
			</td>
		</tr>
	</table>
	<?php
}






function printquery_systemsnapshots()
{
	global $Config;
	
	/* this dir needs to exist for us to scan it... */
	if (!is_dir($d = "{$Config->dir->upload}/dump"))
		mkdir($d);
	
	if (isset($_GET['snapshotFunction']))
		switch($_GET['snapshotFunction'])
		{
		case 'createSystemSnapshot':
			cqpweb_dump_snapshot("$d/CQPwebFullDump-" . time());
			break;
		case 'createUserdataBackup':
			cqpweb_dump_userdata("$d/dump/CQPwebUserDataDump-" . time());
			break;
		case 'undumpSystemSnapshot':
			/* check that the argument is an approrpiate-format undump file that exists */
			if 	(	preg_match('/^CQPwebFullDump-\d+$/', $_GET['undumpFile']) > 0
					&&
					is_file($_GET['undumpFile'])
				)
				/* call the function */
				cqpweb_undump_snapshot("$d/".$_GET['undumpFile']);
			else
				exiterror_parameter("Invalid filename, or file does not exist!");
			break;
		case 'undumpUserdataBackup':
			/* check that the argument is an approrpiate-format undump file that exists */
			if 	(	preg_match('/^CQPwebUserDataDump-\d+$/', $_GET['undumpFile']) > 0
					&&
					is_file($_GET['undumpFile'])
				)
				/* call the function */
				cqpweb_undump_userdata("$d/{$_GET['undumpFile']}");
			else
				exiterror_parameter("Invalid filename, or file does not exist!");
			break;
		default:
			break;
		}
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="3">
				CQPweb system snapshots
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="3">
				&nbsp;<br/>
				Use the button below to create a system snapshot (a zip file containing all the data from this
				CQPweb system's current state, <em>except</em> the CWB registry and data files).
				<br/>&nbsp;<br/>
				Snapshot files are create as .tar.gz files in the "dump" subdirectory of the upload area.
				<br/>&nbsp;<br/>
				Warning: snapshot files <em>can be very big.</em>
				<br/>&nbsp;<br/>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral" align="center" colspan="3">
				<form action="index.php" method="get">
					<br/>
					<input type="submit" value="Create a snapshot file!" />
					<br/>
					<input type="hidden" name="thisF" value="systemSnapshots"/>
					<input type="hidden" name="snapshotFunction" value="createSystemSnapshot"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" colspan="3">
				&nbsp;<br/>
				Use the button below to create a userdata backup (a zip file containing all the 
				<strong>irreplaceable</strong> data in the system).
				<br/>&nbsp;<br/>
				Currently, this means user-saved queries and categorised queries. It is assumed
				that the corpus itself and all associated metadata is <em>not</em> irreplaceable
				(as you will have your own backup systems in place) but that user-generated data
				<em>is</em>.
				<br/>&nbsp;<br/>
				These backups are placed initially in the same location as snapshot files, but
				you should move them as soon as possible to a backup location.
				<br/>&nbsp;<br/>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral" align="center" colspan="3">
				<form action="index.php" method="get">
					<br/>
					<input type="submit" value="Create a userdata backup file!" />
					<br/>
					<input type="hidden" name="thisF" value="systemSnapshots"/>
					<input type="hidden" name="snapshotFunction" value="createUserdataBackup"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
		<tr>
			<th class="concordtable" colspan="3">
				The following files currently exist in the "dump" directory.
			</th>
		</tr>
		<tr>
			<th class="concordtable">Filename</th>
			<th class="concordtable">Size (K)</th>
			<th class="concordtable">Date modified</th>
		</tr>
		<?php
		$num_files = 0;
		$file_options = "\n";
		$file_list = scandir($d);
		foreach ($file_list as &$f)
		{
			$file = "$d/$f";
			
			if (!is_file($file))
				continue;
			$stat = stat($file);
			$num_files++;
			
			$file_options .= "\t\t\t<option>$f</option>\n";

			?>
			<tr>
				<td class="concordgeneral" align="left">
					<?php echo $f; ?>
				</td>
				
				<td class="concordgeneral" align="right";>
					<?php echo number_format(round($stat['size']/1024, 0)); ?>
				</td>
				
				<td class="concordgeneral" align="center">
					<?php echo date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']); ?>
				</td>
			
			</tr>
			<?php
		}
		if ($num_files < 1)
			echo "\n\n\t<tr><td class='concordgrey' align='center' colspan='3'>
				&nbsp;<br/>This directory is currently empty.<br/>&nbsp;</td></tr>\n";

		?>
		<tr>
			<th class="concordtable" colspan="3">
				Undump system snapshot
			</th>
		<tr>
			<td class="concordgeneral" colspan="3">
				<strong>Warning: this function is experimental.</strong>
				<br/>&nbsp;<br/>
				It will overwrite the current state of the CQPweb system.
				<br/>&nbsp;<br/>
				Select a file from the "dump" directory:
				
				<form action="index.php" method="get">
					<select name="undumpFile">
						<?php 
						echo ($file_options == "\n" ? '<option>No undump files available</option>' : $file_options);
						?>
					</select>
					<br/>&nbsp;<br/>
					Press the button below to overwrite CQPweb with the contents of this snapshot:
					<br/>
					<input type="submit" value="Undump snapshot" />
					<input type="hidden" name="thisF" value="systemSnapshots"/>
					<input type="hidden" name="snapshotFunction" value="undumpSystemSnapshot"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
		<tr>
			<th class="concordtable" colspan="3">
				Reload backed-up userdata
			</th>
		<tr>
			<td class="concordgeneral" colspan="3">
				<strong>Warning: this function is experimental.</strong>
				<br/>&nbsp;<br/>
				It will overwrite any queries with the same name that are in the system already.
				<br/>&nbsp;<br/>
				Select a file from the "dump" directory:
				
				<form action="index.php" method="get">
					<select>
						<?php 
						echo ($file_options== "\n" ? '<option>No undump files available</option>' : $file_options);
						?>
					</select>
					<br/>&nbsp;<br/>
					Press the button below to overwrite CQPweb with the contents of this snapshot:
					<br/>
					<input type="submit" value="Reload user data" />
					<input type="hidden" name="thisF" value="systemSnapshots"/>
					<input type="hidden" name="snapshotFunction" value="undumpUserdataBackup"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
	</table>
	<?php
}


function printquery_systemdiagnostics()
{
	global $Config;

	if (empty($_GET['runDiagnostic']))
		$_GET['runDiagnostic'] = 'none';
		
	/* every case of this switch should print an entire table, then return */
	switch ($_GET['runDiagnostic'])
	{
	case 'general':
		//TODO
		return;
	
	
	case 'dbVersion':
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Database version check
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					&nbsp;<br/>
					The system database is at version <?php echo get_db_version(); ?>. 
					<br/>&nbsp;<br/>
					CQPweb's code is at version <?php echo CQPWEB_VERSION; ?>. 
					<br/>&nbsp;<br/>
					It is normal for the database version to be a little behind the code. 
					But if there is a major mismatch between the two, you may run into trouble.
					<br/>&nbsp;<br/>
					If in doubt, run the <b>upgrade-databse</b> script (see system adminisatrator's manual for detail).
					<br/>&nbsp;<br/>
				</td>
			</tr>
		</table>
		<?php
		return;
		
		
	case 'phpStubs':
		/*
		 * WE NO LONGER USE STUB FILES
		 */
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Done diagnosing issues with PHP inclusion scripts
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					NB. as of version 3.2 we no longer use stub files. So there is nothing to repair here.
					&nbsp;<br/>
				</td>
			</tr>
		</table>
		<?php
		return;
	
		
	case 'cqp':
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Diagnosing connection to child process for CQP back-end
				</th>
			</tr>
			<tr>
				<td class="concordgrey">
					<pre>
					<?php echo "\n" . CQP::diagnose_connection($Config->path_to_cwb, $Config->dir->registry) . "\n"; ?>
					</pre>
				</td>
			</tr>
		</table>
		<?php
		return;
		
		
	case 'none':
	default:
		/* this is the only route to the rest of the function */
		break;
	}
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				CQPweb system diagnostics
			</th>
		</tr>	
		
		<tr>
			<td class="concordgrey">
				&nbsp;<br/>
				Use the controls below to run diagnostics for parts of CQPweb that aren't working properly.
				<br/><b>UNDER DEVELOPMENT. Only some of them work.</b>
				<br/>&nbsp;<br/>
			</td>
		</tr>
		<tr>
			<th class="concordtable">
				Generalised problem check
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" align="center" colspan="3">
				<form action="index.php" method="get">
					<br/>
					<input type="submit" value="Run general check for common problems" />
					<br/>
					<input type="hidden" name="thisF" value="systemDiagnostics"/>
					<input type="hidden" name="runDiagnostic" value="general"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
		<tr>
			<th class="concordtable">
				Check corpus PHP inclusion files
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" align="center" colspan="3">
				<form action="index.php" method="get">
					<br/>
					<input type="submit" value="Run a check for missing PHP script inclusion files in corpus webfolders" />
					<br/>
					<input type="hidden" name="thisF" value="systemDiagnostics"/>
					<input type="hidden" name="runDiagnostic" value="phpStubs"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
		<tr>
			<th class="concordtable">
				Check database version
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" align="center" colspan="3">
				<form action="index.php" method="get">
					<br/>
					<input type="submit" value="Find out if the system database is out-of-sync with the CQPweb code" />
					<br/>
					<input type="hidden" name="thisF" value="systemDiagnostics"/>
					<input type="hidden" name="runDiagnostic" value="dbVersion"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
		<tr>
			<th class="concordtable">
				Check CQP back-end
			</th>
		</tr>
		<tr>
			<td class="concordgeneral" align="center" colspan="3">
				<form action="index.php" method="get">
					<br/>
					<input type="submit" value="Run a system check on the CQP back-end process connection" />
					<br/>
					<input type="hidden" name="thisF" value="systemDiagnostics"/>
					<input type="hidden" name="runDiagnostic" value="cqp"/>
					<input type="hidden" name="uT" value="y" />
				</form>
			</td>
		</tr>
	</table>
	<?php
}




function printquery_systemannouncements()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Add a system message
			</th>
		</tr>
		<form action="index.php" method="get">
			<tr>
				<td class="concordgeneral" align="center">
					<strong>Heading:</strong>
					<input type="text" name="systemMessageHeading" size="90" maxlength="100"/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" align="center">
					<textarea name="systemMessageContent" rows="5" cols="65" 
						style="font-size: 16px;"></textarea>
					<br/>&nbsp;<br/>
					<input type="submit" value="Add system message" />
					&nbsp;&nbsp;
					<input type="reset" value="Clear form" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="admFunction" value="addSystemMessage"/>
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	
	<?php
	display_system_messages();
}



function printquery_tableview()
{
	if (isset($_GET['limit']) && strlen($_GET['limit']) > 0 )
		$limit = mysql_real_escape_string($_GET['limit']);
	else
		$limit = "NO_LIMIT";

	
	if(isset($_GET['table']))
	{
		/* a table has already been chosen */
		
		?>
		<table class="concordtable" width="100%">
		
			<tr>
				<th class="concordtable">Viewing mySQL table
				<?php echo $_GET['table']; ?>
				</th>
			</tr>
			
		<tr><td class="concordgeneral">
		
		<?php
		
		$table = mysql_real_escape_string($_GET['table']);
		$sql_string = "SELECT * FROM $table";		
		if ($limit != "NO_LIMIT")
			$sql_string .= " LIMIT $limit";
		
		$result = do_mysql_query($sql_string);
		
		echo print_mysql_result_dump($result);
	}
	else
	{
		/* no table has been chosen */
		$result = do_mysql_query("SHOW TABLES");

		?>
		<table class="concordtable" width="100%">
		
			<tr>
				<th class="concordtable">View a mySQL table</th>
			</tr>

		<tr><td class="concordgeneral">
		
			<form action="index.php" method="get"> 
				<input type="hidden" name="thisF" value="tableView"/>

				<table><tr>
				<td class="basicbox">Select table to show:</td>
				
				<td class="basicbox">
					<select name="table">

		<?php
			while ( ($row = mysql_fetch_row($result)) != FALSE )
				echo "<option value='$row[0]'>$row[0]</option>\n";
		?>
					</select>
				</td></tr>
				
				<tr><td class="basicbox">Optionally, enter a LIMIT:</td>
				
				<td class="basicbox">
					<input type="text" name="limit" />
				</td></tr>
				<tr><td class="basicbox">&nbsp;</td>

				<td class="basicbox">
					<!-- this input ALWAYS comes last -->
					<input type="hidden" name="uT" value="y"/>
					<input type="submit" value="Show table"/>
				</td></tr>
				</table>

			</form>

		<?php
	}

	?></td></tr></table><?php
}




function printquery_systemprocesses()
{
	global $Config;
	

	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="5">Manage MySQL processes</th>
		</tr>
	</table>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="5">Viewing registered &ldquo;big&rdquo; MySQL processes</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="5">
				<p>
					This table displays CQPweb's log of database creation processes (aka "big" processes, MySQL calls that
					require lots of hardware resources). If a process runs to completion correctly, it will be removed from
					this log. However, if a process is interrupted for any reason, its entry on the log will remain here.
				</p>
				<p>
					Only a certain number of concurrent "big" processes are allowed - thus, if the log fills up with "zombie" processes,
					new databases will be blocked from creation.
				</p>
				<p>
					If a big process listed in this table is no longer running on the MySQL daemon (i.e. is flagged as zombie in this table),
					it is safe to delete it from the log.
				</p>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" colspan="3">Number of big processes allowed:<br/><i>(for most database types)</i></td>
			<td class="concordgeneral" colspan="2" align="right"><?php echo $Config->mysql_big_process_limit; ?></tyd>
		</tr>
		<tr>
			<td class="concordgrey" colspan="3">Number of big processes allowed:<br/><i>(for distribution databases, which are quicker)</i></td>
			<td class="concordgeneral" colspan="2" align="right"><?php echo $Config->mysql_process_limit['dist']; ?></tyd>
		</tr>
		<tr>
			<th class="concordtable" >Database being created</th>
			<th class="concordtable" >Time process began</th>
			<th class="concordtable" >Process type</th>
			<th class="concordtable" >Still running?</th>
			<th class="concordtable" >Delete</th>
		</tr>
		<?php

		$processlist = do_mysql_query('show full processlist');
		
		$db_processes = array();
		
		while (false !== ($o = mysql_fetch_object($processlist)))
			if (preg_match('|create table (db_\w+)\b|i', $o->Info, $m))
				$db_processes[] = $m[1];
		//TODO this does not allow for freqlist creation processes, which look different from DB creation processes
		// (and involve the creation of different things)
		// actually won't work for  DBs either, cos they spenmd more time loading than creating.
		//TODO result is that freqlist processes ALWAYS look like zombies.
		// solution:  capture the whole query and search for the "dbname" in each entry?
		
		mysql_data_seek($processlist, 0);

		$result = do_mysql_query('SELECT * from system_processes');
		
		if (mysql_num_rows($result) > 0)
		{
			while (($process = mysql_fetch_object($result)) !== false)
			{
				echo '<tr>'
					, '<td class="concordgeneral">' , $process->dbname , '</td>'
					, '<td class="concordgeneral" align="center">' , date(CQPWEB_UI_DATE_FORMAT, $process->begin_time) , '</td>'
					, '<td class="concordgeneral" align="center">' , $process->process_type , '</td>'
					, '<td class="concordgeneral" align="center">' 
						, (in_array($process->dbname, $db_processes) ? ('<a class="menuItem" href="#'.$process->dbname.'">[Yes!]</a>') : 'No: zombie')
					, '</td>'
					, '<td class="concordgeneral" align="center">'
						, '<a class="menuItem" href="index.php?locationAfter='
						, urlencode('index.php?thisF=manageProcesses&uT=y')
						, '&admFunction=execute&function=unregister_db_process&args=' 
						, $process->process_id , '&uT=y">[x]</a>'
					, '</td>'
					, "</tr>\n"
					;
			}
		}
		else
			echo '<tr><td class="concordgeneral" align="center" colspan=5">&nbsp;<br>The log is currently empty.<br>&nbsp;</td></tr>';
		?>
		
	</table>
	
	
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">MySQL Daemon: activity snapshot</th>
		</tr>
		
		<tr>
			<th class="concordtable">Thread ID</th>
			<th class="concordtable">Command/State</th>
			<th class="concordtable">User</th>
			<th class="concordtable">Source</th>
			<th class="concordtable">Running time (s)</th>
			<th class="concordtable">Request Kill</th>

		</tr>
		
		<?php
		
		/* if number of rows == 1, then nothing is running but "show processlist" itself */
		if (2 > mysql_num_rows($processlist))
			echo '<tr><td class="concordgeneral" align="center" colspan="6">&nbsp;<br>There is no activity on the MySQL daemon.<br>&nbsp;</td></tr>';
		else
		{
			while (false !== ($o = mysql_fetch_object($processlist)))
			{
				/* catch jump hyperlinks from table above... */
				if (preg_match('|create table (db_\w+)\b|i', $o->Info, $m))
					echo "\n\n", '<a name="', $m[1], '">' , "\n\n";
				
				/* extract info hidden in the query comment */ 
				if (preg_match('~/\* from User: (\w+) \| Function: (\w+)\(\) \| (\S+ \S+) \*/~s', $o->Info, $m))
					list (, $o->q, $o->u, $o->f, $o->t) = $m;
				else
					$o->q = $o->u = $o->f = $o->t = '???';
				
				echo '<tr>'
					, '<td class="concordgeneral">' , $o->Id , '</td>'
					, '<td class="concordgeneral">' , $o->Command, ' / ',  (empty($o->State) ? 'NULL' : $o->State), '</td>'
					, '<td class="concordgeneral">' , $o->u , '</td>'
					, '<td class="concordgeneral">' 
						, escape_html($o->q) , '<br>from function ' , $o->f , '()<br>at time ', $o->t
					, '</td>'
					, '<td class="concordgeneral">' , $o->Time , '</td>'
					, '<td class="concordgeneral">TODO:kill: ' , $o->Id , '</td>'
					, "</tr>\n"
					;
					// TODO: kill button??????
			}
		}
		
		?>
		
	</table>
	<?php

}



function printquery_statistic($type = 'user')
{
	global $Config;

	/* note usage of the same system of "perpaging" as the "Query History" function */
	if (isset($_GET['beginAt']))
		$begin_at = $_GET['beginAt'];
	else
		$begin_at = 1;

	if (isset($_GET['pp']))
		$per_page = $_GET['pp'];
	else
		$per_page = $Config->default_history_per_page;


	switch($type)
	{
	case 'corpus':
		$bigquery = 'select corpus, count(*) as c from query_history group by corpus order by c desc';
		$colhead = 'Corpus';
		$pagehead = 'for corpora';
		$list_of_corpora = list_corpora();
		break;
	case 'query':
		$bigquery = 'select cqp_query, count(*) as c from query_history group by cqp_query order by c desc';
		$colhead = 'Query';
		$pagehead = 'for particular query strings';
		break;
	case 'user':
	default:
		$bigquery = 'select user, count(*) as c from query_history group by user order by c desc';
		$colhead = 'Username';
		$pagehead = 'for user accounts';
		break;
	}
	
	$result = do_mysql_query($bigquery);
	
	?>
	<table width="100%" class="concordtable">
		<tr>
			<th colspan="3" class="concordtable">Usage statistics <?php echo $pagehead;?></th>
		</tr>
		<tr>
			<th class="concordtable" width="10%">No.</th>
			<th class="concordtable" width="60%"><?php echo $colhead; ?></th>
			<th class="concordtable" width="30%">No. of queries</th>
		</tr>
		
		<?php
		
		$toplimit = $begin_at + $per_page;
		$alt_toplimit = mysql_num_rows($result);

		if (($alt_toplimit + 1) < $toplimit)
			$toplimit = $alt_toplimit + 1;

		for ( $i = 1 ; $i < $toplimit ; $i++ )
		{
			if ( !($row = mysql_fetch_row($result)) )
				break;
			if ($i < $begin_at)
				continue;
			
			if ($type == 'corpus')
				if( !in_array($row[0], $list_of_corpora))
					$row[0] .= ' <em>(deleted)</em>';

			echo "<tr>\n";
			echo '<td class="concordgeneral" align="center">' . "$i</td>\n";
			echo '<td class="concordgeneral" align="left">' . "{$row[0]}</td>\n";
			echo '<td class="concordgeneral" align="center">' . number_format((float)$row[1]) . "</td>\n";
			echo "\n</tr>\n";
		}
		?>
		
	</table>
	<?php

	$navlinks = '<table class="concordtable" width="100%"><tr><td class="basicbox" align="left';

	if ($begin_at > 1)
	{	
		$new_begin_at = $begin_at - $per_page;
		if ($new_begin_at < 1)
			$new_begin_at = 1;
		$navlinks .=  '"><a href="index.php?' . url_printget(array(array('beginAt', "$new_begin_at")));
	}
	$navlinks .= '">&lt;&lt; [Move up the list]';
	if ($begin_at > 1)
		$navlinks .= '</a>';
	$navlinks .= '</td><td class="basicbox" align="right';
	
	if (mysql_num_rows($result) > $i)
		$navlinks .=  '"><a href="index.php?' . url_printget(array(array('beginAt', "$i + 1")));
	$navlinks .= '">[Move down the list] &gt;&gt;';
	if (mysql_num_rows($result) > $i)
		$navlinks .= '</a>';
	$navlinks .= '</td></tr></table>';
	
	echo $navlinks;
}


function printquery_phpconfig()
{
	if (isset ($_GET['showPhpInfo']) && $_GET['showPhpInfo'])
	{
		/* this messes up the HTML styling unfortunately, but I can't see a way to stop it from doing so */
		phpinfo();
		return;
	}
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				Internal PHP settings relevant to CQPweb
			</th>
		</tr>
		<tr>
			<td colspan="2" class="concordgrey" align="center">
				&nbsp;<br/>
				To see the full phpinfo() dump, 
					<a href="index.php?thisF=phpConfig&showPhpInfo=1&uT=y">click here</a>.
				<br/>&nbsp;	
			</td>
		</tr>
		
		<tr>
			<th colspan="2" class="concordtable">
				General
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				PHP version
			</td>
			<td class="concordgeneral">
				<?php echo phpversion(); ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Location of INI file
			</td>
			<td class="concordgeneral">
				<?php echo php_ini_loaded_file(); ?>
			</td>
		</tr>
		
		<tr>
			<th colspan="2" class="concordtable">
				Magic quotes
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				Magic quotes for GET, POST, COOKIE
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('magic_quotes_gpc') ? 'On' : 'Off'; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Magic quotes at runtime
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('magic_quotes_runtime')? 'On' : 'Off'; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Magic quotes sybase mode
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('magic_quotes_sybase')? 'On' : 'Off'; ?>
			</td>
		</tr>




		<tr>
			<th colspan="2" class="concordtable">
				Memory and runtime
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				PHP's memory limit
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('memory_limit'); ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Maximum script running time 
				<br/>
				<em>(turned off by some scripts)</em>
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('max_execution_time'); ?> seconds
			</td>
		</tr>




		<tr>
			<th colspan="2" class="concordtable">
				File uploads
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				File uploads enabled
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('file_uploads')? 'On' : 'Off'; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Temporary upload directory
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('upload_tmp_dir') ; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Maximum upload size
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('upload_max_filesize'); ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Maximum size of HTTP post data
				<br/>
				<em>(NB: uploads cannot be bigger than this)</em>
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('post_max_size'); ?>
			</td>
		</tr>



		<tr>
			<th colspan="2" class="concordtable">
				MySQL
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				Client API version
			</td>
			<td class="concordgeneral">
				<?php echo mysql_get_client_info(); ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Socket on localhost
			</td>
			<td class="concordgeneral">
				<?php echo ini_get('mysql.default_socket'); ?>
			</td>
		</tr>

	</table>
	
	<?php
}

function printquery_opcodecache()
{
	$mode = detect_php_opcaching();
	$mode_names = array ('apc'=>'APC', 'opcache'=>'OPcache', 'wincache'=>'WinCache');

	$codefiles = list_cqpweb_php_files('code');
	$stubfiles = list_cqpweb_php_files('stub');
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Opcode cache overview
			</th>
		</tr>
		<tr>
			<td class="concordgrey">
				&nbsp;<br/>
				Opcode caches are tools to speed up PHP applications like CQPweb. Several different ones are available,
				but any individual server will only use <i>one</i>. 
				<b>APC</b>, <b>OPcache</b> and <b>WinCache</b> are three opcode caches that can be monitored from within CQPweb.
				<?php
				echo '<ul>'
					, '<li><b>APC</b> '     , $mode == 'apc'      ? 'is <u>active</u>' : 'is inactive or unavailable', '.</li>'
					, '<li><b>OPcache</b> ' , $mode == 'opcache'  ? 'is <u>active</u>' : 'is inactive or unavailable', '.</li>'
					, '<li><b>WinCache</b> ', $mode == 'wincache' ? 'is <u>active</u>' : 'is inactive or unavailable', '.</li>'
					, "</ul>\n"
 					;
				?>
				
				Use the controls below to monitor your opcode cache, and to clear/reload it if necessary (e.g. after a version upgrade;
				should not normally be necessary as a properly-working opcode cache will reload from disk automatically as needed).
				<br/>&nbsp;
			</td>
		</tr>
	</table>
	
	<?php
	
	if ($mode === false)
	{
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Opcode cache monitor unavailable
				</th>
			</tr>
			<tr>
				<td class="concordgrey" align="center">
					&nbsp;<br/>
					Opcode cache monitoring is not available (opcode cache extension not installed?)
					<br/>&nbsp;
				</td>
			</tr>
		</table>
		<?php	
	}
	else
	{
		switch($mode)
		{
		case 'apc':
			$info = apc_cache_info();
			$rawinfo = $info['cache_list'];
			$fnkey = 'filename';
			$func_date_timestamp = create_function('$x', 'return $x["creation_time"];');
			$hitkey = 'num_hits';
			break;
		case 'opcache':
			$info = opcache_get_status(true);
			$rawinfo = $info['scripts'];
			$fnkey = 'full_path';
			$func_date_timestamp = create_function('$x', 'return $x["timestamp"];');
			$hitkey = 'hits';
			break;
		case 'wincache':
			$info = wincache_ocache_fileinfo(false);
			$rawinfo = $info['file_entries'];
			$fnkey = 'file_name';
			$func_date_timestamp = create_function('$x', 'return (time() - $x["add_time"]);');
			$hitkey = 'hit_count';
			break;
		}
		$codeinfo = array();
		$stubinfo = array();
		
		foreach($rawinfo as $f)
		{
			if (in_array($f[$fnkey], $stubfiles))
				$stubinfo[$f[$fnkey]] = $f;
			else if(in_array($f[$fnkey], $codefiles))
				$codeinfo[$f[$fnkey]] = $f;
		}
		
		$n_cqpweb = ($n_stub = count($stubinfo)) + ($n_code = count($codeinfo));
		$n_overall = count($rawinfo);
		
		/* locationAfter for buttons */
		$loc = '&locationAfter=' . urlencode('index.php?thisF=opcodeCache&uT=y');
		
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable" colspan="4">
					<?php echo $mode_names[$mode], ' status as of <u>', date(CQPWEB_UI_DATE_FORMAT), '</u>'; ?>
				</th>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="4" align="center">
					&nbsp;<br/>
					<?php 
					echo "The cache contains <b>", $n_overall, "</b> files, <b>", $n_cqpweb, "</b> of which are part of CQPweb."; 
					echo "<br/>&nbsp;<br/>";
					echo "<b>", $n_stub, "</b> of these are stub-files and <b>", $n_code, "</b> of these are library code files (see below).";
					echo "<br/>&nbsp; <br/>(Stub-files present on the system: <b>", count($stubfiles), "</b>)."; 
					?>
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<th class="concordtable" colspan="4">Manipulate cache</th>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="4" align="center">
					<table class="basicbox" width="100%">
						<tr>
							<td class="basicbox" align="center" width="25%">
								<a class="menuItem" href="index.php?admFunction=execute<?php echo $loc; ?>&function=do_opcache_full_unload&uT=y">
									[Clear all files from cache]
								</a>
							</td>
							<td class="basicbox" align="center" width="25%">
								<a class="menuItem" href="index.php?admFunction=execute<?php echo $loc; ?>&function=do_opcache_full_load&args=code&uT=y">
									[Insert library files to cache]
								</a>
							</td>
							<td class="basicbox" align="center" width="25%">
								<a class="menuItem" href="index.php?admFunction=execute<?php echo $loc; ?>&function=do_opcache_full_load&args=stub&uT=y">
									[Insert stub files to cache]
								</a>
							</td>
							<td class="basicbox" align="center" width="25%">
								<a class="menuItem" href="index.php?admFunction=execute<?php echo $loc; ?>&function=do_opcache_full_load&uT=y">
									[Insert all files to cache]
								</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<th class="concordtable">Library file</th>
				<th class="concordtable">Last loaded</th>
				<th class="concordtable">Times reused</th>
				<th class="concordtable">Actions</th>
			</tr>
			<?php
			
			$chop_off = realpath('../lib/'). '/';
			
			foreach($codefiles as $f)
			{
				echo "<tr>\n"
					, '<td class="concordgeneral">', str_replace($chop_off, '', $f), "</td>\n";
				if (isset ($codeinfo[$f]))
				{
					$i = $codeinfo[$f];
					echo '<td class="concordgeneral" align="center">', date(CQPWEB_UI_DATE_FORMAT, $func_date_timestamp($i)), "</td>\n"
						, '<td class="concordgeneral" align="center">', number_format($i[$hitkey]), "</td>\n"
						, '<td class="concordgeneral" align="center">'
							, '<a class="menuItem" href="index.php?admFunction=execute'
							, $loc, '&function=do_opcache_unload_file&args=', urlencode($f), '&uT=y">[Unload]</a>' 
						, "</td>\n"
						;
				}
				else
				{
					echo  '<td class="concordgeneral" align="center" colspan="2">-</td>'
						, '<td class="concordgeneral" align="center">'
							, '<a class="menuItem" href="index.php?admFunction=execute'
							, $loc, '&function=do_opcache_load_file&args=', urlencode($f), '&uT=y">[Load]</a>' 
						, "</td>\n"
						, "\n"
						;
				}
				echo "</tr>\n";
			}
			
			?>
		</table>
		<?php
	}
}


//function printquery_advancedstats()
//{
//
//	// TODO
//
//}


function printquery_message()
{
	
	$msg = escape_html($_GET['message']);
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="2" class="concordtable">
				CQPweb says:
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p>&nbsp;</p>
				<p align="center">
					<?php echo $msg; ?>
				</p>
				<p>&nbsp;</p>
			</td>
		</tr>
	</table>
	<?php
}


