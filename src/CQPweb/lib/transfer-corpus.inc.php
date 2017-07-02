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
 * This file contains functions for exportIng and importing a corpus,
 * allowing a corpus set up on one installation of CQPweb to be moved to 
 * another without needing to reindex.
 */


///TODO TODO TODO nothing in this file is finished, or even apporximates finished.



// TODO should these functions call exiterror instead of returning false?
// yes, probably. return value not necessary.




// TODO use cqpweb_dump_targzip / cqpweb_dump_untargzip








/**
 * Export an indexed corpus into a single file that can be moved to another CQPweb installation and reimported.
 * 
 * Corpus transfer files should be given the extension ".cqpwebdata", but this is subject to the user's determination;
 * the output file will be placed in $filepath.
 * 
 * NOTE - the $Corpus->name will be embedded in multiple places. So a corpus can only be imported under
 * the same name it had in the previous system.
 * 
 * (This is a limitation which will be removed in a later version.) 
 * 
 * @see             import_cqpweb_corpus
 * 
 * @param corpus    The identifier of the corpus to export.
 * @param filepath  Path of the file to write. Note that if this file exists, it will be overwritten.
 * @return          Boolean: true for success, false for failure.
 *
 */
function export_cqpweb_corpus($corpus, $filepath)
{
	global $Config;
	
	/* check that $corpus really is a corpus */
	if (! in_array($corpus, list_corpora()))
		return false;
	
	/* create a directory in the temp space to build the structure of the .cqpwebdata file. */
	mkdir($d = "{$Config->dir->cache}/export.$corpus");
	mkdir("$d/mysql");
	mkdir("$d/reg");
	mkdir("$d/data");
	mkdir("$d/php");
	
	/* MySQL entries: */ 
	/* there are two types of things here: commands to recreate, and tables for import */
	$recreate_commands = array();
	
		/* fixed metadata */
		$fixed = mysql_fetch_assoc(do_mysql_query("select * from corpus_info where corpus='$corpus'"));
		$recreate_commands[] = "insert into corpus_info (corpus) values ('$corpus')";
		foreach($fixed as $k => $v)
		{
			switch ($k)
			{
			case 'corpus_cat':
				/* re-set to uncategorised for new system */
				$v = 1;
				break;
			case 'visible':
			case 'cwb_external':
				/* numeric / boolean entries: do nothing */
				if (is_null($v))
					$v = "NULL";
				else
					;
				break;
			default:
				/* string entries: escape */
				if (is_null($v))
					$v = "NULL";
				else
					$v = "'" . mysql_real_escape_string($v) . "'";
				break;
			}
			$recreate_commands[] = "update corpus_info set $k = $v where corpus = '$corpus'";
		}

		/* variable metadata */
		$result = do_mysql_query("select attribute, value from corpus_metadata_variable where corpus = '$corpus'");
		while (false !== ($o = mysql_fetch_object($result)))
		{
			$o->attribute = mysql_real_escape_string($o->attribute);
			$o->value = mysql_real_escape_string($o->value);
			$recreate_commands[] = "insert into corpus_metadata_variable (corpus, attribute, value) values ('$corpus','{$o->attribute}','{$o->value}')";
		}
		
		// Annotation metadata
		$result = do_mysql_query("select * from corpus_metadata_variable where corpus = '$corpus'");
		while (false !== ($r = mysql_fetch_assoc($result)))
		{
			//foreach(
			
		}
		
		
		// Any related XML settings and/or visualisations
		
		// corpus metadata table && its create_table
	
	/* write out our collected recreate-commands */
	file_put_contents("$d/mysql/recreate-commands", implode("\n", $recreate_commands)."\n");
	
	
	/* CWB data: */
	
		/* registry file */
		copy("{$Config->dir->registry}/$corpus", "$d/reg/$corpus");
		
		/* index folder */
		if (! get_corpus_metadata("cwb_external"))
			recursive_copy_directory("{$Config->dir->index}/$corpus", "$d/data/index");
		else
		{
// temp code
			// this doesn't work for now, since my test space does not have any.
			exiterror_general("You called export_cqpweb_corpus() on a corpus with an external index!!!!!");
// end temp code
			// TODO find the path from the registry file (would be useful to have an interface to the registry)
			$reg_content = file_get_contents("{$Config->dir->registry}/$corpus");
			
			$src = "TODO";
			recursive_copy_directory($src, "$d/data/index");
		}

	/* NOT INCLUDED: the ___freq corpus, and any MySQL freq tables for the corpus (they can be rebuilt on import) */

	
	
	// TODO use the functions from admin lib ?
	/* tar and gzip it all */
	exec("{$Config->path_to_gnu}tar -cf $d.tar $d");
	exec("{$Config->path_to_gnu}gzip $d.tar");
	
	/* delete entire working directory that was in temp space... */
	recursive_delete_directory($d);
	
	/* rename the tar.gz to the second argument. */
	return rename("$d.tar.gz", $filepath);
}


/**
 * Import a corpus from a file created by the function export_cqpweb_corpus().
 * 
 * Note: the corpus name is taken from the internal structure of the 

 * @param string $filepath  Path of the file to import. Note that if this file exists, it will be overwritten.
 * @return bool             True for success, false for failure.
 */
function import_cqpweb_corpus($filepath)
{
	global $Config;
	
	/* check: does file exist? */
	if(!is_file($filepath))
		return false;
	
	// gunzip from parameter into tempspace
	
	// untarcorpus_sql_
	
	// delete tar
	
	// check corpus name does not already exist; if it does, delete the folder and abort
	
	
	// CWB data:
	
		// TODO
	
	
	// MySQL rebuild:
	
		// run all recreate commands
		
		// create metadata table and load data local infile....
	
	
	// PHP:
	
		// create web folder full of stubs
		
		// move settings file
	
	
	// recursive delete twemp untarred directory
	
	return true;
}




?>
