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
 * 
 * @file
 * 
 * Script that allows superusers direct access to the function library via the URL / get method.
 * 
 * in the format:
 * 
 * execute.php?function=foo&args=["string"#1#2]&locationAfter=[index.php?thisQ=search]&uT=y
 * 
 * (note that everything within [] needs to be url-encoded for non-alphanumerics)
 * 
 *    
 * ANOTHER IMPORTANT NOTE:
 * =======================
 * 
 * It is quite possible to **break CQPweb** using this script.
 * 
 * It has been written on the assumption that anyone who is a superuser is sufficiently
 * non-idiotic to avoid doing so.
 * 
 * If for any given superuser this assumption is false, then that is his/her/your problem.
 * 
 * Not CQPweb's.
 * 
 */


/* include defaults and settings */
require('../lib/environment.inc.php');


/* include all function files */
include('../lib/admin-lib.inc.php');
include("../lib/admin-install.inc.php");
include('../lib/cache.inc.php');
include('../lib/ceql.inc.php');
include('../lib/db.inc.php');
include('../lib/colloc-lib.inc.php');
include('../lib/concordance-lib.inc.php');
include('../lib/cqp.inc.php');
include('../lib/cwb.inc.php');
include('../lib/exiterror.inc.php');
include('../lib/freqtable.inc.php');
include('../lib/html-lib.inc.php');
include('../lib/indexforms-admin.inc.php');
include('../lib/indexforms-others.inc.php');
include('../lib/indexforms-queries.inc.php');
include('../lib/indexforms-saved.inc.php');
include('../lib/indexforms-subcorpus.inc.php');
include('../lib/indexforms-user.inc.php');
include('../lib/library.inc.php');
include('../lib/metadata.inc.php');
include('../lib/subcorpus.inc.php');
require('../lib/templates.inc.php');
include('../lib/uploads.inc.php');
include('../lib/user-lib.inc.php');
include('../lib/xml.inc.php');


cqpweb_startup_environment(CQPWEB_STARTUP_CHECK_ADMIN_USER, (PHP_SAPI == 'cli' ? RUN_LOCATION_CLI : RUN_LOCATION_CORPUS));
/* 
 * note above - only superusers get to use this script!
 * also note, this script assumes it is running within a corpus.
 * If it ISN'T, then you need to be careful not to call any
 * function that needs an environment $Corpus to be specfied.
 * This applies, most critically, to admin-execute and execute-cli. 
 */




/* get the name of the function to run */
if (isset($_GET['function']))
	$function = $_GET['function'];
else
	execute_print_and_exit('No function specified for execute.php', 
		"You did not specify a function name for execute.php.\n\nYou should reload and specify a function.");




/* extract the arguments */
if (isset($_GET['args']))
{
	/* if args is not a string, then it has already been prepared, (e.g. by admin-execute... )
	 * so we just need to array-ise it for passing to call_user_func_array... */
	if (!is_string($_GET['args']))
	{
		$argv = array($_GET['args']);
		$argc = 1;
	}
	/* otherwise, it's a string containing a series of string arguments */
	else
	{
		$argv = explode('#', $_GET['args']);
		$argc = count($argv);
	}
}
else
{
	$argc = 0;
	$argv = array();
}
if ($argc > 20)
	execute_print_and_exit('Too many arguments for execute.php', 
'You specified too many arguments for execute.php.

The script only allows up to twenty arguments, as a cautionary measure.'
		);



/* check the function is safe to call */
$all_function = get_defined_functions();
if (in_array($function, $all_function['user']))
	; /* all is well */
else
	execute_print_and_exit('Function not available -- execute.php',
'The function you specified is not available via execute.php.

The script only allows you to call CQPweb\'s own function library -- NOT the built-in functions
of PHP itself. This is for security reasons (otherwise someone could hijack your password and go
around calling passthru() or unlink() or any other such dodgy function with arbitrary arguments).'
		);



/* run the function */
call_user_func_array($function, $argv);


cqpweb_shutdown_environment();


/* go to the specified address, if one was specified AND if the HTTP headers have not been sent yet 
 * (if execution of the function caused anything to be written, then they WILL have been sent)      
 */


if ( isset($_GET['locationAfter']) && headers_sent() == false )
	header('Location: ' . url_absolutify($_GET['locationAfter']));
else if ( ! isset($_GET['locationAfter']) && headers_sent() == false )
	execute_print_and_exit( 'CQPweb -- execute.php', 'Your function call has been finished executing!');


/*
 * =============
 * END OF SCRIPT
 * =============
 */

/** a special form of "exit" function just used by execute.php script */
function execute_print_and_exit($title, $content)
{
	global $execute_cli_is_running;
	if (isset($execute_cli_is_running) && $execute_cli_is_running)
		exit("CQPweb has completed the requested action.\n");
	else
		exit(<<<HERE
<html><head><title>$title</title></head><body><pre>
$content

CQPweb (c) 2008-today
</pre></body></html>
HERE
		);
}


