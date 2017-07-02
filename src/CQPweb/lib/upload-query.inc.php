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
 * This file contains the script for uploading query files.
 */

require('../lib/environment.inc.php');

require('../lib/user-lib.inc.php');
require('../lib/cqp.inc.php');
require('../lib/cache.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/library.inc.php');
require('../lib/uploads.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/html-lib.inc.php');

cqpweb_startup_environment();



/* ----------------------------------------- *
 * check that we have the parameters we need * 
 * ----------------------------------------- */



/* do we have the save name? */

if (isset($_GET["uploadQuerySaveName"]))
	$save_name = $_GET["uploadQuerySaveName"];
else
	exiterror_parameter('No save name was specified!');


/* do we have the array of the uploaded file? */

if (! (isset($_FILES["uploadQueryFile"]) && is_array($_FILES["uploadQueryFile"])) )
	exiterror_parameter('Information on the uploaded file was not found!');



/* ------------------- *
 * check the save name *
 * ------------------- */


/* is it a handle? */
if (! cqpweb_handle_check($save_name) )
	exiterror(array(
					'Names for saved queries can only contain letters, numbers and the underscore character (&ldquo;_&rdquo;)!',
					'Please use the BACK-button of your browser and change your input accordingly.'
					) );

/* Does a query by that name already exist for (this user + this corpus) ? */
if ( save_name_in_use($save_name) )
	exiterror(array(
					/* note, it's safe to echo back without XSS risk, because we know it is handle at this point */
					"A saved query with the name ``$save_name'' already exists.",
					'Please use the BACK-button of your browser and change your input accordingly.'
					) );	



/* ------------------ *
 * get file locations *
 * ------------------ */

/* get the filepath of the uploaded file */

$uploaded_file = uploaded_file_to_upload_area($_FILES["uploadQueryFile"]["name"], 
                                              $_FILES["uploadQueryFile"]["type"],
                                              $_FILES["uploadQueryFile"]["size"],
                                              $_FILES["uploadQueryFile"]["tmp_name"],
                                              $_FILES["uploadQueryFile"]["error"],
                                              true
                                              );

/* determine the filepath we want to put it in for undumping */

$undump_file = $uploaded_file;

while (file_exists($undump_file ))
	$undump_file .= '_';





/* ----------------------------------------------------------------------------- *
 * incremetally copy the file and check its format: every line two \d+ with tabs *
 * ----------------------------------------------------------------------------- */

$source = fopen($uploaded_file, 'r');
$dest = fopen($undump_file, 'w');
$count = 0;
$hits = 0;

while (false !== ($line = fgets($source)))
{
	$count++;
	
	/* do what tidyup we can, to reduce errors */
	$line = rtrim($line);
	
	if (empty($line))
		continue;
	
	if ( ! (0 < preg_match('/\A\d+\t\d+\z/', $line)) )
	{
		/* error detected */
		fclose($source);
		fclose($dest);
		unlink($undump_file);
		unlink($uploaded_file);
		$paragraphs = array();
		$paragraphs[] = 'Your uploaded file has a format error.';
		$paragraphs[] = 'The file must only consist of two columns of numbers (separated by a tab-stop).';
		$paragraphs[] = 'The error was encountered at line ' . $count . '. The incorrect line is as follows:';
		$paragraphs[] = "   $line   ";
		$paragraphs[] = 'Please amend your query file and retry the upload.';
		exiterror_general($paragraphs);
	}
	
	/* for line breaks, we are now OS-independent, here at least! */
	fputs($dest, $line . PHP_EOL);
	$hits++;
}

fclose($source);
fclose($dest);
unlink($uploaded_file);




/* -------------------------------------------------- *
 * Create a saved query in cache from the undump file *
 * -------------------------------------------------- */

$qname = qname_unique($Config->instance_name);


/* undump to CQP as a new query, and save */
$cqp->execute("undump $qname < '$undump_file'");
$cqp->execute("save $qname");


/* delete the uploaded file */
unlink($undump_file);

/* work out how many texts have hits (for the DB record) */
$num_of_texts = count( $cqp->execute("group $qname match text_id") );

/* put the query into the saved queries DB */
$cache_record = QueryRecord::create(
		$qname, 
		$User->username, 
		$Corpus->name, 
		'uploaded', 
		'', 
		'', 
		QueryScope::new_by_unserialise(""),
// 		NULL, NULL,
		$hits, 
		$num_of_texts, 
		"upload[{$Config->instance_name}]"
		);
$cache_record->saved = CACHE_STATUS_SAVED_BY_USER;
$cache_record->save_name = $save_name;
$cache_record->save();



/* and let's finish, assuming all succeeded, by redirecting ... */
set_next_absolute_location('index.php?thisQ=savedQs&uT=y');

cqpweb_shutdown_environment();

