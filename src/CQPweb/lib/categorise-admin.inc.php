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
 * This script does a number of different things, depending on the value of GET->categoriseAction .
 * 
 */

/* ------------ *
 * BEGIN SCRIPT *
 * ------------ */

/* initialise variables from settings files  */
require('../lib/environment.inc.php');


/* include function library files */
require('../lib/library.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/cache.inc.php');
require('../lib/db.inc.php');
require('../lib/user-lib.inc.php');

require("../lib/cwb.inc.php");
require("../lib/cqp.inc.php");

cqpweb_startup_environment();





/* choose script action on the basis of the programmed action */
switch ($_GET['categoriseAction'])
{
	case 'enterCategories':
		categorise_enter_categories();
		break;
	
	case 'createQuery':
		categorise_create_query();
		break;
	
	case 'updateQueryAndLeave':
		categorise_update();
		header("Location: index.php?thisQ=categorisedQs&uT=y");
		break;
		
	case 'updateQueryAndNextPage':
		categorise_update();
		$inputs = url_printget(array(
			array('uT', ''),
			array('categoriseAction', ''),
			array('pageNo', (string)(isset($_GET['pageNo']) ? (int)$_GET['pageNo'] + 1 : 2)) 
			));
		header("Location: concordance.php?program=categorise&$inputs&uT=y");
		break;
		
	case 'noUpdateNewQuery':
		header("Location: index.php");
		break;	

	case 'separateQuery':
		categorise_separate();
		header("Location: index.php?thisQ=savedQs&uT=y");
		break;
		
	case 'enterNewValue':
		categorise_enter_new_value();
		break;
		
	case 'addNewValue':
		categorise_add_new_value();
		header("Location: index.php?thisQ=categorisedQs&uT=y");
		break;
	
	case 'deleteCategorisedQuery':
		categorise_delete_query();
		header("Location: index.php?thisQ=categorisedQs&uT=y");
		break;
		
	default:
		echo '<p>categorise-admin.php was passed an invalid parameter as categoriseAction! CQPweb aborts.</p>';
		break;
}


cqpweb_shutdown_environment();


/* ------------- *
 * END OF SCRIPT *
 * ------------- */













function categorise_create_query()
{
	global $Config;
	global $User;
	global $Corpus;

	/* get values from $_GET */
	
	/* qname to begin with = qname */
	$qname = safe_qname_from_get();

	if (false === QueryRecord::new_from_qname($qname))
		exiterror_general("The specified query $qname was not found in cache!");
	

	if(isset($_GET['defaultCat']))
		$default_cat_number = (int)$_GET['defaultCat'];
	else
		$default_cat_number = 0;


	/* check there is a savename for the catquery and it contains no badchars */
	if (empty($_GET['categoriseCreateName']))
	{
		categorise_enter_categories('no_name');
		cqpweb_shutdown_environment();
		exit();
	}
	$savename = $_GET['categoriseCreateName'];
	if ( (! cqpweb_handle_check($savename)) || 100 < strlen($savename))
	{
		categorise_enter_categories('bad_names');
		cqpweb_shutdown_environment();
		exit();
	}
	
	/* make sure no catquery of that name already exists */
	if (save_name_in_use($savename))
	{
		categorise_enter_categories('name_exists');
		cqpweb_shutdown_environment();
		exit();
	}

	
	$categories = array();
	for ($i = 1 ; $i < 7 ; $i++)
	{
		$thiscat = (isset($_GET["cat_$i"]) ? $_GET["cat_$i"] : ''); 

		/* skip any zero-length cats */
		if ($thiscat === '')
			continue;
		/* skip the defaults if they have been entered */
		if ($thiscat == 'other' || $thiscat == 'unclear')
			continue;
		/* make sure there are no non-word characters in the name of each category */
		if ( (! cqpweb_handle_check($thiscat) ) || 99 < strlen($thiscat))
		{
			categorise_enter_categories('bad_names');
			cqpweb_shutdown_environment();
			exit();
		}
		/* make sure there are no categories that are the same */
		if (in_array($thiscat, $categories))
		{
			categorise_enter_categories('cat_repeated');
			cqpweb_shutdown_environment();
			exit();
		}
		/* this cat is OK! */
		$categories[$i] = $thiscat;
	}
	/* make sure there actually exist some categories */
	if (count($categories) == 0)
	{
		categorise_enter_categories('no_cats');
		cqpweb_shutdown_environment();
		exit();
	}
	$categories[] = 'other';
	$categories[] = 'unclear';
	$cat_list = implode('|', $categories);


	/* save the current query using a new qname name that was set for categorised query */
	$newqname = qname_unique($Config->instance_name);
	if (false === ($record = copy_cached_query($qname, $newqname)))
		exiterror("Unable to copy query data for new categorised query!");
	
	/* get the query record for the newly-saved query */
	
	/* and update it */
	$record->user = $User->username;
	$record->saved = CACHE_STATUS_CATEGORISED;
	$record->save_name = $savename;
	$record->set_time_to_now();
	$record->save();
	
	/* and refresh CQP's listing  of queries in the cache directory */
	refresh_directory_global_cqp();

	
	/* create a db for the categorisation */
	$dbname = create_db(new DbType(DB_TYPE_CATQUERY), $newqname, $record->cqp_query, $record->query_scope, $record->postprocess);

	/* if there is a default category, set that default on every line */
	if ($default_cat_number != 0)
		do_mysql_query("update $dbname set category = '{$categories[$default_cat_number]}'");


	/* create a record in saved_catqueries that links the query and the db */
	$sql = "insert into saved_catqueries (catquery_name, user, corpus, dbname, category_list) 
					values ('$newqname', '{$User->username}', '{$Corpus->name}', '$dbname', '$cat_list')";
	do_mysql_query($sql);

	header("Location: concordance.php?qname=$newqname&program=categorise&uT=y");

}




function categorise_update()
{
	$qname = safe_qname_from_get();
	
	$dbname = catquery_find_dbname($qname);
	
	foreach ($_GET as $key => $val)
	{
		if ( preg_match('/^cat_(\d+)$/', $key, $m) < 1)
			continue;
		$refnumber = $m[1];
		unset($_GET[$key]);
		$selected_cat = preg_replace('/\W/', '', $val);
		/* the above is easier than mysql_real_escape_string because we KNOW that all real cats are \w-only by definition. */
		
		/* don't update if all we've been passed for this concordance line is an empty string */
		if (empty($selected_cat))
			continue;
		
		do_mysql_query("update $dbname set category = '$selected_cat' where refnumber = $refnumber");
	}
	
	/* and finish by ... */
	touch_db($dbname);
	touch_cached_query($qname);
}





function categorise_separate()
{
	global $Config;
	global $User;
	global $Corpus;
	
	global $cqp;
	
	$qname = safe_qname_from_get();

	/* check that the query in question exists and is a catquery */
	$query_record = QueryRecord::new_from_qname($qname);
	if ($query_record === false || $query_record->saved != CACHE_STATUS_CATEGORISED)
		exiterror_general("The specified categorised query \"$qname\" was not found!");
	
	
	$dbname = catquery_find_dbname($qname);
	
// TODO : the following is deeply shonky. Something should be done about it.
	/* we DO NOT use a unique ID from instance_name, because we want to be able to 
	 * delete this query later if the mother-query is re-separated. See below. */
	$newqname_root = $qname . '_';
	$newsavename_root = $query_record->save_name . '_';

	$outfile_path = "{$Config->dir->cache}/temp_cat_$newqname_root.tbl";
	if (is_file($outfile_path))
		unlink($outfile_path);

	
	/* MAIN LOOP for this function :  applies to every category in the catquery we are dealing with */
	
	foreach(catquery_list_categories($qname) as $category)
	{
		$newqname = $newqname_root . $category;
		/* if the query exists... (note, we wouldn't normally overwrite, but for separation we do */
		delete_cached_query($newqname);
		/* we also want to eliminate any existing DBs based on this query, 
		 * so any data based on a previous separation is removed */
		delete_dbs_of_query($newqname);
		
		refresh_directory_global_cqp();
		
		$newsavename = $newsavename_root . $category;
		
		/* create the dumpfile & obtain solution count */
		$solution_count = do_mysql_outfile_query("SELECT beginPosition, endPosition FROM $dbname WHERE category = '$category'", $outfile_path);
		
		if ($solution_count < 1)
		{
			unlink($outfile_path);
			continue;
		}	
		
		$cqp->execute("undump $newqname < '$outfile_path'");
		$cqp->execute("save $newqname");

		unlink($outfile_path);
		
		
		/* create, update and then save a new query record. */
		$new_record = clone $query_record;
		$new_record->postprocess_append("cat[$category]", $solution_count);
		$new_record->qname = $newqname;
		$new_record->file_size = cqp_file_sizeof($newqname);
		$new_record->saved = CACHE_STATUS_SAVED_BY_USER;
		$new_record->save_name = $newsavename;
		$new_record->set_time_to_now();
		$new_record->save();

	}
}




/** categorise-admin: delete the database, the cached query, and the record in saved_catqueries */
function categorise_delete_query()
{
	$qname = safe_qname_from_get();

	list($dbname) = mysql_fetch_row(do_mysql_query("select dbname from saved_catqueries where catquery_name = '$qname'"));

	delete_db($dbname);

	delete_cached_query($qname);
	
	do_mysql_query("delete from saved_catqueries where catquery_name = '$qname'");
}







function categorise_add_new_value()
{
	$qname = safe_qname_from_get();

	if (isset($_GET['newCategory']))
		$new_cat = mysql_real_escape_string($_GET['newCategory']);
	else
		exiterror('Critical parameter "newCategory" was not defined!');
	
	if (! cqpweb_handle_check($new_cat))
		exiterror('The category name you tried to add contains spaces or punctuation. '
					. 'Category labels can only contain unaccented letters, digits, and the underscore.');
	if (99 < strlen($new_cat))
		exiterror('The category name you tried to add is too long. Category labels can only be 99 letters long at most.');


	/* get the current list of categories */
	$category_list = catquery_list_categories($qname);
	
	/* adjust the category list */
	if (in_array($new_cat, $category_list))
		return;
	foreach($category_list as $i => $c)
		if ($c == 'other' || $c == 'unclear')
			unset($category_list[$i]);
	$category_list[] = $new_cat;
	$category_list[] = 'other';
	$category_list[] = 'unclear';
	
	$cat_list_string = implode('|', $category_list);
	
	do_mysql_query("update saved_catqueries set category_list = '$cat_list_string' where catquery_name='$qname'");
	
	/* and finish by ... */
	$dbname = catquery_find_dbname($qname);
	touch_db($dbname);
	touch_cached_query($qname);
}





/** categorise-admin: this function prints a page with a simple form for a new categorisation value to be entered */
function categorise_enter_new_value()
{
	global $Config;

	$qname = safe_qname_from_get();

	echo print_html_header('Categorise Query -- CQPweb', $Config->css_path, array('cword'));

	?>
	<form action="redirect.php" method="get">
		<table class="concordtable" width="100%">
			<tr>
				<th colspan="2" class="concordtable">
					Add a category to the existing set of categorisation values for this query
				</th>
			</tr>
			<tr>
				<td class="concordgrey">
					&nbsp;<br/>
					Current categories:
					<br/>&nbsp;
				</td>
				<td class="concordgeneral">
					<em>
						<?php echo implode(', ', catquery_list_categories($qname)); ?>
					</em>
				</td>
			</tr>
			<tr>
				<td class="concordgrey">
					&nbsp;<br/>
					New category:
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" >
					&nbsp;<br/>
					<input type="text" name="newCategory" maxlength="99"/>
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" align="center" colspan="2">
					<input type="submit" value="Submit" />
				</td>
			</tr>
		</table>
		<input type="hidden" name="qname" value="<?php echo $qname; ?>"/>
		<input type="hidden" name="redirect" value="categorise"/>
		<input type="hidden" name="categoriseAction" value="addNewValue"/>
		<input type="hidden" name="uT" value="y" />
	</form>
	
	<?php
	
	echo print_html_footer('categorise');	
}




/**
 * This function prints a webpage enabling the user to enter their category names;
 * passing it an error argument affects the display in various ways,
 * but it will always produce a full webpage.
 */
function categorise_enter_categories($error = NULL)
{
	global $Config;

	$qname = safe_qname_from_get();


	/* if an error is specified, an error message is printed at the top, and the values from GET are re-printed */
	switch($error)
	{

		case 'no_name':
			$error_message = 'You have not entered a name for your query result! Please amend the settings below.';
			break;
		case 'bad_names':
			$error_message = 'Query names and category labels can only contain letters, numbers and the underscore character' .
				' (&ldquo;_&rdquo;)! Moreover, they can only be 100 letters long (the query name) or' .
				' 99 letters long (the categories). Please amend the badly-formed name(s) below (an alternative has been suggested).';
			break;
		case 'no_cats':
			$error_message = 'You have not entered any categories! Please add some category names below.';
			break;
		case 'name_exists':
			$error_message = 'A categorised or saved query with the name you specified already exists! Please choose a different name.';
			break;
		case 'cat_repeated':
			$error_message = 'You have entered the same category more than once! Please double-check your category names.';
			break;
	
		/* note that default includes "NULL", which is the norm */
		default:
			break;	
	}

	echo print_html_header('Categorise Query -- CQPweb', $Config->css_path)

	?>

	<form action="redirect.php" method="get">
		<table class="concordtable" width="100%">
			<tr>
				<th colspan="2" class="concordtable">Categorise query results</th>
			</tr>
			<?php
			if (!empty($error_message))
				echo "\n", '<tr><td class="concorderror" colspan="2"><strong>Error!</strong><br/>' , $error_message , "</td></tr>\n";
			?>
			<tr>
				<td class="concordgrey">
					&nbsp;<br/>
					Please enter a name for this set of categories:
					<br/>&nbsp;
				</td>
				<td class="concordgeneral" align="center">
					<input type="text" name="categoriseCreateName" size="34" maxlength="100"
					<?php 
					if ($error !== NULL && isset($_GET['categoriseCreateName']))
						echo 'value="' , substr(preg_replace('/\W/', '', $_GET['categoriseCreateName']), 0, 100) , '"';
					?>
					/>
				</td>
			</tr>
			<tr>
				<th class="concordtable">List category labels:</th>
				<th class="concordtable">Default category?</th>
			</tr>
		<?php
		for($i = 1 ; $i < 7 ; $i++)
		{
			$val = '';
			$selected = '';
			
			if ($error !== NULL && isset($_GET["cat_$i"]))
				$val = 'value="' . substr(preg_replace('/\W/', '', $_GET["cat_$i"]), 0, 99) . '"';
			if ($error !== NULL && $_GET["defaultCat"] == $i)
				$selected = 'checked="checked"';
				
			echo "
			<tr>
				<td class=\"concordgeneral\" align=\"center\">
					<input type=\"text\" name=\"cat_$i\" size=\"34\" maxlength=\"99\" $val>
				</td>
				<td class=\"concordgeneral\" align=\"center\">
					<input type=\"radio\" name=\"defaultCat\" value=\"$i\" $selected>
				</td>
			</tr>";
		}
		?>
			<tr>
				<td class="concordgeneral" align="center" colspan="2">
					<input type="submit" value="Submit" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="concordgrey">
					<strong>Instructions</strong>
					<br/>&nbsp;<br/>
					<ul>
						<li>
							Names can only contain letters, numbers and the underscore character (
							<strong>_</strong> ) and can be at most 99 letters long. 
						</li>
						<li>
							The categories <strong>Unclear</strong> and <strong>Other</strong>
							will be automatically added to the list
						</li>
						<li>
							Selecting a default category will mean that all hits will be automatically 
							set to this value. This can be useful if you expect most of the hits
							to belong to one particular category. However, it will mean that you 
							have to go through the <em>complete</em> set of concordances (and not only 
							the first x number of hits of a randomly-ordered query result).
						</li>
						<li>
							You can add additional categories at any time.
						</li>
					</ul>
				</td>
			</tr>
		</table>
		<input type="hidden" name="qname" value="<?php echo $qname; ?>"/>
		<input type="hidden" name="redirect" value="categorise"/>
		<input type="hidden" name="categoriseAction" value="createQuery"/>
		<input type="hidden" name="uT" value="y" />
	</form>
	
	<?php
	echo print_html_footer('categorise');

}
