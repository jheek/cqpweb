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
 * This file contains functions that simulate those in the mysql_ extension.
 * 
 * It is for use only in environments where PHP has been compiled without the
 * mysql extension but with the mysqli extension (expected to be increasingly
 * common).
 * 
 * Only the subset of mysql_* functions that are used by CQPweb are simulated.
 * 
 * The file library.inc.php contains the check on whether or not this file is
 * included into CQPweb.
 * 
 * Its existence means that CQPweb's prerequisites for PHP extensions are
 * more flexible.
 * 
 * Because of the function-calling overhead (and the fact that none of the actual
 * advanced features of mysqli are used) fake-mysql will be less efficient than 
 * "real-mysql". So, consider adding the mysql extension if you're in a situation
 * where every nanosecond counts!
 */



/* functions for the mysql_link resource (actually a mysqli object masquerading as a mysql_link resource) */


/**
 * Fake MySQL connect function using MySQLi.
 * 
 * Note this only supports the first three arguments of the original function.
 */
function mysql_connect($server = NULL, $username = NULL, $password = NULL)
{
	if ($server === NULL)
		$server = ini_get("mysqli.default_host");
	if ($username === NULL)
		$username = ini_get("mysqli.default_user");
	if ($server === NULL)
		$password = ini_get("mysqli.default_pw");
	
	$obj = mysqli_connect($server, $username, $password);
	
	if (mysqli_connect_error() === NULL)
	{
		global $mysql_fake_connect_last_opened_link_identifier;
		$mysql_fake_connect_last_opened_link_identifier = $obj;
		return $obj;
	}
	else
		return false; 
}


/**
 * Fake MySQL close-connection function using MySQLi.
 */
function mysql_close($link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return false;

	return mysqli_close($link_identifier);	
}


/**
 * Fake MySQL version-string-getter using MySQLi.
 */
function mysql_get_server_info($link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return false;
	
	return mysqli_get_server_info($link_identifier);
}

/**
 * False MySQL charset set function using MySQLi.
 */
function mysql_set_charset($charset, $link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return false;
	
	return mysqli_set_charset($link_identifier, $charset);
}


/**
 * Fake MySQL real-escape-string function using MySQLi.
 */
function mysql_real_escape_string($unescaped_string, $link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return false;
	
	$string = mysqli_real_escape_string($link_identifier, $unescaped_string);
	
	if (!mysqli_errno($link_identifier) )
		return $string;
	else
		return false;
}


/**
 * Fake MySQL error-number-getter function using MySQLi.
 */
function mysql_errno($link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return 0;
	
	return mysqli_errno($link_identifier);
}



/**
 * Fake MySQL error-string-getter function using MySQLi.
 */
function mysql_error($link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return '';
	
	return mysqli_error($link_identifier);
}



/**
 * Fake MySQL schema-setter function using MySQLi.
 */
function mysql_select_db($database_name, $link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return false;
	
	return mysqli_select_db($link_identifier, $database_name);	
}



/**
 * Fake MySQL query function using MySQLi.
 */
function mysql_query($query, $link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return false;
		
	return mysqli_query($link_identifier, $query);
}


/**
 * Fake MySQL count-affected-rows function using MySQLi.
 */
function mysql_affected_rows($link_identifier = NULL)
{
	if (!mysql_fake_force_link_set($link_identifier))
		return false;

	return mysqli_affected_rows($link_identifier);
}


/**
 * Fake MySQL get-insert-id function using MySQLi
 */
function mysql_insert_id($link_identifier = NULL) 
{
	if (!mysql_fake_force_link_set($link_identifier))
		return false;	
	
	return mysqli_insert_id($link_identifier);
}



/**
 * Module-internal utility function. Returns Boolean (whether the link was successfully made).
 */
function mysql_fake_force_link_set(&$link_identifier)
{
	if ($link_identifier !== NULL)
		return true;
	
	/* "If the link identifier is not specified, the last link opened by mysql_connect() is assumed" */
	global $mysql_fake_connect_last_opened_link_identifier;
	$link_identifier = $mysql_fake_connect_last_opened_link_identifier;
	
	/* "If no such link is found, it will try to create one as if mysql_connect() was called with no arguments" */
	if (!is_object($link_identifier))
		$link_identifier = mysql_connect();
	else
		return true;
	
	/* "If no connection is found or established, an E_WARNING level error is generated" */
	if (!is_object($link_identifier))
	{
		trigger_error('Could not find or create a link to MySQL', E_USER_WARNING);
		return false;
	}
	else 
		return true;	
}



/* functions for the mysql result object/resource */

/* there are 3 constants asociated with mysql_fetch_array */
define('MYSQL_NUM',   '1');
define('MYSQL_ASSOC', '2');
define('MYSQL_BOTH',  '3');    /* NB this is intentionally = MYSQL_NUM | MYSQL_ASSOC */

/**
 * Fake MySQL-result table-row-fetching function.
 */
function mysql_fetch_array($result, $result_type = MYSQL_BOTH)
{
	switch($result_type)
	{
		case MYSQL_ASSOC:	return mysql_fetch_assoc($result);	
		case MYSQL_NUM:		return mysql_fetch_row($result);
		case MYSQL_BOTH:
			$array = mysql_fetch_assoc($result);
			foreach($array as $a)
				$array2[] = $a;	
			return array_merge($array, $array2);
	}
}

/**
 * Fake MySQL-result table-row-fetching function.
 */
function mysql_fetch_assoc($result)
{
	$ret = mysqli_fetch_assoc($result);
	if ($ret === NULL)
		return false;
	else
		return $ret;
}

/**
 * Fake MySQL-result table-row-fetching function.
 */
function mysql_fetch_row($result)
{
	$ret = mysqli_fetch_row($result);
	if ($ret === NULL)
		return false;
	else
		return $ret;
}

/**
 * Fake MySQL-result table-row-fetching function.
 */
function mysql_fetch_object($result, $class_name = 'stdClass', $params = NULL)
{
	if ($params === NULL)
		$ret = mysqli_fetch_object($result, $class_name);
	else
		$ret = mysqli_fetch_object($result, $class_name, $params);
		
	if ($ret === NULL)
		return false;
	else
		return $ret;
}

/**
 * Fake MySQL-result table-info function.
 */
function mysql_num_rows($result)
{
	$ret = mysqli_num_rows($result);
	if ($ret === NULL)
		return false;
	else
		return $ret;
}

/**
 * Fake MySQL-result table-info function.
 */
function mysql_num_fields($result)
{
	$ret = mysqli_num_fields($result);
	if ($ret === NULL)
		return false;
	else
		return $ret;	
}



/**
 * Fake MySQL-result table-info function.
 */
function mysql_field_name($result, $field_offset)
{
	$info = mysqli_fetch_field_direct($result, $field_offset);
	if (isset( $info['name'] ))
		return $info['name'];
	else
		return false;	
}

/**
 * Fake MySQL-result move-row-pointer function.
 */
function mysql_data_seek($result, $row_number)
{
	return mysqli_data_seek($result, $row_number);
}



//TODO test this module
