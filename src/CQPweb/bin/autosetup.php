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
 * This script finalises CQPweb setup once the config file has been created.
 */



require('../lib/environment.inc.php');

/* include function library files */
require('../lib/library.inc.php');
require('../lib/admin-lib.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/ceql.inc.php');

require ('../bin/cli-lib.php');




/* BEGIN HERE */


/* refuse to run unless we are in CLI mode */
if (php_sapi_name() != 'cli')
	exit("Critical error: Cannot run CLI scripts over the web!\n");

echo "\nNow finalising setup for this installation of CQPweb....\n";

/* create partial environment */

include ('../lib/defaults.inc.php');
include ('../lib/config.inc.php');
/* Create only those config values needed to make mysql connection work */
$Config = new stdClass();
$Config->print_debug_messages = false;
// $Config->mysql_link = (isset($mysql_link) ? $mysql_link: NULL); // TODO this line should be deleted, no? connect_global_mysql(); creates this var, below.
$Config->mysql_server = $mysql_server;
$Config->mysql_webuser = $mysql_webuser;
$Config->mysql_webpass = $mysql_webpass;
$Config->mysql_schema = $mysql_schema;
$Config->mysql_utf8_set_required = $mysql_utf8_set_required;


connect_global_mysql();


/* another partial environment! -- these are the values needed for user account creation */
$Config = new stdClass();
$Config->print_debug_messages = false;
$Config->debug_messages_textonly = true;
$Config->all_users_see_backtrace = false;
$Config->default_colloc_calc_stat = $default_colloc_calc_stat;
$Config->default_colloc_minfreq = $default_colloc_minfreq;
$Config->default_colloc_range = $default_colloc_range;
$Config->default_max_dbsize = $default_max_dbsize;


echo "\nInstalling database structure; please wait.\n";
cqpweb_mysql_total_reset();

echo "\nDatabase setup complete.\n";

echo "\nNow, we must set passwords for each user account specified as a superuser.\n";


foreach(explode('|', $superuser_username) as $super)
{
	$pw = get_variable_string("a password for user ``$super''");
	add_new_user($super, $pw, 'not-specified@nowhere.net', USER_STATUS_ACTIVE);
	echo "\nAccount setup complete for ``$super''\n";
}

echo "\n--- done.\n";


/* destroy partial environment */

unset($Config);
disconnect_global_mysql();


/* with DB installed, we can now startup the environment.... */

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP , RUN_LOCATION_CLI);



/* No longer necessary because CSS files are now in the code base.
echo "\nCreating CSS files....\n";

cqpweb_regenerate_css_files();

echo "\n--- done.\n";
 */



echo "\nCreating built-in mapping tables....\n";

regenerate_builtin_mapping_tables();

echo "\n--- done.\n";



/*
 * If more setup actions come along, add them here
 * (e.g. annotation templates, xml templates...
 */

echo "\nAutosetup complete; you can now start using CQPweb.\n";

cqpweb_shutdown_environment();

exit(0);


