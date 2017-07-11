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




/*
 * ==============
 * USER FUNCTIONS
 * ==============
 */


function user_name_to_id($user)
{
	$user = mysql_real_escape_string($user);
	$result = do_mysql_query("select id from user_info where username = '$user'");
	if (mysql_num_rows($result) < 1)
		exiterror_general("Invalid user name specified at database level: $user");
	list($id) = mysql_fetch_row($result);
	return $id;
}

function user_id_to_name($id)
{
	$id = (int)$id;
	$result = do_mysql_query("select username from user_info where id = $id");
	if (mysql_num_rows($result) < 1)
		exiterror_general("Invalid user ID specified at database level: $id");
	list($name) = mysql_fetch_row($result);
	return $name;
}

/**
 * returns flat array of usernames, ordered alphabetically
 */
function get_list_of_users()
{
	$result = do_mysql_query("select username from user_info order by username asc");
	$u = array();
	while (false !== ($r = mysql_fetch_row($result)))
		$u[] = $r[0];
	return $u;
}

/**
 * Adds a new user with the specified username, password and email.
 * 
 */
function add_new_user($username, $password, $email, $initial_status = USER_STATUS_UNVERIFIED, $expiry_time = 0)
{
	global $Config;
	
	/* checks, e.g. on usernames containing non-word characters, must be performed at a higher level. 
	 * Here, we only check for database safety. */
	$username = mysql_real_escape_string($username);
	$email = mysql_real_escape_string($email);
	
	/* no need to check password, since we create a passhash from the password & don't store it */
	$passhash = generate_new_hash_from_password($password);


	$sql_query = "INSERT INTO user_info (
		username,
		realname,
		email,
		passhash,
		acct_status,
		expiry_time,
		acct_create_time,
		conc_kwicview,
		conc_corpus_order,
		cqp_syntax,
		context_with_tags,
		use_tooltips,
		thin_default_reproducible,
		coll_statistic,
		coll_freqtogether,
		coll_freqalone,
		coll_from,
		coll_to,
		max_dbsize,
		linefeed
		)
		VALUES
		(
		'$username',
		'unknown person',
		'$email',
		'$passhash',
		$initial_status,
		0,
		CURRENT_TIMESTAMP,
		1,
		1,
		0,
		0,
		1,
		1,
		{$Config->default_colloc_calc_stat},
		{$Config->default_colloc_minfreq},
		{$Config->default_colloc_minfreq},
		" . (-1 * ($Config->default_colloc_range-2)) . ",
		" . ($Config->default_colloc_range-2) . ",
		{$Config->default_max_dbsize},
		'au'
		)"
		;
		
	do_mysql_query($sql_query);
	
	/* check for automatic group ownership */
	
	foreach (list_group_regexen() as $group => $regex)
		if (0 < preg_match("/$regex/", $email))
			add_user_to_group($username, $group);
}


/**
 * Automatically creates a batch of numbered users. Function available to admin users only.
 * 
 * @param string $username_root  Root of username to which numbers are added.
 * @param int $number_in_batch   N of accounts to create (usernames will be 1 through N)
 * @param string $password       Password for batch of accounts.
 * @param string $autogroup      If specified, users will automatically be added to the group with this name.
 */
function add_batch_of_users($username_root, $number_in_batch, $password, $autogroup = NULL)
{
	global $User;
	
	/* fail silently if the user is not admin. */
	if (!$User->is_admin())
		return;

	$autogroup = preg_replace('/\W/', '', $autogroup);
	if (! in_array($autogroup, get_list_of_groups()))
		$autogroup = NULL;
	$username_root = preg_replace('/\W/', '', $username_root);
	$number_in_batch = (int)$number_in_batch;
	
	for ($i = 1 ; $i <= $number_in_batch; $i++)
	{
		$u = "$username_root$i";
		add_new_user($u, $password, '');
		change_user_status($u, USER_STATUS_ACTIVE);
	
		if (!empty($autogroup))
			add_user_to_group($u, $autogroup);
	}
}





/**
 * Add a whole number of users.
 * 
 * The argument is a matrix (array of arrays).
 * 
 * Each inner array has:
 *    0 => username
 *    1 => password
 *    2 => email
 * 
 */
function add_multiple_users($data_matrix)
{
	// TODO : not sure how much use this function will actually be.
	foreach($data_matrix as $arr)
		add_new_user($arr[0], $arr[1], $arr[2]);
}



/**
 * Deletes a specified user account (and all its saved and categorised queries)
 * 
 * If the username passed in is an empty string,
 * it will return without doing anything; all non-word
 * characters are removed for database safety.
 */
function delete_user($user)
{
	global $Config;
	
	/* db sanitise */
	$user = preg_replace('/\W/', '', $user);
	if (empty($user))
		return;
	
	/* refuse to delete admin users. */
	if (user_is_superuser($user))
		return;
	
	$id = user_name_to_id($user);
	
	/* unjoin all groups */
	do_mysql_query("delete from user_memberships where user_id = $id");

	/* delete user privilege grants */
	do_mysql_query("delete from user_grants_to_users where user_id = $id");
	
	/* delete uploaded files (and directory) */
	$d = $Config->dir->upload . '/' . $user;
	if (is_dir($d))
		recursive_delete_directory($d);
	
	/* delete user saved queries and categorised queries */
	$result = do_mysql_query("select query_name, saved from saved_queries where saved > ".CACHE_STATUS_UNSAVED." and user = '$user'");
	while (false !== ($q = mysql_fetch_object($result)))
	{
		if ($q->saved == CACHE_STATUS_CATEGORISED)
		{
			/* catquery */
			$inner_result = do_mysql_query("select dbname from saved_catqueries where catquery_name='{$q->query_name}'");
			list($dbname) = mysql_fetch_row($inner_result);			
			do_mysql_query("drop table if exists $dbname");
			do_mysql_query("delete from saved_catqueries where catquery_name='{$q->query_name}'");
		}
		/* for both catquery and saved query */
		delete_cached_query($q->query_name);
	}

	/* delete user itself */
	do_mysql_query("delete from user_info where id = $id");
}

/**
 * Touch the specified user's last_seen_time....
 */
function touch_user($username)
{
	$username = mysql_real_escape_string($username);
	do_mysql_query("update user_info set last_seen_time = CURRENT_TIMESTAMP where username='$username'");
}


function update_user_password($user, $new_password)
{
	$user = mysql_real_escape_string($user);

	if (empty($new_password))
		exiterror_general("Cannot set password to empty string!");
	$new_passhash = generate_new_hash_from_password($new_password);
	
	do_mysql_query("update user_info set passhash = '$new_passhash' where username = '$user'");
}



/**
 * Uses the details within a user database-object to render a name
 * and email suitable for use within an email body and header respectively.
 * 
 * Usage: list($realname, $address) = render_user_name_and_email($object);
 * 
 * @param stdClass $user_object  A DB object (members used: realname, email).
 * @return array                 An array with first member printable name,
 *                               second member email (either raw address or "Name <address>").
 */
function render_user_name_and_email($user_object)
{
	if (empty($user_object->realname) || $user_object->realname == 'unknown person')
	{
		$realname = 'User';
		$user_address = $user_object->email;
	}
	else
	{
		$realname = $user_object->realname;
		$user_address = "$realname <{$user_object->email}>";
	}
	
	return array($realname, $user_address);
}


/**
 * Sends out an account verification email,
 * with a freshly-generated verification key.
 * 
 * The verification key goes into the db.
 */
function send_user_verification_email($user)
{
	/* create key and set in database */
	$verify_key = set_user_verification_key($user);
	
	list($realname, $user_address) 
		= render_user_name_and_email(mysql_fetch_object(do_mysql_query("select email, realname from user_info where username='$user'")));

	$verify_url = url_absolutify('../usr/redirect.php?redirect=verifyUser&v=' . urlencode($verify_key) . '&uT=y');
	
	$body = <<<HERE
Dear $realname,

A new user account has been created on our CQPweb server in
association with your email address.

To validate this new account, and confirm as yours the address to
which this email was sent, please visit the following link:

$verify_url

If your email client disables external links, copy and paste 
the address above into a web browser.

If CQPweb cannot read your verification code successfully
from the link, it will ask you for a verification key. 
In that case, copy-and-paste the following 32-letter code:

$verify_key 

If you DID NOT create this account, or request it to be created
on your behalf, then all you need to do is ignore this email; 
the account will then never be activated.

Yours sincerely,

The CQPweb User Administration System


HERE;
	
	send_cqpweb_email($user_address, 'CQPweb: please verify user account creation!', $body);
}

/**
 * Sets a key to verify either an account or a password reset.
 * 
 * Creates and returns a 32-byte key, which is also stored in the DB for the 
 * specified user.
 * 
 * Note this function does not check for the reality of the user -
 * if a nonexistent user is specified, then the result will be no change
 * to the DB, and the key returned will be useless.
 */
function set_user_verification_key($user)
{
	$user = mysql_real_escape_string($user);
	
	$key = md5(uniqid($user,true));
	/* use an additional round of md5 -- less security than for password, but for a one-use token that's OK */
	$stored_key = md5($key);

	do_mysql_query("update user_info set verify_key = '$stored_key' where username = '$user'");
	
	return $key;
}

/**
 * Removes a user verification key for the given user, setting the entry in the DB to NULL.
 */
function unset_user_verification_key($user)
{
	$user = mysql_real_escape_string($user);
	
	do_mysql_query("update user_info set verify_key = NULL where username = '$user'");
}

/**
 * Gets the username associated with a given verification key, if one exists. 
 * 
 * If the key does not exist, returns false.
 */
function resolve_user_verification_key($key)
{
	$key = md5($key);
	$result = do_mysql_query("select username from user_info where verify_key = '$key'");
	if (1 > mysql_num_rows($result))
		return false;
	else
	{
		list($u) = mysql_fetch_row($result);
		return $u;
	}
}

/**
 * Resets the user account status: 2nd arg must be one of the USER_STATUS_* constants.
 */
function change_user_status($user, $new_status)
{
	$new_status = (int) $new_status;
	$user = mysql_real_escape_string($user);
	
	/* do nothing if mew status not a valid status constant */
	switch ($new_status)
	{
	case USER_STATUS_UNVERIFIED:
	case USER_STATUS_ACTIVE:
	case USER_STATUS_SUSPENDED:
		do_mysql_query("update user_info set acct_status = $new_status where username = '$user'");
		break;
	default:
		/* do nothing */
		break;
	}
}


/** Wrapper for the two steps involved in verifying a user account */
function verify_user_account($username)
{
	/* first, change user status; second, remove the verifcation key set for them. */
	change_user_status($username, USER_STATUS_ACTIVE);
	unset_user_verification_key($username);
}



/**
 * Retrieves a given setting for a particular user.
 * 
 * Note that it's not necessary for the user to be the same person
 * as the user logged-on in the environment (global $User).
 */
function get_user_setting($username, $field)
{
	$o = get_user_info($username);
	if (empty($o))
		return false;
	else
		return $o->$field;
}

/** 
 * Returns an object (stdClass with members corresponding to the
 * fields of the user_info table in the database) containing
 * the specified user's data.
 * 
 * Returns false in case of a nonexistent user.
 * 
 * Note that it's not necessary for the user to be the same person
 * as the user logged-on in the environment (global $User).
 */
function get_user_info($username)
{	
	static $cache = array();
	
	if (isset($cache[$username]))
		return $cache[$username];
	
	$username = mysql_real_escape_string($username);
	
	$result = do_mysql_query("SELECT * from user_info WHERE username = '$username'");
	
	if (mysql_num_rows($result) == 0)
		return false;
	else
	{
		$cache[$username] = mysql_fetch_object($result);
		return $cache[$username];
	}
}

/**
 * Gets full info for all users.
 * 
 * Returns array of objects of the kind returned by get_user_info.
 * 
 * The keys are the user ids.
 */
function get_all_users_info()
{
	$users = array();
	
	$result = do_mysql_query("select * from user_info");
	
	while (false !== ($o = mysql_fetch_object($result)))
		$users[$o->id] = $o;
		
	return $users;
}



/** 
 * Update a user setting relating to the user interface tweaks.
 * TODO change name?
 */
function update_user_setting($username, $field, $setting)
{
	$field = mysql_real_escape_string($field);
	$setting = mysql_real_escape_string($setting);
	$username = mysql_real_escape_string($username);
	
	/* only certain fields are allowed to be changed via this function. */
	$fields_allowed = array(
		'conc_kwicview',
		'conc_corpus_order',
		'cqp_syntax',
		'context_with_tags',
		'use_tooltips',
		'coll_statistic',
		'coll_freqtogether',
		'coll_freqalone',
		'coll_from',
		'coll_to',
		'max_dbsize',
		'linefeed',
		'thin_default_reproducible',
		'realname',
		'affiliation',
		'country'
	);
//TODO above not actually used???
	
	/* nb. This treats all values as string, although many are ints, but it seems to work... */
	do_mysql_query("UPDATE user_info SET $field = '$setting' WHERE username = '$username'");
}

/** 
 * Update many user-interface settings all at once.
 * TODO change name?
 * 
 * @param string $username  Username of account to update.
 * @param array $settings   Associative field=>value array of settings to update. 
 */
function update_multiple_user_settings($username, $settings)
{	
	foreach ($settings as $field => $value)
		update_user_setting($username, $field, $value);
}






function get_user_linefeed($username)
{
	$current = get_user_setting($username, 'linefeed');
	
	if ($current == NULL || $current == '' || $current = 'au')
		$current = guess_user_linefeed($username);

	switch ($current)
	{
	case 'd':	return "\r";
	case 'a':	return "\n";
	case 'da':	return "\r\n";
	default:	return "\r\n";		/* shouldn't be possible to get here */
	}
}



function guess_user_linefeed($user_to_guess)
{
	global $User;
	
	if ($user_to_guess != $User->username)
		return 'da';
	/* da is the default guess when no guess can be made, because Windows dominates the OS market.
	 * and *nix people are more likely to be computer literate enough to fix it ;-P */
	
	/* a and d are symbols, of course, for \n and \r respectively. */
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'Windows') !== false)
		return 'da';
	else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Macintosh')   !== false || 
			 strpos($_SERVER['HTTP_USER_AGENT'], 'Mac_PowerPC') !== false)
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'OS X') !== false)
			return 'a';		/* cos OS X is like *nix */
		else
			return 'd';		/* cos old Macs aren't */
	}
	else /* unix or linux prolly */
		return 'a';
}



/*
 * =======================
 * LOGON-RELATED FUNCTIONS
 * =======================
 */


/**
 * Returns a string with the name of the function
 * to use for getting random integers for account security.
 * 
 * Note that for non-security purposes, we can just use mt_rand.
 */
function get_security_rand_function()
{
	/* Note: If we have PHP 7, we get random_int() */
	if (0 >= version_compare('7.0.0', PHP_VERSION))
		return 'random_int';
	/* otherwise, use mcrypt, if we have it */
	else if (function_exists('mcrypt_create_iv'))
		return 'wrapped_mcrypt_rand';
	else 
		return 'mt_rand';
	/* mt_rand is our fallback albeit it's a poor substitute... */
}
/*
 * Get a random 32-bit signed integer (in a specified range).
 * Note, there are no checks for dodgy parameters, so make sure you use it correctly!
 */
function wrapped_mcrypt_rand($min, $max)
{
	list($val) = unpack("l_", mcrypt_create_iv(4, MCRYPT_DEV_URANDOM));
	
	/* $val is in the range -0x7fffffff to 0x7fffffff, so abs it*/
	$fraction = ((float) abs($val)) / (float)0x7fffffff;
	/* any fraction between 0/0x7fffffff is equally likely */
	
	/* return min plus the randomised fraction of the range */
	return $min + (int)round( $fraction * ($max - $min), 0);
}


/**
 * For creating new passwords. Returns the hash to store in the database.
 */
function generate_new_hash_from_password($password)
{
	/* we are using BLOWFISH with 2^11 iterations, so start of salt always same: */
	$salt = '$2a$11$';
	/* nb. Blowfish cost parameter increased from 10 to 11 in v3.2 according to updated best advice */
	
	/* get 22 (pseudo-)random bytes */
	$salt_language = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	$randfunc = get_security_rand_function();
	for ($i = 0; $i < 22 ; $i++)
		$salt .= $salt_language[$randfunc(0,63)];

	return crypt($password, $salt);
}

/**
 * Check a username / password combo against the passhash held in the database.
 * 
 * Returns a database record for the user (as an object),  
 * if the user account exists and the password matches its hash.
 *  
 * Otherwise returns false.
 */
function check_user_password($username, $password)
{
	$username = mysql_real_escape_string($username);
	
	$result = do_mysql_query("select * from user_info where username = '$username'");
	
	if (1 != mysql_num_rows($result))
		return false;
	
	$obj = mysql_fetch_object($result);
	
	$newhash = crypt($password, $obj->passhash);
	
	/* in PHP 5.6.0+ use the timing-attack secure comparison function */
	if (0 >= version_compare('5.6.0', PHP_VERSION))
	{
		if (hash_equals($obj->passhash, $newhash))
			return $obj;
	}
	else
	{
		if ($obj->passhash == $newhash)
			return $obj;
	}
	return false;
}



/**
 * Checks whether a given cookie token is for a valid login.
 * 
 * Returns an object if it is, and returns false if it isn't.
 * 
 * The object contains 2 fields: username, creation.
 */
function check_user_cookie_token($token)
{
	list ($username, $content) = split_up_cookie_token($token);
	if (empty($content))
		return false;
	$u_id = user_name_to_id($username);
	$hash = hash('sha256', $token);

	$result = do_mysql_query("select creation from user_cookie_tokens where token = CAST(x'$hash' as UNSIGNED) and user_id = $u_id");

	if (mysql_num_rows($result) < 1)
		return false;
	else
	{
		list($creation) = mysql_fetch_row($result);
		return (object) array ('username'=>user_id_to_name($u_id), 'creation'=>(int) $creation);
	}
}

/**
 * Creates a new pseudorandom string of letters and numbers (32 chars in length),
 * which is appended to username + separator to form a token.
 */
function generate_new_cookie_token($username)
{
	$token_language = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_~|';
	$randfunc = get_security_rand_function();
	
	$token = $username . ':';
	for ($i = 0; $i < 100 ; $i++)
		$token .= $token_language[$randfunc(0,64)];
	/* 
	 * Note a possible race condition: once a token is generated, 
	 * the same token could be generated by another process from 
	 * the same user *before* the first goes into the DB.
	 * But this is a tiny chance, and even if it happens, nothing bad results.
	 */
	
	return $token;
}

/**
 * Cookie tokens are generated as "$username:$random_text".
 *
 * This function breaks up such a token
 * and returns the two pieces as an array (0=>username, 1=>random text).
 *
 * If the random text is not valid (32 characters from the cookie-token alphabet),
 * or if the divisor ":" is not present, then it returns ['',''].
 */
function split_up_cookie_token($token)
{
	/* note: this MUST be the same as in the generate-function. */
	$token_language = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_~|';

	if (! preg_match("/^(\w+):([$token_language]{100})$/", $token, $m))
		return array("", "");
	else
		return array($m[1], $m[2]);
}


/**
 * Creates a DB entry for the specified cookie token,
 * signifiying a login of the username given.
 */
function register_new_cookie_token($token)
{
	global $Config;

	/* we store the SHA-256 hash of the username + ':' + token combination. */
	list ($username, $content) = split_up_cookie_token($token);
	if (!empty($content))
	{
		$u_id = user_name_to_id($username);
		$hash = hash('sha256', $token);
		$ctime = time();
		$expiry = $ctime + $Config->cqpweb_cookie_max_persist;
		do_mysql_query("insert into user_cookie_tokens (token, user_id, expiry, creation) values (CAST(x'$hash' as UNSIGNED), $u_id, $expiry, $ctime)");

		/* every time a new token is registered, run cleanup */
		cleanup_expired_cookie_tokens();
	}
	/* we do not register if, for whatever reason, we were passed a bad token. */
}


/**
 * Resets the expiry time of a cookie token to the maximum
 * logon persist time in the future.
 */
function touch_cookie_token($token)
{
	global $Config;
	$expiry = time() + $Config->cqpweb_cookie_max_persist;

	list ($username) = split_up_cookie_token($token);
	$u_id = user_name_to_id($username);
	$hash = hash('sha256', $token);

	do_mysql_query("update user_cookie_tokens set expiry = $expiry where token = CAST(x'$hash' as UNSIGNED) and user_id = $u_id");
}


/**
 * Deletes all cookie tokens whose expiry time is in the past.
 */
function cleanup_expired_cookie_tokens()
{
	do_mysql_query("delete from user_cookie_tokens where expiry < " . time() );
}

/**
 * Deletes a specified cookie token.
 */
function delete_cookie_token($token)
{
	list($username, $content) = split_up_cookie_token($token);
	/* note that if ["",""] was returned, delete will fail. Ergo, the following. */
	if (!empty($content))
	{
		$u_id = user_name_to_id($username);
		$hash = hash('sha256', $token);
		do_mysql_query("delete from user_cookie_tokens where token = CAST(x'$hash' as UNSIGNED) and user_id = $u_id");
	}
}


/**
 * Does all necessary steps to generate and emit a cookie token for the currently-logged in user.
 *
 * @param $username
 * @param $persist   Boolean: persistent cookie = true; this browser session only = false.
 */
function emit_new_cookie_token($username, $persist)
{
	global $Config;
	
	$token = generate_new_cookie_token($username);
	register_new_cookie_token($token);
	
	/* how long before timeout? either 1 year (i.e. forever given that tokens expire before that at the server end), or till browser closed */
	$browser_timeout = ($persist ? (time()+(365*24*60*60)) : 0);

	setcookie($Config->cqpweb_cookie_name, $token, $browser_timeout, '/');
	setcookie($Config->cqpweb_cookie_name . 'Persist', ($persist ? '1' : '0'), $browser_timeout, '/');
}





/*
 * ====================
 * USER GROUP FUNCTIONS
 * ====================
 */




function group_name_to_id($group)
{
	$group = mysql_real_escape_string($group);
	$result = do_mysql_query("select id from user_groups where group_name = '$group'");
	if (mysql_num_rows($result) < 1)
		exiterror_general("Invalid group name specified at database level: $group");
	list($id) = mysql_fetch_row($result);
	return $id;
}

function group_id_to_name($id)
{
	$id = (int)$id;
	$result = do_mysql_query("select group_name from user_groups where id = $id");
	if (mysql_num_rows($result) < 1)
		exiterror_general("Invalid group ID specified at database level: $id");
	list($name) = mysql_fetch_row($result);
	return $name;
}


/**
 * Create a new group with the speciifed name (description and autojoin-regex can also
 * be set at creation time).
 */
function add_new_group($group, $description = '', $regex = '')
{
	$group = cqpweb_handle_enforce($group);
	if (empty($group))
		exiterror_general("You cannot create a group with no name!");
	if (0 < mysql_num_rows(do_mysql_query("select id from user_groups where group_name = '$group'")))
		exiterror_general("You tried to create a group which already exists!"); 

	$description = mysql_real_escape_string($description);
	$regex = mysql_real_escape_string($regex);
	
	do_mysql_query("insert into user_groups (group_name,description,autojoin_regex) values
		('$group','$description','$regex')");
}




function delete_group($group)
{
	assert_not_reserved_group($group);
	
	$g = group_name_to_id($group);
	
	/* delete all memberships */
	do_mysql_query("delete from user_memberships where group_id = $g");
	
	/* delete group */
	do_mysql_query("delete from user_groups where id = $g");
}


/**
 * Assertion: causes an error abort if this group is one of the "reserved" 
 * group names (i.e. magic, can't be deleted from the database etc.)
 */
function assert_not_reserved_group($group)
{
	if ($group == 'superusers' || $group == 'everybody')
		exiterror("An illegal operation was attempted on one of the system-reserved groups, namely $group.");
}

/**
 * returns flat array of group names (ordered alphabetically, but with superusers and everybody first)
 */
function get_list_of_groups()
{
	$result = do_mysql_query("select group_name from user_groups order by group_name asc");
	$g = array('superusers','everybody');
	while (false !== ($r = mysql_fetch_row($result)))
		if ( ! ($r[0] == 'superusers' || $r[0] == 'everybody') )
			$g[] = $r[0];	
	return $g;
}




/**
 * Returns flat array of usernames.
 */
function list_users_in_group($group)
{
	/* specials: user membership not recorded in user_memberships table */
	if ($group == 'superusers')
		return list_superusers();
	else if ($group == 'everybody')
		$sql = "select username from user_info";
	else
	{
		$g = group_name_to_id($group);
		$sql = "select user_info.username from user_memberships 
				inner join user_info on user_info.id = user_memberships.user_id where group_id = $g";
	}
	
	$result = do_mysql_query($sql);

	$users = array();
	
	while (false !== ($r = mysql_fetch_row($result)))
		$users[] = $r[0];
	
	return $users;
}

function get_group_info($group)
{
	$group = mysql_real_escape_string($group);
	$result = do_mysql_query("select * from user_groups where group_name = $group");
	if (1 > mysql_num_rows($result))
		exiterror_general("Info requested for non-existent group $group!");
	return mysql_fetch_object($result);
}

/**
 * Returns array of group DB objects, ordered alphabetically, but with superusers and everybody first.
 */
function get_all_groups_info()
{
	$result = do_mysql_query("select * from user_groups order by group_name asc");
	$all = array();
	while (false !== ($o = mysql_fetch_object($result)))
	{
		if ( $o->group_name == 'everybody' )
			$everybody = $o;
		else if ( $o->group_name == 'superusers')
			$superusers = $o;
		else
			$all[] = $o;
	}
	array_unshift($all, $superusers, $everybody);
	return $all;
}

/**
 * Set new values for group description and/or regex 
 */
function update_group_info($group, $new_description, $new_regex)
{
	assert_not_reserved_group($group);
	$group = mysql_real_escape_string($group);
	$new_description = mysql_real_escape_string($new_description);  
	$new_regex = mysql_real_escape_string($new_regex);
	do_mysql_query("update user_groups set description = '$new_description', autojoin_regex = '$new_regex' where group_name = '$group'");
}


function add_user_to_group($user, $group, $expiry_time = 0)
{
	/* do not add user to group if user is already a member */
	if (user_is_group_member($user, $group))
		return;
	assert_not_reserved_group($group);
	$g = group_name_to_id($group);
	$u = user_name_to_id($user);
	$expiry_time = (int) $expiry_time;
	do_mysql_query("insert into user_memberships (user_id,group_id,expiry_time)values($u,$g,$expiry_time)");
}


function remove_user_from_group($user, $group)
{
	assert_not_reserved_group($group);
	$g = group_name_to_id($group);
	$u = user_name_to_id($user);
	do_mysql_query("delete from user_memberships where group_id = $g and user_id = $u");
}

function user_is_group_member($user, $group)
{
	switch ($group)
	{
	case 'everybody':
		return true;
	case 'superusers':
		return user_is_superuser($user);
	default:
		$g = group_name_to_id($group);
		$u = user_name_to_id($user);
		return (0 < mysql_num_rows(do_mysql_query("select * from user_memberships where user_id = $u and group_id = $g")));	
	}
}

/**
 * Returns associative array of groupname => group_autojoin_regex
 *  
 */
function list_group_regexen()
{
	$result = do_mysql_query("select group_name, autojoin_regex from user_groups");
	$list = array();
	while (false !== ($o = mysql_fetch_object($result)))
	{
		if (empty($o->autojoin_regex))
			continue;
		else
			$list[$o->group_name] = $o->autojoin_regex;
	}
	return $list;
}

/**
 * Run group regex against all existing users; if their email address mathces the regex, 
 * they are added to the group.
 * 
 * (Note this is the only way for regexes to take effect retroactively; otherwise regexen
 * only run at account create-time.)
 */
function reapply_group_regex($group)
{
	$group = mysql_real_escape_string($group);
	
	$result = do_mysql_query("select autojoin_regex from user_groups where group_name = '$group'");
	 
	if (1 > mysql_num_rows($result))
		return;
	
	list($rx) = mysql_fetch_row($result);
	
	foreach(get_all_users_info() as $u)
		if (0 < preg_match("/$rx/", $u->email))
			add_user_to_group($u->username, $group);
}


/**
 * Run an arbitrary regex acropss all users' emails, and add them to the group specified
 * if their email address matches.
 */
function apply_custom_group_regex($group, $regex)
{
	foreach(get_all_users_info() as $u)
		if (0 < preg_match("/$regex/", $u->email))
			add_user_to_group($u->username, $group);
}


/**

	THE CODING OF PRIVILEGES
	========================
	
	Privileges are coded as follows.
	
	The *owner* or *subject* of the privilege is not stored in the privilege table.
	Instead, that info is stored in the "_grants" tables.
	
	The privilege table consists of "verbs" and "objects".
	
	The "verb" consists of an integer constant explaining what kind of access privilege
	this is. The "object" is expressed as an array of entities to which the privilege
	applies (or, in some cases, as a simplex variable). 
	
	All "object" arrays are encodable as strings, which are what is stored in the
	database in the `scope` field. The "verb" is stored in the `type` field using the 
	correct constant.

	The following explains the nature, and subcategorisation frame template,
	of each type of privilege.
	
	
	Privileges of type PRIVILEGE_TYPE_CORPUS_FULL
	---------------------------------------------
	
	This privilege represents the level of access a user can have when it is assumed that
	they have full rights to access the underlying text of a particular corpus.
	
	- Concordances WILL NOT be auto-thinned.
	- User can access the "Context" feature with the maximum possible scope.
	- User can access the "Browse Text" feature (anyway they will be able to, once it is implemented).
	
	Privileges of type PRIVILEGE_TYPE_CORPUS_NORMAL
	-----------------------------------------------
	
	This privilege represents a normal level of access a user can have when it is assumed that
	they have normal privileges to use a particular corpus.
	
	- Concordances WILL NOT be auto-thinned.
	- User can access the "Context" feature with a configurable scope.
	- User cannot access the "Browse Text" feature.
	
	This is equivalent to the level of access that any user had in CQPweb v less than 3.1.
	
	Privileges of type PRIVILEGE_TYPE_CORPUS_RESTRICTED
	---------------------------------------------------
	
	This privilege represents the level of access a user can have when it is assumed that
	they can only be allowed restricted access to a particular corpus.
	
	- Concordances WILL be auto-thinned to a configurable maximum number of hits (random, reproducible)
	- User can access the "Context" feature with a configurable scope (less than that for normal privilege).
	- User cannot access the "Browse Text" feature.
	
	Syntax for PRIVILEGE_TYPE_CORPUS_*
	----------------------------------
	
	The "object" of these privileges is a set of corpora which must contain at least one corpus.
	
	The data-object representation of this is an array of strings, where each string is an array of
	corpus handles.
	
	The string representation of this in the DB is the strings in question concatenated together and
	separated by ~ .
	
	Privileges of type PRIVILEGE_TYPE_FREQLIST_CREATE
	-------------------------------------------------
	
	This privilege enables a user to build frequency lists up to a certain size (while working
	within any corpus).
	
	This applies currently only to subcorpus frequency list creation; in future, it might apply
	to collocation frquency list creation as well (the latter currently keys off a pair
	of global variables set via the configuration file).
	
	The "object" of this kind of privilege is an integer. Its string representation is that integer,
	as a string. (Simples!)
	
*/

/**
 * Encodes a complex value that is the "object" of a privilege "verb" into
 * a string for the database.
 */
function encode_privilege_scope_to_string($type, $object)
{
	switch($type)
	{
	case PRIVILEGE_TYPE_CORPUS_FULL:
	case PRIVILEGE_TYPE_CORPUS_NORMAL:
	case PRIVILEGE_TYPE_CORPUS_RESTRICTED:
		/* "object" is an array of corpus names... */
		foreach($object as &$c)
			$c = cqpweb_handle_enforce($c);
		return implode('~', $object);
		
	case PRIVILEGE_TYPE_FREQLIST_CREATE:
		/* "object" is an integer */
		return (string)$object;
		
	/* Add more privileges here as the system develops. */
	
	default:
		exiterror_general("Critical error: invalid privilege type constant encountered!", __FILE__, __LINE__);
	}
}


/**
 * Encodes a complex value that is the "object" of a privilege "verb" into
 * a string for the database.
 */
function decode_privilege_scope_from_string($type, $string)
{
	switch($type)
	{
	case PRIVILEGE_TYPE_CORPUS_FULL:
	case PRIVILEGE_TYPE_CORPUS_NORMAL:
	case PRIVILEGE_TYPE_CORPUS_RESTRICTED:
		/* "object" is an array of corpus names... */
		return explode ('~', $string);

	case PRIVILEGE_TYPE_FREQLIST_CREATE:
		/* "object" is a single integer */
		return (int)$string;
		
	/* Add more privileges here as the system develops. */

	default:
		exiterror_general("Critical error: invalid privilege type constant encountered!", __FILE__, __LINE__);
	}
}

/**
 * Produces an HTML representation of a complex value that is the "object" of a privilege "verb".
 */
function print_privilege_scope_as_html($type, $object)
{
	switch($type)
	{
	case PRIVILEGE_TYPE_CORPUS_FULL:
	case PRIVILEGE_TYPE_CORPUS_NORMAL:
	case PRIVILEGE_TYPE_CORPUS_RESTRICTED:
		/* "object" is an array of corpus names... */
		foreach($object as &$c)
			$c = cqpweb_handle_enforce($c);
		$s = (count($object) > 1 ? '<b>Corpora:</b> ' : '<b>Corpus:</b> ');
		$s .= implode(', ', $object);
		return $s;

	case PRIVILEGE_TYPE_FREQLIST_CREATE:
		/* "object" is a single integer */
		return number_format($object) . ' tokens';
			
	/* Add more privileges here as the system develops. */
	
	default:
		exiterror_general("Critical error: invalid privilege type constant encountered!", __FILE__, __LINE__);
	}
}




/**
 * Creates a new privilege.
 */
function add_new_privilege($type, $scope, $description = '')
{
	$scope_string = encode_privilege_scope_to_string($type, $scope);
	$type = (int)$type;
	$description = mysql_real_escape_string($description);
	
	do_mysql_query("insert into user_privilege_info (type,scope,description) values ($type, '$scope_string', '$description')") ;
}

/**
 * Generate the 3 default privileges for a specified corpus.
 * 
 * Returns boolean (false if corpus did not exist, otherwise true).
 */
function create_corpus_default_privileges($corpus)
{
	/* generates the descriptions for the new privileges .... */
	static $mapper = array(
		PRIVILEGE_TYPE_CORPUS_FULL       => "Full access privilege",
		PRIVILEGE_TYPE_CORPUS_NORMAL     => "Normal access privilege",
		PRIVILEGE_TYPE_CORPUS_RESTRICTED => "Restricted access privilege",
		);
	
	if (!in_array($corpus, list_corpora()))
		return false;
		
	foreach(array_keys($mapper) as $type)
	{
		/* does a privilege exist which has this type and scope over just this corpus? */ 
		if (false === check_privilege_by_content($type, array($corpus)))
			add_new_privilege($type, array($corpus), $mapper[$type] . " for corpus [$corpus]");
	}
	return true;
}

function create_all_default_privileges()
{
	/* create default privileges for freqlists of 1 million, 10 million, 25 million, and 100 million. */
	$text = 'Frequency lists for subcorpora up to';
	if (false === check_privilege_by_content(PRIVILEGE_TYPE_FREQLIST_CREATE, 1000000))
		add_new_privilege(PRIVILEGE_TYPE_FREQLIST_CREATE, 1000000,   "$text one million tokens.");
	if (false === check_privilege_by_content(PRIVILEGE_TYPE_FREQLIST_CREATE, 10000000))
		add_new_privilege(PRIVILEGE_TYPE_FREQLIST_CREATE, 10000000,  "$text ten million tokens.");
	if (false === check_privilege_by_content(PRIVILEGE_TYPE_FREQLIST_CREATE, 25000000))
		add_new_privilege(PRIVILEGE_TYPE_FREQLIST_CREATE, 25000000,  "$text 25 million tokens.");
	if (false === check_privilege_by_content(PRIVILEGE_TYPE_FREQLIST_CREATE, 100000000))
		add_new_privilege(PRIVILEGE_TYPE_FREQLIST_CREATE, 100000000, "$text 100 million tokens.");

	/* create default privileges for each extant corpus */
	foreach(list_corpora() as $c)
		create_corpus_default_privileges($c);
}

/**
 * Delete the privilege with the specified ID number.
 */
function delete_privilege($id)
{
	$id = (int) $id;
	
	/* delete all grants of this privilege; then delete the privilege itself. */
	do_mysql_query("delete from user_grants_to_users  where privilege_id = $id");
	do_mysql_query("delete from user_grants_to_groups where privilege_id = $id");
	do_mysql_query("delete from user_privilege_info where id = $id");
}

/** 
 * Returns an object (stdClass with members corresponding to the
 * fields of the user_privilege_info table in the database) containing
 * all DB fields for the specified privilege.
 * 
 * An extra field is added, namely the DECODED SCOPE. This is a complex value
 * (array or object) in the member scope_object.
 * 
 * Returns false in case of a nonexistent privilege.
 */
function get_privilege_info($id)
{
	$id = (int)$id;

	if (1 > mysql_num_rows($result = do_mysql_query("select * from user_privilege_info where id = $id")))
		return false; 
	else
	{
		$o = mysql_fetch_object($result);
		$o->scope_object = decode_privilege_scope_from_string($o->type, $o->scope);
		return $o;
	}
}

/**
 * Gets the description string for a given privilege ID.
 * Returns empty string in case of an invalid ID.
 */
function privilege_id_to_description($privilege_id)
{
	$p = (int)$privilege_id;
	$result = do_mysql_query("select description from user_privilege_info where id = $p");
	if (1 > mysql_num_rows($result))
		return '';
	list($d) = mysql_fetch_row($result);
	return $d; 
}

/**
 * Gets an array mapping privilege ids (as keys) to descriptions (as values).
 */
function get_all_privilege_descriptions()
{
	$a = array();
	$result = do_mysql_query("select id, description from user_privilege_info order by id asc");
	while (false !== ($o = mysql_fetch_object($result)))
		$a[$o->id] = $o->description;
	return $a;
}




/** 
 * Returns an array of objects of the type returned by get_privilege_info().
 * 
 * The array keys are integers equal to the privilege ID code. 
 * The array is sorted by ascending ID.
 * 
 * Optional conditions can be specified as an associative array, as follows:
 * 
 * - If key 'corpus' is set, then only privileges that affect the corpus specified
 *   are returned. 
 * - (others to follow)
 * 
 * 
 * (Add specification of how different conditions interact, if they do....)
 * 
 * @see get_privilege_info
 */
function get_all_privileges_info($conditions = array())
{
	$cond = '';
		
	if (isset($conditions['corpus']))
		$cond = 'where type=' . PRIVILEGE_TYPE_CORPUS_FULL 
				. ' or type=' . PRIVILEGE_TYPE_CORPUS_NORMAL 
				. ' or type=' . PRIVILEGE_TYPE_CORPUS_RESTRICTED; 

	$list = array();
	$result = do_mysql_query("select * from user_privilege_info $cond order by id");
	while (false !== ($o = mysql_fetch_object($result)))
	{
		$o->scope_object = decode_privilege_scope_from_string($o->type, $o->scope);
		
		/* corpus filter, if requested */
		if (isset($conditions['corpus']))
			if (!in_array($conditions['corpus'], $o->scope_object))
				continue;
		/* end corpus filter */
		
		/* no filter has taken effect, so add to returnable list */
		$list[$o->id] = $o;
	}
	return $list;
}

/**
 * Checks whether at least one privilege exists with the given type and scope.
 * 
 * Pass in scope as data object, not as string.
 *  
 * Returns the privilege ID (if a privilege exists) or false (no such privilege exists).
 */
function check_privilege_by_content($type, $scope)
{
	$type = (int)$type;
	$scope_string = encode_privilege_scope_to_string($type,$scope);
	
	if (1 > mysql_num_rows($result = do_mysql_query("select id from user_privilege_info where type = $type and scope = '$scope_string'")))
		return false;
	
	list($id) = mysql_fetch_row($result);
	return $id;
}



function grant_privilege_to_user($user, $privilege_id, $expiry = 0)
{
	if (empty($user))
		return;
	$user_id = user_name_to_id($user);
	$privilege_id = (int)$privilege_id;
	$expiry = (int)$expiry;

	if (0 < mysql_num_rows(do_mysql_query("select user_id from user_grants_to_users where user_id=$user_id and privilege_id=$privilege_id")))
		return;

	do_mysql_query("insert into user_grants_to_users(user_id, privilege_id, expiry_time) values ($user_id, $privilege_id,$expiry)");  
}

function grant_privilege_to_group($group, $privilege_id, $expiry = 0)
{
	if (empty($group))
		return;
	$group_id = group_name_to_id($group);
	$privilege_id = (int)$privilege_id;
	$expiry = (int)$expiry;
	
	if (0 < mysql_num_rows(do_mysql_query("select group_id from user_grants_to_groups where group_id=$group_id and privilege_id=$privilege_id")))
		return;
	
	do_mysql_query("insert into user_grants_to_groups(group_id, privilege_id, expiry_time) values ($group_id, $privilege_id,$expiry)");  
}

function remove_grant_from_user($user, $privilege_id)
{
	$user_id = user_name_to_id($user);
	$privilege_id = (int)$privilege_id;
	
	do_mysql_query("delete from user_grants_to_users where user_id = $user_id and privilege_id = $privilege_id");
}

function remove_grant_from_group($group, $privilege_id)
{
	$group_id = group_name_to_id($group);
	$privilege_id = (int)$privilege_id;

	do_mysql_query("delete from user_grants_to_groups where group_id = $group_id and privilege_id = $privilege_id");
}

/**
 * Returns flat array of usernames of all users who INDIVIDUALLY have the specified privilege.
 * 
 * For non-existent privilege, or privilege not assigned to anyone, returns empty array.
 */
function list_users_with_privilege($privilege_id)
{
	$privilege_id = (int) $privilege_id;
	
	$result = do_mysql_query("select username from user_grants_to_users 
							inner join user_info on user_grants_to_users.user_id = user_info.id 
							where privilege_id = $privilege_id");
	
	$names = array();
	while (false !== ($r = mysql_fetch_row($result)))
		$names[] = $r[0];
	return $names;
}

/**
 * Returns flat array of names of groups with the specified privilege.
 */
function list_groups_with_privilege($privilege_id)
{
	$privilege_id = (int) $privilege_id;
	
	$result = do_mysql_query("select group_name from user_grants_to_groups
							inner join user_groups on user_grants_to_groups.group_id = user_groups.id 
							where privilege_id = $privilege_id");	
	
	$names = array();
	while (false !== ($r = mysql_fetch_row($result)))
		$names[] = $r[0];
	return $names;	
}


/**
 * Returns an array of DB objects, representing the grants given to the user with the specified name.
 */
function list_user_grants($user)
{
	$uid = user_name_to_id($user);
	$ret = array();
	$result = do_mysql_query("select * from user_grants_to_users where user_id = $uid order by privilege_id asc");
	while (false !== ($o = mysql_fetch_object($result)))
		$ret[] = $o;
	return $ret;
}


/**
 * Returns an array of DB objects, representing the grants given to the user with the specified name.
 */
function list_group_grants($group)
{
	$gid = group_name_to_id($group);
	$ret = array();
	$result = do_mysql_query("select * from user_grants_to_groups where group_id = $gid order by privilege_id asc");
	while (false !== ($o = mysql_fetch_object($result)))
		$ret[] = $o;
	return $ret;
}


/**
 * Returns an array of privilege objects, containing (unique) objects for the
 * privileges that the given user has, whether by virtue of a user grant, or via
 * their group memberships and group grants.
 * 
 * The privilege id numbers are the array keys (that's how the contents are kept unique!)
 */
function get_collected_user_privileges($user)
{
	$all_privs = get_all_privileges_info(); 
	$privileges = array();

	/* add privileges held individually */ 
	foreach(list_user_grants($user) as $grant)
		if ( ! isset($privileges[$grant->privilege_id]) )
			$privileges[$grant->privilege_id] = $all_privs[$grant->privilege_id];
	
	/* add privileges held via groups */
	foreach(get_list_of_groups() as $group)
		if (user_is_group_member($user, $group))
			foreach(list_group_grants($group) as $grant)
				if ( ! isset($privileges[$grant->privilege_id]) )
					$privileges[$grant->privilege_id] = $all_privs[$grant->privilege_id];

	return $privileges; 
}

function clone_group_grants($from_group, $to_group)
{
	/* checks for group validity */
	if ($from_group == $to_group || empty($from_group) || empty($to_group))
		return;
	
	$id_from = group_name_to_id($from_group);
	$id_to   = group_name_to_id($to_group);
	
	do_mysql_query("delete from user_grants_to_groups where group_id = $id_to");
	
	$result = do_mysql_query("select * from user_grants_to_groups where group_id = $id_from");
	
	while (false !== ($o = mysql_fetch_object($result)))
		do_mysql_query("insert into user_grants_to_groups 
			(group_id,privilege_id,expiry_time) values ($id_to,{$o->privilege_id},{$o->expiry_time})");
}








function user_macro_create($username, $macro_name, $macro_body)
{
	$username = mysql_real_escape_string($username);
	$macro_name = mysql_real_escape_string($macro_name);
	$macro_body = mysql_real_escape_string($macro_body);
	
	/* convert any \r to \n and delete multiple \n */
	$macro_body = str_replace("\r", "\n", $macro_body);
	$macro_body = "\n" . str_replace("\n\n", "\n", $macro_body);
	
	/* deduce macro_num_args by matching all strings of form $\d+ */
	preg_match_all('|[^\\\\]\$(\d+)|', $macro_body, $m, PREG_PATTERN_ORDER);
	
	$top_mentioned_arg = -1;
	
	foreach($m[1] as $num)
		if ($num > $top_mentioned_arg)
			$top_mentioned_arg = $num;
	
	/* The $\d references count from zero so if $1 is top mentioned, num args is actually 2 */
	$macro_num_args = $top_mentioned_arg + 1;
	
	/* delete macro if already exists */
	user_macro_delete($username, $macro_name, $macro_num_args);
	
	$sql_query = "INSERT INTO user_macros
		(user, macro_name, macro_num_args, macro_body)
		values
		('$username', '$macro_name', $macro_num_args, '$macro_body')";
	
	do_mysql_query($sql_query);
}

function user_macro_delete($username, $macro_name, $macro_num_args)
{
	$username = mysql_real_escape_string($username);
	$macro_name = mysql_real_escape_string($macro_name);
	$macro_num_args = (int)$macro_num_args;
	
	do_mysql_query("delete from user_macros 
						where user='$username' 
						and macro_name='$macro_name'
						and macro_num_args = $macro_num_args");
}


/**
 * Load all macros for the specified user. 
 */
function user_macro_loadall($username)
{
	global $cqp;
	
	$username = mysql_real_escape_string($username);

	$result = do_mysql_query("select * from user_macros where user='$username'");

	while (false !== ($r = mysql_fetch_object($result)))
	{
		$block = "define macro {$r->macro_name}({$r->macro_num_args}) ' ";
		/* nb. Rather than use str_replace here, maybe use the CQP:: escape method? */
		$block .= str_replace("'", "\\'", strtr($r->macro_body, "\t\r\n", "   ")) . " '";
		$cqp->execute($block);
	}
}






/* ***************** *
 * CAPTCHA FUNCTIONS *
 * ***************** */

/**
 * Returns true if the captcha in DB matches the response.
 * 
 * Captcha comparison is always case-insensitive.
 * 
 * Whether true or false, destroys the captcha.
 */
function check_captcha($which, $response)
{
	$which = (int) $which;
	
	$result = do_mysql_query('select captcha from user_captchas where id = ' . $which);
	
	if (mysql_num_rows($result) < 1) 
		return false;
	
	list($correct) = mysql_fetch_row($result);
	
	do_mysql_query('delete from user_captchas where id = ' . $which);

	return ($correct == strtolower($response));
}

/**
 * Puts a new captcha into the DB, and return its DB number (for reference)
 */
function create_new_captcha()
{
	global $Config;
	static $alphabet = 'abcdefghijkmnpqrstuvwxyz+=&!@:^23456789';

	/* delete any captchas that are past their expiry time. */
	$now = time();
	do_mysql_query("delete from user_captchas where expiry_time < $now");
	
	/* create a new captcha from the alphabet above & store in the DB */
	
	for($i = 0, $n = 6, $l = strlen($alphabet)-1, $captcha = ''; $i < $n; $i++)
		$captcha .= $alphabet[mt_rand(0, $l)];
	
	/* captchas expire after 30 mins */
	$t = $now + 1800;
	
	do_mysql_query("insert into user_captchas (captcha, expiry_time) values ('$captcha', $t)");
	
	/* we get back the ID, which is our reference number, & return it */
	return get_mysql_insert_id();
}

/**
 * Sends to the browser the binary data of an image for the captcha in question.
 */
function send_captcha_image($which)
{	
	/* parameters used by all algorithms */
	$font = realpath('../css/img/LinLibertine_Mah.ttf');
	$height = 100;
	$width  = 240;
	
	$image = imagecreatetruecolor($width, $height);

	
	/* get the capcha from DB; if not available, put error message on image */
	
	$which = (int)$which;
	
	$result = do_mysql_query('select captcha from user_captchas where id=' . $which);

	if (mysql_num_rows($result) < 1)
	{
		$image = imagecreatetruecolor($width, $height);		
		$bgcol   = imagecolorallocate($image, 255, 255, 255);
		$textcol = imagecolorallocate($image, 0, 0, 0);
		imagefill($image, 0, 0, $bgcol);
		imagettftext($image, 12, 0, 15, 15, $textcol, $font, 'Error: please retry');
		header("Content-Type: image/jpeg");
		imagejpeg($image); 
		imagedestroy($image);
		return;
	}
	
	list($captcha) = mysql_fetch_row($result);


	/* three different image algorithms, to mix up the kinds of captcha people see */
	
	switch (mt_rand(0,2))
	{
	case 0:
	
		/* COLOURFUL */
		
		$bgcol   = imagecolorallocate($image, mt_rand(0,100),  mt_rand(0,100),  mt_rand(0,100));
		$textcol = imagecolorallocate($image, mt_rand(200,255),mt_rand(80,255), mt_rand(100,200));

		imagefill($image, 0, 0, $bgcol);

		$fs = mt_rand(20,45);
		$x = mt_rand(5,20);
		$y = mt_rand(45,75);
		
		for ($i = 0 ; $i < 6 ; $i++)
		{
			$angle = mt_rand(-12,12);
			
			imagettftext($image, 
				$fs,           /* font size */
				$angle,        /* angle */
				$x,            /* x basepoint */
				$y,            /* y basepoint */
				$textcol,
				$font,
				$captcha[$i]);

			$x += $fs - mt_rand(0,$fs-(int)($fs/2));
			$y += ($angle > 0 ? 1 : -1) * mt_rand(0,10);
			$fs += mt_rand(-5,5);
			if ($fs < 16)
				$fs = $fs + 8;
			if ($fs > 45)
				$fs = $fs - 8;
		}

		for($x = 0, $n = mt_rand(7,12); $x < $n ; $x++)
			imageline($image, 0, mt_rand(0,$height), $width, 10 + mt_rand(0,$height), $textcol);

		break;
		
	case 1:
	
		/* GREY ON WHITE */
	
		$bgcol   = imagecolorallocate($image, 255, 255, 255);
		$textcol = imagecolorallocate($image, 150, 150, 150);

		imagefill($image, 0, 0, $bgcol);
		
		for($x = 1; $x <= 25; $x++)
			imageline($image, mt_rand(1, $width), mt_rand(1, $height), mt_rand(1, $width), mt_rand(1, $height), $textcol);

		$fs = mt_rand(20,56);
		$x = mt_rand(10,15);
		$y = mt_rand(45,70);
		
		for ($i = 0 ; $i < 6 ; $i++)
		{
			$angle = mt_rand(-4,4);
			
			imagettftext($image, 
				$fs,          /* font size */
				$angle,       /* angle */
				$x,           /* x basepoint */
				$y,           /* y basepoint */
				$textcol,
				$font,
				$captcha[$i]
				);

			$x += $fs - mt_rand(0,$fs-(int)($fs/2));
			$y += ($angle > 0 ? 1 : -1) * mt_rand(0,10);
			$fs += mt_rand(-5,5);
			if ($fs < 20)
				$fs = $fs + 8;
			if ($fs > 56)
				$fs = $fs - 8;
		}

		break;		

	
	case 2:
	
		/* BLUE WITH DOTTY BACKGROUND */
	
		$font_size = $height * 0.40; 

		/* set the colours */ 
		$bgcol    = imagecolorallocate($image, 255, 255, 255);
		$textcol  = imagecolorallocate($image, 20, 40, 100); 
		$noisecol = imagecolorallocate($image, 60, 80, 140); 

		imagefill($image, 0, 0, $bgcol);
		
		/* random dots */ 
		for($x = 0; $x < ($width*$height)/8; $x++ ) 
			imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noisecol);
		
		/* random lines */ 
		for($x = 0; $x < ($width*$height)/300 ; $x++ )
			imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noisecol);

		$fs = $height * 0.40;
		$x = ($width - mt_rand(($width - 20), ($width - 5)));
		$y = ($height - mt_rand(0, (int)$height/2));
		
		for ($i = 0 ; $i < 6 ; $i++)
		{
			$angle = mt_rand(-4,4);
			
			imagettftext($image, 
				$fs,          /* font size */
				$angle,       /* angle */
				$x,           /* x basepoint */
				$y,           /* y basepoint */
				$textcol,
				$font,
				$captcha[$i]
				);

			$x += $fs - mt_rand(0,$fs-(int)($fs/2));
			$y += ($angle > 0 ? 1 : -1) * mt_rand(0,10);
			$fs += mt_rand(-5,5);
			if ($fs < 20)
				$fs = $fs + 8;
			if ($fs > 56)
				$fs = $fs - 8;
		}

		break;
	}
	
	
	/* send the image */
	
	header("Content-Type: image/jpeg");
	imagejpeg($image); 
	imagedestroy($image);
}







