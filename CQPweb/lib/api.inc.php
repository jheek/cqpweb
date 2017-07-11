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
 * This file contains the main script for the CQPweb API-via-HTTP.
 * 
 * It processes incoming requests and calls other bits of CQPweb in
 * such a way as to send back the results of each function in
 * accordance with the API documentation.
 * 
 * This is generally as plain text (easily explode()-able or otherwise
 * manipulable within Perl or PHP).
 */

require('../lib/environment.inc.php');


/* include function library files */
require('../lib/library.inc.php');
require('../lib/concordance-lib.inc.php');
require('../lib/concordance-post.inc.php');
require('../lib/ceql.inc.php');
require('../lib/metadata.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/cache.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/db.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/cwb.inc.php');
require('../lib/cqp.inc.php');

/*
 * Important note: what about exiterror? should we engage a special mode where it writes
 * a more machine-readable error message?
 */

/* ensure error messages go in text format */
$debug_messages_textonly = true;
// this is ok for now but in the long rn we may want a special mode for API where a more formal error is returned
// in the HTTP response.


if (!url_string_is_valid())
	exiterror_bad_url();


/*
 * This switch runs across functions available within the web-api.
 * 
 * Each option assembles the _GET as appropiate, then include()s the right other file.
 * 
 * If the included file produces output that we don't want, then we need to turn on
 * output buffering, capture the HTML, extract what we need from it, then throw it away.
 * e.g. the qname from the concordance output in the query function.
 * 
 * In some cases, just writing a simpler version of the script may be justifiable.
 */



switch($_GET['function'])
{
case 'query':
	/* run a query */
	//return value: the query name.
	// Allow it to be auto-saved under a specified save-name as well??
	break;
	/* endcase query */
	
	
case 'concordance':
	/* print out a concordance for an existing query */
	// interface to concordance download.
	break;
	/* endcase concordance */
	
case 'collocationTable':
	/* get a collocation table for an existing query */
	break;
case 'collocationSoloInfo':
	/* interface to collocation solo mode */
	break;
case 'collocationThin':
	/* postprocess the query according to a specified collocate */
	break;
case 'distributionTable':
	break;
case 'frequencyBreakdownTable':
	break;
/* insert other cases HERE */

/*
 * cases to be added include:
 * 
 * postprocesses - each one should return the new query name.
 * interrogateCache - check whether a cached query still exists.
 */

default:
	// send error code: bad function.
	break;
}

?>
