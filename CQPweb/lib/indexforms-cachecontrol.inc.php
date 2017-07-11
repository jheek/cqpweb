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
 * This file contains a subset of functions used in the admin interface: those concerned with Cache Control of one kind or another.
 */




// TO DO This function still detexts subcorpus files as "could be deleted files" -- needs to change, need separate func for stray subcorpus files. */  
//TODO
//TODO
/**
 * 
 */
function printquery_querycachecontrol()
{
	global $Config;
	
	$saved_queries = $recorded_files = $unrecorded_files = array();

	php_execute_time_unlimit();

	
	/* list saved queries */
	$result = do_mysql_query("select query_name from saved_queries");
	while (false !== ($r = mysql_fetch_row($result)))
		$saved_queries[] = $r[0];

	foreach(scandir($Config->dir->cache) as $f)
	{
		if ('.' == $f || '..' == $f)
			continue;
		if (preg_match('/^scdf-(\w+)$/', $f, $m))
			continue;
		/* note we skip subcorpus files because they are managed by a different cache view. */
		
		if (false === strpos($f, ':'))
			$unrecorded_files[] = $f;
		else
		{
			list(, $q) = explode(':', $f);
			if (false === ($keyfound = array_search($q, $saved_queries)))
				$unrecorded_files[] = $f;
			else
			{
				$recorded_files[] = $f;
				unset($saved_queries[$keyfound]);
			}
		}
	}
	$no_file_queries = array_diff($saved_queries, $recorded_files);
	
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">Query cache control</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="2">
				<p>
					The <b>query cache</b> contains binary files representing saved and cached queries.
				</p>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" width="50%">
				Maximum cache size (set in the configuration file)
			</td>
			<td class="concordgeneral">
				<?php echo number_format(((float)$Config->cache_size_limit)/1024.0), " KB\n"; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">
				Current cache size
			</td>
			<td class="concordgeneral">
				<?php
				
				list($size_in_bytes) = mysql_fetch_row(do_mysql_query("select sum(file_size) from saved_queries"));
				if (empty($size_in_bytes))
					$size_in_bytes = 0;
				echo number_format(((float)$size_in_bytes) / 1024.0, 0)
					, " KB<br/>("
					, number_format( ( ((float)$size_in_bytes) / ((float)$Config->cache_size_limit) ) * 100.0, 0)
					, "% of maximum)\n"
					;
				 
				?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">
				Number of entries in cache table
			</td>
			<td class="concordgeneral">
				<?php
				
				list($n_table_entries) = mysql_fetch_row(do_mysql_query("select count(*) from saved_queries"));
				if (empty($n_table_entries))
					$n_table_entries = 0;
				echo number_format($n_table_entries), "\n";
				
				?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">
				Number of actual files in cache directory
				<br/>
				(includes temporary files, so will be momentarily larger than the N of cache table entries)
			</td>
			<td class="concordgeneral">
				<?php 
				echo number_format(count($recorded_files) + count($unrecorded_files)); 
				?>
			</td>
		</tr>

	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">Query cache leak monitor</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="4">
				<p>
					This table lists files that are present in the cache directory
					but do not correspond to any entry in the database's cache table. 
				</p>
				<p>
					It is quite likely that these files result from glitches in CQPweb
					and should be deleted.
				</p>
				<p>
					Note that these files are not counted towards the size limit of the
					cache, and so if they are (individually or collectively) large, your cache
					directory may substantially exceed the limit set in the CQPweb configuration.
				</p>
			</td>
		</tr>	
		<tr>
			<th class="concordtable">Filename</th>
			<th class="concordtable">Size (K)</th>
			<th class="concordtable">Date modified</th>
			<th class="concordtable">Delete</th>
		</tr>
		<form action="index.php">
			<input type="hidden" name="admFunction" value="deleteCacheLeakFiles">
			<?php
			if (empty($unrecorded_files))
			{
				?>
				
				<tr>
					<td colspan="4" class="concordgeneral" align="center">
						<p>
							There are <b>no</b> files in the cache directory that lack a matching entry in the cache table.
						</p> 
					</td>
				</tr>
	
				<?php
			}
			else
			{
				foreach ($unrecorded_files as $f)
				{
					$stat = stat($Config->dir->cache . '/' . $f);
					echo "\n\t\t<tr>"
						, '<td class="concordgrey">', $f, '</td>'
						, '<td class="concordgrey" align="right">', number_format(round($stat['size']/1024, 0)), '</td>'
						, '<td class="concordgrey" align="center">', date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']), '</td>'
						, '<td class="concordgrey" align="center"><input type="checkbox" name="fn_', $f, '" value="1"></td>'
						, "</tr>\n"
						;
				}
				
				?>
				
				<tr>
					<td class="concordgeneral" align="center" colspan="4">
						<input type="submit" value="Click here to delete selected files">
					</td>
				</tr>
			
				<?php
			}

			?>
			
			<input type="hidden" name="uT" value="y">
			
		</form>

	</table>
	
	
	<!-- END OF FIRST MONITOR, START OF SECOND -->
	
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">Query cache inconsistency monitor</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="6">
				<p>
					This table lists files present in the cache table that seem to have been deleted. 
				</p>
				<p>
					It is quite likely that these cache table entries result from glitches in CQPweb
					and should be deleted.
				</p>
			</td>
		</tr>	
		<tr>
			<th class="concordtable">Query ID</th>
			<th class="concordtable">User</th>
			<th class="concordtable">Corpus</th>
			<th class="concordtable">Last used</th>
			<th class="concordtable">Saved by User?</th>
			<th class="concordtable">Mark for deletion</th>
		</tr>
		
		<form method="get" action="index.php">
		
			<input type="hidden" name="admFunction" value="deleteCacheLeakDbEntries">

			<?php
	
			if (empty($no_file_queries))
			{
				?>
				
				<tr>
					<td colspan="6" class="concordgrey" align="center">
						<p>
							There are <b>no</b> entries in the cache table whose files are missing from the cache directory.
						</p> 
					</td>
				</tr>
	
				<?php
			}
			else
			{
				foreach ($no_file_queries as $q)
				{
					$qr = QueryRecord::new_from_qname($q);
					echo "\n\t\t<tr>"
						, '<td class="concordgrey">', $q, '</td>'
						, '<td class="concordgrey" align="center">', $qr->user, '</td>'
						, '<td class="concordgrey" align="center">', $qr->corpus, '</td>'
						, '<td class="concordgrey" align="center">', ((CACHE_STATUS_UNSAVED !== $qr->saved) ? 'Yes' : 'No'), '</td>'
						, '<td class="concordgrey" align="center">', $qr->print_time(), '</td>'
						, '<td class="concordgrey" align="center">'
						, '<input type="checkbox" name="qn_', $q, '" value="1">'
						, '</td>'
						, "</tr>\n"
						;
				}
		
				?>
		
				<tr>
					<td class="concordgeneral" align="center" colspan="6">
						<input type="submit" value="Click here to delete selected cache entries">
					</td>
				</tr>
				
				<?php
			}
			
			?>
			
			<input type="hidden" name="uT" value="y">
			
		</form>

	</table>

	<?php
}





function printquery_dbcachecontrol()
{
	//TODO
	//TODO
	//TODO
	//TODO
	//TODO
	echo '<p class="errormessage">We\'re sorry, this function has not been built yet.</p>';
}






function printquery_freqtablecachecontrol()
{
	global $Config;
	
	php_execute_time_unlimit();

	

	
	/* set up arrays of known tables etc. */

	$annotations = array(); 
	foreach(list_corpora() as $c)
		$annotations[$c] = array_merge(array('word'), array_keys(get_corpus_annotations($c)));
	
	$known_fts = array();
	$expected_tables = array(); /* note, because this list will be big, we insert the entries as keys not valeus (for lookup speed)*/
	$result = do_mysql_query("select freqtable_name, corpus from saved_freqtables");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		foreach($annotations[$o->corpus] as $att)
		{
			$t = "{$o->freqtable_name}_{$att}";
			$known_fts[$o->freqtable_name][] = $t;
			$expected_tables[$t] = $o->freqtable_name;
		}
	}
	$n_entries = count($known_fts);
	$n_expected_tables = count($expected_tables);
	
	$stray_tables = array(); /* note, because this list will be big, we insert the entries as keys not valeus (for lookup speed)*/
	$result = do_mysql_query("show tables like 'freq_sc_%'");
	while (false !== ($r = mysql_fetch_row($result)))
	{
		if (!isset($expected_tables[$r[0]]))
			$stray_tables[$r[0]] = true;
		else
			$expected_tables[$r[0]] = true;
	}
	$n_actual_tables = mysql_num_rows($result);
	
	$entries_with_missing_tables = array();
	foreach ($known_fts as $ft => $tables)
	{
		foreach($tables as $t)
		{
			if (true !== $expected_tables[$t])
			{
				if (!isset($entries_with_missing_tables[$ft]))
					$entries_with_missing_tables[$ft] = array( );
				$entries_with_missing_tables[$ft][] = $t;
			}
		}
	}
	/* so at this point:
	 *  - $known_fts has freqtable names as keys, lists of EXPECTED mysql tables as values.
	 *  - $expected_tables has expected mysql tables as keys, where value is TRUE if the table exists, the corresponding freqtable anme if not.
	 *  - $stray_tables has mysql tables that were found but not expected as keys, true as value.
	 *  - $entries_with_missing_tables has freqtable names as keys, lists of MISSING mysql tables as values.
	 */
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">Frequency list cache control</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="2">
				<p class="spacer">&nbsp;</p>
				<p>
					The <b>frequency list cache</b> contains MySQL tables with frequency data for subsections of corpora, 
					created dynamically upon user request.
				</p>
				<p>
					A list of correctly-cached frequency tables for each corpus can be found in that corpus's interface 
					(under &ldquo;Cached frequency lists&rdquo;). 
				</p>
				<p>
					<a href="index.php?admFunction=execute&function=delete_freqtable_overflow&locationAfter=index.php%3FthisF%3DfreqtableCacheControl%26uT%3Dy&uT=y">
					Click here to run a cache-overflow check for the frequency table cache</a>
					(this will delete old tables until the total cache size falls under the limit). 
				</p>
				<p class="spacer">&nbsp;</p>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" width="50%">
				Maximum frequency-table cache size (set in the configuration file)
			</td>
			<td class="concordgeneral">
				<?php echo number_format(((float)$Config->freqtable_cache_size_limit)/1024.0), " KB\n"; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">
				Current frequency-table cache size
			</td>
			<td class="concordgeneral">
				<?php
				
				list($size_in_bytes) = mysql_fetch_row(do_mysql_query("select sum(ft_size) from saved_freqtables"));
				if (empty($size_in_bytes))
					$size_in_bytes = 0;
				echo number_format(((float)$size_in_bytes) / 1024.0, 0)
					, " KB<br/>("
					, number_format( ( ((float)$size_in_bytes) / ((float)$Config->freqtable_cache_size_limit) ) * 100.0, 0)
					, "% of maximum)\n"
					;
				 
				?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">
				Number of entries in the saved-frequency-tables list
			</td>
			<td class="concordgeneral">
				<?php echo number_format($n_entries), "\n"; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">
				Expected number of database tables
				<br/>
				(each cached frequency table has one database table per p-attribute)
			</td>
			<td class="concordgeneral">
				<?php echo number_format($n_expected_tables); ?>
			</td>
		</tr>

		<tr>
			<td class="concordgrey">
				Actual number of database tables
				<br/>
				(if  greater or lesser than expected, there may be a leak!)
			</td>
			<td class="concordgeneral">
				<?php echo number_format($n_actual_tables); ?>
			</td>
		</tr>

	</table>
	
	<?php 
	/* before anything else: a by-corpus breakdown. */
	$result = do_mysql_query("select corpus, sum(ft_size) as size, count(*) as n_entries from saved_freqtables group by corpus order by size desc");
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">Cache usage by corpus</th>
		</tr>
		<tr>
			<th class="concordtable">Corpus</th>
			<th class="concordtable">Number of cached freq tables</th>
			<th class="concordtable">Total size (K)</th>
			<th class="concordtable">Actions</th>
		</tr>
		
		<?php 
		
		while (false !== ($o = mysql_fetch_object($result)))
			echo "\n\t\t\t<tr>"
 				, '<td class="concordgeneral" align="center">', $o->corpus , '</td>'
 				, '<td class="concordgeneral" align="center">', number_format($o->n_entries, 0), '</td>'
 				, '<td class="concordgeneral" align="center">', number_format(((float)$o->size)/1024.0, 0), '</td>'
 				, '<td class="concordgeneral" align="center"><a class="menuItem" href="../'
 				, $o->corpus
 				, '/index.php?thisQ=cachedFrequencyLists&uT=y">[View this corpus\'s cached frequency tables]</a></td>'
 				, '</tr>'
 				;

		?>

	</table>

							
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="5">Frequency table cache leak monitor</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="5">
				<p>
					This table lists frequency-tables that are present in the MySQL database,
					but do not correspond to any entry in the saved-frequency-tables cache monitor table. 
				</p>
				<p>
					It is quite likely that these result from glitches in CQPweb and should be deleted.
				</p>
				<p>
					Note that these tables are not counted towards the size limit of the cache,
					and so if they are (individually or collectively) large, MySQL may be using 
					ubstantially more space for frequency tables than the size limit would suggest.
				</p>
			</td>
		</tr>	
		<tr>
			<th class="concordtable">Table name</th>
			<th class="concordtable">Size (K)</th>
			<th class="concordtable">Date created</th>
			<th class="concordtable">Date modified</th>
			<th class="concordtable">Delete</th>
		</tr>
		<form action="index.php">
			<input type="hidden" name="admFunction" value="deleteFreqtableLeak">
			<?php

			if (empty($stray_tables))
			{
				?>
				
				<tr>
					<td colspan="5" class="concordgrey" align="center">
						<p>
							There are <b>no</b> stray frequency tables in MySQL that lack a matching entry in the frequency-table cache record.
						</p> 
					</td>
				</tr>
	
				<?php
			}
			else
			{
				foreach ($stray_tables as $stray => $v)
				{
					$info = mysql_fetch_object(do_mysql_query("show table status like '$stray'"));
					$size = $info->Data_length + $info->Index_length;
					echo "\n\t\t<tr>"
						, '<td class="concordgrey">', $stray, '</td>'
						, '<td class="concordgrey" align="right">', number_format(((float)$size)/1024.0, 0), '</td>'
						, '<td class="concordgrey" align="center">', $info->Create_time, '</td>'
						, '<td class="concordgrey" align="center">', $info->Update_time, '</td>'
						, '<td class="concordgrey" align="center"><input type="checkbox" name="del_', $stray, '" value="1"></td>'
						, "</tr>\n"
						;
				}
				
				?>
				
				<tr>
					<td class="concordgeneral" align="center" colspan="5">
						<input type="submit" value="Click here to delete selected stray frequency table parts">
					</td>
				</tr>
			
				<?php
			}

			?>
			
			<input type="hidden" name="uT" value="y">
			
		</form>

	</table>
	
	
	<!-- END OF FIRST MONITOR, START OF SECOND -->
	
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="5">Incomplete frequency table monitor</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="5">
				<p>
					This table lists frequency tables for which a record exists, but which appear to be missing one or more components
					(where a &ldquo;component&rdquo; is the actual MySQL table for a particular p-attribute.
				</p>
				<p>
					It is quite likely that these missing components result from glitches in CQPweb.
					Normally, a frequency table with missing components must be regenerated in full.
				</p>
			</td>
		</tr>	
		<tr>
			<th class="concordtable">Table name</th>
			<th class="concordtable">User</th>
			<th class="concordtable">Corpus</th>
			<th class="concordtable">Creation date</th>
			<th class="concordtable">Components missing</th>
<!-- 			<th class="concordtable">Mark for deletion</th> -->
		</tr>
		
<!-- 		<form method="get" action="index.php"> -->
		
<!-- 			<input type="hidden" name="admFunction" value="somethingHere"> -->

			<?php
	
			if (empty($entries_with_missing_tables))
			{
				?>
				
				<tr>
					<td colspan="5" class="concordgrey" align="center">
						<p>
							There are <b>no</b> entries in the cache table with missing component tables.
						</p> 
					</td>
				</tr>
	
				<?php
			}
			else
			{
				foreach ($entries_with_missing_tables as $ft => $missing)
				{
					$ft_info = mysql_fetch_object(do_mysql_query("select * from saved_freqtables where freqtable_name = '$ft'"));
					echo "\n\t\t<tr>"
						, '<td class="concordgrey">', $ft, '</td>'
						, '<td class="concordgrey" align="center">', $ft_info->user, '</td>'
						, '<td class="concordgrey" align="center">', $ft_info->corpus, '</td>'
						, '<td class="concordgrey" align="center">', date(CQPWEB_UI_DATE_FORMAT, $ft_info->create_time), '</td>'
						, '<td class="concordgrey" align="center">', implode('<br/>', $missing), '</td>'
						, "</tr>\n"
						;
				}
		
				?>
		
<!-- 				<tr> -->
<!-- 					<td class="concordgeneral" align="center" colspan="6"> -->
<!-- 						<input type="submit" value="Click here to delete selected cache entries"> -->
<!-- 					</td> -->
<!-- 				</tr> -->
				
				<?php
			}
			
			?>
			
<!-- 			<input type="hidden" name="uT" value="y"> -->
			
<!-- 		</form> -->

	</table>

	<?php
}



function printquery_restrictioncachecontrol()
{
	global $Config;

	$result = do_mysql_query("select * from saved_restrictions order by cache_time desc");
	$n_stored = mysql_num_rows($result);
	
	$size_stored = get_mysql_table_size("saved_restrictions");
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">Restriction cache control</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="2">
				<p>
					The <b>restriction cache</b> is a single MySQL table containing the internal data of recently-used query restrictions.
				</p>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" width="50%">
				Maximum cache size (set in the configuration file)
			</td>
			<td class="concordgeneral">
				<?php echo number_format(((float)$Config->restriction_cache_size_limit)/1024.0), " KB\n"; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" width="50%">
				Current cache size
			</td>
			<td class="concordgeneral">
				<?php 
				echo number_format(((float)$size_stored) / 1024.0, 0) 
					, " KB<br/>(" 
 					, number_format( ( ((float)$size_stored) / ((float)$Config->restriction_cache_size_limit) ) * 100.0, 0)
					, "% of maximum)\n"
 					; 
				?> 
			</td>
		</tr>
		<tr>
			<td class="concordgrey">
				Current number of restrictions in the cache
			</td>
			<td class="concordgeneral">
				<?php
				echo number_format(((float) $n_stored), 0)
					, " cached restrictions\n"
					;
				?> 
			</td>
		</tr>
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="6">Contents of restriction cache</th>
		</tr>
		<tr>
			<th class="concordtable">ID</th>
			<th class="concordtable">Corpus</th>
			<th class="concordtable">Restriction code</th>
			<th class="concordtable">Size of cpos data (Kb)</th>
			<th class="concordtable">Date cached</th>
			<th class="concordtable">Delete</th>
		</tr>
		
		<?php 
		
		if (0 == $n_stored)
		{
			?>
			
			<tr>
				<td colspan="6" class="concordgrey" align="center">
					<p>
						There are <b>no</b> entries in the restriction cache.
					</p> 
				</td>
			</tr>
			
			<?php 
		}
		
		while (false !== ($o = mysql_fetch_object($result)))
			echo "\n\t\t<tr>"
				, '<td class="concordgrey"    align="center">', $o->id, '</td>'
				, '<td class="concordgeneral" align="center">', $o->corpus, '</td>'
				, '<td class="concordgeneral" align="center">'
 					, ( 100 > strlen($o->serialised_restriction) ? $o->serialised_restriction : (substr($o->serialised_restriction, 0, 100).'&hellip;') )
 				, '</td>'
				, '<td class="concordgeneral" align="center">', number_format(((float)strlen($o->data)) / 1024.0, 1), '</td>'
				, '<td class="concordgeneral" align="center">', date(CQPWEB_UI_DATE_FORMAT, $o->cache_time), '</td>'
				, '<td class="concordgeneral" align="center"><a href="index.php?admFunction=execute&function=delete_restriction_from_cache&args='
					, $o->id, '&locationAfter=', urlencode('index.php?thisF=restrictionCacheControl&uT=y'), '&uT=y">[x]</a></td>'
				, "</tr>\n"
				;
		?>
		
	</table>

	<?php
}




function printquery_subcorpuscachecontrol()
{
	global $Config;
	
	$sc_ids = array();
	$result = do_mysql_query("select id from saved_subcorpora");
	while (false !== ($r = mysql_fetch_row($result)))
		$sc_ids[$r[0]] = true;
	
	$sc_files = array();
	$sc_files_no_sc = array();
	$size_total = 0;
	
	foreach(scandir($Config->dir->cache) as $f)
	{
		if (!preg_match('/^scdf-(\w+)$/', $f, $m))
			continue;

		$sc_files[] = $f;
		$size_total += filesize("{$Config->dir->cache}/$f");
	
		if ( !isset($sc_ids[hexdec($m[1])]) )
			$sc_files_no_sc[] = $f;
	}
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">Subcorpus cache control</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="2">
				<p>
					Some user-created subcorpora are stored as files; they are kept in the same disk location as cached queries, 
					but do not count towards the size limit of the query cache.
				</p>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" width="50%">
				Number of subcorpus files currently in the system:
			</td>
			<td class="concordgeneral">
				<?php echo number_format((float)count($sc_files)); ?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" width="50%">
				Total size of these files:
			</td>
			<td class="concordgeneral">
				<?php echo number_format(((float)$size_total) / 1024.0, 0), " KB"; ?> 
			</td>
		</tr>
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">Subcorpus file leak monitor</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="4">
				<p>
					This table lists subcorpus files stored for subcorpora that seem not to exist any more. 
				</p>
				<p>
					It is quite likely that these files result from glitches in CQPweb
					and should be deleted.
				</p>
			</td>
		</tr>
		<tr>
			<th class="concordtable">Filename</th>
			<th class="concordtable">Size (K)</th>
			<th class="concordtable">Date modified</th>
			<th class="concordtable">Delete</th>
		</tr>
		<form action="index.php">
			<!--
				Note that we use the same function as for stray query files, because the mechanism is identical. 
			-->
			<input type="hidden" name="admFunction" value="deleteCacheLeakFiles">
			<?php
			if (empty($sc_files_no_sc))
			{
				?>
				
				<tr>
					<td colspan="4" class="concordgeneral" align="center">
						<p>
							There are <b>no</b> subcorpus files whose subcorpus seems to have been deleted.
						</p> 
					</td>
				</tr>
	
				<?php
			}
			else
			{
				foreach ($sc_files_no_sc as $f)
				{
					$stat = stat($Config->dir->cache . '/' . $f);
					echo "\n\t\t<tr>"
						, '<td class="concordgrey">', $f, '</td>'
						, '<td class="concordgrey" align="right">', number_format(round($stat['size']/1024, 0)), '</td>'
						, '<td class="concordgrey" align="center">', date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']), '</td>'
						, '<td class="concordgrey" align="center"><input type="checkbox" name="fn_', $f, '" value="1"></td>'
						, "</tr>\n"
						;
				}
				
				?>
				
				<tr>
					<td class="concordgeneral" align="center" colspan="4">
						<input type="submit" value="Click here to delete selected files">
					</td>
				</tr>
			
				<?php
			}

			?>
			
			<input type="hidden" name="uT" value="y">
			
		</form>

	</table>

	<?php
}


