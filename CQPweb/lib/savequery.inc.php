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
 * Contains the interface/actions for converting an everyday, cached query to a user-saved query.
 */


/* script to be included within redirect.php -- thus, $_GET will be full of all sorts, 
 * BUT the only bit that is used for the save is $qname
 */




/* include defaults and settings */
require('../lib/environment.inc.php');


/* include function files */
require('../lib/html-lib.inc.php');
include('../lib/cache.inc.php');
include('../lib/subcorpus.inc.php');
include('../lib/concordance-lib.inc.php');
include('../lib/library.inc.php');
include('../lib/user-lib.inc.php');
include('../lib/exiterror.inc.php');


cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP );


$qname = safe_qname_from_get();


if (!isset($_GET['saveScriptMode']))
	$this_script_mode = 'get_save_name';
else
	$this_script_mode = $_GET['saveScriptMode'];



switch ($this_script_mode)
{
case 'save_error':

	print_savename_top();
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Save Query: Error message</th>
		</tr>
		<tr>
			<td class="concordgeneral"><table><tr><td class="basicbox">
				<?php
				if (isset($_GET['saveScriptNameExists']))
				{
					$n = mysql_real_escape_string($_GET['saveScriptNameExists']);
					echo "A query called <strong>$n</strong> has already been saved. Please specify a different name.
								<br/>&nbsp;<br/>";
				}
				?>
				
				Names for saved queries can only contain letters, numbers and 
				the underscore character ("_")!
				
				<br/>&nbsp;<br/>
			
				Enter a name that follows this rule into the form below.
			</td></tr></table></td>
		</tr>

	</table>
	<?php
	
	print_savename_page();
	cqpweb_shutdown_environment();
	exit();



case 'get_save_name':

	print_savename_top();
	print_savename_page();
	cqpweb_shutdown_environment();
	exit();


	
case 'ready_to_save':

	if(!isset($_GET['saveScriptSaveName']))
		exiterror_general('No save name was specified!');
	
	$savename = $_GET['saveScriptSaveName'];

	if (preg_match('/\W/', $savename) > 0)
	{
		$url = 'redirect.php?' 
			. url_printget(array(array('redirect', 'saveHits'), array('saveScriptSaveName', ''), array('saveScriptMode', 'save_error')));
		cqpweb_shutdown_environment();
		header('Location: ' . url_absolutify($url));
		exit();
	}
	/* check if a saved query with this savename exists */
	if (save_name_in_use($savename))
	{
		$url = 'redirect.php?' 
			. url_printget(array(array('redirect', 'saveHits'), array('saveScriptSaveName', ''), 
				array('saveScriptMode', 'save_error'), array('saveScriptNameExists', $savename)));
		cqpweb_shutdown_environment();
		header('Location: ' . url_absolutify($url));
		exit();
	}
	
	$newqname = qname_unique($Config->instance_name);

	if (false === ($new_query = copy_cached_query($qname, $newqname)))
		exiterror("Unable to copy query data for new saved query!");
	
	$new_query->user = $User->username;
	$new_query->save_name = $savename;
	$new_query->saved = CACHE_STATUS_SAVED_BY_USER;
	$new_query->set_time_to_now();
	$new_query->save();


	$url = 'concordance.php?' 
		. url_printget(array(array('theData', ''), array('redirect', ''), array('saveScriptSaveName', ''), array('saveScriptMode', '')));
	/* delete theData cos god knows how often it's been passed around; delete all the parameters to do with redirect.php and savequery.php */
	
	cqpweb_shutdown_environment();
	header('Location: ' . url_absolutify($url));
	exit();




case 'rename_error':

	print_replacesavename_top();
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Rename Saved Query: Error message</th>
		</tr>
		<tr>
			<td class="concordgeneral"><table><tr><td class="basicbox">
				Names for saved queries can only contain letters, numbers and 
				the underscore character ("_")!
				
				<br/>&nbsp;<br/>
			
				Enter a name that follows this rule into the form below.
			</td></tr></table></td>
		</tr>

	</table>
	<?php
	print_replacesavename_page();
	cqpweb_shutdown_environment();
	exit();



case 'get_save_rename':

	print_replacesavename_top();
	print_replacesavename_page();
	cqpweb_shutdown_environment();
	exit();


case 'rename_saved':

	if(!isset($_GET['saveScriptSaveReplacementName']))
		exiterror_general('No replacement save name was specified!');

	$replacename = $_GET['saveScriptSaveReplacementName'];

	if (preg_match('/\W/', $replacename) > 0)
	{
		$url = 'redirect.php?' 
			. url_printget(array(array('redirect', 'saveHits'), array('saveScriptSaveReplacementName', ''), array('saveScriptMode', 'rename_error')));
		cqpweb_shutdown_environment();
		header('Location: ' . url_absolutify($url));
		exit();
	}
	
	if (false !== ($record = QueryRecord::new_from_qname($qname)))
	{
		$record->save_name = (string)$replacename;
		$record->save();
	}
	else
		exiterror("cache record for the specified query ($qname) couold not be found.");

	$url = 'index.php?'
		. url_printget(array(array('theData', ''), array('redirect', ''), array('saveScriptSaveReplacementName', ''), array('saveScriptMode', '')));
	cqpweb_shutdown_environment();
	header('Location: ' . url_absolutify($url));
	exit();





case 'delete_saved':
	delete_cached_query($qname);
	$url = 'index.php?'
		. url_printget(array(array('qname', ''), array('theData', ''), array('redirect', ''), array('saveScriptMode', '')));
	cqpweb_shutdown_environment();
	header('Location: ' . url_absolutify($url));
	exit();



default:
	exiterror_general('Unrecognised scriptmode for savequery.inc.php!');


} /* end of switch */


/* ---------- *
 * END SCRIPT *
 * ---------- */


function print_savename_top()
{
	global $Config;
	echo print_html_header('CQPweb Save Query', $Config->css_path, array('cword'));
}

function print_savename_page()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Save a query result</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<table>	
					<tr>
						<form action="redirect.php" method="get">
							<td width="35%" class="basicbox">Please enter a name for your query:</td>
					
							<td class="basicbox">
								<input type="text" name="saveScriptSaveName" size="50" maxlength="200" />
								&nbsp;&nbsp;&nbsp;
								<input type="submit" value="Save the query" />
							</td>
							<?php echo url_printinputs(array(array('redirect', 'saveHits'), array('saveScriptMode', 'ready_to_save'))); ?>
						</form>
					</tr>
					<tr>
						<td class="basicbox" colspan="2">
							The name for your saved query may be up to 200 characters long (only unaccented letters, numbers, and underscore allowed!)
							After entering the name you will be taken back to the previous query result display. 
							The saved query can be accessed through the <b>Saved queries</b> link on the main page.
			 			</td>
			 		</tr>
			 	</table>
			</td>
		</tr>
	</table>
	<?php
	echo print_html_footer('savequery');
}







function print_replacesavename_top()
{
	global $Config;
	echo print_html_header('CQPweb Rename Saved Query', $Config->css_path);
}

function print_replacesavename_page()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">Rename a saved query</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<table>	
					<tr>
						<form action="redirect.php" method="get">
							<td width="35%" class="basicbox">Please enter a new name for your query:</td>
					
							<td class="basicbox">
								<input type="text" name="saveScriptSaveReplacementName" size="50" maxlength="200" />
								&nbsp;&nbsp;&nbsp;
								<input type="submit" value="Rename the query" />
							</td>
							<?php echo url_printinputs(array(array('redirect', 'saveHits'),array('saveScriptMode', 'rename_saved'))); ?>
						</form>
					</tr>
					<tr>
						<td class="basicbox" colspan="2">
							The name for your saved query may be up to 200 characters long. 
							After entering the name you will be taken back to the list of saved queries. 
			 			</td>
			 		</tr>
			 	</table>
			</td>
		</tr>
	</table>
	<?php
	echo print_html_footer('savequery');
}


