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
 * This file has some funcitons that deal directly with CWB index data.
 * 
 * There are only 2 of them, so they could prob be put somewhere else, and this file deleted. 
 */





/** 
 * Checks if a cwb-"corpus" exists for the specified corpus handle. 
 * 
 * @param string $corpus  The corpus handle for the CQPweb corpus. Note: this is the "name" from the "corpus"
 *                        field in the corpus_info table, not the "cqp_name". 
 * @return bool           True iff the CWB corpus exists for the specified CQPweb corpus.
 */
function cwb_corpus_exists($corpus)
{
	global $Config;
	
	/* if the registry file does not exist, the corpus definitely doesn't */
	if (! is_file("{$Config->dir->registry}/$corpus"))
		return false;

	/* now check for the existence of the data directory */
	
	$c_info = get_corpus_info($corpus);
	
	if ( false === $c_info || ! (bool) $c_info->cwb_external)
		return is_dir("{$Config->dir->index}/$corpus");
	else
	{
		$regdata = file_get_contents("{$Config->dir->registry}/$corpus");
		preg_match("/\bHOME\s+(\/[^\n\r]+)\s/", $regdata, $m);
		return is_dir($m[1]);
	}
}



/**
 * Removes a CWB corpus from the system.
 * 
 * The function also deletes any frequency-text-index in the MySQL system.
 * It doesn't touch the record of the corpus in corpus_info and other tables.
 * 
 * TODO surely it should nto touch the mysql at all?
 * 
 * The argument must be the *lowercase* version of the registry name (ie *with* the __freq 
 * suffix, if nec.)
 * 
 * TODO check against the delete function in admin-lib
 * 
 * ONLY USED ONCE in the freqlist creation function.
 * 
 * 
 */
function cwb_uncreate_corpus($corpus_name)
{
	global $Config;
	global $User;
	
	/* only superusers are allowed to do this! */
	if (! $User->is_admin())
		return;

	$dir_to_delete = "{$Config->dir->index}/$corpus_name";
	$reg_to_delete = "{$Config->dir->registry}/$corpus_name";
	
	/* delete all files in the directory and the directory itself */
	if (is_dir($dir_to_delete))
		recursive_delete_directory($dir_to_delete);
	
	/* delete the registry file */
	if (is_file($reg_to_delete))
		unlink($reg_to_delete);
		
	/* is there a text indextable derived from this cwb freq "corpus"? if so, delete */
	/* nb there will only be one *IF* this is a __freq corpus */
	do_mysql_query("drop table if exists freq_text_index_$corpus_name");

}





