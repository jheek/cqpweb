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





/* include defaults and settings */
require('../lib/environment.inc.php');


/* library files */
require('../lib/user-lib.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/library.inc.php');

/**
 * @file
 * 
 * Receiver script for a whole bunch of actions relating to users.
 * 
 * Some come via redirect from various forms; others come via admin action.
 * 
 * The actions are controlled via switcyh and mostly work by sorting through
 * the "_GET" parameters, and then calling the underlying functions
 * (mostly in user-lib).
 */


$script_called_from_admin = (isset ($_GET['userFunctionFromAdmin']) && $_GET['userFunctionFromAdmin'] == 1); 


/* a slightly tricky one, since functions here are accessible with or without login,
 * and also by admin only (in some caseS) and by anyone (in others).
 * 
 * Either admin did it, in which case we need admin login; or new user did it, 
 * in which case we do not need any login at all ........... 
 */
cqpweb_startup_environment(
	/* flags: */
		CQPWEB_STARTUP_DONT_CONNECT_CQP	| ($script_called_from_admin ? CQPWEB_STARTUP_CHECK_ADMIN_USER : CQPWEB_STARTUP_ALLOW_ANONYMOUS_ACCESS),
	/* run location: */
		($script_called_from_admin ? RUN_LOCATION_ADM : RUN_LOCATION_USR)
	);
/* BUT NOTE, some of the script below will re-impose the user-test. */


$script_mode = isset($_GET['userAction']) ? $_GET['userAction'] : false; 

switch ($script_mode)
{
	/*
	 * Cases in this switch are grouped according to the TYPE OF USER ACCESS.
	 * 
	 * First, come the ones where NO LOGIN IS REQUIRED.
	 * 
	 * So no additional check is required other than the one done at environment startup.
	 * 
	 */
	
case 'userLogin':

	/* step 1 - delete the logon cookie, and stop it being sent if it was going to be. */
	if (isset($_COOKIE[$Config->cqpweb_cookie_name]))
	{
		delete_cookie_token($_COOKIE[$Config->cqpweb_cookie_name]);
		unset($_COOKIE[$Config->cqpweb_cookie_name]);
		header_remove('Set-Cookie');
		
		/* if the cookie WAS set, the global $User object will have the wrong user in it.
		 * but we don't need to worry about that, because this script just redirects anyway:
		 * does not actually DO anything. */
	}

	/* step 2 - retrieve user info from form && check, piece by piece */
	
	/* easy one first: stay logged in on this browser? */
	$persist = (isset($_GET['persist']) && $_GET['persist']);

	/* username  & password */
	if (! isset($_POST['username'], $_POST['password']))
		exiterror_login("Sorry but the system didn't receive your username/password. Please try again.");
	
	$username_for_login = trim($_POST['username']);
	/* we perform a basic check of the username now, to enabl;e amore informative error message */
	if (0 < preg_match('/\W/', $username_for_login))
		exiterror_login("Login error: please re-check your password, you may have mistyped it.");
	
	if ( false === ($userinfo = check_user_password($username_for_login, $_POST['password'])))
	{
		/* add a delay to reduce the possibility of excessive experimentation via login form */
		sleep(3);
		exiterror_login(array(
			"That username/password combination was not recognised.",
			"You may have mistyped either the username or the password. (Both are case-sensitive.)",
			"Please go back to the log on page and try again."
			));		
	}
	else
	{
		/* check that the account is active */
		switch ($userinfo->acct_status)
		{
		case USER_STATUS_ACTIVE:
			/* break and fall through to the rest of this "else" which completes login. */
			break;
			
		case USER_STATUS_UNVERIFIED:
			exiterror_login(array(
				"You cannot log in to this account because it has not been activated yet.",
				"Please use the link in the verification message sent to your email address to activate this account."
				));
		case USER_STATUS_SUSPENDED:
			exiterror_login(array(
				"You cannot log in because your account has been suspended.",
				"This may have happened because your account was time-limited and has now expired.",
				"Alternatively, it is possible that the system administrator has suspected your account.",
				"If in doubt, you should contact the system administrator."
				));
		case USER_STATUS_PASSWORD_EXPIRED:
			exiterror_login(array(
				"Your cannot log in because your password has expired.",
				"Please use the [Reset lost password] function (from the account creation page) to change your password.",
				"You will then be able to log in."
				));
		default:
			/* should never be reached */
			exiterror("Unreachable option was reached!");
		}
		
		/* OK , user now logged in. Register a token for them, and send it as a cookie */
		emit_new_cookie_token($username_for_login, $persist);
	}
	
	if (isset($_GET['locationAfter']))
		$next_location = $_GET['locationAfter'];
	else
		$next_location = '../usr/index.php?thisQ=welcome';
	
	
	break;


case 'userLogout':

	/* to log out, all that is necessary is to delete the cookie, delete the token, and end this run of the script... */
	
	if (isset($_COOKIE[$Config->cqpweb_cookie_name]))
		delete_cookie_token($_COOKIE[$Config->cqpweb_cookie_name]);
	setcookie($Config->cqpweb_cookie_name, '', time() - 3600, '/');
	
	/* redirect to mainhome */
	$next_location = '..';
	break;





case 'newUser':

	/* CREATE NEW USER ACCOUNT */

	if (!$User->is_admin())
		if (!$Config->allow_account_self_registration)
			exiterror("Sorry but self-registration has been disabled on this CQPweb server.");

	if (!isset($_GET['newUsername'],$_GET['newPassword'],$_GET['newEmail']))
		exiterror("Missing information: you must specify a username, password and email address to create an account!");
	
	$new_username = trim($_GET['newUsername']);

	if (0 < preg_match('/\W/', $new_username))
		exiterror("The username you specified contains an illegal character: only letters, numbers and underscore are allowed.");
	
	if (0 < mysql_num_rows(do_mysql_query("select id from user_info where username = '$new_username'")))
		exiterror("The username you specified is not available: please go back and specify another!");

	/* allow anything in password except empty string */
	$password = $_GET['newPassword'];
	if (empty($password))
		exiterror("The password cannot be an empty string!");		
	if (! $script_called_from_admin)
	{
		/* check for the standard password-typed-twice thing. */
		if ( ! (isset($_GET['newPasswordCheck']) && $password == $_GET['newPasswordCheck']))
			exiterror(array("The password you typed the second time did not match the password you typed the first time",
				"Please click the Back button on your browser and try again."));   
	}
	
	$email = trim($_GET['newEmail']);
	if (empty($email))
		exiterror("The email address for a new account cannot be an empty string!");
	
	// TODO make it a config option whether or not the same email address can have more than one acct ... 
	// For now, it universally can.
	
	/* OK, all 3 things now collected, so we can call the sharp-end function... */

	/* but first check for CAPTCHA, if not called from admin */
	if ($Config->account_create_captcha && ! $script_called_from_admin)
	{
		if (!isset($_GET['captchaRef'], $_GET['captchaResponse']))
			exiterror("Missing information: no response to the human being test received!!");
		if (!check_captcha((int)$_GET['captchaRef'], $_GET['captchaResponse']))
		{
			/* instead of an error message, go back to a pre-filled create form. */
			$prefill = array(
				'thisQ'=>'create',
				'captchaFail'=>'1',
				'newUsername'=>$new_username,
				'newEmail'=>$email
				);
			if (!empty($_GET['country']))
				$prefill['country'] = $_GET['country'];
			if (!empty($_GET['realName']))
				$prefill['realName'] = $_GET['realName'];
			if (!empty($_GET['affiliation']))
				$prefill['affiliation'] = $_GET['affiliation'];
			$prefill['uT'] = 'y';
			$_GET = $prefill;
			$next_location = 'index.php?' . url_printget();
			break;
		}
	}

	add_new_user($new_username, $password, $email);

	/* which also, note, does the group regexen... */
	
	/* verification status: do we email? do we change it? */
	if ($script_called_from_admin)
	{
		/* look for extra _GET parameter.... */
		if (!isset($_GET['verifyType']))
			/* it SHOULDN'T be absent! but let's just guess. */
			$verify_type = ($Config->cqpweb_no_internet ? 'no:DontVerify' : 'yes');
		else
			$verify_type = $_GET['verifyType'];
	}
	else
		/* with a user-self-create, we always request verification via email */
		$verify_type = 'yes';

	switch($verify_type)
	{
	case 'yes':
		send_user_verification_email($new_username);
		break;
	case 'no:Verify':
		change_user_status($new_username, USER_STATUS_ACTIVE);
		break;
	case 'no:DontVerify':
	default:
		/* do nowt. */
		break;
	}
	
	/* if the script was not called from the admin interface, we may also have the non-essential fields... */
	
	if (!empty($_GET['country']))
	{
		require('../lib/user-iso31661.inc.php');
		if (! isset($Config->iso31661[$_GET['country']]))
			/* no error, cos acct created already... */
			;
		else
			update_user_setting($new_username, 'country', $_GET['country']);
	}
	/* latter 2 sanitised at DB level... */
	if (!empty($_GET['realName']))
		update_user_setting($new_username, 'realname', $_GET['realName']);
	if (!empty($_GET['affiliation']))
		update_user_setting($new_username, 'affiliation', $_GET['affiliation']);
	
		
	/* and redirect out */
	
	if ($script_called_from_admin)
		$next_location = "index.php?thisF=showMessage&message=" . urlencode("User account '$new_username' has been created.") . "&uT=y";
	else
		$next_location = "index.php?extraMsg=" . urlencode("User account '$new_username' has been created.") . "&uT=y";
	break;


case 'captchaImage':
	
	/* this option is very different to all the others. All it does is write out a captcha image. */

	/* we can't do anything unless we know which captcha has been asked for */
	if (!isset($_GET['which']))
		break;

	$which = cqpweb_handle_enforce($_GET['which']);

	send_captcha_image($which);

	break;


case 'ajaxNewCaptchaImage':

	/* like the prev option, very different; just returns (in plain text) the code for a brand-new captcha. */
	
	echo create_new_captcha();
	
	break;


case 'verifyUser':

	/* incoming check for user verification link; DOES NOT originate from admin interface. */
	
	$key = trim($_GET['v']);

	if (1 > preg_match('/^[abcdef1234567890]{32}$/',$key))
	{
		$next_location = 'index.php?thisQ=verify&verifyScreenType=badlink&uT=y';
		break;
	}
	
	if (false === ($the_username = resolve_user_verification_key($key)))
		exiterror("That activation code was not recognised. Go back and try again, or request a new verification email.");
	else
		verify_user_account($the_username);

	$next_location = 'index.php?thisQ=verify&verifyScreenType=success&uT=y';
	
	break;


case 'resendVerifyEmail':

	/* re-send a verification email, w/ a new activation code */
	
	if (empty($_GET['email']))
		exiterror("You did not type an email address! Please go back and try again.");
	
	$result = do_mysql_query("select username from user_info where email = '".mysql_real_escape_string($_GET['email'])."'
							and acct_status=" . USER_STATUS_UNVERIFIED . " limit 1");
	if (mysql_num_rows($result) < 1)
		exiterror("No unverified account associated with that email could be found on our system.");
	
	list($resend_username) = mysql_fetch_row($result);
	
	send_user_verification_email($resend_username);
	
	$next_location = 'index.php?thisQ=verify&verifyScreenType=newEmailSent&uT=y';
	
	break;


case 'resetUserPassword':

	/* 
	 * change a user's password to the new value specified. 
	 */
	
	/* there are big differences in the checks needed between calling this from admin and from a normal login.... 
	 * This if/else contains just the checks that everything is OK and in place before we call password-reset function. */
	if ($script_called_from_admin)
	{
		if ( ! $User->is_admin())
			exiterror("You do not have permission to use that function.");
		if ( ! isset($_GET['userForPasswordReset'], $_GET['newPassword']) )
			exiterror("Badly-formed password reset request. Please go back and try again.");
		if ( ! in_array($_GET['userForPasswordReset'], get_list_of_users()) )
			exiterror("Invalid username!");
		$next_location = 'index.php?thisF=userAdmin&uT=y';
	}
	else
	{
		/* username and password are needed PLUS one of the old password / a verification key;
		 * the latter is checked below*/
		if ( ! isset($_GET['userForPasswordReset'],$_GET['newPassword'], $_GET['newPasswordCheck']) )
			exiterror("Badly-formed password reset request. Please go back and try again.");
		
		if ( $_GET['newPassword'] != $_GET['newPasswordCheck'] )
			exiterror(array("The password you typed the second time did not match the password you typed the first time",
				"Please click the Back button on your browser and try again."));

		if ($User->logged_in)
		{
			/* if the user is logged in, they must supply an existing password */
			if ( ! isset($_GET['oldPassword']) )
				exiterror("No existing password found in form submission. Please go back and try again.");
			
			if ($User->username != $_GET['userForPasswordReset'])
				exiterror("Invalid username specified in form submission. Please go back and try again.");
			
			if ( false === check_user_password($User->username, $_GET['oldPassword']) )
				exiterror("The existing password you entered was not correct. Please go back and try again.");
		}
		else
		{
			/* if the user is not logged in, they must provide a suitable verification key */
			if (!isset($_GET['v']))
				exiterror("You must be logged in to CQPweb, or supply a verification code, to perform that action.");
			
			$key = str_replace(" ", "", trim($_GET['v']));
		
			if (1 > preg_match('/^[abcdef1234567890]{32}$/',$key))
				exiterror("Mis-typed verification code; please go back and try again.");
			
			if ($_GET['userForPasswordReset'] != resolve_user_verification_key($key))
				exiterror("That verification code was not valid. Please go back and try again.");

			/* all successful, so delete the verification key */
			unset_user_verification_key($_GET['userForPasswordReset']);
		}
		
		$next_location = "index.php?thisQ=welcome&extraMsg=" . urlencode("Your password has been reset.") . "&uT=y";
	}
	
	/* if we got to here, one way or another, everything is OK. */
	
	update_user_password($_GET['userForPasswordReset'], $_GET['newPassword']);

	break;


case 'remindUsername':

	if (!isset($_GET['emailToRemind']))
		exiterror("No email address specified! Please go back and try again.");

	$sqemail = mysql_real_escape_string($_GET['emailToRemind']);
	$result = do_mysql_query("select username, realname, email from user_info where email='$sqemail'");
	
	if (1 > mysql_num_rows($result))
		exiterror(array(
			"No account with the following email address was found on the system:",
			$_GET['emailToRemind'], 
			"Please go back and try again!"
			));
	
	/* there may be more than one account with the same email.... */
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$reminder = $o->username;

		list($realname, $user_address) = render_user_name_and_email($o);
		
		$body = <<<HERE
Dear $realname,

A username reminder has been requested for this email address on 
CQPweb.

The CQPweb username associated with your email is as follows:

    $reminder

Yours sincerely,

The CQPweb User Administration System

HERE;
		
		send_cqpweb_email($user_address, 'CQPweb: username reminder', $body);
	}
	
	/* but the message assumes just one email, since that's the normal case */
	$next_location = "index.php?thisQ=welcome&extraMsg=" . urlencode("A reminder email with your username has been sent.") . "&uT=y";

	break;



case 'requestPasswordReset':

	if (!isset($_GET['userForPasswordReset']))
		exiterror("No username supplied. Please go back and try again.");

	if ( ! in_array($_GET['userForPasswordReset'], get_list_of_users()) )
		exiterror("Invalid username! Please go back and try again.");

	if (false === ($reset_user = get_user_info($_GET['userForPasswordReset'])))
		exiterror("Invalid username! Please go back and try again.");

	list($realname, $user_address) = render_user_name_and_email($reset_user);

	$vcode = set_user_verification_key($reset_user->username);
	$vcode_render = trim(chunk_split($vcode, 4, ' '));
	$abs_url = url_absolutify("index.php?thisQ=lostPassword&uT=y");

		$body = <<<HERE
Dear $realname,

A password reset has been requested for your user account on CQPweb.

If you really want to reset your password, you can use the following 
32-letter code on the password-reset form:

    $vcode_render

The form can be accessed at the following URL:

    $abs_url

If you DO NOT want to reset your password, ignore this email.

Yours sincerely,

The CQPweb User Administration System

HERE;
		
		send_cqpweb_email($user_address, 'CQPweb: password reset', $body);


	$next_location = "index.php?thisQ=lostPassword&showSentMessage=1&uT=y";
	
	break;
	
	
	




	/*
	 * 
	 * now come the cases where A USER LOGIN IS REQUIRED and WE ERROR-MESSAGE IF IT WAS NOT THERE 
	 * 
	 */



case 'revisedUserSettings':

	/* change user's interface preferences */
	
	if (!$User->logged_in)
		exiterror("You must be logged in to perform that action.");

	update_multiple_user_settings($User->username, parse_get_user_settings());
	$next_location = 'index.php?thisQ=userSettings&uT=y';

	break;



case 'updateUserAccountDetails':

	if (!$User->logged_in)
		exiterror("You must be logged in to perform that action.");

	if (!isset($_GET['fieldToUpdate'], $_GET['updateValue']))
		exiterror("Invalid parameters supplied for user account detail update; please go back and try again. ");

	switch ($_GET['fieldToUpdate'])
	{
	case 'country':
		require('../lib/user-iso31661.inc.php');
		if (! isset($Config->iso31661[$_GET['updateValue']]))
			exiterror("Invalid country code supplied.");
		/* and fallthrough... */
	case 'realname':
	case 'affiliation':
		update_user_setting($User->username, $_GET['fieldToUpdate'], $_GET['updateValue']);
		$next_location = 'index.php?thisQ=userDetails&uT=y';
		break;
	default:
		exiterror("Invalid user account details field provided.");
	}
	
	
	
	break;
	
	
	
	/*
	 * 
	 * Finally, default is an unconditional abort, so it really doesn't matter whether or not one is logged in.
	 * 
	 */
	
default:

	/* dodgy parameter: ERROR out. */
	exiterror("A badly-formed user administration operation was requested!"); 
	break;
}


if (isset($next_location))
	set_next_absolute_location($next_location);

cqpweb_shutdown_environment();

exit(0);


/* ------------- *
 * END OF SCRIPT *
 * ------------- */


/** Gets all "newSetting" parameters (relating to UI settings) from $_GET and sanitises for correct type of input. */
function parse_get_user_settings()
{
	$settings = array();
	foreach($_GET as $k => $v)
	{
		if (preg_match('/^newSetting_(\w+)$/', $k, $m) > 0)
		{
			switch($m[1])
			{
			/* boolean settings */
			case 'conc_kwicview':
			case 'conc_corpus_order':
			case 'cqp_syntax':
			case 'context_with_tags':
			case 'use_tooltips':
			case 'thin_default_reproducible':
			case 'css_monochrome':
				$settings[$m[1]] = (bool)$v;
				break;
					
			/* integer settings */
			case 'coll_statistic':
			case 'coll_freqtogether':
			case 'coll_freqalone':
			case 'coll_from':
			case 'coll_to':
			case 'max_dbsize':
				$settings[$m[1]] = (int)$v;
				break;
				
			/* patterned settings */
			case 'linefeed':
				if (preg_match('/^(da|d|a|au)$/', $v) > 0)
					$settings[$m[1]] = $v;
				break;
			}
		} 
	}
	return $settings;
}



