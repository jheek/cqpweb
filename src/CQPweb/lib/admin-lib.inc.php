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
 * This file contains functions used in the administration of CQPweb.
 * 
 * It should generally not be included into scripts unless the user is a sysadmin.
 */




/*
 * ===========================================
 * code file self-awareness and opcode caching
 * =========================================== 
 */

/** 
 * Returns a list of realpaths for the PHP files that make up
 * the online CQPweb system. Offline "bin" scripts are excluded.
 *  
 * Return is flat array with numeric keys.
 */
function list_cqpweb_php_files($limit = 'all')
{
	$r = array();
	
	if ($limit == 'all' || $limit == 'stub')
	{
		/* add stubs */
		$r = array_merge($r, array('../index.php'));
		foreach(array('adm', 'rss', 'usr', 'exe') as $c)
			$r = array_merge($r, glob("../$c/*.php"));
	}
	
	if ($limit == 'all' || $limit == 'code')
		/* add lib + plugins */
		$r = array_merge($r, glob('../lib/*.php'), glob('../lib/plugins/*.php'));;
	
	return array_map('realpath', $r); 
}

/**
 * Detects which of the three opcache extensions is loaded, if any.
 * 
 * Returns a string (same as the internal extension label, all lowercase)
 * or false if none of them is available.
 */
function detect_php_opcaching()
{
	switch (true)
	{
	case extension_loaded('opcache'):
		return 'opcache';
	case extension_loaded('apc'):
		return 'apc';
	case extension_loaded('wincache'):
		return 'wincache';
	default:
		return false;
	}
}

/**
 * Loads a code file into whatever opcode cache is in use. 
 */
function do_opcache_load_file($file)
{
	switch (detect_php_opcaching())
	{	
	case 'apc':
		apc_compile_file(realpath($file));
		break;
	case 'opcache':
		opcache_compile_file(realpath($file));
		break;
	case 'wincache':
		/* note, we don't have an "load" in this case. So, refresh instead. */
		wincache_refresh_if_changed(array(realpath($file)));
		break;	/* default do nothing */	
	}
}

/**
 * Unloads a code file from whatever opcode cache is in use.
 */
function do_opcache_unload_file($file)
{
	switch (detect_php_opcaching())
	{
	case 'apc':
		apc_delete_file($file);
		break;
	case 'opcache':
		opcache_invalidate($file, true);
		break;
	case 'wincache':
		/* note, we don't have an "unload" in this case. So, refresh instead. */
		wincache_refresh_if_changed(array(realpath($file)));
		break;
	/* default do nothing */	
	}
}

/**
 * Loads ALL code files to opcode cache.
 * 
 * Accepts same "limit" as list_cqpweb_php_files(). 
 */
function do_opcache_full_load($limit = 'all')
{
	array_map('do_opcache_load_file', list_cqpweb_php_files($limit));
}

/** 
 * Unloads ALL code files from opcode cache.
 * 
 * Accepts same "limit" as list_cqpweb_php_files(). 
 */
function do_opcache_full_unload($limit = 'all')
{
	switch(detect_php_opcaching())
	{
	case 'apc':
		apc_delete_file(list_cqpweb_php_files($limit));
		break;
	case 'opcache':
		foreach(list_cqpweb_php_files($limit) as $f)
			opcode_invalidate($f, true);
		break;
	case 'wincache':
		wincache_refresh_if_changed(list_cqpweb_php_files($limit));
		break;
	/* default do nothing */
	}
}








/*
 * =====================================================
 * corpus setup and deletuion -0 assorted functionality.
 * =====================================================
 */




// TODO -- check this against cwb_uncreate_corpus and prevent duplication of functionality
/**
 * Main corpus-deletion function.
 * 
 * The order of installation is WEB SYMLINK -- MYSQL -- CWB.
 * 
 * So, the order of deletion is:
 * 
 * (1) delete CWB - depends on both settings file and DB entry.
 * (2) delete MySQL - does not depend on CWB still being present
 * (3) delete the web directory symlink.
 */
function delete_corpus_from_cqpweb($corpus)
{
	global $Config;
	

	if (empty($corpus))
		exiterror_general('No corpus specified. Cannot delete. Aborting.');	

	$corpus = mysql_real_escape_string($corpus);
	
	
	/* get the cwb name of the corpus, etc. */
	$result = do_mysql_query("select * from corpus_info where corpus = '$corpus'");
	if (mysql_num_rows($result) < 1)
		exiterror('Master database entry for this corpus is not present - '
			. 'accessibility of the CWB data files could not be determined.' . "\n"
			. 'This can happen if the corpus information in the database has been incorrectly inserted or '
			. 'incompletely deleted. You must delete the CWB data files and any other database references manually.');
	$info = mysql_fetch_object($result);

	
	/* we can trust strtolower() because CWB standards define identifiers as ASCII */
	$corpus_cwb_lower = strtolower($info->cqp_name);
	
	/* do we also want to delete the CWB data? */
	$also_delete_cwb = !( (bool)$info->cwb_external);
	

	/* if they exist, delete the CWB registry and data for his corpus's __freq */
	if (file_exists("{$Config->dir->registry}/{$corpus_cwb_lower}__freq"))
		unlink("{$Config->dir->registry}/{$corpus_cwb_lower}__freq");
	recursive_delete_directory("{$Config->dir->index}/{$corpus_cwb_lower}__freq");
	/* note, __freq deletion is not conditional on cwb_external -> also_delete_cwb
	 * because __freq corpora are ALWAYS created by CQPweb itself.
	 * 
	 * But the next deletion, of the main corpus CWB data, IS so conditioned.
	 *
	 * What this implies is that a registry file / data WON'T be deleted 
	 * unless CQPweb created them in the first place -- even if they are in
	 * the CQPweb standard registry / data locations. */
	if ($also_delete_cwb)
	{
		/* delete the CWB registry and data */
		if (file_exists("{$Config->dir->registry}/$corpus_cwb_lower"))
			unlink("{$Config->dir->registry}/$corpus_cwb_lower");
		recursive_delete_directory("{$Config->dir->index}/$corpus_cwb_lower");
	}
	
	/* CWB data now clean: on to the MySQL database. All these queries are "safe":
	 * they will run OK even if some of the expected data has already been deleted. */

	/* delete all saved queries, frequency tables, and dbs associated with this corpus */
	$result = do_mysql_query("select query_name from saved_queries where corpus = '$corpus'");
	while (($r = mysql_fetch_row($result)) !== false)
		delete_cached_query($r[0]);

	$result = do_mysql_query("select dbname from saved_dbs where corpus = '$corpus'");
	while (($r = mysql_fetch_row($result)) !== false)
		delete_db($r[0]);

	$result = do_mysql_query("select freqtable_name from saved_freqtables where corpus = '$corpus'");
	while (($r = mysql_fetch_row($result)) !== false)
		delete_freqtable($r[0]);

	/* delete the actual subcorpora */
	$result = do_mysql_query("select * from saved_subcorpora where corpus = '$corpus'");
	while (false !== ($sc = Subcorpus::new_from_db_result($result)))
		$sc->delete();

	/* delete main frequency tables */
	$result = do_mysql_query("select handle from annotation_metadata where corpus = '$corpus'");
	while (($r = mysql_fetch_row($result)) !== false)
		do_mysql_query("drop table if exists freq_corpus_{$corpus}_{$r[0]}");
	do_mysql_query("drop table if exists freq_corpus_{$corpus}_word");

	/* delete CWB freq-index table */
	do_mysql_query("drop table if exists freq_text_index_$corpus");

	/* clear the text metadata (see below) */
	delete_text_metadata_for($corpus);

	/* clear the annotation metadata */
	do_mysql_query("delete from annotation_metadata where corpus = '$corpus'");

	/* clear any xml-idlink metadata */
	foreach(get_xml_all_info($corpus) as $x)
		if (METADATA_TYPE_IDLINK == $x->datatype)
			delete_xml_idlink($corpus, $x->handle);
	
	/* clear the XML metadata */
	delete_xml_metadata_for($corpus);

	/* delete the variable metadata */
	do_mysql_query("delete from corpus_metadata_variable where corpus = '$corpus'");

	/* corpus_info is the master entry, so we have left it till last. */
	do_mysql_query("delete from corpus_info where corpus = '$corpus'");

	/* mysql cleanup is now complete */

	/* NOTE, this order of operations means it is possible - if a failure happens at 
	 * the right point - for the web entry to exist, but for the interface not to know
	 * about it (because there is no "master entry" in the MySQL corpus_info table).
	 * 
	 * This is low risk - a leftover symlink should not be so very problematic. */

	/* SO FINALLY: delete the web "directory" (actually a symlink to ../exe */
	if (is_link("../$corpus"))
		unlink("../$corpus"); 
	else if (is_dir("../$corpus"))
		recursive_delete_directory("../$corpus");

}





/**
 * This function, for admin use only, updates the text metadata of the corpus with begin and end 
 * positions for each text, acquired from CQP; needs running on setup.
 * 
 * It also sets wordcount totals in the main corpus_info.
 */
function populate_corpus_cqp_positions($corpus)
{
	$corpus = mysql_real_escape_string($corpus);
	$info = get_corpus_info($corpus);

	global $cqp;

	if (isset($cqp))
		$cqp_was_set = true;
	else
	{
		$cqp_was_set = false;
		connect_global_cqp($info->cqp_name);
	}

	$cqp->execute("A = <text> [] expand to text");
	$lines = $cqp->execute("tabulate A match, matchend, match text_id");

// 	foreach ($lines as $a)
// 	{
// 		list($begin, $end, $id) = explode("\t", $a);
// 		/* Doing a mysql query inside a loop would be much more efficient if we could
// 		 * use a prepared query - but, alas, we don't *YET* want to require the more recent
// 		 * versions of the mysql server that enable this (or, indeed, PHP's mysqli 
// 		 * extension that supports it) */
// 		do_mysql_query("update text_metadata_for_$corpus set cqp_begin = $begin, cqp_end = $end where text_id = '$id'");
// 	}
	/* new algorithm suggested by K. Rothenhäusler speeds up the above by reducing N of updates. */
	$temp_table = "___temp_cqp_text_positions_for_$corpus";
	do_mysql_query("create table `$temp_table` (
						`text_id` varchar(255) NOT NULL,
						`cqp_begin` BIGINT UNSIGNED NOT NULL default '0',
						`cqp_end` BIGINT UNSIGNED NOT NULL default '0',
						primary key (text_id)
					) CHARSET utf8 COLLATE utf8_bin ");
	
	$row_strings = array();	
	foreach ($lines as $a)
	{
		list($begin, $end, $id) = explode("\t", $a);
		$row_strings[] = "('$id', $begin, $end)";
		if (0 == count($row_strings) % 10000)
		{
			do_mysql_query("insert into `$temp_table` (text_id, cqp_begin, cqp_end) VALUES " 
								. implode(",", $row_strings));
			$row_strings = array();
		}
	}
	if ( !empty($lst_items) )
		do_mysql_query("insert into $temp_table (text_id, cqp_begin, cqp_end) VALUES "
							. implode(",", $row_strings));
	
	do_mysql_query("update `text_metadata_for_$corpus`
						inner join `$temp_table`
						on  `text_metadata_for_$corpus`.text_id   = `$temp_table`.text_id
						set `text_metadata_for_$corpus`.cqp_begin = `$temp_table`.cqp_begin,
							`text_metadata_for_$corpus`.cqp_end   = `$temp_table`.cqp_end");
	
	do_mysql_query("drop table `$temp_table`");

	/* update word counts for each text and for whole corpus */
	do_mysql_query("update text_metadata_for_$corpus set words = cqp_end - cqp_begin + 1");

	/* the following depends on the CQP positions being populated, because it sums "words".... */
	update_corpus_size($corpus);

	if (!$cqp_was_set)
		disconnect_global_cqp();
}




/**
 * Adds an attribute-value pair to the variable-metadata table.
 * 
 * Note, there is no requirement for attribute names to be unique.
 */
function add_variable_corpus_metadata($corpus, $attribute, $value)
{
	$corpus    = mysql_real_escape_string($corpus);
	$attribute = mysql_real_escape_string($attribute);
	$value     = mysql_real_escape_string($value);
	
	$sql = "insert into corpus_metadata_variable (corpus, attribute, value) values ('$corpus', '$attribute', '$value')";
	do_mysql_query($sql);
}

/**
 * Deletes an attribute-value pair from the variable-metadata table.
 * 
 * The pair to be deleted must both be specified, as well as the corpus,
 * because there is no requirement that attribute names be unique.
 */
function delete_variable_corpus_metadata($corpus, $attribute, $value)
{
	$corpus    = mysql_real_escape_string($corpus);
	$attribute = mysql_real_escape_string($attribute);
	$value     = mysql_real_escape_string($value);
	
	$sql = "delete from corpus_metadata_variable 
			where corpus    = '$corpus'
			and   attribute = '$attribute'
			and   value     = '$value'";
	do_mysql_query($sql);
}




/**
 * Creates a javascript function with $n password candidates that will write
 * one of its candidates to id=passwordField on each call.
 */
function print_javascript_for_password_insert($password_function = NULL, $n = 49)
{
	/* JavaScript function to insert a new string from the initialisation array */
	global $Config;
	
	if (empty($password_function))
		$password_function = $Config->create_password_function;

	foreach ($password_function($n) as $pwd)
		$raw_array[] = "'$pwd'";
	$array_initialisers = implode(',', $raw_array);
	
	return "

	<script type=\"text/javascript\">
	<!--
	function insertPassword()
	{
		if ( typeof insertPassword.index == 'undefined' ) 
		{
			/* Not here before ... perform the initilization */
			insertPassword.index = 0;
		}
		else
			insertPassword.index++;
	
		if ( typeof insertPassword.passwords == 'undefined' ) 
		{
			insertPassword.passwords = new Array( $array_initialisers);
		}
	
		document.getElementById('passwordField').value = insertPassword.passwords[insertPassword.index];
	}
	//-->
	</script>
	
	";
}


/**
 * password_insert_internal is the default function for CQPweb candidate passwords.
 * 
 * 
 * Whatever function you use must be in a source file included() in adminhome.inc.php
 * (i.e. you need to hack the code a little bit).
 * 
 * All password-creation functions must return an array of n candidate passwords.
 * 
 */
function password_insert_internal($n)
{
	$pwd = array();
	
	$randfunc = get_security_rand_function();
	
	for ( $i = 0 ; $i < $n ; $i++ )
	{
		$pwd[$i] = sprintf("%c%c%c%c%d%d%c%c%c%c",
						$randfunc(0x61, 0x7a), $randfunc(0x61, 0x7a), $randfunc(0x61, 0x7a), $randfunc(0x61, 0x7a),
						$randfunc(0,9), $randfunc(0,9),
						$randfunc(0x61, 0x7a), $randfunc(0x61, 0x7a), $randfunc(0x61, 0x7a), $randfunc(0x61, 0x7a)
						); 
	}
	return $pwd;
}



/**
 * Utility function for the create_text_metadata_..... functions.
 * 
 * Returns nothing, but deletes the text_metadata_for table and aborts the script 
 * if there are bad text ids.
 * 
 * (NB - doesn't do any other cleanup e.g. temporary files).
 * 
 * This function should be called before any other updates are made to the database.
 */
function create_text_metadata_check_text_ids($corpus)
{
	if (false === ($bad_ids = create_text_metadata_get_bad_ids($corpus, 'text_id')))
		return;

	/* database revert to zero text metadata prior to abort */
	do_mysql_query("drop table if exists text_metadata_for_" . mysql_real_escape_string($corpus));
	do_mysql_query("delete from text_metadata_fields where corpus = '" . mysql_real_escape_string($corpus) . '\'');
	
	$msg = "The data source you specified for the text metadata contains badly-formatted text"
		. " ID codes, as follows: <strong>"
		. $bad_ids
		. "</strong> (text ids can only contain unaccented letters, numbers, and underscore).";
	
	exiterror($msg);
}

/**
 * Utility function for the create_text_metadata functions.
 * 
 * Returns nothing, but deletes the text_metadata_for table and aborts the script 
 * if there are any non-word values in the specified field.
 * 
 * Use for categorisation columns. A BIT DIFFERENT to how we do it for text ids
 * (different error message).
 * 
 * (NB - doesn't do any other cleanup e.g. temporary files).
 * 
 * This function should be called before any other updates are made to the database.
 */
function create_text_metadata_check_field_words($corpus, $field)
{
	if (false === ($bad_ids = create_text_metadata_get_bad_ids($corpus, $field)))
		return;
	
	/* database revert to zero text metadata prior to abort */
	do_mysql_query("drop table if exists text_metadata_for_" . mysql_real_escape_string($corpus));
	do_mysql_query("delete from text_metadata_fields where corpus = '" . mysql_real_escape_string($corpus) . '\'');
	
	$msg = "The data source you specified for the text metadata contains badly-formatted "
		. " category handles in field [$field], as follows: "
		. $bad_ids
		. " ... (category handles can only contain unaccented letters, numbers, and underscore).";
	
	exiterror($msg);	
}

/**
 * Returns false if there are no bad ids in the field specified.
 * 
 * If there are bad ids, a string containing those ids (space-separated) is returned.
 */
function create_text_metadata_get_bad_ids($corpus, $field)
{
	$corpus = mysql_real_escape_string($corpus);
	$field  = mysql_real_escape_string($field);
	
	$result = do_mysql_query("select distinct `$field` from text_metadata_for_$corpus where `$field` REGEXP '[^A-Za-z0-9_]'");
	if (0 == mysql_num_rows($result))
		return false;

	$bad_ids = '';
	while (false !== ($r = mysql_fetch_row($result)))
		$bad_ids .= " '${r[0]}';";
	
	return $bad_ids;
}


/**
 * Groups together the function calls needed for auto-setup of freqlist after the installation of a text metadata table.
 */
function create_text_metadata_auto_freqlist_calls($corpus)
{
	print_debug_message('About to start running auto-pre-setup functions');

	$corpus = mysql_real_escape_string($corpus);

	/* do unconditionally */
	populate_corpus_cqp_positions($corpus);
	
	/* if there are any classifications... */
	if (0 < mysql_num_rows(
			do_mysql_query("select handle from text_metadata_fields 
				where corpus = '$corpus' and datatype = " . METADATA_TYPE_CLASSIFICATION)
			) )
		metadata_calculate_category_sizes($corpus);
		
	/* if there is more than one text ... */
	list($n) = mysql_fetch_row(do_mysql_query("select count(text_id) from text_metadata_for_$corpus"));
	if ($n > 1)		
		make_cwb_freq_index($corpus);
	
	/* do unconditionally */
	corpus_make_freqtables($corpus);
	
	print_debug_message('Auto-pre-setup functions complete.');
}



/**
 * Wrapper round create_text_metadata_from_file() for when we need to create the file from CQP.
 *
 * @see   create_text_metadata_from_file
 * @param $corpus  The corpus affected. (System "name").
 * @param $fields  Field descriptors, as per create_text_metadata_from_file();
 *                 however, all handles MUST be valid s-attributes.
 * @param $primary_classification
 *                 As per create_text_metadata_from_file().
 */
function create_text_metadata_from_xml($corpus, $fields, $primary_classification = NULL)
{
	global $Config;
	
	if (! cqpweb_handle_check($corpus))
		exiterror("Invalid corpus argument to create text metadata function!");

	if ( ! ($c_info = get_corpus_info($corpus)) )
		exiterror("Corpus $corpus does not seem to be installed!\nMetadata setup aborts.");	
	
	$full_filename = "{$Config->dir->upload}/___createMetadataFromXml_$corpus";

	/* quickly process the fields. */
	$fields_to_show = '';
	foreach($fields as $f)
	{
		if (!xml_exists($f['handle'], $corpus))
			exiterror("You have specified an s-attribute that does not seem to exist!");
		$fields_to_show .= ', match ' . $f['handle'];
	}
	/* other than the above, we leave all checks of the field array to the "wrapped" function */

	global $cqp;
	$cqp->set_corpus($c_info->cqp_name);
	$cqp->execute('c_M_F_xml = <text> []');
	$cqp->execute("tabulate c_M_F_xml match text_id $fields_to_show > \"$full_filename\"");

	/* the wrapping is done: pass to create_text_metadata_from_file() */
	create_text_metadata_from_file($corpus, $full_filename, $fields, $primary_classification);
	
	/* cleanup the temp file */
	unlink($full_filename);
}


/**
 * Install a text-metadata table for the given corpus.
 * 
 * @param string $corpus  The corpus affected. (System "name").
 * @param string $file    Full path to the input file to use.
 * @param array  $fields  Array of field descriptors. A field descriptor is an associative array
 *                        of three elements: handle, description, datatype.
 * @param string $primary_classification
 *                        A handle, corresponding to something in the array of fields, which is
 *                        will be installed as the corpus's primary classification. Optional.
 */
function create_text_metadata_from_file($corpus, $file, $fields, $primary_classification = NULL)
{
	global $Config;
	
	if (! cqpweb_handle_check($corpus))
		exiterror("Invalid corpus argument to create text metadata function!");
	
	if (!in_array($corpus, list_corpora()))
		exiterror("Corpus $corpus does not seem to be installed!\nMetadata setup aborts.");	
	
	if (!is_file($file))
		exiterror("The metadata file you specified does not appear to exist!\nMetadata setup aborts.");

	/* create a temporary input file with the additional necessary zero fields (for CQP positions) */
	$input_file = "{$Config->dir->cache}/___install_temp_{$Config->instance_name}";
	
	$source = fopen($file, 'r');
	$dest = fopen($input_file, 'w');
	while (false !== ($line = fgets($source)))
		fputs($dest, rtrim($line, "\r\n") . "\t0\t0\t0\n");
	fclose($source);
	fclose($dest);

	/* get ready to process field declarations... */

	
	$classification_scan_statements = array();
	$inserts_for_metadata_fields = array();

	$create_statement = "create table `text_metadata_for_$corpus`(
		`text_id` varchar(255) NOT NULL";

	
	
	foreach ($fields as $field)
	{
		$field['handle'] = cqpweb_handle_enforce($field['handle']);
		$field['description'] = mysql_real_escape_string($field['description']);
		/* check for valid datatype */
		if(! metadata_valid_datatype($field['datatype'] = (int)$field['datatype']))
			exiterror("Invalid datatype specified for field ``{$field['handle']}''.");
		
		/* the record in the metadata-fields table has a constant format.... */
		$inserts_for_metadata_fields[] = 
			"insert into text_metadata_fields 
			(corpus, handle, description, datatype)
			values 
			('$corpus', '{$field['handle']}', '{$field['description']}', {$field['datatype']} )
			";

		/* ... but the create statement depends on the datatype */
		$create_statement .= ",\n\t\t`{$field['handle']}` {$Config->metadata_mysql_type_map[$field['datatype']]}";
		
		/* ... as do any additional actions */ 
		switch ($field['datatype'])
		{
		case METADATA_TYPE_CLASSIFICATION:
			/* we need to scan this field for values to add to the values table! */
			$classification_scan_statements[$field['handle']] = "select distinct({$field['handle']}) from text_metadata_for_$corpus";
			break;
			
		case METADATA_TYPE_FREETEXT:
			/* no extra actions */
			break;
		
		/* TODO extra actions for other datatypes here. */
//TODO
//TODO
//TODO idlink especially. not done on texts,a lthough done on XML, for now..... (as of 3.2.7)
//TODO
//TODO
		
		/* no default needed, because we have already checked for a valid datatype above. */
		}
	}

	/* add the standard fields; begin list of indexes. */
	$create_statement .= ",
		`words` INTEGER NOT NULL default '0',
		`cqp_begin` BIGINT UNSIGNED NOT NULL default '0',
		`cqp_end` BIGINT UNSIGNED NOT NULL default '0',
		primary key (text_id)
		";
	
	/* we also need to add an index for each classifcation-type field;
	 * we can get these from the keys of the scan-statements array */
	foreach (array_keys($classification_scan_statements) as $cur)
		$create_statement .= ", index(`$cur`) ";
	
	/* finish off the rest of the create statement */
	$create_statement .= "
		) CHARSET=utf8";

	/* now, execute everything! */
	foreach($inserts_for_metadata_fields as $ins)
		do_mysql_query($ins);

	do_mysql_query($create_statement);
	
	do_mysql_infile_query("text_metadata_for_$corpus", $input_file);
	
	unlink($input_file);

	/* check resulting table for invalid text ids and invalid category handles */
	create_text_metadata_check_text_ids($corpus);
	/* again, use the keys of the classifications array to work out which we need to check */
	foreach (array_keys($classification_scan_statements) as $cur)
		create_text_metadata_check_field_words($corpus, $cur);


	foreach($classification_scan_statements as $field_handle => $statement)
	{
		$result = do_mysql_query($statement);

		while (($r = mysql_fetch_row($result)) !== false)
			do_mysql_query("insert into text_metadata_values 
					(corpus, field_handle, handle)
					values
					('$corpus', '$field_handle', '{$r[0]}')"
				);
	}
	
	/* if one of the classifications is primary, set it */
	if (array_key_exists($primary_classification, $classification_scan_statements))
		do_mysql_query("update corpus_info set primary_classification_field = '$primary_classification' where corpus = '$corpus'");

	/* there is no return value. IF anything has gone wrong, exiterror() will have been called above. */
}

/**
 * A much, much simpler version of create_text_metadata()
 * which simply creates a table of text_ids with no other info.
 */
function create_text_metadata_minimalist($corpus)
{
	global $Config;

	$c_info = get_corpus_info($corpus);
	
	if (empty($c_info))
		exiterror_general("Corpus $corpus does not seem to be installed!");	

	$input_file = "{$Config->dir->cache}/___install_temp_metadata_$corpus";

	exec("{$Config->path_to_cwb}cwb-s-decode -n -r \"/{$Config->dir->registry}\" {$c_info->cqp_name} -S text_id > $input_file");

	$create_statement = "create table `text_metadata_for_$corpus`(
		`text_id` varchar(255) NOT NULL default '',
		`words` INTEGER UNSIGNED NOT NULL default '0',
		`cqp_begin` BIGINT UNSIGNED NOT NULL default '0',
		`cqp_end` BIGINT UNSIGNED NOT NULL default '0',
		primary key (text_id)
		) CHARSET=utf8";

	do_mysql_query($create_statement);

	do_mysql_infile_query("text_metadata_for_$corpus", $input_file);

	create_text_metadata_check_text_ids($corpus);
	
	/* since it's minimilist, there are no classifications. */

	unlink($input_file);
	
	/* finally call position and word count update. */
	populate_corpus_cqp_positions($corpus);
}



/** 
 * Deletes the metadata table plus the records that log its fields/values.
 * this is a separate function because it reverses the "create_text_metadata_for" function 
 * and it is called by the general "delete corpus" function 
 */
function delete_text_metadata_for($corpus)
{
	$corpus = mysql_real_escape_string($corpus);
	
	/* delete the table */
	do_mysql_query("drop table if exists text_metadata_for_$corpus");
	
	/* delete its explicator records */
	do_mysql_query("delete from text_metadata_fields where corpus = '$corpus'");
	do_mysql_query("delete from text_metadata_values where corpus = '$corpus'");
}

















/*
 * ======================================================================
 * functions for dumping part/all of the CQPweb system (for backup, etc.)
 * ======================================================================
 */







/** support function for the functions that create/read from dump files. */
function dumpable_dir_basename($dump_file_path)
{
	if (substr($dump_file_path,	-7) == '.tar.gz')
		return substr($dump_file_path, 0, -7);
	else
		return rtrim($dump_file_path, '/');
}

/** 
 * Support function for the functions that create/read from dump files. 
 * 
 * Parameter: a directory to turn into a .tar.gz (path, WITHOUT .tar.gz at end). 
 */
function cqpweb_dump_targzip($dirpath)
{
	global $Config;
	
	$dir = end(explode('/', $dirpath));
	
	$back_to = getcwd();
	
	chdir($dirpath);
	chdir('..');
	
	exec("{$Config->path_to_gnu}tar -cf $dir.tar $dir");
	exec("{$Config->path_to_gnu}gzip $dir.tar");
	
	recursive_delete_directory($dirpath);

	chdir($back_to);
}

/** support function for the functions that create/read from dump files. 
 *  Parameter: a .tar.gz to turn into a directory, but does not delete the archive. */
function cqpweb_dump_untargzip($path)
{
	global $Config;
	
	$back_to = getcwd();
	
	chdir(dirname($path));
	
	$file = basename($path, '.tar.gz');
	
	exec("{$Config->path_to_gnu}gzip -d $file.tar.gz");
	exec("{$Config->path_to_gnu}tar -xf $file.tar");
	/* put the dump file back as it was */
	exec("{$Config->path_to_gnu}gzip $file.tar");
	
	chdir($back_to);
}

/**
 * A variant dump function which only dumps user-saved data.
 * 
 * This currently includes: 
 * (1) cached queries which are saved; 
 * (2) categorised queries and their database.
 * 
 * (possible additions: subcorpora, user CQP macros...)
 */
function cqpweb_dump_userdata($dump_file_path)
{
	global $Config;
	
	php_execute_time_unlimit();
	
	$dir = dumpable_dir_basename($dump_file_path);
	
	if (is_dir($dir))				recursive_delete_directory($dir);
	if (is_file("$dir.tar"))		unlink("$dir.tar");
	if (is_file("$dir.tar.gz"))		unlink("$dir.tar.gz");
	
	mkdir($dir);
	
	/* note that the layout is different to a snapshot - we do not have 
	 * subdirectories or sub-contained tar.gz files */
	
	/* copy saved queries (status: saved or saved-for-cat) */
	$saved_queries_dest = fopen("$dir/__SAVED_QUERIES_LINES", 'w');
	$result = do_mysql_query("select * from saved_queries where saved > 0");
	while (false !== ($row = mysql_fetch_row($result)))
	{
		/* copy any matching files to the location */
		foreach (glob("{$Config->dir->cache}/*:{$row[0]}") as $f)
			if (is_file($f))
				copy($f, "$dir/".basename($f));
				
		/* write this row of the saved_queries to file */
		foreach($row as &$v)
			if (is_null($v))
				$v = '\N';
				
		fwrite($saved_queries_dest, implode("\t", $row) . "\n");
	}
	fclose($saved_queries_dest);
	
	/* write the saved_catqueries table, plus each db named in it, to file */
	
	$tables_to_save = array('saved_catqueries');
	$result = do_mysql_query("select dbname from saved_catqueries");
	while (false !== ($row = mysql_fetch_row($result)))
		$tables_to_save[] = $row[0];

	$create_tables_dest = fopen("$dir/__CREATE_TABLES_STATEMENTS", "w");
	foreach ($tables_to_save as $table)
	{
		$dest = fopen("$dir/$table", "w");
		$result = do_mysql_query("select * from $table");
		while (false !== ($r = mysql_fetch_row($result)))
		{
			foreach($r as &$v)
				if (is_null($v))
					$v = '\N';
			fwrite($dest, implode("\t", $r) . "\n");
		}
		$result = do_mysql_query("show create table $table");
				list($junk, $create) = mysql_fetch_row(do_mysql_query("show create table $table"));
		fwrite($create_tables_dest, $create ."\n\n~~~###~~~\n\n");
		
		fclose($dest);
	}
	fclose($create_tables_dest);

	cqpweb_dump_targzip($dir);

	php_execute_time_relimit();
}

/**
 * Undump a userdata snapshot.
 * 
 * TODO not tested yet
 */
function cqpweb_undump_userdata($dump_file_path)
{
	global $Config;
	
	php_execute_time_unlimit();

	$dir = dumpable_dir_basename($dump_file_path);
	
	cqpweb_dump_untargzip("$dir.tar.gz");
	
	/* copy cache files back where they came from */
	foreach (glob("/$dir/*:*") as $f)
		if (is_file($f))
			copy($f, $Config->dir->cache . '/' . basename($f));

	/* load back the mysql tables */
	foreach (explode('~~~###~~~', file_get_contents("$dir/__CREATE_TABLES_STATEMENTS")) as $create_statement)
	{
		if (preg_match('/CREATE TABLE `([^`]*)`/', $create_statement, $m) < 1)
			continue;
		if ($m[1] == 'saved_catqueries')
			continue;
			/* see below for what we do with saved_catqueries */

		do_mysql_query("drop table if exists {$m[1]}");
		do_mysql_query($create_statement);
		do_mysql_infile_query($m[1], $m[1]);
	}
	
	/* now, we need to load the data back into saved_queries  --
	 * but we need to check for the existence of like-named save-queries and delete them first. 
	 * Same deal for saved_catqueries. */
	foreach (file("$dir/__SAVED_QUERIES_LINES") as $line)
	{
		list($qname, $junk, $corpus) = explode("\t", $line);
		do_mysql_query("delete from saved_queries where query_name = '$qname' and corpus = '$corpus'");
	}
	//do_mysql_query("$mysql_LOAD_DATA_INFILE_command '$dir/__SAVED_QUERIES_LINES' into table saved_queries");
	do_mysql_infile_query('saved_queries', "$dir/__SAVED_QUERIES_LINES");

	foreach (file("$dir/saved_catqueries") as $line)
	{
		list($qname, $junk, $corpus) = explode("\t", $line);
		do_mysql_query("delete from saved_catqueries where catquery_name = '$qname' and corpus = '$corpus'");
	}
	//do_mysql_query("$mysql_LOAD_DATA_INFILE_command '$dir/saved_catqueries' into table saved_catqueries");
	do_mysql_infile_query('saved_catqueries', "$dir/saved_catqueries");

	recursive_delete_directory($dir);
	
	php_execute_time_relimit();

}

/**
 * Dump an entire snapshot of the CQPweb system.
 */
function cqpweb_dump_snapshot($dump_file_path)
{
	global $Config;
	
	php_execute_time_unlimit();
	
	$dir = dumpable_dir_basename($dump_file_path);
	
	if (is_dir($dir))				recursive_delete_directory($dir);
	if (is_file("$dir.tar"))		unlink("$dir.tar");
	if (is_file("$dir.tar.gz"))		unlink("$dir.tar.gz");
	
	mkdir($dir);
	
	cqpweb_mysql_dump_data("$dir/__DUMPED_DATABASE.tar.gz");
	
	mkdir("$dir/cache");
	
	/* copy the cache */
	foreach(scandir($Config->dir->cache) as $f)
		if (is_file("{$Config->dir->cache}/$f"))
			copy("{$Config->dir->cache}/$f", "$dir/cache/$f");
		
	/* NOTE: we do not attempt to dump out CWB registry or data files. */
			
	cqpweb_dump_targzip($dir);
	
	php_execute_time_relimit();
}

function cqpweb_undump_snapshot($dump_file_path)
{
	global $Config;
	
	php_execute_time_unlimit();

	$dir = dumpable_dir_basename($dump_file_path);
	
	cqpweb_dump_untargzip("$dir.tar.gz");
	
	/* copy cache files back where they came from */
	foreach(scandir("$dir/cache") as $f)
		if (is_file("$dir/cache/$f"))
			copy("$dir/cache/$f", "{$Config->dir->cache}/$f");
	
	/* corpus settings: create the directory if necessary */
	foreach (scandir("$dir") as $sf)
	{
		if (!is_file($sf))
			continue;
		list($corpus) = explode('.', $sf);
		if (! is_dir("../$corpus"))
			mkdir("../$corpus");
		/* in case these were damaged or not yet created... */
		install_create_corpus_script_files("../$corpus");
	}
	
	/* call the MySQL undump function */
	cqpweb_mysql_undump_data("$dir/__DUMPED_DATABASE.tar.gz");

	recursive_delete_directory($dir);
	
	php_execute_time_relimit();
}


/**
 * Does a data dump of the current status of the mysql database.
 * 
 * The database is written to a collection of text files that are compressed
 * into a .tar.gz file (whose location should be specified as either
 * an absolute path or a path relative to the working directory of the script
 * that calls this function.)
 * 
 * Note that the path, minus the .tar.gz extension, will be created as an
 * intermediate directory during the dump process.
 * 
 * The form of the .tar is as follows: one text file per table in the database,
 * plus one text file containing create table statements as PHP code.
 * 
 * If the $dump_file_path argument does not end in ".tar.gz", then that 
 * extension will be added.
 * 
 * TODO not tested yet
 */
function cqpweb_mysql_dump_data($dump_file_path)
{
	$dir = dumpable_dir_basename($dump_file_path);
		
	if (is_dir($dir))				recursive_delete_directory($dir);
	if (is_file("$dir.tar"))		unlink("$dir.tar");
	if (is_file("$dir.tar.gz"))		unlink("$dir.tar.gz");
			
	mkdir($dir);
		
	$create_tables_dest = fopen("$dir/__CREATE_TABLES_STATEMENTS", "w");
	
	$list_tables_result = do_mysql_query("show tables");
	while (false !== ($r = mysql_fetch_row($list_tables_result)))
	{
		list($junk, $create) = mysql_fetch_row(do_mysql_query("show create table {$r[0]}"));
		fwrite($create_tables_dest, $create ."\n\n~~~###~~~\n\n");
		
		$dest = fopen("$dir/{$r[0]}", "w");
		$result = do_mysql_query("select * from {$r[0]}");
		while (false !== ($line_r = mysql_fetch_row($result)))
		{
			foreach($line_r as &$v)
				if (is_null($v))
					$v = '\N';
			fwrite($dest, implode("\t", $line_r) . "\n");
		}
		fclose($dest);
	}
	
	fclose($create_tables_dest);
	
	cqpweb_dump_targzip($dir);
}

/**
 * Undoes the dumping of the mysql directory.
 * 
 * Note that this overwrites any tables of the same name that are present.
 * 
 * TODO NOT TESTED YET.
 * 
 * If the $dump_file_path argument does not end in ".tar.gz", then that 
 * extension will be added.
 */
function cqpweb_mysql_undump_data($dump_file_path)
{	
	$dir = dumpable_dir_basename($dump_file_path);
	
	cqpweb_dump_untargzip("$dir.tar.gz");
	
	foreach (explode('~~~###~~~', file_get_contents("$dir/__CREATE_TABLES_STATEMENTS")) as $create_statement)
	{
		if (preg_match('/CREATE TABLE `([^`]*)`/', $create_statement, $m) < 1)
			continue;
		do_mysql_query("drop table if exists {$m[1]}");
		do_mysql_query($create_statement);
		//do_mysql_query("$mysql_LOAD_DATA_INFILE_command '{$m[1]}' into table {$m[1]}");
		do_mysql_infile_query($m[1], $m[1]);
	}
	
	recursive_delete_directory($dir);
}


















/*
 * =============================
 * MySQL setup / reset functions
 * =============================
 */






/**
 * Function to set the mysql setup to its initialised form.
 */
function cqpweb_mysql_total_reset()
{
	foreach (array( 'db_', 
					'freq_corpus_', 
					'freq_sc_',
					'freq_text_index_',
					'temporary_freq_', 
					'text_metadata_for_',
					'__freqmake_temptable'
					)
			as $prefix)
	{
		$result = do_mysql_query("show tables like \"$prefix%\"");
		while ( ($r = mysql_fetch_row($result)) !== false)
			do_mysql_query("drop table if exists $r");
	}
	
	$array_of_create_statements = cqpweb_mysql_recreate_tables();

	foreach ($array_of_create_statements as $name => $statement)
	{
		do_mysql_query("drop table if exists $name");
		do_mysql_query($statement);
	}
	
	$array_of_extra_statements = cqpweb_mysql_recreate_extras();
	
	foreach ($array_of_extra_statements as $statement)
		do_mysql_query($statement);		
}

/**
 * Gives you the create table statements for setup as an array.
 */
function cqpweb_mysql_recreate_tables()
{
	$create_statements = array();

	/* 
	 * IMPORTANT NOTE.
	 * 
	 * MySQL 5.5.5 (in Q2 2010) changed the default storage engine to InnoDB. 
	 * 
	 * CQPweb was originally based on the assumption that the engine would be MyISAM and
	 * thus, several of the statements below contained MyISAM-isms.
	 * 
	 * In Nov 2013, the MyISAM-isms were removed, so it will still work with the default InnoDB.
	 * 
	 * HOWEVER, fulltext index was not added to InnoDB until 5.6 (rel in Feb 2013)... ergo...
	 * 
	 * Note that we need InnoDB to allow tables to be stored in different locations (i.e. on different
	 * drives if necessary....)
	 */
	global $mysql_link;
	list($major, $minor, $rest) = explode('.', mysql_get_server_info($mysql_link), 3);
	$engine_if_fulltext_key_needed = ( ($major > 5 || ($major == 5 && $minor >= 6) ) ? 'ENGINE=InnoDB' : 'ENGINE=MyISAM');
	$engine = 'ENGINE=InnoDB';
	
	/*
	 * STRING FIELD LENGTHS TO USE
	 * ===========================
	 *  
	 * EXCEPTION: dbname is 200 because historically it was built from many components. 
	 * However,now its maxlength = 'db_catquery_' (12) plus the length of an instance name (which is 10).
	 * But we are keeping it at 200 for now because old data could be lost otherwise.
	 * 
	 * Long string (for names, descriptions, etc) - varchar 255
	 * 
	 * Handles - are informed by the limits of MYSQL. Most MySQL identfiiers are limited to 64 chars.
	 * =======
	 *  - the corpus:      20  (as needs to be a sub-part of a table name)
	 *  - p-attributes:    20  (also need to be a sub-part of a table name)
	 *  - s-attributes:    64  (as may need to be a column name)
	 *  - metadata fields: 64  (as may need to be a column name)
	 *  - username:        64
	 *  - metadata values: 200 (as they may need to be part of a key made up of multiple varchars)
	 *  - save names:      200 (to fit with the above)
	 *  - unique text ID:  255 (as they may need to be a key on their own) 
	 */
	
	/* nb it is somewhat inconsistent that here "name" = long desc rather than short handle. never mind.... */
	$create_statements['annotation_mapping_tables'] =
		"CREATE TABLE `annotation_mapping_tables` (
			`handle` varchar(20) NOT NULL,
			`name` varchar(255), 
			`mappings` text character set utf8,
			primary key(`handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['annotation_metadata'] =
		"CREATE TABLE `annotation_metadata` (
			`corpus` varchar(20) NOT NULL,
			`handle` varchar(20) NOT NULL,
			`description` varchar(255) default NULL,
			`is_feature_set` tinyint(1) NOT NULL default 0,
			`tagset` varchar(255) default NULL,
			`external_url` varchar(255) default NULL,
			primary key (`corpus`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['annotation_template_info'] =
		"CREATE TABLE `annotation_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			`primary_annotation` varchar(20) default NULL,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['annotation_template_content'] =
		"CREATE TABLE `annotation_template_content` (
			`template_id` int unsigned NOT NULL,
			`order_in_template` smallint unsigned,
			`handle` varchar(20) NOT NULL,
			`description` varchar(255) default NULL,
			`is_feature_set` tinyint(1) NOT NULL default 0,
			`tagset` varchar(255) default NULL,
			`external_url` varchar(255) default NULL
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";	

	
	$create_statements['corpus_categories'] =
		"CREATE TABLE `corpus_categories` (
			`id` int NOT NULL AUTO_INCREMENT,
			`label` varchar(255) DEFAULT '',
			`sort_n` int NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";
	
	
	$create_statements['corpus_info'] =
		"CREATE TABLE `corpus_info` (
			/* 
			 * General fields: identity, CWB data addresses, && size 
			 */
			`id` int NOT NULL AUTO_INCREMENT,
			`corpus` varchar(20) NOT NULL,                                           # NB. This is always 100% the same as the handle in the PHP code.
			`cqp_name` varchar(255) NOT NULL default '',                             # needed because cwb_external might be true.........
			`date_of_indexing` timestamp NOT NULL default CURRENT_TIMESTAMP,
			`cwb_external` tinyint(1) NOT NULL default 0,
			`is_user_corpus` tinyint(1) NOT NULL default 0,
			`size_tokens` int NOT NULL DEFAULT 0,
			`size_types` int NOT NULL DEFAULT 0,
			`size_texts` int NOT NULL DEFAULT 0,

			/* 
			 * Licensing and access info
			 */
			`access_statement` TEXT default NULL,

			/* 
			 * Search & analysis settings
			 */
			`primary_classification_field` varchar(64) default NULL,
			`uses_case_sensitivity` tinyint(1) NOT NULL default 0,

			/* 
			 * System display: how the corpus is listed / appears in the interface
			 */
			`visible` tinyint(1) NOT NULL default 1,
			`title` varchar(255) default '', 
			`corpus_cat` int NOT NULL DEFAULT 1,
			`external_url` varchar(255) default NULL,
			`public_freqlist_desc` varchar(150) default NULL,
			`css_path` varchar(255) default '../css/CQPweb.css',

			/* 
			 * Annotation (p-attribute) info fields
			 */
			`primary_annotation` varchar(20) default NULL,
			`secondary_annotation` varchar(20) default NULL,
			`tertiary_annotation` varchar(20) default NULL,
			`tertiary_annotation_tablehandle` varchar(40) default NULL,
			`combo_annotation` varchar(20) default NULL,

			/* 
			 * Concordance/Context: appearance and visualisation control
			 * NB. Move viz to separate table?
			 */
			`main_script_is_r2l` tinyint(1) NOT NULL default 0,
			`conc_s_attribute` varchar(64) NOT NULL default '', # default for this + next translates to 12 words.
			`conc_scope` smallint NOT NULL default 12,
			`initial_extended_context` smallint NOT NULL default 100,
			`max_extended_context` smallint NOT NULL default 1100,
			`alt_context_word_att` varchar(20) default '',
			`visualise_gloss_in_concordance` tinyint(1) NOT NULL default 0,
			`visualise_gloss_in_context` tinyint(1) NOT NULL default 0,
			`visualise_gloss_annotation` varchar(20) default NULL,
			`visualise_translate_in_concordance` tinyint(1) NOT NULL default 0,
			`visualise_translate_in_context` tinyint(1) NOT NULL default 0,
			`visualise_translate_s_att` varchar(64) default NULL,
			`visualise_position_labels` tinyint(1) NOT NULL default 0,
			`visualise_position_label_attribute` varchar(64) default NULL,

			/* 
			 * Housekeeping
			 */
			`indexing_notes` TEXT default NULL,

			unique key (`corpus`),
			primary key (`id`)

		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";

	
	$create_statements['corpus_metadata_variable'] =
		"CREATE TABLE `corpus_metadata_variable` (
			`corpus` varchar(20) NOT NULL,
			`attribute` text NOT NULL,
			`value` text,
			key(`corpus`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['idlink_fields'] =
		"CREATE TABLE `idlink_fields` (
			`corpus` varchar(20) NOT NULL,
			`att_handle` varchar(64) NOT NULL,
			`handle` varchar(64) NOT NULL,
			`description` varchar(255) default NULL,
			`datatype` tinyint(2) NOT NULL default 0,   # uses METADATA_TYPE_* constants
			primary key (`corpus`, `att_handle`, `handle`)
	) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['idlink_values'] =
		"CREATE TABLE `text_metadata_values` (
			`corpus` varchar(20) NOT NULL,
			`att_handle` varchar(64) NOT NULL,
			`field_handle` varchar(64) NOT NULL,
			`handle` varchar(200) NOT NULL,
			`description` varchar(255) default NULL,
			`category_n_items` int unsigned default NULL,
			`category_n_tokens` int unsigned default NULL,
			primary key(`corpus`, `att_handle`, `field_handle`, `handle`)
	) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['metadata_template_info'] = 
		"CREATE TABLE `metadata_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			`primary_classification` varchar(64) default NULL,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['metadata_template_content'] =
		"CREATE TABLE `metadata_template_content` (
			`template_id` int unsigned NOT NULL,
			`order_in_template` smallint unsigned,
			`handle` varchar(64) NOT NULL,
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_FREETEXT . "
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['query_history'] =
		"create table query_history (
			`instance_name` varchar(31) default NULL,
			`user` varchar(64) default NULL,
			`corpus` varchar(20) NOT NULL default '',
			`cqp_query` text  NOT NULL,
			`query_scope` text,
			`date_of_query` timestamp NOT NULL default CURRENT_TIMESTAMP,
			`hits` int(11) default NULL,
			`simple_query` text,
			`query_mode` varchar(12) default NULL,
			KEY `user` (`user`),
			KEY `corpus` (`corpus`),
			KEY `cqp_query` (`cqp_query`(255))
		) $engine CHARACTER SET utf8 collate utf8_bin";

	
	$create_statements['saved_catqueries'] =
		"CREATE TABLE `saved_catqueries` (
			`catquery_name` varchar(150) NOT NULL,
			`user` varchar(64) default NULL,
			`corpus` varchar(20) NOT NULL  default '',
			`dbname` varchar(150) NOT NULL  default '',
			`category_list` TEXT,
			KEY `catquery_name` (`catquery_name`),
			KEY `user` (`user`),
			KEY `corpus` (`corpus`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['saved_dbs'] =
		"CREATE TABLE `saved_dbs` (
			`dbname` varchar(200) NOT NULL,
			`user` varchar(64) default NULL,
			`create_time` int(11) default NULL,
			`cqp_query` text NOT NULL,
			`query_scope` text,
			`postprocess` text,
			`corpus` varchar (20) NOT NULL default '',
			`db_type` varchar(15) default NULL,
			`colloc_atts` varchar(200) default '',
			`colloc_range` int default 0,
			`sort_position` int default 0,
			`db_size` bigint UNSIGNED default NULL,
			`saved` tinyint(1) NOT NULL default 0,
			primary key(`dbname`),
			key (`user`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['saved_freqtables'] =
		"CREATE TABLE `saved_freqtables` (
			`freqtable_name` varchar(150) NOT NULL,
			`corpus` varchar(20) NOT NULL default '',
			`user` varchar(64) default NULL,
			`query_scope` text,
			`create_time` int(11) default NULL,
			`ft_size` bigint UNSIGNED default NULL,
			`public` tinyint(1) default 0,
			primary key (`freqtable_name`),
			key `query_scope` (`query_scope`(255))
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	

	$create_statements['saved_matrix_info'] =
		"CREATE TABLE `saved_matrix_info` (
			`id` int NOT NULL AUTO_INCREMENT,
			`savename` varchar(200),
			`user` varchar(64) default NULL,
			`corpus` varchar(20) NOT NULL default '',
			`subcorpus` varchar(200) NOT NULL default '',   #TODO this could be changed to an integer, couldn't it?? 
			`unit` varchar(200) default NULL,
			`create_time` int(11) default NULL,
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['saved_matrix_features'] = 
		"CREATE TABLE `saved_matrix_features` (
			`id` int NOT NULL AUTO_INCREMENT,
			`matrix_id` int NOT NULL,
			`label` varchar(255) NOT NULL,
			`source_info` varchar(255) default NULL,
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['saved_queries'] =
		"CREATE TABLE `saved_queries` (
			`query_name` varchar(150) NOT NULL,
			`user` varchar(64) default NULL,
			`corpus` varchar(20) NOT NULL default '',
			`query_mode` varchar(12) default NULL,
			`simple_query` text,
			`cqp_query` text NOT NULL,
			`query_scope` text,
			`postprocess` text,
			`hits_left` text,
			`time_of_query` int(11) default NULL,
			`hits` int(11) default NULL,
			`hit_texts` int(11) default NULL,
			`file_size` int(10) unsigned default NULL,
			`saved` tinyint(1) default 0,
			`save_name` varchar(200) default NULL,
			KEY `query_name` (`query_name`),
			KEY `user` (`user`),
			KEY `corpus` (`corpus`),
			FULLTEXT KEY `query_scope` (`query_scope`),
			FULLTEXT KEY `postprocess` (`postprocess`(100)),
			KEY `time_of_query` (`time_of_query`),
			FULLTEXT KEY `cqp_query` (`cqp_query`)
		) $engine_if_fulltext_key_needed CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['saved_restrictions'] =
		"CREATE TABLE `saved_restrictions` (
			`id` bigint unsigned NOT NULL AUTO_INCREMENT,
			`cache_time` bigint unsigned NOT NULL default 0,
			`corpus` varchar(20) NOT NULL default '',
			`serialised_restriction` text,
			`n_items` int unsigned,
			`n_tokens` bigint unsigned,
			`data` longblob,
			primary key (`id`),
			key(`corpus`),
			key(`serialised_restriction`(255))
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['saved_subcorpora'] =
		"CREATE TABLE `saved_subcorpora` (
			`id` bigint unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(200) NOT NULL default '',
			`corpus` varchar(20) NOT NULL default '',
			`user` varchar(64) default NULL,
			`content` mediumtext,
			`n_items` int(11) unsigned default NULL,
			`n_tokens` bigint(21) unsigned default NULL,
			primary key (`id`),
			key(`corpus`, `user`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	

	$create_statements['system_info'] =
		"CREATE TABLE `system_info` (
			setting_name varchar(20) NOT NULL collate utf8_bin,
			value varchar(255),
			primary key(`setting_name`)
		) CHARACTER SET utf8 COLLATE utf8_general_ci"; /* note that for this one we don't care about the engine */


	$create_statements['system_longvalues'] =
		"CREATE TABLE `system_longvalues` (
			`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			`id` varchar(40) NOT NULL,
			`value` text NOT NULL,
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['system_messages'] =
		"CREATE TABLE `system_messages` (
			`message_id` varchar(150) NOT NULL,
			`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			`header` varchar(150) default '',
			`content` text character set utf8 collate utf8_bin,
			`fromto` varchar(150) default NULL,
			primary key (`message_id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";
	
	
	$create_statements['system_processes'] =
		"CREATE TABLE `system_processes` (
			`dbname` varchar(200) NOT NULL,
			`begin_time` int(11) default NULL,
			`process_type` varchar(15) default NULL,
			`process_id` varchar(15) default NULL,
			primary key (`dbname`)
		) CHARACTER SET utf8 COLLATE utf8_bin"; /* note that for this one we don't care about the engine */
	

	$create_statements['text_metadata_fields'] =
		"CREATE TABLE `text_metadata_fields` (
			`corpus` varchar(20) NOT NULL,
			`handle` varchar(64) NOT NULL,
			`description` varchar(255) default NULL,
			`datatype` tinyint(2) NOT NULL default 0,   # uses METADATA_TYPE_* constants
			primary key (`corpus`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['text_metadata_values'] =
		"CREATE TABLE `text_metadata_values` (
			`corpus` varchar(20) NOT NULL,
			`field_handle` varchar(64) NOT NULL,
			`handle` varchar(200) NOT NULL,
			`description` varchar(255) default NULL,
			`category_num_words` int unsigned default NULL,
			`category_num_files` int unsigned default NULL,
			primary key(`corpus`, `field_handle`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['user_captchas'] = 
		"CREATE TABLE `user_captchas` (
			`id` bigint unsigned NOT NULL AUTO_INCREMENT,
			`captcha` char(6),
			`expiry_time` int unsigned,
			primary key (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['user_cookie_tokens'] =
		"CREATE TABLE `user_cookie_tokens` (
			`token` bigint UNSIGNED NOT NULL default 0,
			`user_id` int NOT NULL,
			`creation`  int UNSIGNED NOT NULL default 0,
			`expiry`  int UNSIGNED NOT NULL default 0,
			key(`token`, `user_id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['user_grants_to_users'] =
		"CREATE TABLE `user_grants_to_users` (
			`user_id` int NOT NULL,
			`privilege_id` int NOT NULL,
			`expiry_time` int UNSIGNED NOT NULL default 0
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";
	
	
	$create_statements['user_grants_to_groups'] =
		"CREATE TABLE `user_grants_to_groups` (
			`group_id` int NOT NULL,
			`privilege_id` int NOT NULL,
			`expiry_time` int UNSIGNED NOT NULL default 0
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";
	
	
	$create_statements['user_groups'] =
		"CREATE TABLE `user_groups` (
			`id` int NOT NULL AUTO_INCREMENT,
			`group_name` varchar(20) NOT NULL UNIQUE COLLATE utf8_bin,
			`description` varchar(255) NOT NULL default '',
			`autojoin_regex` text,
			primary key (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_general_ci";


	$create_statements['user_info'] =
		"CREATE TABLE `user_info` (
			`id` int NOT NULL AUTO_INCREMENT,
			`username` varchar(64) NOT NULL,
			`realname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
			`email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
			`affiliation` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
			`country` char(2) default '00',
			`passhash` char(61),
			`acct_status` tinyint(1) NOT NULL default 0,
			`verify_key` varchar(32) default NULL,
			`expiry_time` int UNSIGNED NOT NULL default 0,
			`password_expiry_time` int UNSIGNED NOT NULL default 0,
			`last_seen_time` timestamp NOT NULL default 0,
			`acct_create_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
			`conc_kwicview` tinyint(1),
			`conc_corpus_order` tinyint(1),
			`cqp_syntax` tinyint(1),
			`context_with_tags` tinyint(1),
			`use_tooltips` tinyint(1),
			`css_monochrome` tinyint(1) NOT NULL default 0,
			`thin_default_reproducible` tinyint(1),
			`coll_statistic` tinyint,
			`coll_freqtogether` int,
			`coll_freqalone` int,
			`coll_from` tinyint,
			`coll_to` tinyint,
			`max_dbsize` int(10) unsigned default NULL,
			`linefeed` char(2) default NULL,
			unique key(`username`),
			primary key (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";

	
	$create_statements['user_macros'] =
		"CREATE TABLE `user_macros` (
			`id` int NOT NULL AUTO_INCREMENT,
			`user` varchar(64) NOT NULL,
			`macro_name` varchar(20) NOT NULL default '',
			`macro_num_args` int,
			`macro_body` text,
			unique key(`user`, `macro_name`),
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['user_memberships'] = 
		"CREATE TABLE `user_memberships` (
			`user_id` int NOT NULL,
			`group_id` int NOT NULL,
			`expiry_time` int UNSIGNED NOT NULL default 0
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	
	
	$create_statements['user_privilege_info'] =
		"CREATE TABLE `user_privilege_info` (
			`id` int NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default '',
			`type` tinyint(1) unsigned default NULL,
			`scope` text,
			primary key(`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	

	$create_statements['xml_metadata'] = 
		"CREATE TABLE `xml_metadata` (
			`id` int NOT NULL AUTO_INCREMENT,      # note: we can use the numeric id in situations where the handle won't fit. int base 36 = max 13 chars
			`corpus` varchar(20) NOT NULL,
			`handle` varchar(64) NOT NULL,
			`att_family` varchar(64) NOT NULL default '',
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_NONE . ",
			primary key(`id`),
			unique key (`corpus`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['xml_metadata_values'] = 
		"CREATE TABLE `xml_metadata_values` (
			`corpus` varchar(20) NOT NULL,
			`att_handle` varchar(64) NOT NULL,
			`handle` varchar(200) NOT NULL,
			`description` varchar(255) default NULL, 
			`category_num_words` int unsigned default NULL,
			`category_num_segments` int unsigned default NULL,
			primary key(`corpus`, `att_handle`, `handle`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['xml_template_info'] = 
		"CREATE TABLE `xml_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			PRIMARY KEY (`id`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";


	$create_statements['xml_template_content'] =
		"CREATE TABLE `xml_template_content` (
			`template_id` int unsigned NOT NULL,
			`order_in_template` smallint unsigned,
			`handle` varchar(64) NOT NULL,
			`att_family` varchar(64) NOT NULL default '',
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_NONE . "
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";




// TODO this table needs a redesign before the actual feature is implemented.

	$create_statements['xml_visualisations'] =
		"CREATE TABLE `xml_visualisations` (
			`id` int NOT NULL AUTO_INCREMENT,
			`corpus` varchar(20) NOT NULL default '',
			`element` varchar(64) NOT NULL default '',
			`cond_attribute` varchar(64) NOT NULL default '',
			`cond_regex` varchar(100) NOT NULL default '',
			`xml_attributes` varchar(100) NOT NULL default '',
			`text_metadata` varchar(255) NOT NULL default '',
			`in_concordance` tinyint(1) NOT NULL default 1,
			`in_context` tinyint(1) NOT NULL default 1,
			`bb_code` text,
			`html_code` text,
			primary key (`id`),
			unique key(`corpus`, `element`, `cond_attribute`, `cond_regex`)
		) $engine CHARACTER SET utf8 COLLATE utf8_bin";
	/* note that, because the attribute/regex condition must be part of the primary key, the regex is limited to
	 * 100 UTF8 characters (keys cannot exceed 1000 bytes = 333 utf8 chars) */ 
	
	
	return $create_statements;
}


/**
 * Returns an array of statements that should be run
 * to put the system into initial state, AFTER creation of the tables.
 */ 
function cqpweb_mysql_recreate_extras()
{
	return array(
		'insert into user_groups (group_name,description)values("superusers","Users with admin power")',
		'insert into user_groups (group_name,description)values("everybody","Group to which all users automatically belong")',
		'insert into system_info (setting_name, value)values("db_version","' . CQPWEB_VERSION . '")',
		'insert into system_info (setting_name, value)values("install_date", "' .  @date('Y-m-d H:i') . '") ',
		'insert into system_info (setting_name, value)values("db_updated", "'   .  @date('Y-m-d H:i') . '") ',
		);
}









/*
 * ==========================================================================================
 * functions dealing with "skins" i.e. the CSS files that implement different colour schemes.
 * ==========================================================================================
 */


function cqpweb_import_css_file($filename)
{
	global $Config;
	
	$orig = "{$Config->dir->upload}/$filename";
	$new = "../css/$filename";
	
	if (is_file($orig))
	{
		if (is_file($new))
			exiterror_general("A CSS file with that name already exists. File not copied.");
		else
			copy($orig, $new);
	}
}



/**
 * Installs the default "skins" ie CSS colour schemes.
 * 
 * Note, doesn't actually specify that one of these should be used anywhere
 * -- just makes them available.
 */
function cqpweb_regenerate_css_files()
{
	// TODO this could be coded more compactly...
	
	$yellow_pairs = array(
		'#ffeeaa' =>	'#ddddff',		/* error */
		'#bbbbff' =>	'#ffbb77',		/* dark */
		'#ddddff' =>	'#ffeeaa'		/* light */
		);
		
	$green_pairs = array(
		'#ffeeaa' =>	'#ffeeaa',		/* error */
		'#bbbbff' =>	'#66cc99',		/* dark */
		'#ddddff' =>	'#ccffcc'		/* light */
		);
		
	$red_pairs = array(
		'#ffeeaa' =>	'#ddddff',		/* error */
		'#bbbbff' =>	'#ff8899',		/* dark */
		'#ddddff' =>	'#ffcfdd'		/* light */
		);
	
	$brown_pairs = array(
		'#ffeeaa' =>	'#ffeeaa',		/* error */
		'#bbbbff' =>	'#cd663f',		/* dark */
		'#ddddff' =>	'#eeaa77'		/* light */
		);
	
	$purple_pairs = array(
		'#ffeeaa' =>	'#ffeeaa',		/* error */
		'#bbbbff' =>	'#be71ec',		/* dark */
		'#ddddff' =>	'#dfbaf5'		/* light */
		);
	
	$darkblue_pairs = array(
		'#ffeeaa' =>	'#ffeeaa',		/* error */
		'#bbbbff' =>	'#0066aa',		/* dark */
		'#ddddff' =>	'#33aadd'		/* light */
		);
	$lime_pairs = array(
		'#ffeeaa' =>	'#00ffff',		/* error */
		'#bbbbff' =>	'#B9FF6F',		/* dark */
		'#ddddff' =>	'#ECFF6F'		/* light */
		);
	$aqua_pairs = array(
		'#ffeeaa' =>	'#ffeeaa',		/* error */
		'#bbbbff' =>	'#00ffff',		/* dark */
		'#ddddff' =>	'#b0ffff'		/* light */
		);
	$neon_pairs = array(
		'#ffeeaa' =>	'#00ff00',		/* error */
		'#bbbbff' =>	'#ff00ff',		/* dark */
		'#ddddff' =>	'#ffa6ff'		/* light */
		);
	$dusk_pairs = array(
		'#ffeeaa' =>	'#ffeeaa',		/* error */
		'#bbbbff' =>	'#8000ff',		/* dark */
		'#ddddff' =>	'#d1a4ff'		/* light */
		);
	$gold_pairs = array(
		'#ffeeaa' =>	'#80ffff',		/* error */
		'#bbbbff' =>	'#808000',		/* dark */
		'#ddddff' =>	'#c1c66c'		/* light */
		);
	$rose_pairs = array(
		'#ffeeaa' =>	'#827839',		/* error */
		'#bbbbff' =>	'#bd6d5d',		/* dark */
		'#ddddff' =>	'#edc9af'		/* light */
		);
	$teal_pairs = array(
		'#ffeeaa' =>	'#e67451',		/* error */
		'#bbbbff' =>	'#009090',		/* dark */
		'#ddddff' =>	'#d0ffff'		/* light */
		);
	/* black will have to wait since inserting white text only where necessary is complex
	$black_pairs = array(
		'#ffeeaa' =>	'#ddddff',		/* error * /
		'#bbbbff' =>	'#ff8899',		/* dark * /
		'#ddddff' =>	'#ffcfdd'		/* light * /
		);
	*/
	
	
	$css_file = cqpweb_css_file();
	
	file_put_contents('../css/CQPweb.css', $css_file);
	file_put_contents('../css/CQPweb-yellow.css', 	strtr($css_file, $yellow_pairs));
	file_put_contents('../css/CQPweb-green.css', 	strtr($css_file, $green_pairs));
	file_put_contents('../css/CQPweb-red.css', 		strtr($css_file, $red_pairs));
	file_put_contents('../css/CQPweb-brown.css', 	strtr($css_file, $brown_pairs));
	file_put_contents('../css/CQPweb-purple.css', 	strtr($css_file, $purple_pairs));
	file_put_contents('../css/CQPweb-darkblue.css', strtr($css_file, $darkblue_pairs));
	file_put_contents('../css/CQPweb-lime.css', 	strtr($css_file, $lime_pairs));
	file_put_contents('../css/CQPweb-aqua.css', 	strtr($css_file, $aqua_pairs));
	file_put_contents('../css/CQPweb-neon.css', 	strtr($css_file, $neon_pairs));
	file_put_contents('../css/CQPweb-dusk.css', 	strtr($css_file, $dusk_pairs));
	file_put_contents('../css/CQPweb-gold.css', 	strtr($css_file, $gold_pairs));
	file_put_contents('../css/CQPweb-rose.css', 	strtr($css_file, $rose_pairs));
	file_put_contents('../css/CQPweb-teal.css', 	strtr($css_file, $teal_pairs));

	
	/* creating the monochrome version for users with vision problems is a spot trickier... */
	$monochrome = $css_file;
	/* start by changing all colors to black. Then switch all background colors to white. */
	$monochrome = preg_replace('|color:.*?;|', 'color: black;', $monochrome);
	$monochrome = str_replace('background-color: black', 'background-color: white', $monochrome);
	/* and make table borders just one pixel */
	$monochrome = preg_replace('|border-width:\s*\d+px|', 'border-width: 1px', $monochrome);
	
	file_put_contents('../css/CQPweb-user-monochrome.css', $monochrome);
}






/**
 * Returns the code of the default CSS file for built-in colour schemes.
 */
function cqpweb_css_file ()
{
	return <<<END_OF_CSS_DATA


/* top page heading */

h1 {
	font-family: Verdana;
	text-align: center;
}



/* different paragraph styles */

p.errormessage {
	font-family: verdana;
	font-size: large;
}

p.instruction {
	font-family: verdana;
	font-size: 10pt;
}

p.helpnote {
	font-family: verdana;
	font-size: 10pt;
}

p.bigbold {
	font-family: verdana;
	font-size: medium;
	font-weight: bold;
}

p.spacer {
	font-size: small;
	padding: 0pt;
	line-height: 0%;
	font-size: 10%;
}

span.hit {
	color: red;
	font-weight: bold;
}

span.contexthighlight {
	font-weight: bold;
}

span.concord-time-report {
	color: gray;
	font-size: 10pt;
	font-weight: normal
}


/* table layout */


table.controlbox {
	border: large outset;
}

td.controlbox {
	font-family: Verdana;
	padding-top: 5px;
	padding-bottom: 5px;
	padding-left: 10px;
	padding-right: 10px;
	border: medium outset;
}




table.concordtable {
	border-style: solid;
	border-color: #ffffff; 
	border-width: 5px;
}

th.concordtable {
	padding-left: 3px;
	padding-right: 3px;
	padding-top: 7px;
	padding-bottom: 7px;
	background-color: #bbbbff;
	font-family: verdana;
	font-weight: bold;
	border-style: solid;
	border-color: #ffffff; 
	border-width: 2px;
}

td.concordgeneral {
	padding-left: 7px;
	padding-right: 7px;
	padding-top: 3px;
	padding-bottom: 3px;
	background-color: #ddddff;	
	font-family: verdana;
	font-size: 10pt;
	border-style: solid;
	border-color: #ffffff; 
	border-width: 2px;
}	


td.concorderror {
	padding-left: 7px;
	padding-right: 7px;
	padding-top: 3px;
	padding-bottom: 3px;
	background-color: #ffeeaa;	
	font-family: verdana;
	font-size: 10pt;
	border-style: solid;
	border-color: #ffffff; 
	border-width: 2px;
}


td.concordgrey {
	padding-left: 7px;
	padding-right: 7px;
	padding-top: 3px;
	padding-bottom: 3px;
	background-color: #d5d5d5;	
	font-family: verdana;
	font-size: 10pt;
	border-style: solid;
	border-color: #ffffff; 
	border-width: 2px;
}	


td.before {
	padding: 3px;
	background-color: #ddddff;	
	border-style: solid;
	border-color: #ffffff; 
	border-top-width: 2px;
	border-bottom-width: 2px;
	border-left-width: 2px;
	border-right-width: 0px;
	text-align: right;
}

td.after {
	padding: 3px;
	background-color: #ddddff;	
	border-style: solid;
	border-color: #ffffff; 
	border-top-width: 2px;
	border-bottom-width: 2px;
	border-left-width: 0px;
	border-right-width: 2px;
	text-align: left;
}

td.node {
	padding: 3px;
	background-color: #f0f0f0;	
	border-style: solid;
	border-color: #ffffff; 
	border-top-width: 2px;
	border-bottom-width: 2px;
	border-left-width: 0px;
	border-right-width: 0px;
	text-align: center;
}

td.lineview {
	padding-left: 7px;
	padding-right: 7px;
	padding-top: 3px;
	padding-bottom: 3px;
	background-color: #ddddff;	
	border-style: solid;
	border-color: #ffffff; 
	border-width: 2px;
}	

td.text_id {
	padding: 3px;
	background-color: #ddddff;	
	border-style: solid;
	border-color: #ffffff; 
	border-width: 2px;
	text-align: center;
}

td.end_bar {
	padding: 3px;
	background-color: #d5d5d5;	
	font-family: verdana;
	border-style: solid;
	border-color: #ffffff; 
	border-width: 2px;
	text-align: center;
}


td.basicbox {
	padding-left: 7px;
	padding-right: 7px;
	padding-top: 10px;
	padding-bottom: 10px;
	background-color: #ddddff;	
	font-family: verdana;
	font-size: 10pt;
}


td.cqpweb_copynote {
	padding-left: 7px;
	padding-right: 7px;
	padding-top: 3px;
	padding-bottom: 3px;
	background-color: #ffffff;
	font-family: verdana;
	font-size: 8pt;
	color: gray;
	border-style: solid;
	border-color: #ffffff; 
	border-width: 2px;
}


/* different types of link */
/* first, for left-navigation in the main query screen */
a.menuItem:link {
	white-space: nowrap;
	font-family: verdana;
	color: black;
	font-size: 10pt;
	text-decoration: none;
}
a.menuItem:visited {
	white-space: nowrap;
	font-family: verdana;
	color: black;
	font-size: 10pt;
	text-decoration: none;
}
a.menuItem:hover {
	white-space: nowrap;
	font-family: verdana;
	color: red;
	font-size: 10pt;
	text-decoration: underline;
}

/* next, for the currently selected menu item 
 * will not usually have an href 
 * ergo, no visited/hover */
a.menuCurrentItem {
	white-space: nowrap;
	font-family: verdana;
	color: black;
	font-size: 10pt;
	text-decoration: none;
}


/* next, for menu bar header text item 
 * will not usually have an href 
 * ergo, no visited/hover 
a.menuHeaderItem {
	white-space: nowrap;
	font-family: verdana;
	color: black;
	font-weight: bold;
	font-size: 10pt;
	text-decoration: none;

}*/

/* here, for footer link to help */
a.cqpweb_copynote_link:link {
	color: gray;
	text-decoration: none;
}
a.cqpweb_copynote_link:visited {
	color: gray;
	text-decoration: none;
}
a.cqpweb_copynote_link:hover {
	color: red;
	text-decoration: underline;
}
END_OF_CSS_DATA;


}



