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
 * The exiterror module prints out an error page and performs necessary
 * error-actions before shutting down CQPweb.
 * 
 * Functions outside the module should always call one of the functions
 * that builds up a message template, e.g. exiterror_general.
 * 
 * These functions in turn call the ones that do formatting etc.
 */
 

/**
 * Function internal to the exiterror module.
 * 
 * Writes the start of an error page, if and only if nothing has been sent back
 * via HTTP yet.
 * 
 * If the HTTP response headers have been sent, it does nothing.
 * 
 * Used by other exiterror functions (can be called unconditionally).
 */
function exiterror_beginpage($page_title = NULL, $page_heading_message = NULL)
{
	global $Config;
	
	if (headers_sent())
		return;

	if (! isset($page_title))
		$page_title = "CQPweb has encountered an error!";
	if (! isset($page_heading_message))
		$page_heading_message = 'CQPweb encountered an error and could not continue.';
	
	if ($Config->debug_messages_textonly)
	{
		header("Content-Type: text/plain; charset=utf-8");
		echo "$page_title\n";
		for ($i= 0, $n = strlen($page_title); $i < $n ; $i++)
			echo '=';
		echo "\n\n";
	}
	else
		echo print_html_header($page_title, isset($Config->css_path) ? $Config->css_path : '');
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable"><?php echo escape_html($page_heading_message); ?></th>
		</tr>
	<?php
}

/**
 * Function internal to the exiterror module.
 * 
 * Prints error message lines in either plaintext or HTML.
 * 
 * (The actual HTML that causes the formatting of the error page is here.)
 */
function exiterror_printlines($lines)
{
	global $Config;
	
	$before = ($Config->debug_messages_textonly ? '' : '<tr><td class="concorderror"><p>&nbsp;</p><p class="errormessage">');
	$after  = ($Config->debug_messages_textonly ? "\n\n" : "</p><p>&nbsp;</p></td></tr>\n\n");

	if (!$Config->debug_messages_textonly)
		$lines = array_map('escape_html', $lines);

	foreach($lines as $l)
		echo $before , $l , $after;
}

/**
 * Function internal to exiterror module.
 * 
 * Prints a debug backtrace if user is superuser.
 * 
 * Prints a footer iff we're in HTML context; then kills CQPweb.
 * 
 * If $backlink is true, a link to the home page for the corpus is included.
 */
function exiterror_endpage($backlink = false)
{
	global $Config;
	global $User;
	
	/* print the PHP back trace */
	if ( (isset($User) && $User->is_admin()) || $Config->all_users_see_backtrace)
	{
		$backtrace = debug_backtrace();
		unset($backtrace[0]); /* because we don't care about the call to *this* function */
		
		if ($Config->debug_messages_textonly)
		{
			echo "\n\nPHP debugging backtrace\n=======================\n";
			
			var_dump($backtrace);
		}
		else
		{
			?>
			<tr>
				<th class="concordtable">PHP debugging backtrace</th>
			</tr>
			<tr>
				<td class="concorderror">
					<pre><?php var_dump($backtrace); ?></pre>
				</td>
			</tr>			
			<?php
		}
	}

	/* print the backlink, if requested. */	
	if ( ! $Config->debug_messages_textonly)
	{
		if ($backlink)
		{
			?>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<p class="errormessage">
						<a href="index.php">Back to main page.</a>
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
		echo print_html_footer('hello');
	}
	
	cqpweb_shutdown_environment();
	exit();
}

/**
 * Function internal to exiterror module.
 * 
 * Adds a script/line location to a specified array of error messages.
 */
function exiterror_msg_location(&$array, $script=NULL, $line=NULL)
{
	if (!empty($script))
		$array[]
			= "... in file $script"
			. ( empty($line) ? '' : " line $line")
			. '.'; 
}




/**
 * Primary function to be called by other modules.
 * 
 * Prints the specified error messages (with location of error if we're told that)
 * and then exits.
 * 
 * The error message is allowed to be an array of paragraphs.
 */
function exiterror($errormessage, $script=NULL, $line=NULL)
{
	if (is_array($errormessage))
		$msg = $errormessage;
	else
		$msg = array($errormessage);
	
	exiterror_msg_location($msg, $script, $line);
	
	exiterror_beginpage();
	exiterror_printlines($msg);
	exiterror_endpage();
}

/**
 * Depracated synonym.
 */
function exiterror_general($errormessage, $script=NULL, $line=NULL)
{
	exiterror($errormessage, $script, $line);
}



/**
 * Variation on general error function specifically for failed login.
 * 
 * Unlike other exiterrors, it does not admit of script / line errors
 * (because this "error" is not a bug: it's a user error but not a
 * software error).
 */
function exiterror_login($errormessage) 
{
	if (is_array($errormessage))
		$msg = $errormessage;
	else
		$msg = array($errormessage);
	
	exiterror_beginpage("Unsuccessful login!", "Your login was not successful.");
	exiterror_printlines($msg);
	exiterror_endpage();
}

function exiterror_bad_url()
{
	$msg = array("We're sorry, but CQPweb could not read your full URL.");
	$msg[] = "This sometimes happens when you have clicked on a link in an email or a document, "
			."and the link has been mis-formatted by your email or document reader.";
	
	exiterror_beginpage();
	exiterror_printlines($msg);
	exiterror_endpage(true);
}

function exiterror_cacheoverload()
{
	$msg = array("CRITICAL ERROR - CACHE OVERLOAD!");
	$msg[] = "CQPweb tried to clear cache space but failed!";
	$msg[] = "Please report this error to the system administrator.";
	
	exiterror_beginpage();
	exiterror_printlines($msg);
	exiterror_endpage();
}


/** used for freqtable overloads too */
function exiterror_dboverload()
{
	$msg = array("CRITICAL ERROR - DATABASE CACHE OVERLOAD!");
	$msg[] = "CQPweb tried to clear database cache space but failed!";
	$msg[] = "Please report this error to the system administrator.";
	
	exiterror_beginpage();
	exiterror_printlines($msg);
	exiterror_endpage();
}


function exiterror_toomanydbprocesses($process_type)
{
	global $Config;

	$msg = array("Too many database processes!");
	$msg[] = "There are already {$Config->mysql_process_limit[$process_type]} " 
		. "{$Config->mysql_process_name[$process_type]}	databases being compiled.";
	$msg[] = "Please use the Back-button of your browser and try again in a few moments.";
	
	exiterror_beginpage();
	exiterror_printlines($msg);
	exiterror_endpage();
}



function exiterror_mysqlquery($errornumber, $errormessage, $origquery=NULL, $script=NULL, $line=NULL)
{
	global $User;
	
	$msg = array("A mySQL query did not run successfully!");
	if (!empty($origquery) &&  (empty($User) || $User->is_admin()) )
		$msg[] = "Original query: \n\n$origquery\n\n";
	$msg[] = "Error # $errornumber: $errormessage ";
	exiterror_msg_location($msg, $script, $line);

	exiterror_beginpage();
	exiterror_printlines($msg);
	exiterror_endpage();
}

//TODO depracated
function exiterror_parameter($errormessage, $script=NULL, $line=NULL)
{
	$msg = array("A script was passed a badly-formed parameter set!");
	$msg[] = $errormessage;
	exiterror_msg_location($msg, $script, $line);

	exiterror_beginpage();
	exiterror_printlines($msg);
	exiterror_endpage();
}
// TODO: begin, printlines($msg), end --> exiterror_finalise_page($msg);


//TODO depracated
function exiterror_arguments($argument, $errormessage, $script=NULL, $line=NULL)
{
	/* in case of XSS attack via invalid argument: */
	$argument = escape_html($argument);
	
	$msg = array("A function was passed an invalid argument type!");
	$msg[] = "Argument value was $argument. Problem:";
	$msg[] = $errormessage;
	exiterror_msg_location($msg, $script, $line);

	exiterror_beginpage();
	exiterror_printlines($msg);
	exiterror_endpage();
}




/** CQP error messages in exiterror format. */
function exiterror_cqp($error_array)
{
	$msg = array("CQP sent back these error messages:");
	$msg = array_merge($msg, $error_array);
	
	exiterror_beginpage('CQPweb -- CQP reports errors!');
	exiterror_printlines($msg);
	exiterror_endpage();
}




