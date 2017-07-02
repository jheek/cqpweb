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
 * Library of functions for dealing with frequency tables for corpora and
 * subcorpora.
 * 
 * These are stored (largely) in MySQL.
 * 
 * Frequency table naming convention:
 * 
 * for a corpus:	freq_corpus_{$corpus}_{$att}
 * 
 * for a subcorpus:	freq_sc_{$corpus}_{$instance_name}_{$att}
 * 
 */

/*
 * =========================
 * FREQTABLE SETUP FUNCTIONS
 * =========================
 * 
 */

/**
 * Creates MySQL frequency tables for each attribute in a corpus;
 * any pre-existing tables are deleted.
 */
function corpus_make_freqtables($corpus)
{
	global $Config;
	global $User;
	global $cqp;
	
	/* only superusers are allowed to do this! */
	if (! $User->is_admin())
		return;

	$corpus = mysql_real_escape_string($corpus);
	
	$c_info = get_corpus_info($corpus);

	$corpus_sql_collation = deduce_corpus_mysql_collation($c_info);

	
	/* list of attributes on which to make frequency tables */
	$attribute = array_merge(array('word'), array_keys(get_corpus_annotations($corpus)));
	
	/* create a temporary table */
	$temp_tablename = "temporary_freq_corpus_{$corpus}";
	do_mysql_query("DROP TABLE if exists $temp_tablename");

	$sql = "CREATE TABLE $temp_tablename (
		freq int(11) unsigned default NULL";
	foreach ($attribute as $att)
		$sql .= ",
			$att varchar(255) NOT NULL";
	foreach ($attribute as $att)
		$sql .= ",
			key ($att)";
	$sql .= "
		) CHARACTER SET utf8 COLLATE $corpus_sql_collation";

	do_mysql_query($sql);
	

	/* for convenience, $filename is absolute */
	$filename = "{$Config->dir->cache}/____$temp_tablename.tbl";

	/* now, use cwb-scan-corpus to prepare the input */	
	$cwb_command = "{$Config->path_to_cwb}cwb-scan-corpus -r \"{$Config->dir->registry}\" -o \"$filename\" -q {$c_info->cqp_name}";
	foreach ($attribute as $att)
		$cwb_command .= " $att";
	$status = 0;
	$msg = array();
	exec($cwb_command . ' 2>&1', $msg, $status);
	if ($status != 0)
		exiterror("cwb-scan-corpus error!\n" . implode("\n", $msg));
	
	/* We need to check if the CorpusCharset is other than ASCII/UTF8. 
	 * If it is, we need to call the library function that runs over it with iconv. */
	if (($corpus_charset = $cqp->get_corpus_charset()) != 'utf8')
	{
		$utf8_filename = $filename .'.utf8.tmp';
		
		change_file_encoding($filename, 
		                     $utf8_filename, 
		                     CQP::translate_corpus_charset_to_iconv($corpus_charset), 
		                     CQP::translate_corpus_charset_to_iconv('utf8') . '//TRANSLIT');
		
		unlink($filename);
		rename($utf8_filename, $filename);
		/* so now, either way, we need to work further on $filename. */
	}


	database_disable_keys($temp_tablename);
	do_mysql_infile_query($temp_tablename, $filename, true);
	database_enable_keys($temp_tablename);

	unlink($filename);

	/* OK - the temporary, ungrouped frequency table is in memory. 
	 * Each line is a unique binary line across all the attributes.
	 * It needs grouping differently for each attribute. 
	 * (This will also take care of putting 'the', 'The' and 'THE' together,
	 * if the collation does that) */

	foreach ($attribute as $att)
	{
		$sql_tablename = "freq_corpus_{$corpus}_$att";

		do_mysql_query("DROP TABLE if exists $sql_tablename");

		$sql = "CREATE TABLE $sql_tablename (
			freq int(11) unsigned default NULL,
			item varchar(255) NOT NULL,
			primary key(item)
			) CHARACTER SET utf8 COLLATE $corpus_sql_collation";
		do_mysql_query($sql);
		
		database_disable_keys($sql_tablename);
		$sql = "
			INSERT INTO $sql_tablename 
				select sum(freq) as f, `$att` as item 
					from $temp_tablename
					group by `$att`";

		do_mysql_query($sql);
		database_enable_keys($sql_tablename);
	}

	/* delete temporary ungrouped table */
	do_mysql_query("DROP TABLE if exists $temp_tablename");
}







/**
 * Creates frequency lists for a --subsection only-- of the current corpus, 
 * ie a restriction or subcorpus.
 * 
 * Note that the specification of a subcorpus trumps restrictions. 
 * (As elsewhere, e.g. when a query happens.) 
 * 
 * @ param string $subcorpus    Serialisation -- ie, integer id in string form; or, empty string.
 * @ param string $restriction  Serialisation -- ie, DB string serialised form rEstriciton obj; or, empty string.
 * 
 * @param QueryScope $qscope  QueryScope object containining the subsection to make freqtables for.
 */
function subsection_make_freqtables($qscope)
{
	global $Config;
	global $Corpus;
	global $User;
	
	global $cqp;

	if (empty($cqp))
		connect_global_cqp();

	
	/* list of attributes on which to make frequency tables */
	$attribute = array('word');
	foreach (get_corpus_annotations() as $a => $junk)
		$attribute[] = $a;


	/* From the unique instance name, create a freqtable base name */
	$freqtables_base_name = freqtable_name_unique("freq_sc_{$Corpus->name}_{$Config->instance_name}");


	/* register this script as working to create a freqtable, after checking there is room for it */
	if ( check_db_max_processes('freqtable') === false )
		exiterror_toomanydbprocesses('freqtable');
	register_db_process($freqtables_base_name, 'freqtable');

	
	/* BEFORE WE START: can we use the text-index, or do we need to fall-back to cwb-scan-corpus? */ 
	
	/* set $use_freq_index to true if the item type is 'text', and get an item list. */
	$item_type = $item_identifier = NULL;
	$list = $qscope->get_item_list($item_type, $item_identifier);
	$use_freq_index = ($item_type == 'text' && $item_identifier == 'id');
	/* if the subsection is a set of complete texts, we can use the more efficient approach via the CWB frequency table
	 * (essentially a cwb-encoded cache of the output from cwb-scan-corpus, grouped text-by-text). */

	/* the temporary table names flag the algorithm version (just in case) */
	$algov = ($use_freq_index ? 'v1' : 'v2');

	
	/* OK we are READY TO RUMBLE. */

	/* STEP 1: Check cache contents. (We do this before building, in order that we don't overflow the cache
	 * by TOO much in the intermediate step when the new freq table is being built.) */
	delete_freqtable_overflow();
	
	
	/* STEP 2: set up the temp vars for the Master Frequency table (will contain ungrouped data!) as a temporary MySQL thingy */
	$master_table = "__freqmake_temptable{$algov}_{$Config->instance_name}";
	do_mysql_query("DROP TABLE if exists $master_table");
	$master_table_loadfile = "{$Config->dir->cache}/__infile$algov$master_table";

	/* This is how we CREATE the Master Frequency table for subcorpus frequencies */
	$sql = "CREATE TABLE `$master_table` ( `freq` int(11) unsigned NOT NULL default 0";
	foreach ($attribute as $att)
		$sql .= ", `$att` varchar(255) NOT NULL default ''";
	foreach ($attribute as $att)
		$sql .= ", key(`$att`)";
	$sql .= ") CHARACTER SET utf8 COLLATE {$Corpus->sql_collation}";
	do_mysql_query($sql);
		
		
	/* NOW: here we switch between the two possible algorithms */
	if ($use_freq_index)
	{
		/* use the algorithm that was originally used: ie, use the freq text index to get already-grouped frequencies by text_id. */
		
		/* save regions to be scanned to a temp file */
		$regionfile = new CQPInterchangeFile($Config->dir->cache);
		$region_list_array = get_freq_index_positionlist_for_text_list($list, $qscope->get_corpus());
	
		foreach ($region_list_array as $reg)
			$regionfile->write("{$reg[0]}\t{$reg[1]}\n");
	
		$regionfile->finish();
	
		/* run command to extract the frequency lines for those bits of the corpus */
		$cmd_scancorpus = "{$Config->path_to_cwb}cwb-scan-corpus -r \"{$Config->dir->registry}\" -F __freq "
			. "-R \"" . $regionfile->get_filename()
			. "\" {$Corpus->cqp_name}__FREQ";
		foreach ($attribute as $att)
			$cmd_scancorpus .= " $att+0";
		$cmd_scancorpus .= " > \"$master_table_loadfile\"";
		
		$status = 0;
		$msg = array();
		exec($cmd_scancorpus, $msg, $status);
		if ($status != 0)
			exiterror("cwb-scan-corpus error!\n" . implode("\n", $msg));
		
		/* close and delete the temp file containing the text regions */
		$regionfile->close(); // nb, if we don't use CQPInterchangeFile, then we can extract out a lot more of the algorithm outside the if-else.
		
		/* END OF ORIGNAL ALGORITHM for subsections consiting of a full number of texts */
	}
	else
	{
		/* use the algorithm for arbitrary sets of ranges: using a dumpfile plus cwb-scan-corpus, this is more like the
		 * manner in which the whole corpus's frequency list was originally created.    */

		/* the regions of the original corpus are in the scope's dumpdfile */
		$remove_file = false;
		$region_path = $qscope->get_dumpfile_path($remove_file);

		/* use cwb-scan-corpus to prepare the input */
		$cmd_scancorpus = "{$Config->path_to_cwb}cwb-scan-corpus -r \"{$Config->dir->registry}\" -o \"$master_table_loadfile\" "
			. "-R \"$region_path\" -q {$Corpus->cqp_name}";
		/* nb the big difference on Algorithm 2 is that we don't have -F option, because we are grouping data 
		 * from the original corpus, not from a CWB-encoded frequency index. */
		foreach ($attribute as $att)
			$cmd_scancorpus .= " $att";
		$cmd_scancorpus .= ' 2>&1';
		
		$status = 0;
		$msg = array();
		exec($cmd_scancorpus, $msg, $status);
		if ($status != 0)
			exiterror("cwb-scan-corpus error!\n" . implode("\n", $msg));
		
		/* if necessary, remove the file (if it was a temp one) */
		if ($remove_file)
			unlink($region_path);
		
		/* END OF REVISED ALGORITHM for subsections not based on full set of text_ids. */ 
	}

	/* 
	 * the following bits are in common to the two algorithms. 
	 */

	
	/* We need to check if the CorpusCharset is other than ASCII/UTF8. 
	 * If it is, we need to open & cycle iconv on the whole thing.     */
	if (($corpus_charset = $cqp->get_corpus_charset()) != 'utf8')
	{
		$utf8_filename = $master_table_loadfile .'.utf8.tmp';
		
		change_file_encoding($master_table_loadfile, 
		                     $utf8_filename, 
		                     CQP::translate_corpus_charset_to_iconv($corpus_charset), 
		                     CQP::translate_corpus_charset_to_iconv('utf8') . '//TRANSLIT');
		
		unlink($master_table_loadfile);
		rename($utf8_filename, $master_table_loadfile);
	}
	
	
	/* ok, now we are ready to transfer the base frequency list from the master loadfile into the master table in mysql */
		
	database_disable_keys($master_table);
	do_mysql_infile_query($master_table, $master_table_loadfile, true);
	database_enable_keys($master_table);
	
	unlink($master_table_loadfile);
		
	
		
	
	/* we now have the ungrouped frequency table ("master table") in MySQL, all we need to do is group its contents
	 * differently to create a freqlist-table for each attribute from the master table */

	foreach ($attribute as $att)
	{
		$att_sql_name = "{$freqtables_base_name}_{$att}";
		do_mysql_query("DROP TABLE if exists `$att_sql_name`");
		
		/* create the table */
		$sql = "create table `$att_sql_name` (
			freq int(11) unsigned default NULL,
			item varchar(255) NOT NULL,
			primary key(item)
			) CHARACTER SET utf8 COLLATE {$Corpus->sql_collation}";
		do_mysql_query($sql);

		/* and fill it */
		database_disable_keys($att_sql_name);
		$sql = "insert into $att_sql_name 
					select sum(freq), `$att` from $master_table
					group by `$att`";
		do_mysql_query($sql);
		database_enable_keys($att_sql_name);
	} 
	/* end foreach $attribute */
	
	/* delete the temporary ungrouped "master" table */
	do_mysql_query("DROP TABLE if exists `$master_table`");
		
	
	/* end of two-algorithm section: all that remains is to create a record for this freqtable. */

	$thistime = time();
	$thissize = get_freqtable_size($freqtables_base_name);

	$sql = "insert into saved_freqtables (
			freqtable_name,
			corpus,
			user,
			query_scope,
			create_time,
			ft_size
		) values (
			'$freqtables_base_name',
			'$Corpus->name',
			'{$User->username}',
			'" . $qscope->serialise() . "',
			$thistime,
			$thissize
		)";
		/* no need to set `public`: it sets itself to 0 by default */
	do_mysql_query($sql);


	/* NB: freqtables share the dbs' register/unregister functions, with process_type 'freqtable' */
	unregister_db_process();


	/* Check cache contents AGAIN (in case the newly built frequency table has overflowed the cache limit */
	delete_freqtable_overflow();

	
	/* return as an assoc array a copy of what has just gone into saved_freqtables */
	return (object) array (
		'freqtable_name' => $freqtables_base_name,
		'corpus' => $Corpus->name,
		'user' => $User->username,
		'query_scope' => $qscope->serialise(),
		'create_time' => $thistime,
		'ft_size' => $thissize,
		'public' => 0
		);
} /* end of function subsection_make_freqtables() */








function make_cwb_freq_index($corpus)
{
	global $Config;
	global $User;
	
	/* only superusers are allowed to do this! */
	if (! $User->is_admin())
		return;
	
	$corpus = mysql_real_escape_string($corpus);
	
	$c_info = get_corpus_info($corpus);
	
	/* disallow this function for corpora with only one text */
	list($count_of_texts_in_corpus) = mysql_fetch_row(do_mysql_query("select count(*) from text_metadata_for_$corpus"));
	if ($count_of_texts_in_corpus < 2)
		exiterror("This corpus only contains one text. Using a CWB frequency text-index is therefore neither necessary nor desirable.");
	
	/* this function may take longer than the script time limit */
	php_execute_time_unlimit();
	
	
	/* list of attributes on which to make frequency tables */
	$attribute[] = 'word';
	$p_att_line = '-P word ';
	$p_att_line_no_word = '';
	foreach (get_corpus_annotations($corpus) as $a => $junk)
	{
		if ($a == '__freq')  /* very unlikely, but... */
			exiterror("you've got a p-att called __freq!! That's very much not allowed.");
		$attribute[] = $a;
		$p_att_line .= "-P $a ";
		$p_att_line_no_word .= "-P $a ";
	}

	/* names of the created corpus (lowercase, upppercase) and various paths for commands */
	$freq_corpus_cqp_name_lc = strtolower($c_info->cqp_name) . '__freq';
	$freq_corpus_cqp_name_uc = strtoupper($freq_corpus_cqp_name_lc);
	
	$datadir = "{$Config->dir->index}/$freq_corpus_cqp_name_lc";
	$regfile = "{$Config->dir->registry}/$freq_corpus_cqp_name_lc";

	
	/* character set to use when encoding the new corpus */
	$cqp = new CQP($Config->path_to_cwb, $Config->dir->registry);
	$cqp->set_error_handler('exiterror_cqp');
	$cqp->set_corpus($c_info->cqp_name);
	$charset = $cqp->get_corpus_charset();
	unset($cqp);


	/* delete any previously existing corpus of this name, then make the data directory ready */
	if (is_dir($datadir))
		cwb_uncreate_corpus($freq_corpus_cqp_name_lc);

	if (! mkdir($datadir))
		exiterror("CQPweb could not create a directory for the frequency index. Check filesystem permissions!");
	chmod($datadir, 0777);

	/* open a pipe **from** cwb-decode and another **to** cwb-encode */
	$cmd_decode = "{$Config->path_to_cwb}cwb-decode -r \"{$Config->dir->registry}\" -Cx {$c_info->cqp_name} $p_att_line -S text_id";

	$source = popen($cmd_decode, 'r');
	if (!is_resource($source))
		exiterror('Freq index creation: CWB decode source pipe did not open properly.');
	/* we are using -Cx mode, so we need to skip the first two lines */
	$junk = fgets($source);
	if ( $junk[0] != '<' || $junk[1] != '?' )
		exiterror("Freq index creation: unexpected first XML line from CWB decode process.");
	$junk = fgets($source);
	if (! preg_match('/^<corpus/', $junk))
		exiterror("Freq index creation: unexpected second XML line from CWB decode process.");
	
	$cmd_encode = "\"{$Config->path_to_cwb}cwb-encode\" -U \"\" -x -d \"$datadir\" -c $charset -R \"$regfile\" $p_att_line_no_word -P __freq -S text:0+id ";
	
	$encode_pipe = NULL;
	$pipe_creator = array(0 => array("pipe", "r"),1 => array("pipe", "w"),2 => array("pipe", "w"));

	$encode_process = proc_open($cmd_encode, $pipe_creator, $encode_pipe);
	if (! is_resource($encode_process))
		exiterror('Freq index creation: CWB encode process did not open properly.');

	/* so now we can stick the pipe to child STDIN into DEST. */
	$dest = $encode_pipe[0];


	/* Right, we can now filter the flow from decode to encode... */
	print_debug_message("Beginning to filter data from decode to encode to build the frequency-list-by-text CWB database...");

	$F = array();

	/* for each line in the decoded output ... */
	while ( ($line = fgets($source)) !== false)
	{
		/* in case of whitespace... */
		$line = trim($line, "\r\n ");
		/* we do not trim off \t because it might be a column terminator */


		if (preg_match('/^<text_id\s+(\w+)>$/', $line, $m) > 0)
		{
			/* extract the id from the preceding regex using (\w+) */
			$current_id = $m[1];
			$F = array();
		}
		else if ($line == '</text_id>')
		{
			/* do the things to be done at the end of each text */
			
			if ( ! isset($current_id) )
				exiterror("Unexpected /text_id end-tag while creating corpus $freq_corpus_cqp_name_uc! -- creation aborted");
			
			if (false === fputs($dest, "<text id=\"$current_id\">\n"))
				exiterror("Freq index creation: Could not write [text] to CWB encode destination pipe");
			arsort($F);
			
			foreach ($F as $l => &$c)
			{
				if (false === fputs($dest, "$l\t$c\n"))
					exiterror("Freq index creation: Could not write [$l--$c] to CWB encode destination pipe");
				/* after each write, check the encode process for errors; print them if found */
				$w = NULL;
				$e = NULL;
				$encode_output = '';
				foreach(array(1,2) as $x)
				{
					$r=array($encode_pipe[$x]);
					while (0 < stream_select($r, $w, $e, 0))
					{
						if (false !== ($fgets_return = fgets($encode_pipe[$x])))
							$encode_output .= $fgets_return;
						else
							break;
						$r=array($encode_pipe[$x]); /* ready for next loop */
					}
				}
				if (! empty($encode_output) )
					print_debug_message($encode_output);
			}
			if (false === fputs($dest, "</text>\n"))
				exiterror("Freq index creation: Could not write [/text] to CWB encode destination pipe");
			unset($current_id, $F);
		}
		else
		{
			/* if we're at the point of waiting for a text_id, and we got this, then ABORT! */
			if ( ! isset($current_id) )
			{
				/* this is the only thing that will validly occur outside of a <text> */
				if ($line == '</corpus>')
					continue;
				else
					exiterror("Unexpected line outside text_id tags while creating corpus $freq_corpus_cqp_name_uc! -- creation aborted");
			}
			/* otherwise... */

			/* first, run line through a minimal XML filter to re-escape things (cwb-decode outputs < if it was &lt; in the input) */
			if (isset($F[$line]))
				$F[$line]++;
			else
				$F[$line] = 1;
			/* whew! that's gonna be hell for memory allocation in the bigger texts */
		}
	}	/* end of while */


	print_debug_message("Encoding of the frequency-list-by-text CWB database is now complete.");

	/* close the pipes and the encode process */
	pclose($source);
	fclose($encode_pipe[0]);
	fclose($encode_pipe[1]);
	fclose($encode_pipe[2]);
	proc_close($encode_process);


	/* system commands for everything else that needs to be done to make it a good corpus */

	$mem_flag = '-M ' . get_cwb_memory_limit();
	$cmd_makeall  = "\"{$Config->path_to_cwb}cwb-makeall\" $mem_flag -r \"{$Config->dir->registry}\" -V $freq_corpus_cqp_name_uc ";
	$cmd_huffcode = "\"{$Config->path_to_cwb}cwb-huffcode\"          -r \"{$Config->dir->registry}\" -A $freq_corpus_cqp_name_uc ";
	$cmd_pressrdx = "\"{$Config->path_to_cwb}cwb-compress-rdx\"      -r \"{$Config->dir->registry}\" -A $freq_corpus_cqp_name_uc ";


	/* make the indexes & compress */
	$output = array();
	exec($cmd_makeall,  $output);
	exec($cmd_huffcode, $output);
	exec($cmd_pressrdx, $output);

	print_debug_message("Compression of the frequency-list-by-text CWB database is now complete...");

	
	/* delete the intermediate files that we were told we could delete */
	foreach ($output as $o)
		if (preg_match('/!! You can delete the file <(.*)> now./', $o, $m) > 0)
			if (is_file($m[1]))
				unlink($m[1]);
	unset ($output);
	
	/* the new CWB frequency-list-by-text "corpus" is now finished! */
	print_debug_message("Done with the frequency-list-by-text CWB database...");


	/*
	 * last thing is to create a file of indexes of the text_ids in this "corpus".
	 * contains 3 whitespace delimited fields: begin_index - end_index - text_id.
	 * 
	 * This then goes into a mysql table which corresponds to the __freq cwb corpus.
	 */
	$index_filename = "{$Config->dir->cache}/{$corpus}_freqdb_index.tbl";
	
	$s_decode_cmd = "{$Config->path_to_cwb}cwb-s-decode -r \"{$Config->dir->registry}\" $freq_corpus_cqp_name_uc -S text_id > \"$index_filename\"";
	exec($s_decode_cmd);

	
	/* make sure the $index_filename is utf8 */
	if ($charset != 'utf8')
	{
		$index_filename_new = $index_filename . '.utf8.tmp';
		
		change_file_encoding($index_filename, 
		                     $index_filename_new,
		                     CQP::translate_corpus_charset_to_iconv($charset), 
		                     CQP::translate_corpus_charset_to_iconv('utf8') . '//TRANSLIT');
		
		unlink($index_filename);
		$index_filename = $index_filename_new;
	}



	
	/* now, create a mysql table with text begin-&-end-point indexes for this cwb-indexed corpus *
	 * (a table which is subsequently used in the process of making the subcorpus freq lists)    */


	$freq_text_index = "freq_text_index_$corpus";
	
	do_mysql_query("drop table if exists $freq_text_index");

	
	$creation_query = "CREATE TABLE `$freq_text_index` 
		(
			`start` int(11) unsigned NOT NULL,
			`end` int(11) unsigned NOT NULL,
			`text_id` varchar(50) NOT NULL,
			KEY `text_id` (`text_id`)
		) 
		CHARACTER SET utf8 COLLATE utf8_bin";
	do_mysql_query($creation_query);

	do_mysql_infile_query($freq_text_index, $index_filename);
	/* NB we don't have to worry about the character encoding of the infile as it contains
	 * only integers and ID codes - so, all ASCII. */

	unlink($index_filename);


	/* turn the limit back on */
	php_execute_time_relimit();
}












/**
 * Turns a list of text IDs into a series of
 * corpus positon pairs corresponding to the 
 * CWB frequency index corpus (NOT the corpus
 * itself).
 *  
 * @param array  $text_list  Array of text_id strings.
 * @param string $corpus     The corpus to look in.
 */
function get_freq_index_positionlist_for_text_list($text_list, $corpus)
{
	/* Check whether the specially-indexed cwb per-file freqlist corpus exists */
	if ( ! check_cwb_freq_index($corpus) )
		exiterror("No CWB frequency-by-text index exists for corpus {$corpus}!");
	/* because if it doesn't exist, we can't get the positions from it! */	
	
	/* For each text id, we now get the start-and-end positions in the
	 * FREQ TABLE CORPUS (NOT the actual corpus).
	 * 
	 * We can't just do a query for "start,end WHERE text_id is ... or text_id is ..." because
	 * this will overflow the max packet size for a server data transfer if the text list is
	 * long. So, instead, let's do it a blob at a time.
	 */

	$position_list = array();

	foreach(array_chunk($text_list, 20) as $chunk_of_texts)
	{
		/* first step: convert list of texts to an sql where clause */
		$textid_whereclause = translate_itemlist_to_where($chunk_of_texts, true);
		
		/* second step: get that chunk's begin-and-end points in the specially-indexed cwb per-file freqlist corpus */
		$result = do_mysql_query("select start, end from freq_text_index_{$corpus} where $textid_whereclause");
		
		/* third step: store regions to be scanned in output array */
		while ( ($r = mysql_fetch_row($result)) !== false )
			$position_list[] = $r;
	}

	/* All position lists must be ASC sorted for CWB to make sense of them. The list we have built from
	 * MySQL may or may not be pre-sorted depending on the original history of the text-list... */
	$position_list = sort_positionlist($position_list);
	
	return $position_list;
}





/**
 * Check if a cwb-frequency-"corpus" exists for the specified lowercase corpus name.
 * 
 * For true to be returned, BOTH the cwb "__freq" corpus AND the corresponding text
 * index must exist.
 * 
 * Note: does not work for subcorpora, because they have neither a CWB "__freq" table,
 * nor a freq text index!
 */
function check_cwb_freq_index($corpus_name)
{
	if (! cwb_corpus_exists($corpus_name . '__freq') )
		return false;
	 
	$mysql_table = "freq_text_index_$corpus_name";
	
	$result = do_mysql_query("show tables");
	while ( false !== ($r = mysql_fetch_row($result)))
		if ($r[0] == $mysql_table)
			return true;
	
	return false;
}





/*
 * ========================
 * SQL Freqtable Management 
 * ========================
 */






/** 
 * Makes sure that the name you are about to give to a freqtable is unique. 
 * 
 * Keeps adding random letters to the end of it if it is not. The name returned
 * is therefore definitely always unique across all corpora.
 */
function freqtable_name_unique($name)
{
	while (true)
	{
		$sql = 'select freqtable_name from saved_freqtables where freqtable_name = \'' . mysql_real_escape_string($name) . '\' limit 1';

		if (0 == mysql_num_rows(do_mysql_query($sql)))
			break;
		else
			$name .= chr(mt_rand(0x41,0x5a));
	}
	return $name;
}






/** Gets the combined size of all freqtables relating to a specific subcorpus. */
function get_freqtable_size($freqtable_name)
{	
	$size = 0;

	$result = do_mysql_query("SHOW TABLE STATUS LIKE '$freqtable_name%'");
	/* note the " % " */

	while ( ($info = mysql_fetch_assoc($result)) !== false)
		$size += ((int) $info['Data_length'] + $info['Index_length']);

	return $size;
}




/** Updates the timestamp (which, note is an int, not the MySQL TIMESTAMP column type). */
function touch_freqtable($freqtable_name)
{	
	$freqtable_name = mysql_real_escape_string($freqtable_name);
		
	$time_now = time();
	
	do_mysql_query("update saved_freqtables set create_time = $time_now where freqtable_name = '$freqtable_name'");
}







/**
 * Deletes a "cluster" of freq tables relating to a particular subsection, + their entry
 * in the saved_freqtables list.
 */
function delete_freqtable($freqtable_name)
{
	$freqtable_name = mysql_real_escape_string($freqtable_name);
	
	/* delete no tables if this is not a real entry in the freqtable db
	 * (this check should in theory not be needed, but let's make sure) */
	$result = do_mysql_query("select freqtable_name from saved_freqtables where freqtable_name = '$freqtable_name'");
	if (1 > mysql_num_rows($result))
		return;
	
	$result = do_mysql_query("show tables like '{$freqtable_name}_%'");

	while ( ($r = mysql_fetch_row($result)) !== false )
		do_mysql_query("drop table if exists ${r[0]}");
	
	do_mysql_query("delete from saved_freqtables where freqtable_name = '$freqtable_name'");
}


/**
 * Deletes a specified frequency-table component from MySQL, unconditionally.
 * 
 * Note it only works on frequency table components!
 * 
 * If passed an array rather than a single table name, it will iterate across 
 * the array, deleting each specified table.
 * 
 * Designed for deleting bits of frequency tables that have become unmoored from
 * the record in saved_freqtables that would normally enable their deletion.
 */
function delete_stray_freqtable_part($table)
{
	if (! is_array($table))
		$table = array($table);
	
	foreach ($table as $t)
		if (preg_match('/^freq_sc_/', $t))
			do_mysql_query("drop table if exists `" . mysql_real_escape_string($t) . "`");
}




/** 
 * Checks the size of the cache of saved frequency tables, and if it is higher
 * than the size limit (from config variable $freqtable_cache_size_limit),
 * then old frequency tables are deleted until the size falls below the said limit.
 * 
 * Public frequency tables will not be deleted from the cache. If you want public
 * frequency tables to be equally "vulnerable", pass in false as the argument.
 * 
 * Note: this function works ACROSS CORPORA.
 */
function delete_freqtable_overflow($protect_public_freqtables = true)
{
	global $Config;
	$limit = $Config->freqtable_cache_size_limit;
	
	$protect_public_freqtables = (bool)$protect_public_freqtables;

	/* step one: how many bytes in size is the freqtable cache RIGHT NOW? */
	list($current_size) = mysql_fetch_row(do_mysql_query("select sum(ft_size) from saved_freqtables"));

	if ($current_size <= $limit)
		return;

	/* step 2 : get a list of deletable freq tables */
	$sql = "select freqtable_name, ft_size from saved_freqtables" 
				. ( $protect_public_freqtables ? " where public = 0" : "") 
				. " order by create_time asc";
	$result = do_mysql_query($sql);

	while ($current_size > $limit)
	{
		if ( ! ($current_ft_to_delete = mysql_fetch_assoc($result)) )
			break;
		
		delete_freqtable($current_ft_to_delete['freqtable_name']);
		$current_size -= $current_ft_to_delete['ft_size'];
	}
	
	if ($current_size > $limit)
		exiterror_dboverload();
}







/** Dumps all cached freq tables from the database (unconditional cache clear). */
function clear_freqtables()
{
	$del_result = do_mysql_query("select freqtable_name from saved_freqtables");

	while ($current_ft_to_delete = mysql_fetch_assoc($del_result))
		delete_freqtable($current_ft_to_delete['freqtable_name']);
}



/*
 * ==========================
 * MANAGING PUBLIC FREQTABLES 
 * ==========================
 */





function publicise_this_corpus_freqtable($description)
{
	global $Corpus;

	$description = mysql_real_escape_string($description);
	
	$sql_query = "update corpus_info set public_freqlist_desc = '$description'
		where corpus = '{$Corpus->name}'";
		
	do_mysql_query($sql_query);
}




function unpublicise_this_corpus_freqtable()
{
	global $Corpus;
	
	do_mysql_query("update corpus_info set public_freqlist_desc = NULL where corpus = '{$Corpus->name}'");
}






function publicise_freqtable($name, $switch_public_on = true)
{
	global $User;

	/* only superusers are allowed to do this! */
	if (! $User->is_admin())
		return;

	$name = mysql_real_escape_string($name);
	
	$sql = "update saved_freqtables set public = " . ($switch_public_on ? 1 : 0) . "where freqtable_name = '$name'";
	
	do_mysql_query($sql);

}


/* this is just for convenience */
function unpublicise_freqtable($name)
{
	publicise_freqtable($name, false);
}




/**
 * Works across the system: returns an array of records, ie an array of associative arrays
 * which could be empty.
 * 
 * the reason it returns an array of records rather than a list of names is that with just a 
 * list of names there would be no way to get at the freqtable_name that is the key ident    
 */
function list_public_freqtables()
{
	$result = do_mysql_query("select * from saved_freqtables where public = 1");

	$public_list = array();
	
	while ( ($r = mysql_fetch_assoc($result)) !== false)
		$public_list[] = $r;
	
	return $public_list;
}


/** 
 * Returns an array of arrays: the inner arrays are associative and contain corpus handles and public descriptions from corpus_info; 
 * works across the system.
 */
function list_public_whole_corpus_freqtables()
{
	$sql = "select corpus, public_freqlist_desc from corpus_info where public_freqlist_desc IS NOT NULL order by corpus asc";
		
	$result = do_mysql_query($sql);

	$list = array();
	while ( ($r = mysql_fetch_assoc($result)) !== false)
		$list[] = $r;
	
	return $list;
}



/**
 * Returns a list of IDs of subcorpora belonging to this corpus and this user.
 * (sorted by the name of the subcorpus!)
 * 
 * Nb -- it's not a list of assoc-array-format records - just an array of IDs - could be empty. 
 */
function list_freqtabled_subcorpora()
{
	global $Corpus;
	global $User;

	$sql = "select saved_freqtables.query_scope as id
				from saved_freqtables inner join saved_subcorpora on saved_freqtables.query_scope = saved_subcorpora.id
				where saved_freqtables.corpus = '{$Corpus->name}' and saved_freqtables.user = '{$User->username}'
				order by saved_subcorpora.name asc";
			/* the inner join is just to get the order... */
// 2 old versions
// 	$sql = "select saved_freqtables.subcorpus, saved_subcorpora.name as grab 
// 				from saved_freqtables inner join saved_subcorpora on saved_freqtables.subcorpus = saved_subcorpora.id
// 				where saved_freqtables.corpus = '{$Corpus->name}' and saved_freqtables.user = '{$User->username}' and subcorpus != ''";
// 				/* note the above query relies on automatic casting from int to string in MySQL */
// 	$sql = "select subcorpus as id from saved_freqtables where corpus = '{$Corpus->name}' and user = '{$User->username}' and subcorpus != ''";
	$result = do_mysql_query($sql);

	$list = array();
	while ( ($r = mysql_fetch_object($result)) !== false)
		$list[] = $r->id;
	
	return $list;
}


// /**
//  * Find the freqtable name for a given subcorpus belonging to this user and this corpus. 
//  * 
//  * Returns false if it was not found.
//  */
// function get_subcorpus_freqtable($subcorpus)
// {
// 	global $Corpus;
// 	global $User;
	
// 	$subcorpus = mysql_real_escape_string($subcorpus);
	
// 	$sql = "select freqtable_name from saved_freqtables where corpus = '{$Corpus->name}' and user = '{$User->username}' and subcorpus = '$subcorpus'";
// 	$result = do_mysql_query($sql);
	
// 	if (mysql_num_rows($result) < 1)
// 		return false;
	
// 	list($name) = mysql_fetch_row($result);
	
// 	return $name;
// }






