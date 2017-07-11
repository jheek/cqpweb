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
 * This file contains the script for actions affecting corpus settings etc.
 * 
 * Currently, mnay of these things are done using execute.php.
 * 
 * However, once people can index their own corpora, this will not be an option:
 * we do not allow non-admin users to use that script!
 */

require('../lib/environment.inc.php');


/* include all function files */
include('../lib/admin-lib.inc.php');
include('../lib/cqp.inc.php');
include('../lib/cwb.inc.php');
include('../lib/exiterror.inc.php');
include('../lib/freqtable.inc.php');
include('../lib/html-lib.inc.php');
include('../lib/library.inc.php');
include('../lib/metadata.inc.php');
include('../lib/user-lib.inc.php');
include('../lib/templates.inc.php');
include('../lib/xml.inc.php');


cqpweb_startup_environment( CQPWEB_STARTUP_DONT_CONNECT_CQP , RUN_LOCATION_CORPUS );


/* check: if this is a system corpus, only let the admin use. */
if (true /* user corpus test to go here */ )
	if (!$User->is_admin())
		exiterror("Non-admin users do not have permission to perform this action.");
/* TODO: when we have user-corpora, users should be able to call this script on their own corpora. 
 * That is why we do it here, rather than by just asking cqpweb_startup_environment() to do it for us. */


/* set a default "next" location..." */
$next_location = "index.php?thisQ=corpusSettings&uT=y";
/* cases are allowed to change this */

$script_action = isset($_GET['caAction']) ? $_GET['caAction'] : false; 


switch ($script_action)
{

case 'updateVisibility':
	
	if (!isset($_GET['newVisibility']))
		exiterror("Missing parameter for new Visibility setting.");

	update_corpus_visible($_GET['newVisibility'], $Corpus->name);

	break;


default:

	exiterror("No valid action specified for corpus administration.");
	break;


} /* end the main switch */



if (isset($next_location))
	set_next_absolute_location($next_location);

cqpweb_shutdown_environment();

exit(0);


/* ------------- *
 * END OF SCRIPT *
 * ------------- */

