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



/* ------------ *
 * BEGIN SCRIPT *
 * ------------ */

/* like similar scripts, this delays writing to stdout until the end because of dual output formats */


/* initialise variables from settings files  */
require('../lib/environment.inc.php');


/* include function library files */
require('../lib/library.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/metadata.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/cache.inc.php');
require('../lib/concordance-lib.inc.php');
require('../lib/concordance-post.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/db.inc.php');
require('../lib/user-lib.inc.php');
require("../lib/cwb.inc.php");
require("../lib/cqp.inc.php");

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP );






/* ------------------------------- *
 * initialise variables from $_GET *
 * and perform initial fiddling    *
 * ------------------------------- */



$qname = safe_qname_from_get();
/* now get all the info about the query in one handy package */
// $query_record = check_cache_qname($qname);
$query_record = QueryRecord::new_from_qname($qname);
if ($query_record === false)
	exiterror_general("The specified query $qname was not found in cache!");


/* $sql_position == the root of the SQL fieldname for the thing we are breaking down */
$int_position = (int)(isset ($_GET['concBreakdownAt']) ? $_GET['concBreakdownAt'] : '0');
$sql_position = sqlise_integer_position($int_position);



/* what attribute are we breaking down? */
switch (isset ($_GET['concBreakdownOf']) ? $_GET['concBreakdownOf'] : 'words')
{
case 'annot':
case 'both':
	$primary_annotation = get_corpus_metadata('primary_annotation');
	$breakdown_of = $_GET['concBreakdownOf'];
	if (empty($primary_annotation))
		exiterror_general('You cannot do a frequency breakdown based on annotation, ' 
			. 'because no primary annotation is specified for this corpus.');
	break;
case 'p_att':
	// TODO this is really just a placeholder for a requested feature.... 
	exiterror_general("Frequency breakdown of a specific annotation is not yet available, sorry.");
default:
	/* inc. case 'words' */
	$breakdown_of = 'words';
	break;
}



/* we can now set up the info relating to the above in the following array... */
$breakdown_of_info = array(
	'words' => array('desc'=>'words',               
					'sql_label'=> "$sql_position",
					'sql_groupby'=> "$sql_position"
					),
	'annot' => array('desc'=>'annotation',
					'sql_label'=> "tag$sql_position",
					'sql_groupby'=> "tag$sql_position"
				   ),
	'both'  => array('desc'=>'both words and annotation', 
					'sql_label'=> "concat($sql_position,'_',tag$sql_position)",
					'sql_groupby'=> "$sql_position, tag$sql_position"
					)
	);



/* do we want a nice HTML table or a downloadable table? */
$download_mode = (isset($_GET['tableDownloadMode']) && $_GET['tableDownloadMode'] == 1);



/* per page and page numbers */
if (isset($_GET['pageNo']))
	$_GET['pageNo'] = $page_no = prepare_page_no($_GET['pageNo']);
else
	$page_no = 1;

if (isset($_GET['pp']))
	$per_page = prepare_per_page($_GET['pp']);   /* filters out any invalid options */
else
	$per_page = $Config->default_per_page;
/* note use of same variables as used in a concordance */

/* but of course if this is a text download, all the above is totally ignored. */
$limit_string = ($download_mode ? '' : ("LIMIT ". ($page_no-1) * $per_page . ', ' . $per_page));





/* does a db for the sort exist? 
 * 
 * Search the db list for a db whose parameters match those of the query
 * named as qname; if it doesn't exist, we need to create one 
 */
$db_record = check_dblist_parameters(new DbType(DB_TYPE_SORT), $query_record->cqp_query, $query_record->query_scope, $query_record->postprocess);
/*
 * Note that this ensures that sorted queries will get a second sort DB created.... 
 * that way, if there has been a sort-thin, it will be applied here.
 * 
 * This is somewhat wasteful of space for the case in which we have sorted, but not filtered,
 * before applying frequency-breakdown. However, the gain in simplicity of implementation is worth it.
 */

if ($db_record === false)
{
	$is_new_db = true;
	$dbname = create_db(new DbType(DB_TYPE_SORT), $qname, $query_record->cqp_query, $query_record->query_scope, $query_record->postprocess);
	$db_record = check_dblist_dbname($dbname);
}
else
{
	$is_new_db = false;
	$dbname = $db_record['dbname'];
	touch_db($dbname);
}
/* this dbname & its db_record can be globalled by print functions in the script */


/* find out how big the db is: types and tokens */
$sql = "select count({$breakdown_of_info[$breakdown_of]['sql_label']})          as tokens, 
			count(distinct({$breakdown_of_info[$breakdown_of]['sql_label']}))   as types 
			from $dbname";
list($db_tokens_total, $db_types_total) = mysql_fetch_row(do_mysql_query($sql));





$sql = "select {$breakdown_of_info[$breakdown_of]['sql_label']} as n, 
	count({$breakdown_of_info[$breakdown_of]['sql_label']}) as sum 
	from $dbname group by {$breakdown_of_info[$breakdown_of]['sql_groupby']} 
	order by sum desc
	$limit_string";
$result = do_mysql_query($sql);




/* Build the description of what we're displaying. */
$query_heading = $query_record->print_solution_heading(true);

$sing_type = (1 == $db_types_total ? '' : 's');

$breakdown_heading 
	= "Showing frequency breakdown of {$breakdown_of_info[$breakdown_of]['desc']} in this query"
	. ('node' == $sql_position ? ', at the query node' : ', at position ' . stringise_integer_position($int_position) ) 
	. '; there ' . ($sing_type!='s' ? 'is ' : 'are ') 
	. number_format((float)$db_types_total) . " different type$sing_type and " 
	. number_format((float)$db_tokens_total) . " tokens at this concordance position."
	;



/* 
 * Time to print the result: we have three options: no display, display HTML, downlaod plaintext
 */
if (mysql_num_rows($result) < 1)
{
	/* normal cause of this: we have overflowed the page number. */
	if ($page_no > 1)
		exiterror_general("You requested a page of the frequency breakdown that appears to be empty!"); 
	else
		exiterror_general("Your frequency breakdown request produced no results. This may indicate a database error. \n"
			. "You should contact the system administrator.\n");
	/* if the query is empty, it suggests the database was not created properly.... */
}
else if ($download_mode)
{
	freqbreakdown_write_download($result, "$query_heading.<br/><br/>$breakdown_heading<br/>", $db_tokens_total);
}
else
{
	/* ----------------------------------------------------- *
	 * create the control row for concordance freq breakdown *
	 * ----------------------------------------------------- */
	$num_of_pages = (int)($db_types_total / $per_page) + (($db_types_total % $per_page) > 0 ? 1 : 0 ); 

	/* now, create backards-and-forwards-links */
	$marker = array( 'first' => '|&lt;', 'prev' => '&lt;&lt;', 'next' => "&gt;&gt;", 'last' => "&gt;|" );
	
	/* work out page numbers */
	$nav_page_no['first'] = ($page_no == 1 ? 0 : 1);
	$nav_page_no['prev']  = $page_no - 1;
	$nav_page_no['next']  = ($num_of_pages == $page_no ? 0 : $page_no + 1);
	$nav_page_no['last']  = ($num_of_pages == $page_no ? 0 : $num_of_pages);
	/* all page numbers that should be dead links are now set to zero  */


	$navlinks = '';
	foreach ($marker as $key => $m)
	{
		$navlinks .= '<td align="center" class="concordgrey"><b><a class="page_nav_links" ';
		if ( $nav_page_no[$key] != 0 )
			/* this should be an active link */
			$navlinks .= 'href="redirect.php?redirect=breakdown&'
				. url_printget(array(
					array('uT', ''), array('pageNo', $nav_page_no[$key]), array('qname', $qname)
					) )
				. '&uT=y"';
		$navlinks .= ">$m</b></a></td>";
	}
	

	$return_option = ($sql_position=='node' 
					  ? '<option value="concBreakdownNodeSort">Show hits sorted by node</option>'
					  : '<option value="concBreakdownPositionSort">Show hits sorted on position '
					  		. stringise_integer_position($int_position). '</option>'
					 );
	
	switch($breakdown_of)
	{
	case 'words':
		$breakdown_of_options 
				=  '<option value="concBreakdownWords" selected="selected">Frequency breakdown of words only</option>
					<option value="concBreakdownAnnot">Frequency breakdown of annotation only</option>
					<option value="concBreakdownBoth" >Frequency breakdown of words and annotation</option>';
		break;
	case 'annot':
		$breakdown_of_options 
				=  '<option value="concBreakdownWords">Frequency breakdown of words only</option>
					<option value="concBreakdownAnnot" selected="selected">Frequency breakdown of annotation only</option>
					<option value="concBreakdownBoth" >Frequency breakdown of words and annotation</option>';
		break;
	case 'both':	
		$breakdown_of_options 
				=  '<option value="concBreakdownWords">Frequency breakdown of words only</option>
					<option value="concBreakdownAnnot">Frequency breakdown of annotation only</option>
					<option value="concBreakdownBoth" selected="selected" >Frequency breakdown of words and annotation</option>';
		break;
	}
	
	$freq_breakdown_controls = '
		<form action="redirect.php" method="get">
			<td class="concordgrey">
				Breakdown position: 
				<select name="concBreakdownAt">
					' . print_sort_position_options(integerise_sql_position($sql_position)) . '
				</select>
			</td>
			<td class="concordgrey">
				<select name="redirect">
					' . $breakdown_of_options . '
					<option value="concBreakdownDownload">Download frequency breakdown table (for ' . $breakdown_of_info[$breakdown_of]['desc'] . ')</option>
					' . $return_option . '
					<option value="newQuery">New query</option>
				</select>

				' . url_printinputs(array(
						array('concBreakdownAt', ''), array('tableDownloadMode', ''), array('redirect', ''), array('uT', ''), array('qname', $qname)
					) ) 
				. '

				<input type="submit" value="Go!"/>

			</td>
			<input type="hidden" name="uT" value="y"/>
		</form>
		';


	/* ------------------------------------------------------------ *
	 * end of create the control row for concordance freq breakdown *
	 * ------------------------------------------------------------ */
	
	
	/* now, put it all together into a pretty HTML page! */
	
	echo print_html_header($Corpus->title . ' -- CQPweb Query Frequency Breakdown', $Config->css_path);
	
	?>
	
		<table class="concordtable" width="100%">
			<tr>
				<th colspan="6" class="concordtable"><?php echo $query_heading; ?></th>
			</tr>
			<tr>
				<th colspan="6" class="concordtable"><?php echo $breakdown_heading; ?></th>
			</tr>
			<tr>

				<?php echo $navlinks, $freq_breakdown_controls; ?>

			</tr>
		</table>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable" align="left">No.</th>
				<th class="concordtable" align="left">Search result</th>
				<th class="concordtable">No. of occurrences</th>
				<th class="concordtable">Percent</th>
			</tr>
			
			<?php
			
			for ( $i = (($page_no-1)*$per_page)+1 ; ($r=mysql_fetch_object($result)) !== false ; $i++ )
			{
				$percent = round(($r->sum / $db_tokens_total)*100, 2);
				
				switch($breakdown_of)
				{
				case 'words':
					$iF = urlencode($r->n);
					$iT = '';
					break;
				case 'annot':
					$iF = '';
					$iT = urlencode($r->n);
					break;
				case 'both':
					preg_match('/\A(.*)_([^_]+)\z/', $r->n, $m);
					$iF = urlencode($m[1]);
					$iT = urlencode($m[2]);
					break;
				}
				$link = "concordance.php?qname=$qname&newPostP=item&newPostP_itemPosition=$int_position&newPostP_itemForm=$iF&newPostP_itemTag=$iT&uT=y";
				
				echo "\n<tr>\n"
					, "\t<td class=\"concordgrey\">$i</td>\n"
					, "\t<td class=\"concordgeneral\"><a href=\"$link\">", escape_html($r->n), "</a></td>\n"
					, "\t<td class=\"concordgeneral\" align=\"center\">{$r->sum}</td>\n"
					, "\t<td class=\"concordgeneral\" align=\"center\">$percent%</td>\n"
					, "</tr>\n\n"
					;
			
			}
			?>
			
		</table>
		
	<?php
	
	
	/* create page end HTML */
	echo print_html_footer('hello');
	
} /* end of if / else tree for "doing something with the result of the main SQL query" */


cqpweb_shutdown_environment();


/* ------------- *
 * END OF SCRIPT *
 * ------------- */


function freqbreakdown_write_download($result, $description, $total_for_percent)
{
	global $User;
	$eol = get_user_linefeed($User->username);
	$description = preg_replace('/&[lr]dquo;/', '"', $description);
	$description = preg_replace('/<\/?em>/', '', $description);
	$description = str_replace('<br/>', $eol, $description);

	header("Content-Type: text/plain; charset=utf-8");
	header("Content-disposition: attachment; filename=concordance_frequency_breakdown.txt");

	echo "$description$eol";
	echo "__________________$eol$eol";
	echo "No.\tSearch result\tNo. of occurrences\tPercent";
	echo "$eol$eol";


	for ( $i = 1 ; false !== ($r = mysql_fetch_row($result)) ; $i++)
	{
		$percent = round(($r[1] / $total_for_percent)*100, 2);
		echo "$i\t{$r[0]}\t{$r[1]}\t$percent$eol";
	}
}



