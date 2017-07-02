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
 * This file contains several things:
 * 
 * (1) Constant definitions for the system.
 * 
 * (2) The three global objects ($Config, $User, $Corpus) into which everything is stuffed.
 * 
 * (3) The environment startup and shutdown functions that need to be called to get things moving.
 * 
 */




/*
 * ------------------------------- *
 * Constant definitions for CQPweb *
 * ------------------------------- *
 */


/*
 * version number of CQPweb 
 */
define('CQPWEB_VERSION', '3.2.11');

/*
 * FLAGS for cqpweb_startup_environment()
 */
 
define('CQPWEB_STARTUP_NO_FLAGS',              0);
define('CQPWEB_STARTUP_DONT_CONNECT_CQP',      1);
define('CQPWEB_STARTUP_DONT_CONNECT_MYSQL',    2);
define('CQPWEB_STARTUP_DONT_CHECK_URLTEST',    4);
define('CQPWEB_STARTUP_CHECK_ADMIN_USER',      8);
define('CQPWEB_STARTUP_ALLOW_ANONYMOUS_ACCESS',16);


/*
 * Run location constants 
 */

define('RUN_LOCATION_CORPUS',                  0);
define('RUN_LOCATION_MAINHOME',                1);
define('RUN_LOCATION_ADM',                     2);
define('RUN_LOCATION_USR',                     3);
define('RUN_LOCATION_CLI',                     4);
define('RUN_LOCATION_RSS',                     5);


/*
 * Metadata type constants (for texts and XML segments)
 */

define('METADATA_TYPE_NONE',                   0);
define('METADATA_TYPE_CLASSIFICATION',         1);
define('METADATA_TYPE_FREETEXT',               2);
define('METADATA_TYPE_UNIQUE_ID',              3);
define('METADATA_TYPE_IDLINK',                 4);
define('METADATA_TYPE_DATE',                   5);


/*
 * User-database type constants
 */

define('DB_TYPE_DIST',                         1);
define('DB_TYPE_COLLOC',                       2);
define('DB_TYPE_SORT',                         3);
define('DB_TYPE_CATQUERY',                     4);	


/* 
 * plugin type constants 
 */

define('PLUGIN_TYPE_UNKNOWN',                  0);
define('PLUGIN_TYPE_ANNOTATOR',                1);
define('PLUGIN_TYPE_FORMATCHECKER',            2);
define('PLUGIN_TYPE_TRANSLITERATOR',           4);
define('PLUGIN_TYPE_POSTPROCESSOR',            8);
define('PLUGIN_TYPE_ANY',                      1|2|4|8);


/*
 * user account state constants
 */

define('USER_STATUS_UNVERIFIED',               0);
define('USER_STATUS_ACTIVE',                   1);
define('USER_STATUS_SUSPENDED',                2);
define('USER_STATUS_PASSWORD_EXPIRED',         3);


/*
 * privilege types
 */

define('PRIVILEGE_TYPE_NO_PRIVILEGE',          0);	/* can be used to indicate absence of one or more privileges; not used in the DB */
define('PRIVILEGE_TYPE_CORPUS_RESTRICTED',     1);
define('PRIVILEGE_TYPE_CORPUS_NORMAL',         2);
define('PRIVILEGE_TYPE_CORPUS_FULL',           3);
/* note that the above 4 definitions create a greater-than/less-than sequence. Intentionally so. */

define('PRIVILEGE_TYPE_FREQLIST_CREATE',       4);


/* 
 * query-record save-status indicators 
 */

/** Signifies that a recorded query exists in cache but has not been saved. */
define('CACHE_STATUS_UNSAVED',                 0);
/** Signifies that a recorded query has been saved by a user. */
define('CACHE_STATUS_SAVED_BY_USER',           1);
/** Signifies that a recorded query has been saved as a "categorised" query. */
define('CACHE_STATUS_CATEGORISED',             2);





/*
 * misc constants
 */

/** The common date-time format string used around CQPweb. */
define('CQPWEB_UI_DATE_FORMAT', 'Y-M-d H:i:s');









/* --------------------- *
 * Global object classes *
 * --------------------- */



/**
 * Class of which each run of CQPweb should only ever have ONE - it holds config settings as public variables
 * (sometimes hierarchically using other objects).
 * 
 * The instantiation should always be the global $Config object.
 * 
 * Has only one function, its constructor, which loads all the config settings.
 * 
 * Config settings in the database are NOT loaded by the constructor.
 * 
 * 
 */
class CQPwebEnvConfig
{
	/**
	 * $instance_name is the unique identifier of the present run of a given script 
	 * which will be used as the name of any queries/records saved by the present script.
	 * 
	 * It was formerly the username plus the unix time, but this raised the possibility of
	 * one user seeing another's username linked to a cached query. So now it's the PHP uniqid(),
	 * which is a hexadecimal version of the Unix time in microseconds. This shouldn't be 
	 * possible to duplicate unless (a) we're on a computer fast enough to call uniqid() twice
	 * in two different processes in the same microsecond AND (b) two users do happen to hit 
	 * the server in the same microsecond. Unlikely, but id codes based on the $instance_name
	 * should still be checked for uniqueness before being used in any situation where the 
	 * uniqueness matters (e.g. as a database primary key).
	 * 
	 * For compactness, we express as base-36. Total length = 10 chars (for the foreseeable future!).
	 */
	public $instance_name;

	/* we don't declare any members that don't need documenting; the constructor function creates them dynamically */
	
	public function __construct($run_location)
	{	
		/* set up the instance name */
		$this->instance_name = base_convert(uniqid(), 16, 36);
		
		/* import config variables from the global state of the config file */
		require('../lib/config.inc.php');
		require('../lib/defaults.inc.php');
		
		/* transfer imported variables to object members */
		$variables = get_defined_vars();
		unset(	$variables['GLOBALS'], $variables['_SERVER'], $variables['_GET'],
				$variables['_POST'],   $variables['_FILES'],  $variables['_COOKIE'],
				$variables['_SESSION'],$variables['_REQUEST'],$variables['_ENV'] 
				);
		foreach ($variables as $k => $v)
			$this->$k = $v;
		/* this also creates run_location as a member.... */

		/* check compulsory config variables */
		$compulsory_config_variables = array(
				'superuser_username',
				'mysql_webuser',
				'mysql_webpass',
				'mysql_schema',
				'mysql_server',
				'cqpweb_tempdir',
				'cqpweb_uploaddir',
				'cwb_datadir',
				'cwb_registry'
			);
		foreach ($compulsory_config_variables as $which)
			if (!isset($this->$which))
				exiterror_general("CRITICAL ERROR: \$$which has not been set in the configuration file.");


		/* and now, let's organise the directory variables into something saner */
		$this->dir = new stdClass;
		$this->dir->cache = $this->cqpweb_tempdir;
		unset($this->cqpweb_tempdir);
		$this->dir->upload = $this->cqpweb_uploaddir;
		unset($this->cqpweb_uploaddir);
		$this->dir->index = $this->cwb_datadir;
		unset($this->cwb_datadir);
		$this->dir->registry = $this->cwb_registry;
		unset($this->cwb_registry);
		
		/* CSS action based on run_location */
		switch ($this->run_location)
		{
		case RUN_LOCATION_MAINHOME:     $this->css_path = $this->css_path_for_homepage;     break;
		case RUN_LOCATION_ADM:          $this->css_path = $this->css_path_for_adminpage;    break;
		case RUN_LOCATION_USR:          $this->css_path = $this->css_path_for_userpage;     break;
		case RUN_LOCATION_RSS:          /* no CSS path needed */                            break;
		/* 
		 * tacit default: RUN_LOCATION_CORPUS, where the $Corpus object takes responsibility
		 * for setting the global $Config->css_path appropriately. 
		 */
		}
		
		/* add further system config here. */
	}
}


/**
 * Class of which each run of CQPweb should only ever have ONE - it represents the logged in user.
 * 
 * The instantiation should always be the global $User object.
 * 
 */
class CQPwebEnvUser 
{
	/** Is there a logged in user? (bool) */
	public $logged_in;
	
	/** full array of privileges (db objects) available to this user (individually or via group) */
	public $privileges;
	
	public function __construct()
	{
		global $Config;

		/* 
		 * Now, let us get the username ... 
		 */
		
		/* if this environment is in a CLI script, count us as being logged in as the first admin user */ 
		if (PHP_SAPI == 'cli')
		{
			list($username) = list_superusers();
			$this->logged_in = true; 
		}
		else
		{
			/* look for logged on user */
			if ( (! isset($_COOKIE[$Config->cqpweb_cookie_name])) ||  false === ($checked = check_user_cookie_token($_COOKIE[$Config->cqpweb_cookie_name])))
			{
				/* no one is logged in */
				$username = '__unknown_user'; // TODO maybe change this
				$this->logged_in = false;
			}
			else
			{
				$this->logged_in = true;
				$username = $checked->username;
				
				/* if the cookie token is more than half an hour old, delete and emit a new one; otherwise, touch the existing one. 
				 * (so the token should only get used within a single session, or for the first connection of a subsequent session.) */
				if (time() - 1800 > $checked->creation )
				{
					emit_new_cookie_token($username, 
						(isset($_COOKIE[$Config->cqpweb_cookie_name . 'Persist']) && '1' === $_COOKIE[$Config->cqpweb_cookie_name . 'Persist'])
						);
				}
				else
				{
					touch_cookie_token($_COOKIE[$Config->cqpweb_cookie_name]);
					/* cookie tokens which don't get touched will eventually get old enough to be deleted */
				}
			}
		}


		/* now we know whether we are logged in and if so, who we are, set up the user information */
		if ($this->logged_in)
		{
			/* Update the last-seen date (on every hit from user's browser!) */
			touch_user($username);
			
			/* import database fields as object members. */
			foreach ( ((array)get_user_info($username)) as $k => $v)
				$this->$k = $v;
			/* will also import $username --> $User->username which is canonical way to acces it. */
			
			// TODO: some of the DB variable, like use_tooltips, should be re-typed (here: bool)
			// perhaps, declare them explicitly as class members?
		}
		
		/* finally: look for a full list of privileges that this user has. */
		$this->privileges = ($this->logged_in ? get_collected_user_privileges($username) : array());
	}
	
	public function is_admin()
	{
		return ( PHP_SAPI=='cli' || ($this->logged_in && user_is_superuser($this->username)) );
	}
	
	/**
	 * Returns the size, in tokens, of the largest sub-corpus for which this user
	 * allowed to create frequency lists.
	 */
	public function max_freqlist()
	{
		static $max = NULL;
		if (! is_null($max) )
			return $max;
		
		/* we begin with a ridiculously low value, so that any privilege will be higher */
		$max = 1000;

		foreach($this->privileges as $p)
			if ($p->type == PRIVILEGE_TYPE_FREQLIST_CREATE)
				if ($p->scope_object > $max)
					$max = $p->scope_object;
		
		return $max;
	}
}



/**
 * Class of which each run of CQPweb should only ever have ONE - it represents the environment-corpus
 * for the currently-rendered page, if there is one.
 * 
 * The instantiation should always be the global $Corpus object.
 * 
 * Has only one function, its constructor, which loads all the info.  
 */ 
class CQPwebEnvCorpus 
{
	/** are we running within a particular corpus ? */
	public $specified = false;
	
	/** This is set to a privilege constant to indicate what level of privilege the currently-logged-on user has. */
	public $access_level;
	
	/**
	 * The parameter allows the environment-corpus to be directly specified.
	 * If it is not given, then it will be deduced (a) on the CLI, from the working directory.
	 * (b) on the Web, from the SCRIPT_NAME value in $_SERVER (i.e. indirectly from the URL).
	 */
	public function __construct($parameter_corpus = NULL)
	{
		/* first, check whether we are actually in the run location required... 
		 * and leave $corpus as just an empty object if we're not. */
		global $Config;
		if (RUN_LOCATION_CORPUS != $Config->run_location)
			return;

		/* first: try to identify the corpus. 
		 * Order of actions is: constructor parameter; $_GET["c"]; guess from the $_SERVER */
		if (! is_null($parameter_corpus))
		{
			$this->name = $parameter_corpus;
			unset($parameter_corpus);
		}
		else if (!empty($_GET['c']))
		{
			/* this is currently only used by the "offline freqlists" script,
			 * but will be used by user-corpora in the future. 
			 * TODO: check that no existing script uses the "c" parameter for anything.
			 * I can't think of any...
			 */
			$this->name = cqpweb_handle_enforce($_GET['c']);
		}
		else 
		{
			if ('cli' == PHP_SAPI)
			{
				/* what corpus are we in? --> last element of the Unix environment variable PWD */
				$junk = explode('/', str_replace('\\', '/', rtrim($_SERVER['PWD'], '/')));
				$this->name = end($junk);
				unset($junk);
				/* note, we cannot use getcwd() as it will (on Linux at least) resolve symlinks! */ 
			}
			else
			{
				/* what corpus are we in? --> last element of SCRIPT_NAME before the filename. */
				if (1 > preg_match('|/(\w+)/[^/]+.php$|', $_SERVER['SCRIPT_NAME'], $m))
					exit("Core critical error: could not determine what corpus we are using.\n");
				$this->name =  $m[1];
				/* getcwd() would have worked here too BUT it is apparently disabled on some servers. */
			}
			/* if we got, for instance, "adm" from this process, then $Config's run_location being CORPUS is clearly wrong. */
			if (in_array($this->name, $Config->cqpweb_reserved_subdirs))
			{
				unset($this->name);
				return;
			}
		}

		if (!empty($this->name))
			$this->specified = true;
		/* if specified is not true, then $Config->run_location will tell you where we are running from. */

		/* only go hunting for more info on the $Corpus if one is actually specified...... */
		if ($this->specified)
		{
			/* import database fields as object members. */

			$result = do_mysql_query("select * from corpus_info where corpus = '{$this->name}'");
			if (mysql_num_rows($result) < 1)
				exit("Core critical error: invalid corpus handle submitted to database.\n");

			foreach (mysql_fetch_assoc($result) as $k => $v)
			{
				/* allows for special cases */
				switch ($k)
				{
				/* fallthrough list for do-nothing cases */
				case 'corpus':
					break;
				/* note that here in the global object, `corpus` ==> $this->name.
				 * but in small objects for non-environment corpora, it stays as `corpus`. */

				/* fallthrough list for bools */
				case 'is_user_corpus':
				case 'cwb_external':
				case 'visible':
				case 'uses_case_sensitivity':
				case 'main_script_is_r2l':
				case 'visualise_gloss_in_concordance':
				case 'visualise_gloss_in_context':
				case 'visualise_translate_in_concordance':
				case 'visualise_translate_in_context':
				case 'visualise_position_labels':
					$this->$k = (bool) $v;
					break;

				/* fallthrough list for integers */
				case 'id':
				case 'corpus_cat':
				case 'initial_extended_context':
				case 'max_extended_context':
				case 'size_tokens':
				case 'size_types':
				case 'size_texts':
				case 'conc_scope':
					$this->$k = (int) $v;
					break;
				
				/* everything else is added as a string - incl. the timestamp column date_of_indexing! */
				default:
					$this->$k = $v;
					break;
				}
			}

			
			/* 
			 * variables which need sanity checks/adjustment/deducing from another setting.... 
			 * ===============================================================================
			 * 
			 * (all so far about concordance/context rendering)
			 */
			
			/* deduce default regex flags for auto-generated CQP-syntax queries */
			$this->cqp_query_default_flags = $this->uses_case_sensitivity ? '' : '%c' ;
			$this->sql_collation = deduce_corpus_mysql_collation($this);
			
			/* concordance scope deduction */
			$this->conc_scope = ($this->conc_scope < 1 ? 1 :  $this->conc_scope);
			$this->conc_scope_is_based_on_s = !empty($this->conc_s_attribute);
			
			/* sanity check for extended context width values... */
			if ($this->initial_extended_context > $this->max_extended_context)
				$this->initial_extended_context = $this->max_extended_context;			

			/* sanity check for concordance / conteaxt glossing */
			if ($this->visualise_gloss_in_concordance || $this->visualise_gloss_in_context)
				if (!isset($this->visualise_gloss_annotation))
					$this->visualise_gloss_annotation = 'word'; 
			
			/* sanity check for translation */
			if (empty($this->visualise_translate_s_att))
			{
				/* we can't default this one: we'll have to switch off these variables */
				$this->visualise_translate_in_context = false;
				$this->visualise_translate_in_concordance = false;
			}
			else
			{
				/* we override $conc_scope etc... if the translation s-att is to be used in concordance */
				if ($this->visualise_translate_in_concordance)
				{
					$this->conc_s_attribute = $this->visualise_translate_s_att;
					$this->conc_scope_is_based_on_s = true;
					$this->conc_scope = 1;
				}
			}
 

			/* some settings then transfer to $Config */
			global $Config;
			if (isset($this->css_path))
			{
				$Config->css_path = $this->css_path;
				/* We keep it here so it can be discovered if necessary. */
			}
		
			/* finally, since we are in a corpus, we need to ascertain (a) whether the user is allowed
			 * to access this corpus; (b) at what level the access is. */
			$this->ascertain_access_level();
			
			if ($this->access_level == PRIVILEGE_TYPE_NO_PRIVILEGE)
			{
				/* redirect to a page telling them they do not have the privilege to access this corpus. */
				set_next_absolute_location("../usr/index.php?thisQ=accessDenied&corpusDenied={$this->name}&uT=y");
				cqpweb_shutdown_environment();
				exit;
				/* otherwise, we know that the user has some sort of access to the corpus, and we can continue */
			}
		}
	}
	
	/**
	 * Sets up the access_level member to the privilege type indicating
	 * the HIGHEST level of access to which the currently-logged-in user
	 * is entitled for this corpus.
	 * 
	 * Assumes that the global object $User is already set up.
	 */ 
	private function ascertain_access_level()
	{
		global $User;
		
		/* superusers have full access to everything. */
		if ($User->is_admin())
		{
			$this->access_level = PRIVILEGE_TYPE_CORPUS_FULL;
			return;
		}
		
		/* otherwise we must dig through the privilweges owned by this user. */
		
		/* start by assuming NO access. Then look for the highest privilege this user has. */
		$this->access_level = PRIVILEGE_TYPE_NO_PRIVILEGE;

		foreach($User->privileges as $p)
		{
			switch($p->type)
			{
			/* a little trick: we know that these constants are 3, 2, 1 respectively,
			 * so we can do the following: */
			case PRIVILEGE_TYPE_CORPUS_FULL:
			case PRIVILEGE_TYPE_CORPUS_NORMAL:
			case PRIVILEGE_TYPE_CORPUS_RESTRICTED:
				if (in_array($this->name, $p->scope_object))
					if ($p->type > $this->access_level)
						$this->access_level = $p->type;
				break;
			default:
				break;
			}
		}
	}
}








/* ============================== *
 * Startup and shutdown functions *
 * ============================== */



/**
 * Declares a plugin for later use.
 *
 * This function does not do any error checking, that is done later by the plugin
 * autoload function.
 * 
 * TODO: move this function elsewhere as it is messy to have it in environment.
 * 
 * @param class                The classname of the plugin. This should be the same as the
 *                             file that contains it, minus .php.
 * @param type                 The type of plugin. One of the following constants:
 *                             PLUGIN_TYPE_ANNOTATOR,
 *                             PLUGIN_TYPE_FORMATCHECKER,
 *                             PLUGIN_TYPE_TRANSLITERATOR,
 *                             PLUGIN_TYPE_POSTPROCESSOR.
 * @param path_to_config_file  What it says on the tin; optional.
 * @return                     No return value.
 */
function declare_plugin($class, $type, $path_to_config_file = NULL)
{
	//TODO why is this a global and n ot under config or summat?
	global $plugin_registry;
	if (!isset($plugin_registry))
		$plugin_registry = array();
	
	$temp = new stdClass();
	
	$temp->class = $class;
	$temp->type  = $type;
	$temp->path  = $path_to_config_file;
	
	$plugin_registry[] = $temp;
}




/**
 * Function that starts up CQPweb and sets up the required environment.
 * 
 * All scripts that require the environment should call this function.
 * 
 * It should be called *after* the inclusion of most functions, but
 * *before* the inclusion of admin functions (if any).
 * 
 * Ultimately, this function will be used instead of the various "setup
 * stuff" that uis currently done repeatedly, per-script.
 * 
 * Pass in bitwise-OR'd flags to control the behaviour. 
 */
function cqpweb_startup_environment($flags = CQPWEB_STARTUP_NO_FLAGS, $run_location = RUN_LOCATION_CORPUS)
{
	if ($run_location == RUN_LOCATION_CLI)
		if (php_sapi_name() != 'cli')
			exit("Critical error: Cannot run CLI scripts over the web!\n");

	/* -------------- *
	 * TRANSFROM HTTP *
	 * -------------- */

	/* the very first thing we do is set up _GET, _POST etc. .... */

	/* MAGIC QUOTES, BEGONE! */
	
	/* In PHP > 5.4 magic quotes don't exist, but that's OK, because the function in the test will always
	 * return false. We also don't worry about multidimensional arrays, since CQPweb doesn't use them. 
	 * The test function also returns false if we are working in the CLI environment. */
	
	if (get_magic_quotes_gpc()) 
	{
		foreach ($_POST as $k => $v) 
		{
			unset($_POST[$k]);
			$_POST[stripslashes($k)] = stripslashes($v);
		}
		foreach ($_GET as $k => $v) 
		{
			unset($_GET[$k]);
			$_GET[stripslashes($k)] = stripslashes($v);
		}
	}
	
	/* WE ALWAYS USE GET! */
	
	/* sort out our incoming variables.... */
	foreach($_POST as $k=>$v)
		$_GET[$k] = $v;
	/* now, we can be sure that any bits of the system that rely on $_GET being there will work. */	


	/* --------------------------------- *
	 * WORKAROUNDS FOR OTHER PHP SADNESS *
	 * --------------------------------- */

	/* As of 5.4, PHP emits nasty warning messages if date.timezone is not set in the ini file.... */
	if ('' == ini_get('date.timezone'))
		ini_set('date.timezone', 'UTC');


	/* -------------- *
	 * GLOBAL OBJECTS *
	 * -------------- */
	
	
	/** Global object containing information on system configuration. */
	global $Config;
	/** Global object containing information on the current user account. */
	global $User;
	/** Global object containing information on the current corpus. */
	global $Corpus;
	
	
	// TODO, move into here the setup of plugins
	// (so this is done AFTER all functions are imported, not
	// in the defaults.inc.php file)

	/* create global settings options */
	$Config = new CQPwebEnvConfig($run_location);
	
	
	
	/*
	 * Now that we have the $Config object, we can connect to MySQL.
	 * 
	 * The flags (here and below) are for "dont" because we assume 
	 * the default behaviour is to need both a DB connection and a 
	 * slave CQP process.
	 * 
	 * If one or both is not required, a flag can be passed in to 
	 * save the connection (not much of a saving in the case of the DB,
	 * potentially quite a performance boost for the slave process.)
	 */
	if ($flags & CQPWEB_STARTUP_DONT_CONNECT_MYSQL)
		;
	else
		connect_global_mysql();


	/* now the DB is connected, we can do the other two global objects. */

	$User   = new CQPwebEnvUser();
	
	$Corpus = new CQPwebEnvCorpus();


	/* now that we have the global $Corpus object, we can connect to CQP */

	if ($flags & CQPWEB_STARTUP_DONT_CONNECT_CQP)
		;
	else
		connect_global_cqp();
	

	
	/* write progressively to output in case of long loading time */
	ob_implicit_flush(true);

 

		
	

	/* We do the following AFTER starting up the global objects, because without it, 
	 * we don't have the CSS path for exiterror. */
	if (($flags & CQPWEB_STARTUP_DONT_CHECK_URLTEST) || PHP_SAPI=='cli')
		;
	else
		if (!url_string_is_valid())
			exiterror_bad_url();
	if ($flags & CQPWEB_STARTUP_CHECK_ADMIN_USER)
		if (!$User->is_admin())
			exiterror_general("You do not have permission to use this part of CQPweb.");
	
	/* end of function cqpweb_startup_environment */
}



/**
 * Performs shutdown and cleanup for the CQPweb system.
 * 
 * The only thing that it will not do is finish off HTML. 
 * The script should do that separately -- BEFORE calling this function.
 * 
 * All scripts should finish by calling this function.
 */
function cqpweb_shutdown_environment()
{	
	/* these funcs have their own "if" clauses so can be called here unconditionally... */
	disconnect_global_cqp();
	disconnect_global_mysql();
}


