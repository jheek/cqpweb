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
 * This script updates the structure of the database to match the version of the code.
 * 
 * It is theoretically always safe to run this function because, if the db structure is up to date, it won't do anything.
 * 
 * Note that, up to and including 3.0.16, it was assumed that DB changes would be done manually. 
 * 
 * So, all manual changes up to 3.0.16 MUST be applied before running this script.
 */


require('../lib/environment.inc.php');

/* include function library files */
require('../lib/library.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/exiterror.inc.php');

require('../bin/cli-lib.php');




/* ============================================================
 * VARS THAT NEED UPDATING EVERY TIME A NEW VERSION IS PILED ON 
 */

		/* the most recent database version: ie the last version whose release involved a DB change */
		$last_changed_version = '3.2.10';
		
		/* 
		 * versions where there is no change. Array of old_version => version that followed. 
		 * E.g. if there were no changes between 3.1.0 and 3.1.1, this array should contain
		 * '3.1.0' => '3.1.1', so the function can then reiterate and look for changes between
		 * 3.1.1 and whatever follows it.
		 */
		$versions_where_there_was_no_change = array(
			'3.1.0'  => '3.1.1',
			'3.1.1'  => '3.1.2',
			'3.1.2'  => '3.1.3',
			'3.1.5'  => '3.1.6',
			'3.1.6'  => '3.1.7',
			'3.1.10' => '3.1.11',
			'3.1.11' => '3.1.12',
			'3.1.12' => '3.1.13',
			'3.1.13' => '3.1.14',
			'3.1.14' => '3.1.15',
			'3.1.15' => '3.1.16',
			'3.2.0'  => '3.2.1',
			'3.2.2'  => '3.2.3',
			'3.2.8'  => '3.2.9',
			'3.2.10' => '3.2.11',
			);

/* END COMPULSORY UPDATE VARS
 * ==========================
 */



/* ============ * 
 * begin script * 
 * ============ */

/* a hack to make debug printing & mysql connection work */
include("../lib/config.inc.php");
$Config = new stdClass();
$Config->print_debug_messages = false;
$Config->debug_messages_textonly = true;
$Config->all_users_see_backtrace = false;
$Config->mysql_utf8_set_required = (isset($mysql_utf8_set_required) && $mysql_utf8_set_required);
$Config->mysql_schema  = $mysql_schema;
$Config->mysql_webpass = $mysql_webpass;
$Config->mysql_webuser = $mysql_webuser;
$Config->mysql_server  = $mysql_server;


/* instead of cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP , RUN_LOCATION_CLI); ....... */
connect_global_mysql();



/* begin by checking for a really old database version ... */

$greater_than_3_0_16 = false;

$result = do_mysql_query('show tables');

while (false !== ($r = mysql_fetch_row($result)))
{
	if ($r[0] == 'system_info')
	{
		$greater_than_3_0_16 = true;
		break;
	}
}

if (!$greater_than_3_0_16)
{
	echo "Database version is now at < 3.1.0. Database will now be upgraded to 3.1.0...\n";
	upgrade_db_version_from('3.0.16');
}

while (0 > version_compare($version = get_db_version(), $last_changed_version))
{
	echo "Current DB version is $version; target version is $last_changed_version .  About to upgrade....\n";
	upgrade_db_version_from($version);
}

echo "CQPweb database is now at or above the most-recently-changed version ($last_changed_version). Upgrade complete!\n";	

disconnect_global_mysql();

exit(0);





/* --------------------------------------------------------------------------------------------------------- */





function upgrade_db_version_from($oldv)
{	
	global $versions_where_there_was_no_change;
	
	if (isset($versions_where_there_was_no_change[$oldv]))
		upgrade_db_version_note($versions_where_there_was_no_change[$oldv]);
	else
	{
		$func = 'upgrade_' . str_replace('.','_',$oldv);
		$func();
	}
}

function upgrade_db_version_note($newv)
{
	do_mysql_query("update system_info set value = '$newv' where setting_name = 'db_version'");
	do_mysql_query("update system_info set value = '" .  date('Y-m-d H:i') . "' where setting_name = 'db_updated'");
}



/* --------------------------------------------------------------------------------------------------------- */



/* 3.2.9->3.2.10 */
function upgrade_3_2_9()
{
	$sql = array(
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
		) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin"
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	upgrade_db_version_note('3.2.10');
}


/* 3.2.7->3.2.8 */
function upgrade_3_2_7()
{
	$sql = array(
		'CREATE TABLE `idlink_fields` (
           `corpus` varchar(20) NOT NULL,
           `att_handle` varchar(64) NOT NULL,
           `handle` varchar(64) NOT NULL,
           `description` varchar(255) default NULL,
           `datatype` tinyint(2) NOT NULL default 0, 
           primary key (`corpus`, `att_handle`, `handle`)
         ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin',
		'CREATE TABLE `idlink_values` (
           `corpus` varchar(20) NOT NULL,
           `att_handle` varchar(64) NOT NULL,
           `field_handle` varchar(64) NOT NULL,
           `handle` varchar(200) NOT NULL,
           `description` varchar(255) default NULL,
           `category_n_items` int unsigned default NULL,
           `category_n_tokens` int unsigned default NULL,
           primary key(`corpus`, `att_handle`, `field_handle`, `handle`)
         ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin'
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	upgrade_db_version_note('3.2.8');
}

/* --------------------------------------------------------------------------------------------------------- */

/* 3.2.6->3.2.7 */
function upgrade_3_2_6()
{
	echo "Warning: this upgrade action can take a long time.\nDO NOT interrupt the script; let it run to completion.\n";
	
	/* we have some extra functions for this one */
	require('../bin/upgrade326utils.inc.php');
	/* replace with the actual funcs if they turn out to be shortish. */
	
	$sql = array(
			'alter table saved_subcorpora change column `numwords` `n_tokens` bigint(21) unsigned default NULL',
			'alter table saved_subcorpora change column `numfiles` `n_items`  int(11) unsigned default NULL',
				/* -------------------------- */
			'alter table saved_subcorpora  add column `content` mediumtext after text_list',
			'alter table query_history     add column `query_scope` text after `subcorpus`',
			'alter table saved_dbs         add column `query_scope` text after `subcorpus`',
			'alter table saved_freqtables  add column `query_scope` text after `subcorpus`',
			'alter table saved_queries     add column `query_scope` text after `subcorpus`',
	);
	foreach ($sql as $q)
		do_mysql_query($q);

	/* the translation stage for the new "scope" / "content" columns. */
	
	/* saved_subcorpora  --- is a somewhat different case, because we have the text_list. */
	echo "    .... now updating the database format for the saved_subcorpora table\n";
	$result = do_mysql_query("SELECT id, restrictions, text_list from saved_subcorpora");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$content = upgrade326_recode_to_new_subcorpus($o->restrictions, $o->text_list);
		do_mysql_query("update saved_subcorpora set content='$content' where id = $o->id");
	}
	
	/* query hist */
	echo "    .... now updating the database format for the query_history table\n";
	do_mysql_query('update query_history set query_scope = ""');
	$result = do_mysql_query("SELECT instance_name, subcorpus, restrictions, query_scope from query_history");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$scope = upgrade326_recode_pair_to_scope($o->subcorpus, $o->restrictions);
		if ($o->query_scope != $scope)
			do_mysql_query("update query_history set query_scope='$scope' where instance_name = '$o->instance_name'");
	}
	
	/* saved_dbs */
	echo "    .... now updating the database format for the saved_dbs table\n";
	$result = do_mysql_query("SELECT dbname, subcorpus, restrictions from saved_dbs");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$scope = upgrade326_recode_pair_to_scope($o->subcorpus, $o->restrictions);
		do_mysql_query("update saved_dbs set query_scope='$scope' where dbname = '$o->dbname'");
	}
	
	/* saved_queries */
	echo "    .... now updating the database format for the saved_queries table\n";
	$result = do_mysql_query("SELECT query_name, subcorpus, restrictions from saved_queries");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$scope = upgrade326_recode_pair_to_scope($o->subcorpus, $o->restrictions);
		do_mysql_query("update saved_queries set query_scope='$scope' where query_name = '$o->query_name'");
	}

	/* saved_freqtables */
	echo "    .... now updating the database format for the saved_freqtables table\n";
	$result = do_mysql_query("SELECT freqtable_name, subcorpus, restrictions from saved_freqtables");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$scope = upgrade326_recode_pair_to_scope($o->subcorpus, $o->restrictions);
		do_mysql_query("update saved_freqtables set query_scope='$scope' where freqtable_name = '$o->freqtable_name'");
	}
	
	/* back to single line database rewrite statements........... */
	
	$sql = array(
			'alter table saved_subcorpora change `restrictions` `_restrictions` text',
			'alter table saved_subcorpora change `text_list` `_text_list` text',
			'alter table query_history change `restrictions` `_restrictions` text',
			'alter table query_history change `subcorpus`    `_subcorpus` varchar(200) NOT NULL default ""',
			'alter table saved_dbs change `restrictions` `_restrictions` text',
			'alter table saved_dbs change `subcorpus`    `_subcorpus` varchar(200) NOT NULL default ""',
			'alter table saved_freqtables change `restrictions` `_restrictions` text',
			'alter table saved_freqtables change `subcorpus`    `_subcorpus` varchar(200) NOT NULL default ""',
			'alter table saved_queries change `restrictions` `_restrictions` text',
			'alter table saved_queries change `subcorpus`    `_subcorpus` varchar(200) NOT NULL default ""',

			'alter table saved_subcorpora drop index `text_list`',
			'alter table saved_freqtables drop index `subcorpus`',
			'alter table saved_freqtables ADD INDEX `query_scope` (`query_scope`(255))',
			'alter table saved_queries drop index `restrictions`',
			'alter table saved_queries drop index `subcorpus`',
			'alter table saved_queries ADD FULLTEXT KEY `query_scope` (`query_scope`)',
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	upgrade_db_version_note('3.2.7');
}

/* 3.2.5->3.2.6 */
function upgrade_3_2_5()
{
	echo "Warning: this upgrade action can take a long time.\nDO NOT interrupt the script; let it run to completion.\n";
	$sql = array(
			'insert into system_info (setting_name, value) values ("install_date", "Pre ' .  date('Y-m-d') . '") ',
			'insert into system_info (setting_name, value) values ("db_updated", "' .  date('Y-m-d H:i') . '") ',
			'alter table saved_queries modify column `save_name` varchar(200) default NULL',
			'alter table saved_subcorpora add column `id` bigint unsigned NOT NULL AUTO_INCREMENT FIRST, add primary key (`id`)',
			'alter table saved_subcorpora change column `subcorpus_name` `name` varchar(200) NOT NULL default ""',
			'update saved_subcorpora set name = "--last_restrictions" where name = "__last_restrictions"',
			'update saved_subcorpora set text_list    = "" where text_list    IS NULL',
			'update saved_subcorpora set restrictions = "" where restrictions IS NULL',
			'alter table saved_matrix_info modify column `subcorpus` varchar(200) NOT NULL default ""',
			'alter table query_history modify column `date_of_query` timestamp NOT NULL default CURRENT_TIMESTAMP',
				/* -------- */
			'alter table query_history modify column `subcorpus` varchar(200) NOT NULL default ""',
			'update query_history set subcorpus    = "" where subcorpus    = "no_subcorpus"',
			'update query_history set restrictions = "" where restrictions = "no_restriction"',
				
			'alter table saved_queries modify column `subcorpus` varchar(200) NOT NULL default ""',
			'update saved_queries set subcorpus    = "" where subcorpus    = "no_subcorpus"',
			'update saved_queries set restrictions = "" where restrictions = "no_restriction"',
				
			'alter table saved_dbs modify column `subcorpus` varchar(200) NOT NULL default ""',
			'update saved_dbs set subcorpus    = "" where subcorpus    = "no_subcorpus"',
			'update saved_dbs set restrictions = "" where restrictions = "no_restriction"',
				
			'alter table saved_freqtables modify column `subcorpus` varchar(200) NOT NULL default ""',
			'update saved_freqtables set subcorpus    = "" where subcorpus    = "no_subcorpus"',
			'update saved_freqtables set restrictions = "" where restrictions = "no_restriction"',
			'update saved_freqtables set restrictions = "" where restrictions = "no_restriction"',
				/*--------- */
	);
	foreach ($sql as $q)
		do_mysql_query($q);

	/* 
	 * switching the subcorpus fields of the above tables to contain an integer ID representation rather than a name is more... complex. 
	 * (Why the change? because storing just the "name" creates an ambiguity: two subcorproa for different users/corpora might have the same "name".)
	 */
	
	/* saved_freqtables */
	$target_result = do_mysql_query ('select freqtable_name, corpus, user, subcorpus from saved_freqtables');
	while (false !== ($o = mysql_fetch_object($target_result)))
	{
		if (!empty($o->subcorpus))
		{
			$seek_result = do_mysql_query("select id from saved_subcorpora where corpus='{$o->corpus}' and user='{$o->user}' and name='{$o->subcorpus}'");
			/* the below should NEVER happen here because FTs are deleted when the SC is. Anwyay tho.... */
			if (0 == mysql_num_rows($seek_result))
				$id = '-1'; /* because it's a signed integer field: so this is a pointer-to-nothing (fine for a deleted subcorpus). */ 
			else
				list($id) = mysql_fetch_row($seek_result);
			do_mysql_query("update saved_freqtables set subcorpus = '$id' where freqtable_name = '{$o->freqtable_name}'");
		}
	}
	
	/* saved_dbs */
	$target_result = do_mysql_query ('select dbname, corpus, user, subcorpus from saved_dbs');
	while (false !== ($o = mysql_fetch_object($target_result)))
	{
		if (!empty($o->subcorpus))
		{
			$seek_result = do_mysql_query("select id from saved_subcorpora where corpus='{$o->corpus}' and user='{$o->user}' and name='{$o->subcorpus}'");
			/* the below should NEVER happen here because DBs are deleted when the SC is. Anwyay tho.... */
			if (0 == mysql_num_rows($seek_result))
				$id = '-1'; 
			else
				list($id) = mysql_fetch_row($seek_result);
			do_mysql_query("update saved_dbs set subcorpus = '$id' where dbname = '{$o->dbname}'");
		}
	}
	
	/* saved_queries */
	$target_result = do_mysql_query ('select query_name, corpus, user, subcorpus from saved_queries');
	while (false !== ($o = mysql_fetch_object($target_result)))
	{
		if (!empty($o->subcorpus))
		{
			$seek_result = do_mysql_query("select id from saved_subcorpora where corpus='{$o->corpus}' and user='{$o->user}' and name='{$o->subcorpus}'");
			/* the below should NEVER happen here because cached Qs are deleted when the SC is. Anwyay tho.... */
			if (0 == mysql_num_rows($seek_result))
				$id = '-1'; 
			else
				list($id) = mysql_fetch_row($seek_result);
			do_mysql_query("update saved_queries set subcorpus = '$id' where query_name = '{$o->query_name}'");
		}
	}

	/* query history */
	$target_result = do_mysql_query ('select instance_name, corpus, user, subcorpus from query_history');
	while (false !== ($o = mysql_fetch_object($target_result)))
	{
		if (!empty($o->subcorpus))
		{
			$seek_result = do_mysql_query("select id from saved_subcorpora where corpus='{$o->corpus}' and user='{$o->user}' and name='{$o->subcorpus}'");
			/* the below is a step DESIGNED for this table really.........  */
			if (0 == mysql_num_rows($seek_result))
				$id = '-1'; 
			else
				list($id) = mysql_fetch_row($seek_result);
 			do_mysql_query("update query_history set subcorpus = '$id' where instance_name = '{$o->instance_name}'");
		}
	}
	

	$sql = array(
			'alter table corpus_info modify column `primary_classification_field` varchar(64) default NULL',
			'alter table corpus_info add column `alt_context_word_att` varchar(20) default "" after `max_extended_context`',			
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	upgrade_db_version_note('3.2.6');
}

/* 3.2.4->3.2.5 */
function upgrade_3_2_4()
{
	$sql = array(
		'alter table saved_queries drop column date_of_saving',
		'update saved_queries set simple_query = "" where simple_query IS NULL',
		'update saved_queries set cqp_query    = "" where cqp_query    IS NULL',
		'update saved_queries set postprocess  = "" where postprocess  IS NULL',
		'update saved_queries set hits_left    = "" where hits_left    IS NULL',
		'update saved_queries set save_name    = "" where save_name    IS NULL',
		'update saved_dbs     set postprocess  = "" where postprocess  IS NULL',
	);
	foreach ($sql as $q)
		do_mysql_query($q);

	do_mysql_query("update system_info set value = '3.2.5' where setting_name = 'db_version'");
}

/* 3.2.3->3.2.4 */
function upgrade_3_2_3()
{
	$sql = array(
		'alter table user_info add column `css_monochrome` tinyint(1) NOT NULL default 0 after use_tooltips',
		'drop table if exists `user_cookie_tokens`',
		'create table `user_cookie_tokens` (
				`token` bigint UNSIGNED NOT NULL default 0,
				`user_id` int NOT NULL,
				`creation`  int UNSIGNED NOT NULL default 0,
				`expiry`  int UNSIGNED NOT NULL default 0,
				key(`token`, `user_id`)
			) CHARACTER SET utf8 COLLATE utf8_bin'
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	/* switch any existing verify-keys to new format. */
	$result = do_mysql_query("select id, verify_key from user_info where verify_key is not null");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$hash = md5($o->verify_key);
		do_mysql_query("update user_info set verify_key = '$hash' where id = {$o->id}");
	}
		
	do_mysql_query("update system_info set value = '3.2.4' where setting_name = 'db_version'");
}

/* 3.2.1->3.2.2 */
function upgrade_3_2_1()
{
	$sql = array( 
		"CREATE TABLE `metadata_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			`primary_classification` varchar(64) default NULL,
			PRIMARY KEY (`id`)
		) CHARACTER SET utf8 COLLATE utf8_bin",

		"CREATE TABLE `metadata_template_content` (
			`template_id` int unsigned NOT NULL,
			`order_in_template` smallint unsigned,
			`handle` varchar(64) NOT NULL,
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_FREETEXT . "
		) CHARACTER SET utf8 COLLATE utf8_bin",
		
		"update system_info set value = '3.2.2' where setting_name = 'db_version'"
	);
	foreach ($sql as $q)
		do_mysql_query($q);	
}

/* 3.1.16->3.2.0 */
function upgrade_3_1_16()
{
	$sql = array(
		'alter table corpus_info add column `cqp_name` varchar(255) NOT NULL default \'\' after `corpus`',
		'alter table corpus_info add column `access_statement` TEXT default NULL',
		'alter table corpus_info add column `uses_case_sensitivity` tinyint(1) NOT NULL default 0 after `primary_classification_field`',
		'alter table corpus_info add column `title` varchar(255) default \'\' after `visible`',
		'alter table corpus_info add column `css_path` varchar(255) default \'../css/CQPweb.css\' after `public_freqlist_desc`',
		'alter table corpus_info add column `main_script_is_r2l` tinyint(1) NOT NULL default 0 after `combo_annotation`',
		'alter table corpus_info add column `conc_s_attribute` varchar(64) NOT NULL default \'\' after `main_script_is_r2l`',
		'alter table corpus_info add column `conc_scope` smallint NOT NULL default 12 after `conc_s_attribute`',
		'alter table corpus_info add column `initial_extended_context` smallint NOT NULL default 100 after `conc_scope`',
		'alter table corpus_info add column `max_extended_context` smallint NOT NULL default 1100 after `initial_extended_context`',
		'alter table corpus_info add column `visualise_gloss_in_concordance` tinyint(1) NOT NULL default 0 after `max_extended_context`',
		'alter table corpus_info add column `visualise_gloss_in_context` tinyint(1) NOT NULL default 0 after `visualise_gloss_in_concordance`',
		'alter table corpus_info add column `visualise_gloss_annotation` varchar(20) default NULL after `visualise_gloss_in_context`',
		'alter table corpus_info add column `visualise_translate_in_concordance` tinyint(1) NOT NULL default 0 after `visualise_gloss_annotation`',
		'alter table corpus_info add column `visualise_translate_in_context` tinyint(1) NOT NULL default 0 after `visualise_translate_in_concordance`',
		'alter table corpus_info add column `visualise_translate_s_att` varchar(64) default NULL after `visualise_translate_in_context`',
		'alter table corpus_info add column `visualise_position_labels` tinyint(1) NOT NULL default 0 after `visualise_translate_s_att`',
		'alter table corpus_info add column `visualise_position_label_attribute` varchar(64) default NULL after `visualise_position_labels`',
		'alter table corpus_info add column `indexing_notes` TEXT default NULL', # NB column is added "LAST".

		'alter table text_metadata_fields modify column `handle` varchar(64) NOT NULL',
		'alter table text_metadata_values modify column `handle` varchar(200) NOT NULL',
		'alter table text_metadata_values modify column `field_handle` varchar(64) NOT NULL',
		'alter table text_metadata_fields add column `datatype` tinyint(2) NOT NULL default 0 after `description`',
		'update text_metadata_fields set datatype = 2',
		'update text_metadata_fields set datatype = 1 where is_classification = 1',
		/* NB this obsoletes the "is_classification" column, but we leave it for safety. */

		/* now, some general cleanup : tables whose collate should have been utf_bin all along, but for whatever reason, wasn't */
		'alter table `annotation_metadata` collate utf8_bin',
		'alter table `annotation_template_info` collate utf8_bin',
		'alter table `annotation_template_content` collate utf8_bin',
		'alter table `corpus_metadata_variable` collate utf8_bin',
		'alter table `saved_dbs` collate utf8_bin',
		'alter table `saved_freqtables` collate utf8_bin',
		'alter table `saved_subcorpora` collate utf8_bin',
		'alter table `user_memberships` collate utf8_bin',
		'alter table `user_privilege_info` collate utf8_bin',
		'alter table `query_history` collate utf8_bin',
		'alter table `system_processes` collate utf8_bin',
		'alter table `text_metadata_fields` collate utf8_bin',
		'alter table `text_metadata_values` collate utf8_bin',
		'alter table `user_info` collate utf8_bin',
		/* using utf8_bin for user_info implies the following for specific columnss: */
		'alter table `user_info` modify column `affiliation` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL',
		'alter table `user_info` modify column `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci  default NULL',
		'alter table `user_info` modify column `realname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL',
		
		/* more misc cleanup : new length for username */
		'alter table `query_history` modify column `user` varchar(64) default NULL',
		'alter table `saved_catqueries` modify column `user` varchar(64) default NULL',
		'alter table `saved_dbs` modify column `user` varchar(64) default NULL',
		'alter table `saved_matrix_info` modify column `user` varchar(64) default NULL',
		'alter table `saved_matrix_info` modify column `corpus` varchar(20) NOT NULL default \'\'',
		'alter table `saved_freqtables` modify column `user` varchar(64) default NULL',
		'alter table `saved_queries` modify column `user` varchar(64) default NULL',
		'alter table `saved_subcorpora` modify column `user` varchar(64) default NULL',
		'alter table `user_macros` modify column `user` varchar(64) default NULL',
		'alter table `user_info` modify column `username` varchar(64) NOT NULL',

		/* now, the 4 new database tables for XML management */
		"CREATE TABLE `xml_metadata` (
			`id` int NOT NULL AUTO_INCREMENT,
			`corpus` varchar(20) NOT NULL,
			`handle` varchar(64) NOT NULL,
			`att_family` varchar(64) NOT NULL default '',
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_NONE . ",
			primary key(`id`),
			unique key (`corpus`, `handle`)
		) CHARACTER SET utf8 COLLATE utf8_bin",
		"CREATE TABLE `xml_metadata_values` (
			`corpus` varchar(20) NOT NULL,
			`att_handle` varchar(64) NOT NULL,
			`handle` varchar(200) NOT NULL,
			`description` varchar(255) default NULL, 
			`category_num_words` int unsigned default NULL,
			`category_num_segments` int unsigned default NULL,
			primary key(`corpus`, `att_handle`, `handle`)
		) CHARACTER SET utf8 COLLATE utf8_bin",
		"CREATE TABLE `xml_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			PRIMARY KEY (`id`)
		) CHARACTER SET utf8 COLLATE utf8_bin",
		"CREATE TABLE `xml_template_content` (
			`template_id` int unsigned NOT NULL,
			`order_in_template` smallint unsigned,
			`handle` varchar(64) NOT NULL,
			`att_family` varchar(64) NOT NULL default '',
			`description` varchar(255) default NULL,
			`datatype`  tinyint(2) NOT NULL default " . METADATA_TYPE_NONE . "
		) CHARACTER SET utf8 COLLATE utf8_bin",
	);
	foreach ($sql as $q)
		do_mysql_query($q);	

	/* do the very last DB change! */
	do_mysql_query("update system_info set value = '3.2.0' where setting_name = 'db_version'");
	
	/* because there is an extra step here, this merits breaking out of the loop and hard-exiting, naturellement */
	
	echo <<<ENDOFNOTE
	
	IMPORTANT MESSAGE
	=================
	
	The database has now upgraded as far v3.2.0; this involves a change to how corpora are stored on the system.
	Before you upgrade the database any further, you need to run the "load-pre-3.2-corpsettings.php" script to 
	make sure your corpus settings transition to the new format.
	
	This script now exits. Please run that other script, then (if you are on a later version than 3.2.0)
	run "upgrade-database.php" again.


ENDOFNOTE;

	disconnect_global_mysql();

	exit;
}

/* 3.1.9->3.1.10 */
function upgrade_3_1_9()
{
	$sql = array(
		'alter table corpus_info add column `size_tokens` int NOT NULL DEFAULT 0 after `public_freqlist_desc`',
		'alter table corpus_info add column `size_texts`  int NOT NULL DEFAULT 0 after `size_tokens`',
		'alter table corpus_info add column `size_types`  int NOT NULL DEFAULT 0 after `size_tokens`'
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	/* now, install token / text counts for each corpus on the system */
	echo "Corpus metadata format has been changed: existing corpus info will now be updated. Please wait.\n";
	$result = do_mysql_query('select corpus from corpus_info');
	while (false !== ($r = mysql_fetch_row($result)))
	{
		// tokens (and texts)
		if (0 < mysql_num_rows(do_mysql_query("show tables like 'text_metadata_for_{$r[0]}'")))
		{
			$inner = do_mysql_query("select sum(words), count(*) from text_metadata_for_{$r[0]}");
			list($ntok, $ntext) = mysql_fetch_row($inner);
			do_mysql_query("update corpus_info set size_tokens = $ntok, size_texts = $ntext where corpus = '{$r[0]}'");
		}
		
		// types
		if (0 < mysql_num_rows(do_mysql_query("show tables like 'freq_corpus_{$r[0]}_word'")))
		{
			list($types) = mysql_fetch_row(do_mysql_query("select count(distinct(item)) from freq_corpus_{$r[0]}_word"));
			do_mysql_query("update corpus_info set size_types = $types where corpus = '$r[0]'");
		}
		
		echo "Corpus info has been updated for ", $r[0], "!\n"; 
	}
	echo "Done updating existing corpus info.\n";
	
	/* do the very last DB change! */
	do_mysql_query("update system_info set value = '3.1.10' where setting_name = 'db_version'");
}

/* 3.1.8->3.1.9 */
function upgrade_3_1_8()
{
	$sql = array(
		"CREATE TABLE `saved_matrix_info` (
           `id` int NOT NULL AUTO_INCREMENT,
           `savename` varchar(255),
           `user` varchar(255) default NULL,
           `corpus` varchar(255) NOT NULL default '',
           `subcorpus` varchar(255) default NULL,
           `unit` varchar(255) default NULL,
           `create_time` int(11) default NULL,
           primary key(`id`)
         ) CHARACTER SET utf8 COLLATE utf8_bin",
		"CREATE TABLE `saved_matrix_features` (
            `id` int NOT NULL AUTO_INCREMENT,
            `matrix_id` int NOT NULL,
            `label` varchar(255) NOT NULL,
            `source_info` varchar(255) default NULL,
            primary key(`id`)
          ) CHARACTER SET utf8 COLLATE utf8_bin"
	);
	foreach ($sql as $q)
		do_mysql_query($q);

	
	/* do the very last DB change! */
	do_mysql_query("update system_info set value = '3.1.9' where setting_name = 'db_version'");
}

/* 3.1.7->3.1.8 */
function upgrade_3_1_7()
{
	/* database format has not changed, but format of the postprocess string HAS. 
	 * So perform surgery on the saved-queries table to update it.
	 * 
	 * WARNING: if any new-format queries (using the new "item" postprocess)
	 * have been carried out between the code being updated and this script being run, 
	 * they will be corrupted by the oepration of this script.
	 */
	$count = 0;
	$result = do_mysql_query("select query_name, postprocess from saved_queries where postprocess like 'item[%' or postprocess like '%~~item[%'");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$new_pp = preg_replace('/^item\[/', 'item[0~', $o->postprocess);
		$new_pp = preg_replace('/~~item\[/', '~~item[0~', $o->postprocess);
		$new_pp = mysql_real_escape_string($new_pp);
		do_mysql_query("UPDATE saved_queries set postprocess = '$new_pp' where query_name = '{$o->query_name}'");
		$count++;
	}	
	echo "The format of $count cached queries has been updated to reflect changes in Frequency Breakdown in v3.1.8.\n\n";
	 
	/* delete databases associated with "item" postprocesses. */
	$result = do_mysql_query("select dbname from saved_dbs where postprocess like 'item[%' or postprocess like '%~~item[%'");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		do_mysql_query("DROP TABLE IF EXISTS {$o->dbname}");
		do_mysql_query("DELETE FROM saved_dbs where dbname = '{$o->dbname}'");
	} 
	
	/* do the very last DB change! */
	do_mysql_query("update system_info set value = '3.1.8' where setting_name = 'db_version'");	
}

/* 3.1.4->3.1.5 */
function upgrade_3_1_4()
{
	$sql = array(
		'alter table annotation_template_content add column `order_in_template` smallint unsigned after `template_id`',
		'alter table annotation_template_info add column `primary_annotation` varchar(20) default NULL after `description`',
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	/* do the very last DB change! */
	do_mysql_query("update system_info set value = '3.1.5' where setting_name = 'db_version'");
}

/* 3.1.3->3.1.4 */
function upgrade_3_1_3()
{
	$sql = array(
		'alter table user_info modify column `username` varchar(30) charset utf8 collate utf8_bin NOT NULL',
		'CREATE TABLE `user_captchas` (
		   `id` bigint unsigned NOT NULL AUTO_INCREMENT,
		   `captcha` char(6),
		   `expiry_time` int unsigned,
		   primary key (`id`)
		 ) CHARACTER SET utf8 COLLATE utf8_bin',
		'alter table `annotation_metadata` add column `is_feature_set` tinyint(1) NOT NULL default 0 AFTER `description`',
		'CREATE TABLE `annotation_template_content` (
			`template_id` int unsigned NOT NULL,
			`handle` varchar(20) NOT NULL,
			`description` varchar(255) default NULL,
			`is_feature_set` tinyint(1) NOT NULL default 0,
			`tagset` varchar(255) default NULL,
			`external_url` varchar(255) default NULL
		) CHARACTER SET utf8 COLLATE utf8_general_ci',
		'CREATE TABLE `annotation_template_info` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default NULL,
			PRIMARY KEY (`id`)
		) CHARACTER SET utf8 COLLATE utf8_general_ci'
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	/* do the very last DB change! */
	do_mysql_query("update system_info set value = '3.1.4' where setting_name = 'db_version'");
}


/* this one is the huge one ....... 3.0.16->3.1.0 */
function upgrade_3_0_16()
{
	/* first, the pre-amendments from v 3.0.15 */
	if (1 > mysql_num_rows(do_mysql_query("show indexes from saved_dbs where Key_name = 'PRIMARY'")))
	{
		if (1 > mysql_num_rows(do_mysql_query("show indexes from saved_dbs where Key_name = 'dbname'")))
			do_mysql_query('alter table saved_dbs drop key dbname');
		do_mysql_query('alter table saved_dbs add primary key `dbname` (`dbname`)');
	}
	if (1 > mysql_num_rows(do_mysql_query("show indexes from mysql_processes where Key_name = 'PRIMARY'")))
		do_mysql_query('alter table mysql_processes add primary key (`dbname`)');
	if (1 > mysql_num_rows(do_mysql_query("show indexes from saved_freqtables where Key_name = 'PRIMARY'")))
		do_mysql_query('alter table saved_freqtables add primary key (`freqtable_name`)');
	if (1 > mysql_num_rows(do_mysql_query("show indexes from saved_dbs where Key_name = 'PRIMARY'")))
	{
		if (1 > mysql_num_rows(do_mysql_query("show indexes from system_messages where Key_name = 'message_id'")))
			do_mysql_query('alter table system_messages drop key `message_id`');
		do_mysql_query('alter table system_messages add primary key (`message_id`)');
	}
	
	/* now, the main course: 3.0.16 */
	
	$sql = array(
		'alter table user_settings    alter column username set default ""',
		'alter table saved_catqueries alter column corpus set default ""',
		'alter table saved_catqueries alter column dbname set default ""',
		'alter table saved_dbs alter column corpus set default ""',
		'alter table saved_subcorpora alter column subcorpus_name set default ""',
		'alter table saved_subcorpora alter column corpus set default ""',
		'alter table saved_freqtables alter column subcorpus set default ""',
		'alter table saved_freqtables alter column corpus set default ""',
		'alter table system_messages modify header varchar(150) default ""',
		'alter table system_messages modify fromto varchar(150) default NULL',
		'alter table user_macros alter column username set default ""',
		'alter table user_macros alter column macro_name set default ""',
		'alter table user_macros add column `id` int NOT NULL AUTO_INCREMENT FIRST, add primary key (`id`)',
		'alter table xml_visualisations alter column corpus set default ""',
		'alter table xml_visualisations alter column element set default ""',
		'alter table xml_visualisations drop primary key',
		'alter table xml_visualisations add column `id` int NOT NULL AUTO_INCREMENT FIRST, add primary key (`id`)',
		'alter table xml_visualisations add unique key(`corpus`, `element`, `cond_attribute`, `cond_regex`)',
		/* The GREAT RENAMING  and rearrangement of main corpus/user tables */
		'rename table mysql_processes to system_processes',
		'rename table user_settings to user_info',
		'rename table corpus_metadata_fixed to corpus_info',
		'alter table user_info drop primary key',
		'alter table user_info add column `id` int NOT NULL AUTO_INCREMENT FIRST, add primary key (`id`)',
		'alter table user_info add unique key (`username`)',		
		'alter table corpus_info drop primary key',
		'alter table corpus_info add column `id` int NOT NULL AUTO_INCREMENT FIRST, add primary key (`id`)',
		'alter table corpus_info add unique key (`corpus`)',
		'alter table corpus_categories drop column idno',
		'alter table corpus_categories add column `id` int NOT NULL AUTO_INCREMENT FIRST, add primary key (`id`)',
		'alter table annotation_mapping_tables drop key `id`',
		'update annotation_mapping_tables set id="oxford_simple_tags" where id="oxford_simplified_tags"',
		'update annotation_mapping_tables set id="rus_mystem_classes" where id="russian_mystem_wordclasses"',
		'update annotation_mapping_tables set id="nepali_simple_tags" where id="simplified_nepali_tags"',
		'update corpus_info set tertiary_annotation_tablehandle="oxford_simple_tags" where tertiary_annotation_tablehandle="oxford_simplified_tags"',
		'update corpus_info set tertiary_annotation_tablehandle="rus_mystem_classes" where tertiary_annotation_tablehandle="russian_mystem_wordclasses"',
		'update corpus_info set tertiary_annotation_tablehandle="nepali_simple_tags" where tertiary_annotation_tablehandle="simplified_nepali_tags"'
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	$result = do_mysql_query("select id from annotation_mapping_tables where char_length(id) > 20");
	while (false !== ($r = mysql_fetch_row($result)))
	{
		list($oldhandle) = $r;
		echo "WARNING. Annotation mapping table handle '$oldhandle' is too long for the new DB version. Please enter one of 20 characters or less.\n";
		for($continue = true; $continue; )
		{
			$newhandle = get_variable_word('a new handle for this table');
			$continue = false;
			
			if (strlen($newhandle) > 20)
			{
				echo "Sorry, that name is too long. 20 characters or less please!\n";
				$continue = true;
			}
			$result = do_mysql_query("select id from annotation_mapping_tables where id = $newhandle");
			if (0 < mysql_num_rows($result))
			{
				echo "Sorry, that handle already exists. Suggest another please!\n";
				$continue = true;
			}		
		}
		echo "thank you, replacing the handle now.........\n"; 
		
		do_mysql_query("update annotation_mapping_tables set id='$newhandle' where id='$oldhandle'");
		do_mysql_query("update corpus_info set tertiary_annotation_tablehandle='$newhandle' where tertiary_annotation_tablehandle='$oldhandle'");
	}

	/* ok, with that fixed, back to just running lists of commands.... */
	
	$sql = array(	
		'alter table annotation_mapping_tables CHANGE `id` `handle` varchar(20) NOT NULL, add primary key (`handle`)',
		/* some new info fields for the corpus table... for use later. */
		'alter table corpus_info add column `is_user_corpus` tinyint(1) NOT NULL default 0',
		'alter table corpus_info add column `date_of_indexing` timestamp NOT NULL default CURRENT_TIMESTAMP',
		/* let's get the system_info table */
		'CREATE TABLE `system_info` (
		   setting_name varchar(20) NOT NULL collate utf8_bin,
		   value varchar(255),
		   primary key(`setting_name`)
		 ) CHARACTER SET utf8 COLLATE utf8_general_ci',
		"insert into system_info (setting_name, value) VALUES ('db_version',  '3.0.16')",	# bit pointless, but establishes the last-SQL template
		/* now standardise length of usernames across all tables to THIRTY. */
		'alter table user_macros drop key username, CHANGE `username`  `user` varchar(30) NOT NULL, add unique key (`user`, `macro_name`)',
		'alter table user_macros CHANGE macro_name `macro_name` varchar(20) NOT NULL default ""',
		'alter table saved_queries modify `user` varchar(30) default NULL',
		'alter table saved_catqueries modify `user` varchar(30) default NULL',
		'alter table query_history modify `user` varchar(30) default NULL',
		'alter table user_info modify `username` varchar(30) NOT NULL',
		/* new tables for the new username system */
		'CREATE TABLE `user_groups` (
		   `id` int NOT NULL AUTO_INCREMENT,
		   `group_name` varchar(20) NOT NULL UNIQUE COLLATE utf8_bin,
		   `description` varchar(255) NOT NULL default "",
		   `autojoin_regex` text,
		   primary key (`id`)
		 ) CHARACTER SET utf8 COLLATE utf8_general_ci',
		 'CREATE TABLE `user_memberships` (`user_id` int NOT NULL,`group_id` int NOT NULL,`expiry_time` int UNSIGNED NOT NULL default 0) CHARACTER SET utf8 COLLATE utf8_general_ci',
		'insert into user_groups (group_name,description)values("superusers","Users with admin power")',
		'insert into user_groups (group_name,description)values("everybody","Group to which all users automatically belong")'
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	echo "User groups are managed in the database now, not in the Apache htgroup file.\n";
	echo "If you want to re-enable your old groups, please use load-pre-3.1-groups.php.\n";
	echo "(Please acknowledge.)\n";
	get_enter_to_continue();
	
	/* back to DB changes again */
	
	$sql = array(
		'alter table user_info add column `passhash` char(61) AFTER email',
		'alter table user_info add column `acct_status` tinyint(1) NOT NULL default 0 AFTER passhash',
		/* all existing users count as validated. */
		'update user_info set acct_status = ' . USER_STATUS_ACTIVE,
		'alter table user_info add column `expiry_time` int UNSIGNED NOT NULL default 0 AFTER acct_status',
		'alter table user_info add column `last_seen_time` timestamp NOT NULL default CURRENT_TIMESTAMP AFTER expiry_time',
		'alter table user_info add column `password_expiry_time` int UNSIGNED NOT NULL default 0 AFTER expiry_time',
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	/* CONVERT EXISTING PASSWORDS INTO PASSHASHES */
	echo "about to shift password system over to hashed-values in the database....\n";
	echo "all users whose accounts go back to the era before CQPweb kept passwords in the database will\n";
	echo "have their password changed to the string ``change_me'' (no quotes) and a near-future expiry date set on that password;\n";
	echo "depending on your code version, password expiry may or may not be implemented. (Please acknowledge).\n";
	get_enter_to_continue();
	
	$result = do_mysql_query("select username, password from user_info");
	$t = time() + (7 * 24 * 60 * 60);
	while (false !== ($o = mysql_fetch_object($result)))
	{
		if (empty($o->password))
		{
			$extra =  ", password_expiry_time = $t";
			$o->password='change_me';
		}
		else
			$extra = '';
		
		$passhash = generate_new_hash_from_password($o->password);
		do_mysql_query("update user_info set passhash = '$passhash'$extra where username = '{$o->username}'");
	}
	echo "done transferring passwords to secure encrypted form. Old passwords will NOT be deleted.\n";
	echo "Once you are satisfied the database transfer has worked correctly, you should MANUALLY run\n";
	echo "the following MySQL statement: \n";
	echo "    alter table `user_info` drop column `password`\n";
	echo "Please acknowledge.\n";
	get_enter_to_continue();
	
	/* back to DB changes again */
	
	$sql = array(
		"alter table user_info add column `verify_key` varchar(32) default NULL AFTER acct_status",
		"CREATE TABLE `user_cookie_tokens` (
			`token` char(33) NOT NULL default '__token' UNIQUE,
			`user_id` int NOT NULL,
			`expiry`  int UNSIGNED NOT NULL default 0
			) CHARACTER SET utf8 COLLATE utf8_bin",
		"alter table user_info modify column `email` varchar(255) default NULL",
		"alter table user_info modify column `realname` varchar(255) default NULL",
		"alter table user_info add column `affiliation` varchar(255) default NULL after `email`",
		"alter table user_info add column `country` char(2) default '00' after `affiliation`",
		"CREATE TABLE `user_privilege_info` (
			`id` int NOT NULL AUTO_INCREMENT,
			`description` varchar(255) default '',
			`type` tinyint(1) unsigned default NULL,
			`scope` text,
			primary key(`id`)
			) CHARACTER SET utf8 COLLATE utf8_general_ci",
		"CREATE TABLE `user_grants_to_groups` 
			(`group_id` int NOT NULL,`privilege_id` int NOT NULL,`expiry_time` int UNSIGNED NOT NULL default 0) 
			CHARACTER SET utf8 COLLATE utf8_general_ci",
		"CREATE TABLE `user_grants_to_users` 
			(`user_id` int NOT NULL,`privilege_id` int NOT NULL,`expiry_time` int UNSIGNED NOT NULL default 0) 
			CHARACTER SET utf8 COLLATE utf8_general_ci",
		"alter table user_info add column `acct_create_time` timestamp NOT NULL default 0 after `last_seen_time`"
	);
	foreach ($sql as $q)
		do_mysql_query($q);
	
	echo "User privileges are managed in the database now, not in Apache htaccess files.\n";
	echo "If you want to re-import your old group access privileges, please use load-pre-3.1-privileges.php.\n";
	echo "(Please acknowledge.)\n";
	get_enter_to_continue();
	
	/* do the very last DB change! */
	do_mysql_query("update system_info set value = '3.1.0' where setting_name = 'db_version'");
}




