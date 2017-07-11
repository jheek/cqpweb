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
 * admin-do: for actions with a bit more checking needed than just the gateway to execute
 * that we have in admin-execute...
 */


require('../lib/environment.inc.php');


/* include function library files */
require("../lib/library.inc.php");
require("../lib/admin-lib.inc.php");
require("../lib/exiterror.inc.php");
require("../lib/user-lib.inc.php");



cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP);

/*
 * all functions operate out of this switch.
 * 
 * Values are the same as in admin-execute, except that here,
 * the contents of the cases can include things that
 * reference the environment.
 */
switch ($_GET['admFunction'])
{
	// none yet, but fundamentally useful to have this....
	
	
	
}

/* ENDSWITCH main switch for this script */


cqpweb_shutdown_environment();

/* this is a pageless admin script, so we use a Location redirect instead of a page end */
if (isset($next_location))
	set_next_absolute_location($next_location);

exit();

