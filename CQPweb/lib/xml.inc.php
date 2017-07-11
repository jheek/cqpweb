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
 * This is the XML library - where "xml" means "interface to S-attributes" just as
 * in CQPweb generally, "annotation" means "interface to P-attributes".
 * 
 * The xml_metadata table makes extensive use of the "attribute family".
 * 
 * These are groups of attributes based on XML <el att="val">...</el> style input.
 * The resulting s-attributes (el, el_att) all have exactly the same sets of ranges.
 * So, we call them a family and only ever store their range-points *once*. 
 * As and when it seems necessary, we flip between treating them as separate s-attributes 
 * (where appropriate) and hiding this truth from the user in favour of making it look like
 * they are still an XML-style structure. The "parent" of a family is its *element* i.e. whatever
 * was the element name of the original XML. This element, by definition, does not have a value;
 * its value is split up into different s-attributes. The element can be spotted because its
 * "family" is listed in the database as being the same as its handle.
 * 
 * Every XML thingy has a type - the same range of datatypes exist as for corpus metadata
 * (since, after all, "text" is just an XML element - listed as normal in the database, but 
 * treated specially by the code. An s-attribute "without annotation" - such as the "parent"
 * of a family - has the NONE datatype.
 * 
 * If the datatype is CLASSIFICATION, then the possible values of that s-attribute all go into
 * the xml_metadata_values table as well.
 * 
 * If the datatype is IDLINK, then there is expected to be an idlink table that satisfies it.
 * 
 * The functions to be found here fall into 2 groups.
 * 
 * First, there are the ones that handle various types of access to the xml_metadata table,
 * which is the master-record of information about s-attributes. If this were object-oriented
 * stylie, which it isn't, these would be methods for the object representing an s-attribute.
 * 
 * 
 * Finally, there are the ones with the names xml_visualisation_*; this cluster 
 */




/*
 * ============================================
 * FUNCTION LIBRARY FOR XML/S-ATTRIBUTE CONCEPT
 * ============================================
 */



/**
 * Gets an array with handles of all s-attributes in the specified corpus.
 * 
 * The array is associative: handle=>description
 */
function list_xml_all($corpus)
{
	$corpus = mysql_real_escape_string($corpus);
	
	$list = array();
	$result = do_mysql_query("select handle,description from xml_metadata where corpus = '$corpus'");
	while (false !== ($r = mysql_fetch_assoc($result)))
		$list[$r['handle']] = $r['description'];
	
	return $list;
}

/**
 * Gets a full set of database objects for xml elements/attributes, from the database;
 * returned in associative array whose keys repeat the object's "handle" element.
 */
function get_xml_all_info($corpus)
{
	$corpus = mysql_real_escape_string($corpus);
	
	$list = array();
	$result = do_mysql_query("select * from xml_metadata where corpus = '$corpus'");
	while (false !== ($o = mysql_fetch_object($result)))
		$list[$o->handle] = $o;
	
	return $list;
}

/**
 * Gets an info object for the specified XML (from the database).
 * 
 * Returns false if not found.
 */
function get_xml_info($corpus, $xml_handle)
{
	$corpus = mysql_real_escape_string($corpus);
	$xml_handle = mysql_real_escape_string($xml_handle);
	
	$result = do_mysql_query("select * from xml_metadata where corpus = '$corpus' and handle = '$xml_handle'");
	if (0 == mysql_num_rows($result))
		return false;
	
	return mysql_fetch_object($result);
}

/**
 * Checks whether or not the specified s-attribute exists in the specified corpus.
 * 
 */
function xml_exists($s_attribute, $corpus)
{
	$corpus = mysql_real_escape_string($corpus);
	$s_attribute = mysql_real_escape_string($s_attribute);
	return (0 < mysql_num_rows(do_mysql_query("select handle from xml_metadata where handle = '$s_attribute' and corpus = '$corpus'")));
}

/**
 * Gets an array of all s-attributes that have annotation values 
 * (includes all those derived from attribute-value annotations
 * of another s-attribute that was specified xml-style).
 * 
 * That is, the listed attributes definitely have a value that can be printed.
 * 
 * The array is associative (handle=>description).
 */
function list_xml_with_values($corpus)
{
	$corpus = mysql_real_escape_string($corpus);

	$list = array();
	$result = do_mysql_query("select handle,description from xml_metadata where corpus = '$corpus' and datatype !=" . METADATA_TYPE_NONE);
	while (false !== ($r = mysql_fetch_assoc($result)))
		$list[$r['handle']] = $r['description'];

	return $list;
}

/**
 * Gets an associative array of of s-attributes that are elements
 * (that is, they are not a member of some other s-attribute's "family"...)
 * in the format handle => description .
 */
function list_xml_elements($corpus)
{
	$corpus = mysql_real_escape_string($corpus);
	
	$list = array();
	$result = do_mysql_query("select handle,description from xml_metadata where corpus = '$corpus' and att_family = handle");
	while (false !== ($r = mysql_fetch_assoc($result)))
		$list[$r['handle']] = $r['description'];

	return $list;
}

/**
 * Gets an array of s-attributes that are subordinate
 * members of a specified element's "attribute family"; 
 * array is associative in the format handle=>description.
 */
function get_xml_family_attributes($corpus, $element)
{
	$corpus  = mysql_real_escape_string($corpus);
	$element = mysql_real_escape_string($element);

	$list = array();
	$result = do_mysql_query("select handle,description from xml_metadata 
								where corpus = '$corpus'
								and handle != '$element'
								and att_family = '$element'");
	while (false !== ($r = mysql_fetch_assoc($result)))
		$list[$r['handle']] = $r['description'];

	return $list;
}




/**
 *  Returns a list of category handles occuring for the given classification.
 * 
 * If categories are not set up, or the $xml_handle is of a datatype other than 
 * CLASSIFICATION, the result will be an empty array.
 */
function xml_category_listall($corpus, $xml_handle)
{
	$corpus = mysql_real_escape_string($corpus);
	$xml_handle = mysql_real_escape_string($xml_handle);

	$result = do_mysql_query("SELECT handle FROM xml_metadata_values WHERE att_handle = '$xml_handle' AND corpus = '$corpus'");

	$return_me = array();
	
	while (($r = mysql_fetch_row($result)) != false)
		$return_me[] = $r[0];
	
	return $return_me;
}



/**
 * Returns an associative array of category descriptions,
 * where the keys are the handles, for the given classification.
 * 
 * If no description exists, the handle is set as the description.
 * 
 * If categories are not set up, or the $xml_handle is of a datatype other than 
 * CLASSIFICATION, the result will be an empty array.
 */
function xml_category_listdescs($corpus, $xml_handle)
{
	$corpus = mysql_real_escape_string($corpus);
	$xml_handle = mysql_real_escape_string($xml_handle);

	$result = do_mysql_query("SELECT handle, description FROM xml_metadata_values WHERE att_handle = '$xml_handle' AND corpus = '$corpus'");

	$return_me = array();
	
	while (($r = mysql_fetch_row($result)) != false)
		$return_me[$r[0]] = (empty($r[1]) ? $r[0] : $r[1]);
	
	return $return_me;
}



/**
 * Resets the description of an XML to a specified value.
 */
function update_xml_description($corpus, $handle, $new_desc)
{
	$corpus = mysql_real_escape_string($corpus);
	$handle = mysql_real_escape_string($handle);
	$new_desc = mysql_real_escape_string($new_desc);
	
	do_mysql_query("update xml_metadata set description = '$new_desc' where corpus = '$corpus' and handle = '$handle'");
}


/**
 * Changes the datatype of an XML thingie in the database, including all necessary parallel changes.
 * 
 * Note that if type conversion fails due to some condition not being fulfilled in the values in the underlying
 * index, then the type will become FREETEXT, which is the fallback type.
 */
function change_xml_datatype($corpus, $handle, $new_datatype)
{
	$corpus = mysql_real_escape_string($corpus);
	$handle = mysql_real_escape_string($handle);
	$new_datatype = (int) $new_datatype;
	
	if (false === ($x = get_xml_info($corpus, $handle)))
		exiterror(escape_html("Undefined XML element $corpus -- $handle specified."));
	
	/* check that fromtype and to type are not the same.  If they are, return. */
	if ($x->datatype == $new_datatype)
		return;
	
	/* if existing data type is not freetext, change it to freetext.
	 * first, in this switch, we do other necessary actions for different types. */
	switch($x->datatype)
	{
	/* the cases where nothing is actually done. */
	case METADATA_TYPE_FREETEXT:
		/* obviously no action here */
	case METADATA_TYPE_DATE:
	case METADATA_TYPE_UNIQUE_ID:
		/* no other actions necessary. The values are simply read as plain strings from now on. */
		break;
		
	case METADATA_TYPE_IDLINK:
		/* Extra action: delete the idlink table if it exists; delete entries from idlink_fields and idlink_values. */
		//TODO
		//TODO
		//TODO
		//TODO
		//TODO
		delete_xml_idlink($corpus, $handle);
		
		
		break;
	
	case METADATA_TYPE_CLASSIFICATION:
		/* extra action: we need to delete entries from xml_metadata_values (category table). */
		do_mysql_query("delete from xml_metadata_values where corpus='$corpus' and att_handle='$handle'");
		break;
	
	case METADATA_TYPE_NONE:
		exiterror("The datatype of an XML element cannot be changed, because it is empty.");
	
	default:
		exiterror("Undefined metadata type specified."); 
		/* shouldn't actually be reachable, because bad datatype constants ought never to go into the DB */ 
	}
	/* this is just in case of trouble in the latter half of the function. */
	do_mysql_query("update xml_metadata set datatype = " . METADATA_TYPE_FREETEXT . " where corpus='$corpus' and handle='$handle'");
	
	
	/* so, where are we now? we are changing FROM free text to something else. */
	switch($new_datatype)
	{
	case METADATA_TYPE_FREETEXT:
		/* already done above: so we can actually just return from the function. */
		return;
	
	case METADATA_TYPE_IDLINK:
		/* no checks needed, since the values on the target table need not be handles. */
		break;

	case METADATA_TYPE_UNIQUE_ID:
		/* We have to check that the values (a) are unique, (b) are CQPweb handles. 
		 * Unique - because that's the whole point. Handles - because text_id has this datatype. */
		if ( ! xml_index_has_handles($corpus, $handle))
			exiterror("The datatype of $handle cannot be changed to [unique ID], because there are non-handle values in the CWB index.");
		if ( ! xml_index_is_unique($corpus, $handle))
			exiterror("The datatype of $handle cannot be changed to [unique ID], because there are duplicate values in the CWB index.");
		break;

	case METADATA_TYPE_DATE:
		/* check that the values are all correctly-formatted date strings. */
		if ( ! xml_index_is_valid_date($corpus, $handle))
			exiterror("The datatype of $handle cannot be changed to [date], because there are non-date values in the CWB index.");
		break;
				
	case METADATA_TYPE_CLASSIFICATION:
		/* this is the tough one! Note that the procedures below implicitly involve TWO sweeps through the s-attribute. 
		 * We accept this overhead for the sake of simplicity and also because s-attributes are not actually all that big.
		 * (plus, after the first time, the index file is likely to be in OS cache, so it should be pretty quick ... */
		
		/* first: check that the values are all valid category handles */
		if ( ! xml_index_has_handles($corpus, $handle))
			exiterror("The datatype of $handle cannot be changed to [classification], because there are non-category-handle values in the CWB index.");
		
		/* so we can safely build a record for the categories of this classification */
		setup_xml_classification_categories($corpus, $handle);

		break;
		
	case METADATA_TYPE_NONE:
		exiterror("You cannot change the datatype of an XML attribute to \"none\", as the data is still there in the underlying index.");

	default:
		exiterror("Critical error: an undefined metadata type specified."); 
	}

	/* so we can now do this. */
	do_mysql_query("update xml_metadata set datatype = $new_datatype where corpus='$corpus' and handle='$handle'");

	/* and that's it! */
}



/**
 * Enters lines into xml_metadata_values for each of the categories in the classification.
 */
function setup_xml_classification_categories($corpus, $handle)
{
	$catlist = array();

	$source = open_xml_attribute_stream($corpus, $handle);

	while (false !== ($line = fgets($source)))
	{
		list($begin, $end, $cat) = explode("\t", trim($line, "\r\n"));
		
		$n_tokens = (int)$end - (int)$begin + 1;
		
		if (isset($catlist[$cat]))
		{
			/* known cat */
			$catlist[$cat]->words += $n_tokens;
			$catlist[$cat]->segments++;
		}
		else
		{
			/* unknown cat */
			$catlist[$cat] = (object) array('words'=>$n_tokens,'segments'=>1);
		} 
	}

	pclose($source);
	
	/* ensure no duplicates */
	do_mysql_query("delete from xml_metadata_values where corpus = '$corpus' and att_handle = '$handle'");

	foreach ($catlist as $c=>$num)
		do_mysql_query("insert into xml_metadata_values 
								(corpus, att_handle, handle, description, category_num_words, category_num_segments)
							values 
								('$corpus', '$handle', '$c', '$c', {$num->words}, {$num->segments})");
}


/**
 * Checks the CWB index of an s-attribute.
 * 
 * Returns true if the values are all handles (c-words up to 64 characters in legnth). 
 * 
 * If the attribute lacks values, or any values are not handles, returns false.
 */
function xml_index_has_handles($corpus, $att_handle)
{
	$answer = true;
	
	$source = open_xml_attribute_stream($corpus, $att_handle);
	
	while (false !== ($line = fgets($source)))
	{
		list(,,$val) = explode("\t", trim($line, "\r\n"));
		if (! preg_match('|^\w{1,64}$|', $val))
		{
			$answer = false;
			break;
		}
	}
	
	pclose($source);
	return $answer;
}


/**
 * Checks the CWB index of an s-attribute.
 * 
 * Returns true if the values are unique. 
 * 
 * If the attribute lacks values, or any value is an empty string, or any values are repeated, returns false.
 */
function xml_index_is_unique($corpus, $att_handle)
{
	$answer = true;
	
	$seen = array();
	
	$source = open_xml_attribute_stream($corpus, $att_handle);

	while (false !== ($line = fgets($source)))
	{
		list(,,$val) = explode("\t", trim($line, "\r\n"));
		if (empty($val) || isset($seen[$val]))
		{
			$answer = false;
			break;
		}
		$seen[$val] = 1;
	}
	
	pclose($source);
	return $answer;
}


/**
 * Checks the CWB index of an s-attribute.
 * 
 * Returns true if the values are all valid DATE serialisations. 
 * 
 * If any value is not a string interpretable as a DATE, returns false.
 */
function xml_index_is_valid_date($corpus, $att_handle)
{
	$answer = true;

	$source = open_xml_attribute_stream($corpus, $att_handle);

	while (false !== ($line = fgets($source)))
	{
		list(,,$val) = explode("\t", trim($line, "\r\n"));
		if (empty($val) || ! preg_match(CQPwebMetaDate::VALIDITY_REGEX, $val) )
		{
			$answer = false;
			break;
		}
	}

	pclose($source);
	return $answer;
}

/**
 * Gets a readable stream resource to cwb-s-decode for 
 * the underlying s-attribute of the specified XML.
 * 
 * Exits with an error if opening of the stream fails.
 * 
 * The resource returned can be closed with pclose().
 */ 
function open_xml_attribute_stream($corpus, $att_handle)
{
	if (false === ($c = get_corpus_info($corpus)))
		exiterror("Cannot open xml attribute stream: corpus does not exist.");
	
	if (false === ($x = get_xml_info($corpus, $att_handle)))
		exiterror("Cannot open xml attribute stream: specified s-attribute does not exist.");

	/* the above also effectively validates the arguments  */

	global $Config;
	
	$cmd = "{$Config->path_to_cwb}cwb-s-decode -r {$Config->dir->registry} {$c->cqp_name} -S $att_handle";
	
	if (false === ($source = popen($cmd, "r")))
		exiterror("Cannot open xml attribute stream: process open failed for ``$cmd'' .");

	return $source;
}



/** 
 * Function used in uninstalling a corpus. 
 * 
 * Deletes all metadata relating to all XML elements for a particular corpus.
 */
function delete_xml_metadata_for($corpus)
{
	$corpus = mysql_real_escape_string($corpus);
	
	do_mysql_query("delete from xml_metadata where corpus = '$corpus'");
	do_mysql_query("delete from xml_metadata_values where corpus = '$corpus'");
}






/* ******************************************** *
 * Functions relating to IDLINK-type attributes *
 * ******************************************** */

//TODO s-att handles are currently able to be up to 64 chars. But the idlink table names use them as part of the table names.
// This implies that, like p-atts & corpus handles, they should be limited to 20 chars
// ..................................worry about this later.

function xml_idlink_table_exists($corpus, $att_handle)
{
	$t = get_idlink_table_name($corpus, $att_handle);
	$result = do_mysql_query("show table status like '$t'");
	return (0 < mysql_num_rows($result));
}


function get_idlink_table_name($corpus, $att_handle)
{
	if ('--text' != substr($att_handle, 0, 6))
	{
		if (!cqpweb_handle_check($corpus))
			exiterror("Invalid corpus handle at database level!!");
		if (!cqpweb_handle_check($att_handle))
			exiterror("Invalid s-attribute handle at database level!!");
		return "idlink_xml_{$corpus}_{$att_handle}";
	}
	else
		; //TODO TODO TODO this is needed in order for us to have idlink columns on texts.
}


// needeD? 
// /** returns array (handle=> description) of the fields of an iflink table */
// function list_idlink_fields($corpus, $att_handle)
// {
// 	$corpus = mysql_real_escape_string($corpus);
// 	$att_handle = mysql_real_escape_string($att_handle);
	
// 	$result = do_mysql_query("select handle, description from idlink_fields where corpus='$corpus' and att_handle = '$att_handle'");
	
// 	$list = array();
	
// 	while (false !== ($o = mysql_fetch_object($result)))
// 		$list[$o->handle] = $o->description;
	
// 	return $list;
// }

/** returns array of database objects for fields of an idlink table (handle is key) */
function get_all_idlink_info($corpus, $att_handle)
{
	$corpus = mysql_real_escape_string($corpus);
	$att_handle = mysql_real_escape_string($att_handle);
	
	$result = do_mysql_query("select * from idlink_fields where corpus='$corpus' and att_handle = '$att_handle'");
	
	$list = array();
	
	while (false !== ($o = mysql_fetch_object($result)))
		$list[$o->handle] = $o;
	
	return $list;
}


/**
 * Returns an associative array of category descriptions,
 * where the keys are the handles, for the given classification.
 * 
 * If no description exists, the handle is set as the description.
 * 
 * If categories are not set up, or the $xml_handle is of a datatype other than 
 * CLASSIFICATION, the result will be an empty array.
 */
function idlink_category_listdescs($corpus, $att_handle, $field_handle)
{
	$corpus = mysql_real_escape_string($corpus);
	$att_handle = mysql_real_escape_string($att_handle);
	$field_handle = mysql_real_escape_string($field_handle);

	$result = do_mysql_query("SELECT handle, description FROM idlink_values 
									WHERE field_handle = '$field_handle' and att_handle = '$att_handle' AND corpus = '$corpus'");

	$return_me = array();
	
	while (($r = mysql_fetch_row($result)) != false)
		$return_me[$r[0]] = (empty($r[1]) ? $r[0] : $r[1]);
	
	return $return_me;
}


/**
 * Returns false if there are no bad ids in the field specified.
 * 
 * If there are bad ids, a string containing those ids (space-separated) is returned.
 */
function check_idlink_get_bad_ids($corpus, $att, $field)
{
	$corpus = mysql_real_escape_string($corpus);
	$att    = mysql_real_escape_string($att);
	$field  = mysql_real_escape_string($field);
	$table = get_idlink_table_name($corpus, $att);
	
	$result = do_mysql_query("select distinct `$field` from `$table` where `$field` REGEXP '[^A-Za-z0-9_]'");
	if (0 == mysql_num_rows($result))
		return false;

	$bad_ids = '';
	while (false !== ($r = mysql_fetch_row($result)))
		$bad_ids .= " '{$r[0]}'";
	
	return $bad_ids;
}



/**
 * Utility function for the create idlink functions.
 * 
 * Returns nothing, but deletes the idlink table and aborts the script 
 * if there are bad ids.
 * 
 * (NB - doesn't do any other cleanup e.g. temporary files).
 * 
 * This function should be called before any other updates are made to the database.
 */
function check_idlink_ids($corpus, $att)
{
	if (false === ($bad_ids = check_idlink_get_bad_ids($corpus, $att, '__ID')))
		return;
	
	$corpus = mysql_real_escape_string($corpus);
	$att = mysql_real_escape_string($att);
	$table = get_idlink_table_name($corpus, $att);
	
	/* database revert to zero text metadata prior to abort */
	do_mysql_query("drop table if exists `$table`");
	do_mysql_query("delete from idlink_fields where corpus = '$corpus'");
	
	$msg = "The data source you specified for the IDLINK metadata contains badly-formatted item ID codes, as follows: <strong>"
		. $bad_ids
		. "</strong> (IDs can only contain unaccented letters, numbers, and underscore).";
	
	exiterror($msg);
}


/**
 * Utility function for the create idlink functions.
 * 
 * Returns nothing, but deletes the idlink table and aborts the script 
 * if there are any non-word values in the specified field.
 * 
 * Use for categorisation columns.
 * 
 * (NB - doesn't do any other cleanup e.g. temporary files).
 * 
 * This function should be called before any other updates are made to the database.
 */
function check_idlink_field_words($corpus, $att, $field)
{
	if (false === ($bad_ids = check_idlink_get_bad_ids($corpus, $att, $field)))
		return;
	
	/* database revert to zero text metadata prior to abort */
	do_mysql_query("drop table if exists `$table`");
	do_mysql_query("delete from idlink_fields where corpus = '$corpus'");
	
	$msg = "The data source you specified for the IDLINK metadata contains badly-formatted "
		. " category handles in field [$field], as follows:  <strong>"
		. $bad_ids
		. " </strong> ... (category handles can only contain unaccented letters, numbers, and underscore).";
	
	exiterror($msg);	
}



/**
 * Install a idlink-metadata table.
 * 
 * @param string $corpus  The corpus affected. 
 * @param string $att     The s-attribute handle (e.g. "u_who") or "--text" id this is for text-idlink.
 * @param string $file    Full path to the input file to use.
 * @param array  $fields  Array of field descriptors (table columns). A field descriptor is an associative array
 *                        of three elements: handle, description, datatype.
 */
function create_idlink_table_from_file($corpus, $att, $file, $fields)
{
	global $Config;
	
	if (! cqpweb_handle_check($corpus))
		exiterror("Invalid corpus argument to create idlink metadata function!");
	
	if (!in_array($corpus, list_corpora()))
		exiterror("Corpus $corpus does not seem to be installed!\nMetadata setup aborts.");	
	
	$tablename = get_idlink_table_name($corpus, $att);
	
	if ('--text' != substr($att, 0, 6))
	{
		$xml = get_xml_info($corpus, $att);
		if (empty($xml))
			exiterror("XML attribute specified does not seem to exist.");
		if (METADATA_TYPE_IDLINK != $xml->datatype)
			exiterror("Cannot create an idlink table for ``$att'', it is not an IDLINK-type attribute!");
	}
	
	if (!is_file($file))
		exiterror("The metadata file you specified does not appear to exist!\nMetadata setup aborts.");

	/* create a temporary input file with the additional necessary zero fields (for counts) */
	$input_file = "{$Config->dir->cache}/___idlink_temp_{$Config->instance_name}";
	
	$source = fopen($file, 'r');
	$dest = fopen($input_file, 'w');
	while (false !== ($line = fgets($source)))
		fputs($dest, rtrim($line, "\r\n") . "\t0\t0".PHP_EOL);
	fclose($source);
	fclose($dest);


	/* get ready to process field declarations... */
	
	$classification_scan_statements = array();
	$inserts_for_idlink_fields = array();

	$create_statement = "create table `$tablename`(
		`__ID` varchar(255) NOT NULL";

	
	
	foreach ($fields as $field)
	{
		$field['handle'] = cqpweb_handle_enforce($field['handle']);
		$field['description'] = mysql_real_escape_string($field['description']);
		/* check for valid datatype */
		if(! metadata_valid_datatype($field['datatype'] = (int)$field['datatype']))
			exiterror("Invalid datatype specified for field ``{$field['handle']}''.");
		
		/* the record in the idlink-fields table has a constant format.... */
		$inserts_for_idlink_fields[] = 
			"insert into idlink_fields 
			(corpus, att_handle, handle, description, datatype)
			values 
			('$corpus', '$att', '{$field['handle']}', '{$field['description']}', {$field['datatype']} )
			";

		/* ... but the create statement depends on the datatype */
		$create_statement .= ",\n\t\t`{$field['handle']}` {$Config->metadata_mysql_type_map[$field['datatype']]}";
		
		/* ... as do any additional actions */ 
		switch ($field['datatype'])
		{
		case METADATA_TYPE_CLASSIFICATION:
			/* we need to scan this field for values to add to the values table! */
// 			$classification_scan_statements[$field['handle']] = "select distinct({$field['handle']}) from `$tablename`";
			$classification_scan_statements[$field['handle']]
				= "select `{$field['handle']}` as handle, count(*) as n_items, sum(n_tokens) as n_tokens from `$tablename` group by handle";
			break;
			
		case METADATA_TYPE_FREETEXT:
			/* no extra actions */
			break;
		
		/* TODO extra actions for other datatypes here. */
	
		/* no default needed, because we have already checked for a valid datatype above. */
		}
	}

	/* add the standard fields; begin list of indexes. */
	$create_statement .= ",
		`n_items` INTEGER UNSIGNED NOT NULL default '0',
		`n_tokens` BIGINT UNSIGNED NOT NULL default '0',
		primary key (__ID)
		";
	
	/* we also need to add an index for each classifcation-type field;
	 * we can get these from the keys of the scan-statements array */
	foreach (array_keys($classification_scan_statements) as $cur)
		$create_statement .= ", index(`$cur`) ";
	
	/* finish off the rest of the create statement */
	$create_statement .= "
		) CHARSET=utf8";

	/* now, execute everything! */
	foreach($inserts_for_idlink_fields as $ins)
		do_mysql_query($ins);

	do_mysql_query("drop table if exists `$tablename`");
	do_mysql_query($create_statement);
	
	do_mysql_infile_query($tablename, $input_file);
	unlink($input_file);

	/* check resulting table for invalid text ids and invalid category handles */
	check_idlink_ids($corpus, $att);
	/* again, use the keys of the classifications array to work out which we need to check */
	foreach (array_keys($classification_scan_statements) as $cur)
		check_idlink_field_words($corpus, $att, $cur);

	
	/* update ID totals in idlink table */

	/* map of __ID => totals. */
	$item_totals_for_id  = array();
	$token_totals_for_id = array();
	
	$source = open_xml_attribute_stream($corpus, $att);
	while (false !== ($line = fgets($source)))
	{
		list($begin, $end, $val) = explode("\t", trim($line));
		if (!isset($item_totals_for_id[$val]))
		{
			$item_totals_for_id[$val] = 0;
			$token_totals_for_id[$val] = 0;
		}
		$item_totals_for_id[$val]++;
		$token_totals_for_id[$val] += (int)$end - (int)$begin + 1;
	}
	pclose($source);
	
	/* update idlink table to contain the counts */
	foreach($item_totals_for_id as $which_id => $v)
		do_mysql_query("update `$tablename` 
				set n_items = {$item_totals_for_id[$which_id]}, n_tokens = {$token_totals_for_id[$which_id]} 
						where __ID = '$which_id'");
	
		
	/* now we can scan for & insert the classification columns */
	
	foreach($classification_scan_statements as $field_handle => $statement)
	{
		/* select `{$field['handle']}` as handle, count(*) as n_items, sum(n_tokens) as n_tokens from `$tablename` group by handle */		
		$result = do_mysql_query($statement);

		while (($o = mysql_fetch_object($result)) !== false)
			do_mysql_query("insert into idlink_values 
					(corpus,    att_handle, field_handle,    handle,         category_n_items, category_n_tokens)
					values
					('$corpus', '$att',     '$field_handle', '{$o->handle}', {$o->n_items},    {$o->n_tokens})"
				);
	}
	
	/* that should now be everything */
}



/**
 * Delete an idlink table and associated info in the idlink_* tables.
 *  
 * @param string $corpus  Corpus to which the idlinked attribute belongs.
 * @param string $att     S-attribute handle. Must be of type IDLINK.
 */
function delete_xml_idlink($corpus, $att)
{
	$corpus = mysql_real_escape_string($corpus);
	$att    = mysql_real_escape_string($att);

	$table = get_idlink_table_name($corpus, $att);
	do_mysql_query("drop table if exists `$table`");
	
	do_mysql_query("delete from idlink_fields where corpus = '$corpus' and att_handle = '$att'");
	do_mysql_query("delete from idlink_values where corpus = '$corpus' and att_handle = '$att'");
}











/*
 * ==================================
 * XML VISUALISATION FUNCTION LIBRARY
 * ==================================
 */



/** "element" must be specified with either the '~start' or '~end' suffixes. */
function xml_visualisation_delete($corpus, $element, $cond_attribute, $cond_regex)
{
	do_mysql_query("delete from xml_visualisations "
		. xml_visualisation_primary_key_whereclause($corpus, $element, $cond_attribute, $cond_regex));
}

/**
 * Turn on/off the use of an XML visualisation in context display.
 */
function xml_visualisation_use_in_context($corpus, $element, $cond_attribute, $cond_regex, $new)
{
	$newval = ($new ? 1 : 0);
	do_mysql_query("update xml_visualisations set in_context = $newval "
		. xml_visualisation_primary_key_whereclause($corpus, $element, $cond_attribute, $cond_regex));	
}

/**
 * Turn on/off the use of an XML visualisation in concordance display.
 */
function xml_visualisation_use_in_concordance($corpus, $element, $cond_attribute, $cond_regex, $new)
{
	$newval = ($new ? 1 : 0);
	do_mysql_query("update xml_visualisations set in_concordance = $newval "
		. xml_visualisation_primary_key_whereclause($corpus, $element, $cond_attribute, $cond_regex));	
}

/** 
 * Generate a where clause for db changes that must affect just one visualisation;
 * does all the string-checking and returns a full whereclause.
 */ 
function xml_visualisation_primary_key_whereclause($corpus, $element, $cond_attribute, $cond_regex)
{
	$corpus = cqpweb_handle_enforce($corpus);
	$element = mysql_real_escape_string($element);
	list($cond_attribute, $cond_regex) = xml_visualisation_condition_enforce($cond_attribute, $cond_regex);
	
	return " where corpus='$corpus' 
			and element = '$element' 
			and cond_attribute = '$cond_attribute' 
			and cond_regex = '$cond_regex'";
}

/**
 * Creates an entry in the visualisation list.
 * 
 * A previously-existing visualisation for that same tag is deleted.
 * 
 * The "code" supplied should be the input BB-code format.
 * 
 * IMPORTANT NOTE: here, $element does NOT include the "~(start|end)", whereas the other xml_vs functions
 * assume that it DOES.
 */
function xml_visualisation_create($corpus, $element, $code, $cond_attribute = '', $cond_regex = '', 
	$is_start_tag = true, $in_concordance = true, $in_context = true)
{
	/* disallow conditions in end tags (because they have no attributes) */
	if (! $is_start_tag)
		$cond_attribute = $cond_regex = '';
	
	/* make safe all db inputs: use handle enforce, where possible */
	$corpus = cqpweb_handle_enforce($corpus);
	$element = cqpweb_handle_enforce($element);
	list($cond_attribute, $cond_regex) = xml_visualisation_condition_enforce($cond_attribute, $cond_regex);
	
	$element_db = $element . ($is_start_tag ? '~start' : '~end');
	
	xml_visualisation_delete($corpus, $element_db, $cond_attribute, $cond_regex);
	
	$html = xml_visualisation_bb2html($code, !$is_start_tag);
	
	$in_concordance = ($in_concordance ? 1 : 0);
	$in_context     = ($in_context     ? 1 : 0);
	
	/* what fields are used? check the html not the bbcode, so $$$*$$$ is already removed from end tags */
	$xml_attributes = implode('~', $fields = xml_visualisation_extract_fields($html, 'xml'));
	if ($cond_attribute != '' && !in_array($cond_attribute, $fields))
		$xml_attributes .= "~$cond_attribute";
	$text_metadata  = implode('~', xml_visualisation_extract_fields($html, 'text'));


	do_mysql_query("insert into xml_visualisations
		(corpus, element, cond_attribute, cond_regex,
			xml_attributes, text_metadata, 
			in_context, in_concordance, bb_code, html_code)
		values
		('$corpus', '$element_db', '$cond_attribute', '$cond_regex', 
			'$xml_attributes', '$text_metadata',
			$in_context,$in_concordance, '$code', '$html')");
}

/** 
 * Returns an arrya contianing its two arguments, adjusted (empty strings
 * idf there is no condition, a handle and a mysql-escaped regex otherwise) 
 */
function xml_visualisation_condition_enforce($cond_attribute, $cond_regex)
{
	$cond_attribute = trim ($cond_attribute);
	
	if (! empty($cond_attribute))
	{
		$cond_attribute = cqpweb_handle_enforce($cond_attribute);
		$cond_regex = mysql_real_escape_string($cond_regex);
	}
	else
	{
		$cond_attribute = '';
		$cond_regex = '';
	}

	return array($cond_attribute, $cond_regex);
}

/** 
 * Returns an array of all fields used in the argument string.
 * 
 * XML-attributes are specified in the form $$$NAME$$$ .
 * 
 * Text metadata attributes are specified in the form ~~~NAME~~~ . 
 * 
 * Specify mode by having second argument be "text" or "xml".
 * 
 * If anything other than those two is specified, "xml" is assumed.
 * 
 * The special markers $$$$$$ and ~~~~~~ are not extracted.
 */
function xml_visualisation_extract_fields($code, $type='xml')
{
	/* set delimiter */
	if ($type == 'text' || $type == 'TEXT')
		$del = '~~~';
	else
		$del = '\$\$\$';
	
	$fields = array();
	
	$n = preg_match_all("/$del(\w*)$del/", $code, $m, PREG_SET_ORDER);
	
	foreach($m as $match)
	{
		/* note that $$$$$$ means value of this s-attribute, whereas ~~~~~~ means the ID of the current text;
		 * in both cases, we want to ignore it from this array, as it is not stored in the DB. */
		if (empty($match[1]))
			continue;
		if ( ! in_array($match[1], $fields))
			$fields[] = $match[1];
	}
	
	return $fields;
}


////////////////////////////////////////////////
// commented out pending rethink - ah 2015-09-25
////////////////////////////////////////////////
//
//function xml_visualisation_bb2html($bb_code, $is_for_end_tag = false)
//{
//	$html = escape_html($bb_code);
//	
//	/* 
//	 * OK, we have made the string safe. 
//	 * 
//	 * Now let's un-safe each of the BBcode sequences that we allow.
//	 */ 
//	
//	/* begin with tags that are straight replacements and do not require PCRE. */
//	static $from = NULL;
//	static $to = NULL;
//	if (is_null($from))
//		initialise_visualisation_simple_bbcodes($from, $to);
//	$html = str_ireplace($from, $to, $html);
//	
//	/* get rid of empty <li>s */
//	$html = preg_replace('|<li>\s*</li>|', '', $html);
//	
//	/* if there are newlines, convert to just normal spaces */
//	$html = strtr($html, "\r\n", "  ");
//	
//	/* table cells - in normal BBcode these are invariant, however, we allow
//	 * [td c=num] for colspan and [td r=num] for rowspan */
//	$func_for_table_cell_callback = function ($m)
//	{
//		$span = '';
//		if (!empty($m[2]))
//		{
//			if (0 < preg_match('/r=\d+/', $m[2], $n))
//				$span .= " rowspan={$n[1]}";
//			if (0 < preg_match('/c=\d+/', $m[2], $n))
//				$span .= " colspan={$n[1]}";
//		}
//		return "<t{$m[1]}$span>";
//	};
//	$html = preg_replace_callback('|\[t([hd])\s+([^\]]*)\]|i',  $func_for_table_cell_callback, $html );
//	
//	/* color opening tags: allow the "colour" alleged-misspelling (curse these US-centric HTML standards! */
//	$html = preg_replace('|\[colou?r=(#?\w+)\]|i', '<span style="color:$1">', $html);
//	
//	/* size opening tags: always in px rather than pt */
//	$html = preg_replace('|\[size=(\d+)\]|i', '<span style="font-size:$1px">', $html);
//	
//	/* an extension for CQPweb: create popup boxes! */
//	$html = preg_replace('|\[popup=([^\]]*])\]|', '<span onmouseover="return escape(\'$1\')">', $html);
//	
//	/* This is another CQPweb extension to BBCode, allow block and nonblock style appliers */
//	$html = preg_replace('~\[(div|span)=(\w+)\]~i', '<$1 class="XmlViz__$2">', $html);
//	
//	/* img is an odd case, in theory we could do it with simple replaces, but since it collapses down to
//	 * just one tag, let's be safe and only allow it in cases where the tags match. */
//	$html = preg_replace('|\[img\]([^"]+?)\[/img\]|i', '<img src="$1" />', $html);
//	/* we also have a variant form with height and width */	
//	$html = preg_replace('|\[img=(\d+)x(\d+)\]([^"]+?)\[/img\]|i', '<img width="$1" height="$2" src="$3" />', $html);
//	$html = preg_replace('|\[img\s+width=(\d+)\s+height=(\d+)\s*\]([^"]+?)\[/img\]|i', 
//							'<img width="$1" height="$2" src="$3" />', $html);
//	$html = preg_replace('|\[img\s+height=(\d+)\s+width=(\d+)\s*\]([^"]+?)\[/img\]|i', 
//							'<img width="$2" height="$1" src="$3" />', $html);
//	
//	/* now links - two sorts of these */
//	$html = preg_replace('|\[url\]([^"]+?)\[/url\]|i', '<a target="_blank" href="$1">$1</a>', $html);
//	$html = preg_replace('|\[url=([^"]+?)\](.+?)\[/url\]|i', '<a target="_blank" href="$1">$2</a>', $html);
//	
//	
//	if ($is_for_end_tag)
//	{
//		/* remove all attribute values: end-tags don't have them in CQP concordances. */
//		$html = preg_replace('/$$$\w*$$$/', '', $html);
//	}
//	
//	return $html;
//}
//
//
///** Initialise arrays of simple bbcode translations */
//function initialise_visualisation_simple_bbcodes(&$from, &$to)
//{
//	/* emboldened text: we use <strong>;  not <b> */
//	$from[0] = '[b]';			$to[0] =  '<strong>'; 
//	$from[1] = '[/b]';			$to[1] =  '</strong>'; 
//	
//	/* italic text: we use <em>;  not <i> */
//	$from[2] = '[i]';			$to[2] =  '<em>'; 
//	$from[3] = '[/i]';			$to[3] =  '</em>'; 
//	
//	/* underlined text: we use <u>;  not <ins> or anything silly. */
//	$from[4] = '[u]';			$to[4] =  '<u>'; 	
//	$from[5] = '[/u]';			$to[5] =  '</u>'; 
//	
//	/* struckthrough text: just use <s> */
//	$from[6] = '[s]';			$to[6] =  '<s>'; 			
//	$from[7] = '[/s]';			$to[7] =  '</s>'; 
//	
//	/* unnumbered list is easy enough. BUT the [*] that creates <li> makes life trickier. */
//	$from[8] =  '[list]';		$to[8] =   '<ul><li>';
//	$from[9] =  '[/list]';		$to[9] =   '</li></ul>';
//	$from[10] = '[*]';			$to[10] =  '</li><li>';
//	/* note we will need a regex to get rid of empty <li>s.  See main processing function. */
//
// 	/* quote is how we get at HTML blockquote. No other styling specified. */
//	$from[11] = '[quote]';		$to[11] =  '<blockquote>';
//	$from[12] = '[/quote]';		$to[12] =  '</blockquote>'; 
//	
//	/* code gives us <pre>. */
//	$from[13] = '[code]';		$to[13] =  '<pre>'; 
//	$from[14] = '[/code]';		$to[14] =  '</pre>';
//	
//	/* table main holder; td and tr are more complex */
//	$from[15] = '[table]';		$to[15] =  '<table>'; 
//	$from[16] = '[/table]';		$to[16] =  '</table>';
//	$from[17] = '[tr]';			$to[17] =  '<tr>'; 
//	$from[18] = '[/tr]';		$to[18] =  '</tr>';
//
//	/* close tags for elements with complicated opening tags */
//	$from[19] = '[/td]';		$to[19] =  '</td>';
//	$from[20] = '[/th]';		$to[20] =  '</th>';
//	$from[21] = '[/size]';		$to[21] =  '</span>';
//	$from[22] = '[/color]';		$to[22] =  '</span>';
//	$from[23] = '[/colour]';	$to[23] =  '</span>';
//	$from[24] = '[/div]';		$to[24] =  '</div>';
//	$from[25] = '[/span]';		$to[25] =  '</span>';
//	$from[34] = '[/popup]';		$to[34] =  '</span>';  // NOTE out of order number cos this was added later
//	
//	/* alternative bbcode list styles - let's support as many as possible */
//	$from[26] = '[ul]';			$to[26] =  '<ul><li>';
//	$from[27] = '[/ul]';		$to[27] =  '</li></ul>';
//	$from[28] = '[ol]';			$to[28] =  '<ol><li>';
//	$from[29] = '[/ol]';		$to[29] =  '</li></ol>';
//	
//	/* something not needed in most cases, but throw it in anyway.... */
//	$from[30] = '[centre]';		$to[30] =  '<center>';
//	$from[31] = '[center]';		$to[31] =  '<center>';
//	$from[32] = '[/centre]';	$to[32] =  '</center>';
//	$from[33] = '[/center]';	$to[33] =  '</center>';
//
///* next number: 35 */
//}


/** 
 * Gets an array of s-attributes that need to be shown in the CQP concordance line
 * in order for visualisation to work. 
 */
function xml_visualisation_s_atts_to_show()
{
	global $Corpus;

	$atts = array();

	$result = do_mysql_query("select element, xml_attributes from xml_visualisations where corpus='{$Corpus->name}'");

	while (false !== ($r = mysql_fetch_object($result)))
	{
		list($r->element) = explode('~', $r->element); 
		if ( ! in_array($r->element, $atts) )
			$atts[] = $r->element;
		if ($r->xml_attributes == '')
			continue;
		foreach (explode('~', $r->xml_attributes) as $a)
		{
			$s_a = "{$r->element}_$a";
			if ( ! in_array($s_a, $atts) )
				$atts[] = $s_a;
		}
	}
	
	return $atts;
}

