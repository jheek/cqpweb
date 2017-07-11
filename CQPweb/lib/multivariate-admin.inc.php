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
 * Receiver script for actions relating to multivariate analysis - 
 * mostly the management of feature matrices.
 * 
 * The actions are triggered through redirect. The script has no stub of its own.
 * 
 * The actions are controlled via switcyh and mostly work by sorting through
 * the "_GET" parameters, and then calling the underlying functions
 * (mostly in multivariate.inc.php).
 * 
 * When a case is complex, it has been hived off into a function within this file.
 * 
 * Multivariate-admin is an odd name... but I could not think of a better one alas.
 */


/* include defaults and settings */
require('../lib/environment.inc.php');


/* library files */
require('../lib/library.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/cache.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/metadata.inc.php');
require('../lib/xml.inc.php');
require('../lib/multivariate.inc.php');
require('../lib/cwb.inc.php');
require('../lib/cqp.inc.php');



cqpweb_startup_environment();


$script_mode = isset($_GET['multivariateAction']) ? $_GET['multivariateAction'] : false; 

switch($script_mode)
{
case 'buildFeatureMatrix':
	/* this is excessively complex, so go into separate function. */
	$id = do_build_feature_matrix();

	/* The next location is the individual view of the new matrix. */
	$next_location = "index.php?thisQ=analyseCorpus&showMatrix=$id&uT=y";
	break;


case 'deleteFeatureMatrix':
	if (!isset($_GET['matrix']))
		exiterror_general("No matrix to delete was specified.");
	
	delete_feature_matrix((int) $_GET['matrix']);
	$next_location = "index.php?thisQ=analyseCorpus&uT=y";
	break;


case 'downloadFeatureMatrix':
	if (!isset($_GET['matrix']))
		exiterror_general("No matrix to delete was specified.");
	
	if (false === ($fm = get_feature_matrix((int) $_GET['matrix'])))
		exiterror_general("The matrix you specified does not seem to exist.");
		
	/* send out a plain text download. */
	header("Content-Type: text/plain; charset=utf-8");
	header("Content-disposition: attachment; filename={$fm->savename}.txt");
	/* following function call writes out to echo.... */
	print_feature_matrix_as_text_table($fm->id);
	break;


default:
	/* dodgy parameter: ERROR out. */
	exiterror_general("A badly-formed multivariate-analysis operation was requested!"); 
	break;
}


if (isset($next_location))
	set_next_absolute_location($next_location);



cqpweb_shutdown_environment();

exit(0);


/* ------------- *
 * END OF SCRIPT *
 * ------------- */





/*
 * =======================================
 * FUNCTIONS for running bits of the above
 * =======================================
 */

/**
 * Function that wrangles user input and calls the appropriate 
 * funcs from the feature matrix library to create a new feature matrix.
 * 
 * Returns the ID of the feature matrix needed for the next location.
 */
function do_build_feature_matrix()
{
	global $Config;
	global $User;
	global $Corpus;

	global $cqp;
	
	// TODO implement a user limit on how big a matrix can be created? database size...
	
	/*
	 * get all values from GET
	 */
	
	// TODO unit of analysis
	$analysis_unit = 'text';
	// not sure exactly how to represent these going forward. Never mind
	
	// TODO more label methods!
	if (!isset($_GET['labelMethod']))
		exiterror("The object labelling method was not specified.");
	
	//TODO temp: enforce use of text_id
	if ($_GET['labelMethod'] != 'id')
		exiterror("Currently, only the ID method of labelling data objects is available.");
	$label_method = 'id';
	
	switch(isset($_GET['corpusSubdiv']) ? $_GET['corpusSubdiv'] : '~~full~corpus~~')
	{
	case '~~full~corpus~~':
		$within_subcorpus = false;
		break;
	default:
		//TODO
		exiterror("Sorry, that's not suppported yet.");
		break;
	}
	
	/* collect an array of info on saved queries to use as features. */
	$feature_list = array();
	foreach($_GET as $k => $v)
	{
		if ( 'useQuery' == substr($k, 0, 8))
		{
			$o = new stdClass();
// 			$record = check_cache_qname($v);
			$record = QueryRecord::new_from_qname($v);
			$o->type = 'from-saved-query';
			$o->qname = $v;
			$o->label = $record->save_name;
			$o->source_info = 'Query = ';
			$o->source_info .= (empty($record->simple_query) ? $record->cqp_query : $record->simple_query);
			$feature_list[] = $o;
		}
	}
	
	// TODO other types of features
	
	/* check we have at least some features */
	if (1 > count($feature_list))
		exiterror("You haven't specific any features, so the matrix could not be built.");
		
	/* get the save-name. Unlike a save query, this is not a handle: it's a description . */
	if ((! isset($_GET['matrixName'])) || 1 > strlen($matrix_savename = trim($_GET['matrixName'])))
		exiterror("No name specified for the new feature matrix! Please go back and try again."); 
	
	/* OK, we are done collecting variables... */	
	
	/* create the entry in the fm info table */
	$id = save_feature_matrix_info( $matrix_savename, 
									$User->username, 
									$Corpus->name, 
									$within_subcorpus, 
									$analysis_unit
									); 
	
	/* add entries to the variable table */
	foreach($feature_list as $variable)
		add_feature_to_matrix($id, $variable);
	
	/* 
	 * The algorithm is as follows:
	 * - get a list of texts and their lengths from MySQL;
	 * - feature by feature, build up a multi-column infile (and create table statement)
	 * - create the table
	 * - load the infile.
	 */

	/* these are our temporary filenames: */
	$source_file = "{$Config->dir->cache}/_temp_matrix_{$Config->instance_name}.source";
	$dest_file   = "{$Config->dir->cache}/_temp_matrix_{$Config->instance_name}.dest";
		
	/* get text lengths  and build array rows */
	$result = do_mysql_query("select text_id, words from text_metadata_for_{$Corpus->name} order by text_id asc");
	$text_info = array(); /* each member of this array is a 2-element array: first = word count; second = a temporary per-feature float. */
	while (false !== ($r = mysql_fetch_row($result)))
		$text_info[$r[0]] = array ((float)$r[1], 0.0);

	file_put_contents($source_file, implode(PHP_EOL,array_keys($text_info)).PHP_EOL);
	
			
	/* begin the create table SQL... */
	
	$sqltblname = feature_matrix_id_to_tablename($id);
	
	$sql = "create table `$sqltblname` ( obj_id varchar(255) NOT NULL ";

	/* the next bit could be lengthy for a big corpus, so we need to turn off the execution time limit. */
	php_execute_time_unlimit();

	/*	
	 * FIRST COLUMN: obj id  - added above.
	 * Add one column per feature in order by reading the file. 
	 * 
	 * While we go, build the create-table.
	 */
	
	foreach($feature_list as $f)
	{
		// add a create-table line.
		$sql .= ", `{$f->label}` DOUBLE default 0.0";
		
		// reset the array
		foreach ($text_info as &$info)
			$info[1] = 0.0;

		// hack hack hack (we will not always be able to do it with texts!)
		foreach($cqp->execute("group {$f->qname} match text_id") as $line)
		{
			list($t, $n) = explode("\t", trim($line));
			$text_info[$t][1] = (float)$n / $text_info[$t][0];
		}
		
		$source = fopen($source_file, 'r');
		$dest   = fopen($dest_file, 'w');
		
		while (false !== ($line = fgets($source)))
		{
			$line = rtrim($line, "\r\n");
			list($text) = explode("\t", $line);
			$line .= "\t" . $text_info[$text][1] . PHP_EOL;
			fputs($dest, $line);
		}
		
		fclose($source);
		fclose($dest);
		
		unlink($source_file);
		rename($dest_file, $source_file);
	}
	
	// round off the create table
	$sql .= ')';

	/* create the mysql table that will contain the matrix. */
	do_mysql_query($sql);
	
	do_mysql_infile_query($sqltblname, $source_file);
	
	unlink($source_file);
	
	return $id;
}
