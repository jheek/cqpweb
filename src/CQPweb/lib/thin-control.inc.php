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
 * This script prints a form to collect the options for numerically-thinning a query.
 */





require('../lib/environment.inc.php');


/* include function library files */
require("../lib/library.inc.php");
require("../lib/html-lib.inc.php");
require("../lib/exiterror.inc.php");
require("../lib/subcorpus.inc.php");
require("../lib/cqp.inc.php");
require("../lib/cache.inc.php");
require("../lib/user-lib.inc.php");

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP);




/* check parameters - only one we really need is qname */

$qname = safe_qname_from_get();

/* get the query record so we can find out how many hits we are thinning */
$query_record = QueryRecord::new_from_qname($qname);
if (false === $query_record)
	exiterror_general("The specified query $qname was not found in cache!");

$hits = $query_record->hits();

$num_of_hits_text = '(current no. of instances: ' . number_format((float)$hits) . ')';

$reproducible_is_selected = (bool) $User->thin_default_reproducible;


/*
 * render interface
 */

echo print_html_header($Corpus->title . ' -- CQPweb Thinning Options', $Config->css_path, array('cword'));


?>


<table width="100%" class="concordtable">
	<form action="concordance.php" method="get">
		<tr>
			<th colspan="4" class="concordtable">
				Choose options for thinning your query <?php echo $num_of_hits_text; ?>
			</th>
		</tr>
		<tr>
			<td class="concordgrey">
				Thinning method:
			</td>
			<td class="concordgrey">
				<select name="newPostP_thinReallyRandom">
					<option value="0"<?php if ($reproducible_is_selected)  echo ' selected="selected";'?>>
						random (selection is reproducible)
					</option>
					<option value="1"<?php if (!$reproducible_is_selected) echo ' selected="selected";'?>>
						random (selection is not reproducible)
					</option>
				</select>
			</td>
			<td class="concordgrey">
				<input type="text" name="newPostP_thinTo"/>
				(number of instances or percentage)
			</td>
			<td class="concordgrey">
				<input type="submit" value="Thin this query"/>
			</td>
		</tr>
		<input type="hidden" name="qname" value="<?php echo $qname; ?>"/>
		<input type="hidden" name="newPostP" value="thin"/>
		<input type="hidden" name="newPostP_thinHitsBefore" value="<?php echo $hits; ?>"/>
		<?php 
		if (isset($_GET['viewMode']))
			echo '<input type="hidden" name="viewMode" value="' . $_GET['viewMode'] . '"/>';
		/* does anything else from GET need passing on? */
		?>
		
		<input type="hidden" name="uT" value="y" />
	</form>
</table>



<?php


/* create page end HTML */
echo print_html_footer('thin');


cqpweb_shutdown_environment();

