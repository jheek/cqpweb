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

// TODO: mind changed about this. should *not* allow user corpora to be accessed within /usr. Access them within /exe instead.
///* BEFORE WE INCLUDE ANY FILES: if _GET[uC] is set, then we need to make this script behave like queryhome */
//if (isset($_GET['uC']))
//{
//	require("../lib/queryhome.inc.php");
//	exit;
//}
///* otherwise, go on and render userhome. */


/* initialise variables from settings files  */
require('../lib/environment.inc.php');

/* include function library files */
require('../lib/library.inc.php');
require('../lib/metadata.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/colloc-lib.inc.php');
require('../lib/indexforms-user.inc.php');
require('../lib/indexforms-others.inc.php');

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP | CQPWEB_STARTUP_DONT_CHECK_URLTEST, RUN_LOCATION_USR);




/* thisQ: the query whose interface page is to be displayed on the right-hand-side. */
$thisQ = ( isset($_GET["thisQ"]) ? $_GET["thisQ"] : 'welcome' );


echo print_html_header('CQPweb User Page', $Config->css_path, array('cword'));


/* ******************* *
 * PRINT SIDE BAR MENU *
 * ******************* */
?>
<!-- main table -->
<table class="concordtable" width="100%">
	<tr>
		<td valign="top">
			<!-- cell for menu -->
		
<table class="concordtable" width="100%">
	<tr>
		<th class="concordtable"><a class="menuHeaderItem">Menu</a></th>
	</tr>
</table>

<table class="concordtable" width="100%">

<?php

/* The menu is different for when we are logged on, versus when we are not */

if ($User->logged_in)
{
	echo print_menurow_heading('Your account');
	echo print_menurow_index('welcome', 'Overview');
	echo print_menurow_index('userSettings', 'Interface settings');
	echo print_menurow_index('userMacros', 'User macros');
	echo print_menurow_index('corpusAccess', 'Corpus permissions');
	echo print_menurow_heading('Your files and corpora');
	echo print_menurow_index('viewFiles', 'Manage files');
	echo print_menurow_index('newUpload', 'Upload a file');
if (!$Config->hide_experimental_features)
{
	echo print_menurow_index('viewCorpora', 'View corpora');
	echo print_menurow_index('createCorpus', 'Create corpus');
}
	echo print_menurow_heading('Account actions');
	echo print_menurow_index('userDetails', 'Account details');
	echo print_menurow_index('changePassword', 'Change password');
	echo print_menurow_index('userLogout', 'Log out of CQPweb');
	if ($User->is_admin())
	{
		?>
		<tr>
			<td class="concordgeneral">
				<a class="menuItem" href="../adm">Go to admin control panel</a>
			</td>
		</tr>
		<?php
	
	}
}
else
{
	/* if we are not logged in, then we want to show a different default ... */
	if ($thisQ == 'welcome')
		$thisQ = 'login';

	/* menu seen when no user is logged in */
	echo print_menurow_heading('Account actions');
	echo print_menurow_index('login', 'Log in to CQPweb');
	echo print_menurow_index('create', 'Create new user account');
	echo print_menurow_index('verify', 'Activate new account');
	echo print_menurow_index('resend', 'Resend account activation');
	echo print_menurow_index('lostUsername', 'Retrieve lost username');
	echo print_menurow_index('lostPassword', 'Reset lost password');
	
}

/* and now the menu that is seen unconditionally ... */
echo print_menu_aboutblock();



?>
</table>

		</td>
		<td valign="top">
		
<table class="concordtable" width="100%">
	<tr>
		<th class="concordtable">
			<a class="menuHeaderItem">
				CQPweb User Page
			</a>
		</th>
	</tr>
</table>



<?php




/* ******************************* *
 * PRINT SELECTED FUNCTION CONTENT *
 * ******************************* */

/*
 * Note: we need to have two wholly disjunct sets here, one if a user is logged in, and one if they are not. 
 */

if ($User->logged_in)
{
	$helplink = false;
	
	switch($thisQ)
	{
	case 'welcome':
		printscreen_welcome();
		display_system_messages();
		break;

	case 'userSettings':
		printscreen_usersettings();
		break;

	case 'userMacros':
		printscreen_usermacros();
		break;

	case 'corpusAccess':
		printscreen_corpusaccess();
		break;

	case 'userDetails':
		printscreen_userdetails();
		break;

	case 'changePassword':
		printscreen_changepassword();
		break;

	case 'userLogout':
		printscreen_logout();
		break;

	/* common cases... */
	
	case 'accessDenied':
		printscreen_accessdenied();
		break;
	
	case 'who_the_hell':
		printquery_who();
		break;
		
	case 'latest':
		printquery_latest();
		break;
	
	case 'bugs':
		printquery_bugs();
		break;
	
	default:
		?>
		<p class="errormessage">&nbsp;<br/>
			&nbsp; <br/>
			We are sorry, but that is not a valid menu option.
		</p>
		<?php
		break;
	}
} /* endif a user is logged in */

else
{
	switch($thisQ)
	{
	case 'login':
		printscreen_login();
		display_system_messages();
		$helplink = 'hello';
		break;
	
	case 'create':
		printscreen_create();
		$helplink = 'signup';
		break;
	
	case 'verify':
		printscreen_verify();
		$helplink = 'hello';
		break;

	case 'resend':
		printscreen_resend();
		$helplink = 'hello';
		break;
	
	case 'lostUsername':
		printscreen_lostusername();
		$helplink = 'hello';
		break;
	
	case 'lostPassword':
		printscreen_lostpassword();
		$helplink = 'hello';
		break;



	/* common cases... (repeated code from above) */

	case 'accessDenied':
		printscreen_accessdenied();
		$helplink = 'hello';
		break;
	
	case 'who_the_hell':
		printquery_who();
		$helplink = 'hello';
		break;
		
	case 'latest':
		printquery_latest();
		$helplink = 'hello';
		break;
	
	case 'bugs':
		printquery_bugs();
		$helplink = 'hello';
		break;
	
	default:
		?>
		<p class="errormessage">&nbsp;<br/>
			&nbsp; <br/>
			We are sorry, but that is not a valid menu option.
		</p>
		<?php
		$helplink = 'hello';
		break;
	}

} /* endif no user is logged in */



/* finish off the page */
?>

		</td>
	</tr>
</table>
<?php

echo print_html_footer($helplink);

cqpweb_shutdown_environment();

exit();


/* ************* *
 * END OF SCRIPT *
 * ************* */
