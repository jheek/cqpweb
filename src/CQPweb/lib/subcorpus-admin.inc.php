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
 * Subcorpus-admin: carries out actions related to subcorpus creation, deletion etc.
 * 
 * This script does its action, then calls the index page with a subcorpus function for whatever is to be displayed next.
 * 
 */


require('../lib/environment.inc.php');


require('../lib/cache.inc.php');
require('../lib/db.inc.php');
require('../lib/library.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/metadata.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/xml.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/freqtable.inc.php');
require('../lib/cwb.inc.php');
require('../lib/cqp.inc.php');


cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP);


if (!isset($_GET['scriptMode']))
	exiterror('No scriptmode specified for subcorpus-admin.inc.php!');
else
	$this_script_mode = $_GET['scriptMode'];


/* this variable is allowed to be missing */
if (isset($_GET['subcorpusNewName']))
	$subcorpus_name = mysql_real_escape_string($_GET['subcorpusNewName']);







/* if "Cancel" was pressed on a form, do nothing, and just go straight to the index */
if (isset ($_GET['action']) && $_GET['action'] == 'Cancel')
{
	set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	cqpweb_shutdown_environment();
	exit();
}




switch ($this_script_mode)
{

case 'create_from_manual':

	if (!isset($_GET['subcorpusListOfFiles']))
	{
		/* effectively do not allow a submission (but sans error message) if the field is empty */
		set_next_absolute_location('index.php?subcorpusCreateMethod=manual&subcorpusFunction=define_subcorpus'
			. "&subcorpusNewName=$subcorpus_name&thisQ=subcorpus&uT=y");
	}
	else
	{
		subcorpus_admin_check_name($subcorpus_name, url_absolutify('index.php?subcorpusBadName=y&' . url_printget()));

		$list_of_texts = explode(' ', (trim(preg_replace('/[\s,]+/', ' ', $_GET['subcorpusListOfFiles']))));

		/* get a list of text names that are not real text ids */
		$errors = check_textlist_valid($list_of_texts, $Corpus->name);

		if (!empty($errors))
		{
			$errstr = implode(' ', $errors);
			set_next_absolute_location('index.php?subcorpusCreateMethod=manual&subcorpusListOfFiles='
				. "$list_of_texts&subcorpusFunction=define_subcorpus&subcorpusNewName="
				. "$subcorpus_name&subcorpusBadTexts=$errstr&thisQ=subcorpus&uT=y");
			break;
		}
		else
		{
			$sc = Subcorpus::create($subcorpus_name, $Corpus->name, $User->username);
			$sc->populate_from_list('text', 'id', $list_of_texts);
			$sc->save();
			
			set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
		}
	}
	break;



case 'create_from_metadata':
	
	$restriction = Restriction::new_from_url($_SERVER['QUERY_STRING']);
	
	if (false === $restriction)
	{
		/* effectively do not allow a submission (but sans error message) if no cats selected */
		set_next_absolute_location('index.php?subcorpusCreateMethod=metadata&subcorpusFunction=define_subcorpus'
			. "&subcorpusNewName=$subcorpus_name&thisQ=subcorpus&uT=y");
		break;
	}
	
	if ($_GET['action'] == 'Get list of texts') /* little trick with the button text! */
	{
		/* then we don't want to actually store it, just display a new form */
		$list_of_texts_to_show_in_form = implode(' ', $restriction->get_item_list());
		$header_cell_text = 'Viewing texts that match the following metadata restrictions: <br/>' . $restriction->print_as_prose();
		$field_to_show = $Corpus->primary_classification_field;
		
		$longval_id = longvalue_store($list_of_texts_to_show_in_form . '~~~~~' . mysql_real_escape_string($header_cell_text) . '~~~~~' . $field_to_show);

		set_next_absolute_location("index.php?subcorpusFunction=list_of_files&listOfFilesLongValueId=$longval_id&thisQ=subcorpus&uT=y");
	}
	else
	{
		subcorpus_admin_check_name($subcorpus_name, 
			url_absolutify('index.php?subcorpusBadName=y&subcorpusCreateMethod='
				. 'metadata&subcorpusFunction=define_subcorpus&' 
				. $restriction->url_serialise() . '&' 
				. url_printget()));

		$sc = Subcorpus::create($subcorpus_name, $Corpus->name, $User->username);
		$sc->populate_from_restriction($restriction);
		$sc->save();
		
		set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	}
	break;






case 'create_from_metadata_scan':

	/* set up variables in memory, manipulate GET, and then re-Location to the index to render the list */

	if (!isset($_GET['metadataFieldToScan']))
		exiterror('No search field specified!');
	else
		$field_to_show = $field = mysql_real_escape_string($_GET['metadataFieldToScan']);

	if (!isset($_GET['metadataScanString']))
		exiterror('No search target specified!');
	else
		$orig_value = $value = mysql_real_escape_string($_GET['metadataScanString']);
	
	$header_cell_text = 'Viewing texts where <em>' . metadata_expand_field($field);	
	
	switch($_GET['metadataScanType'])
	{
	case 'begin':
		$value .= '%';
		$header_cell_text .= '</em> begins with';
		break;
		
	case 'end':
		$value = '%' . $value;
		$header_cell_text .= '</em> ends with';
		break;
		
	case 'contain':
		$value = '%' . $value . '%';	
		$header_cell_text .= '</em> contains';
		break;
		
	case 'exact':
		/* note - if nothing is specified, assume exact match required */
	default:
		$header_cell_text .= '</em> matches exactly';
		break;
	}
	
	$header_cell_text .= ' &ldquo;' . $orig_value . '&rdquo;';
	
	$result = do_mysql_query("select text_id from text_metadata_for_{$Corpus->name} where $field like '$value'");
	
	$list_of_texts_to_show_in_form = '';
	
	while ( false !== ($r = mysql_fetch_row($result)) )
		$list_of_texts_to_show_in_form .= ' ' . $r[0];
		
	$list_of_texts_to_show_in_form = trim($list_of_texts_to_show_in_form);

	$longval_id = longvalue_store($list_of_texts_to_show_in_form . '~~~~~' . mysql_real_escape_string($header_cell_text) . '~~~~~' . $field_to_show);
	
	set_next_absolute_location("index.php?subcorpusFunction=list_of_files&listOfFilesLongValueId=$longval_id&thisQ=subcorpus&uT=y");
	break;




case 'create_from_query':

	if (!isset($_GET['savedQueryToScan']))
	{
		/* effectively do not allow a submission (but sans error message) if no query specified */
		set_next_absolute_location(
			"index.php?subcorpusCreateMethod=query&subcorpusFunction=define_subcorpus&subcorpusNewName=$subcorpus_name&thisQ=subcorpus&uT=y");
		break;
	}
	
	$create = ($_GET['action'] != 'Get list of texts');
	$qname = mysql_real_escape_string($_GET['savedQueryToScan']);

	if ($create)
	{
		subcorpus_admin_check_name($subcorpus_name, 
			url_absolutify('index.php?subcorpusBadName=y&subcorpusCreateMethod='
				. 'query&subcorpusFunction=define_subcorpus&' 
				. url_printget()));

		$sc = Subcorpus::create($subcorpus_name, $Corpus->name, $User->username);
		$sc->populate_from_query_texts($qname);
		$sc->save();
	
		set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	}
	else
	{
		$header_cell_text = "Viewing texts in saved query &ldquo;$qname&rdquo;";
		$field_to_show = get_corpus_metadata('primary_classification_field');
		
		connect_global_cqp();
		
		$grouplist = $cqp->execute("group $qname match text_id");
		
		$texts = array();
		foreach($grouplist as &$g)
			list($texts[]) = explode("\t", $g);
	
		$list_of_texts_to_show_in_form = implode(' ', $texts);
	
		$longval_id = longvalue_store($list_of_texts_to_show_in_form . '~~~~~' . mysql_real_escape_string($header_cell_text) . '~~~~~' . $field_to_show);
	
		set_next_absolute_location("index.php?subcorpusFunction=list_of_files&listOfFilesLongValueId=$longval_id&thisQ=subcorpus&uT=y");
	}
	break;

	
	

case 'create_inverted':

	if (empty($_GET['subcorpusToInvert']))
		exiterror("You must specify a subcorpus to invert!");

	subcorpus_admin_check_name($subcorpus_name, 
		url_absolutify('index.php?subcorpusBadName=y&subcorpusCreateMethod='
			. 'invert&subcorpusFunction=define_subcorpus&' 
			. url_printget()));

	$sc = Subcorpus::create($subcorpus_name, $Corpus->name, $User->username);
	if (! $sc->populate_from_inverting($_GET['subcorpusToInvert']))
		exiterror("You can (at present) only use the invert-subcorpus function with a subcorpus made up of a set of texts. ");
		// TODO see the SC::populate_from_invert comments for why; this might change later. 
	$sc->save();
	
	set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	break;



case 'create_text_id':

	$text_list = corpus_list_texts($Corpus->name);
	
	if (count($text_list) > 100)
		exiterror('This corpus contains more than 100 texts, so you cannot use the one-subcorpus-per-text function!');
	
	foreach($text_list as $id)
	{
		$sc = Subcorpus::create($id, $Corpus->name, $User->username);
		$sc->populate_from_list('text', 'id', array($id));
		$sc->save();
	}
		
	set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	break;


case 'copy':

	if (! isset($_GET['subcorpusToCopy']) )
		exiterror('No subcorpus was specified for copying!');
	if (! isset($subcorpus_name))
		exiterror('No name was supplied for the new subcorpus!');

	if (preg_match('/\W/', $subcorpus_name) > 0)
	{
		/* call the index script with a rejected name */
		set_next_absolute_location('index.php?subcorpusBadName=y&' . url_printget());
		break;
	}
	
	if (false === ($copy_src = Subcorpus::new_from_id((int)$_GET['subcorpusToCopy'])))
		exiterror('The subcorpus you want to copy does not seem to exist!');
	
	if ($subcorpus_name == $copy_src->name)
		exiterror("It's not possible to create a copy of a subcorpus with the sam,e name as the original ({$copy_src->name}).");

	/* What this call does: clone the subcorpus, then flag as unsaved to give it a new ID, then save. */
	Subcorpus::duplicate($copy_src, $subcorpus_name);
	/* We just throw away the return value, since we don't do anything with it. */
	
	set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	break;
	

case 'delete':

	if (isset($_GET['subcorpusToDelete']))
	{
		if (false !== ($delenda = Subcorpus::new_from_id((int)$_GET['subcorpusToDelete'])))
		{
			if (! $delenda->owned_by_user())
				exiterror("You cannot delete this subcorpus, it is not linked to your user account.");
			$delenda->delete();
		}

		set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	}
	else
		exiterror('No subcorpus specified to delete!');	
	break;



case 'remove_texts':

	if (! (isset($_GET['subcorpusToRemoveFrom']) ) )
		exiterror('No subcorpus was specified for text removal!');
	else
		$subcorpus_from = Subcorpus::new_from_id($_GET['subcorpusToRemoveFrom']);

	if (false === $subcorpus_from)
		exiterror("The specified subcorpus does not seem to exist.");
	if (! $subcorpus_from->owned_by_user())
		exiterror("You cannot modify a subcorpus that your user account does not own.");

	preg_match_all('/dT_([^&]*)=1/', $_SERVER['QUERY_STRING'], $m, PREG_PATTERN_ORDER);

	if (!empty($m[1]))
	{
		$list_of_texts = array_unique(array_map('cqpweb_handle_enforce', $m[1]));
		$subcorpus_from->modify_remove_items($list_of_texts);
		$subcorpus_from->save();
	}
	else
		exiterror("You didn't specify any files to remove from this subcorpus! Go back and try again.");

	set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	break;

	
case 'add_texts':

	if (! (isset($_GET['subcorpusToAddTo']) ) )
		exiterror('No subcorpus ID specified for adding texts to in subcorpus-admin.inc.php!');

	$subcorpus_to = Subcorpus::new_from_id($_GET['subcorpusToAddTo']);
	
	if (false === $subcorpus_to)
		exiterror('The subcorpus you specified does not seem to exist.');
	if (! $subcorpus_to->owned_by_user())
		exiterror("You cannot modify this subcorpus, it is not linked to your user account.");

	if (!isset($_GET['subcorpusListOfFiles']))
	{
		/* no texts specified, so don't do anything. (Will redirect back to the subcorpus UI.) */
		;
	}
	else
	{
		$list_of_texts = explode(' ', trim(preg_replace('/[\s,]+/', ' ', $_GET['subcorpusListOfFiles'])));
		
		/* get a list of text names that are not real text ids */
		$errors = check_textlist_valid($list_of_texts, $Corpus->name);

		if (!empty($errors))
		{
			$errstr = implode(' ', $errors);
			set_next_absolute_location('index.php?thisQ=subcorpus&subcorpusListOfFiles='
				. "$list_of_texts&subcorpusFunction=add_texts_to_subcorpus&subcorpusToAddTo="
				. "$subcorpus_to&subcorpusBadTexts=$errstr&uT=y");
			break;
		}
		
		/* OK, we now know the list of names is OK */
		$subcorpus_to->modify_add_items($list_of_texts);
		$subcorpus_to->save();
	}
	
	set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	break;


case 'process_from_text_list':

	if (isset($_GET['processTextListAddAll']))
	{
		/* "include all texts" was ticked */
		/* the actual list of texts may be too long for HTTP GET, so is stored in the longvalues table */
		$text_list_to_add = array_unique(explode(' ', preg_replace('/\W+/', ' ', longvalue_retrieve($_GET['processTextListAddAll']))));
	}
	else
	{
		/* "include all" not ticked: refer to individual checkboxes. */ 
		if (0 > preg_match_all('/aT_([^&]*)=1/', $_SERVER['QUERY_STRING'], $m, PREG_PATTERN_ORDER))
			$text_list_to_add = array_map('cqpweb_handle_enforce', array_unique($m[1]));
		else
			exiterror("You didn't specify any texts to add to this subcorpus! Go back and try again.");
	}

	/* work out if we're adding or creating */
	
	if (  (! isset($_GET['subcorpusToAddTo']) ) && (! isset($subcorpus_name) )  )
		exiterror('No subcorpus name specified for adding these texts to!');

	if ($_GET['subcorpusToAddTo'] !== '!__NEW')
	{
		/* add to existing */
		$subcorpus_to = Subcorpus::new_from_id($_GET['subcorpusToAddTo']);
		if (false === $subcorpus_to)
			exiterror('The subcorpus you specified does not seem to exist.');
		if (! $subcorpus_to->owned_by_user())
			exiterror("You cannot modify this subcorpus, it is not linked to your user account.");
		
		$subcorpus_to->modify_add_items($list_of_texts);
		$subcorpus_to->save();
	}
	else
	{
		if (! cqpweb_handle_check($subcorpus_name, 200))
			exiterror('The subcorpus name you specified is invalid. Please go back and revise!');
		$subcorpus_to = Subcorpus::create($subcorpus_name, $Corpus->name, $User->username);
		
		/* note we get the obj to do che3ckign for us just in case... */
		$subcorpus_to->populate_from_list('text', 'id', $text_list_to_add);
		$subcorpus_to->save();
	}
	
	set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	break;


case 'compile_freqtable':
	
	if (!isset($_GET['compileSubcorpus']))
		exiterror('No subcorpus was specified - frequency tables cannot be compiled!!');
	
	if (false == ($sc = Subcorpus::new_from_id($_GET['compileSubcorpus'])))
		exiterror('A non-existent subcorpus was specified - frequency tables cannot be compiled!!');
	else
	{
		if ($User->max_freqlist() < $sc->size_tokens())
	 		exiterror('You do not have the necesssary permission to create a frequency list for this subcorpus: '
	 				. 'it is too big (' . $sc->print_size_tokens() . ' words).');
		
	 	/* otherwise... */
		$qs = QueryScope::new_by_unserialise("{$sc->id}");
//show_Var($qs);
	 	subsection_make_freqtables($qs);
//	 	subsection_make_freqtables($sc->name);
	}
	
	set_next_absolute_location('index.php?thisQ=subcorpus&uT=y');
	break;
	

default:
	exiterror('Unrecognised scriptmode for subcorpus-admin.inc.php!');


} 
/* end of big switch. Some paths through it exit; the ones that break are ready to shutdown. */


/* final actions.... */
cqpweb_shutdown_environment();
exit();



/* ---------- *
 * END SCRIPT *
 * ---------- */




/**
 * Checks the subcorpus name parameter for validity: redirects to specified URL & exits if the test is failed.
 * 
 * @param string $subcorpus_name
 * @param string $location_url
 */
function subcorpus_admin_check_name($subcorpus_name, $location_url)
{
	if (empty($subcorpus_name) || 0 < preg_match('/\W/', $subcorpus_name) || 200 < strlen($subcorpus_name))
	{
		set_next_absolute_location($location_url );
		cqpweb_shutdown_environment();
		exit();
	}
}


