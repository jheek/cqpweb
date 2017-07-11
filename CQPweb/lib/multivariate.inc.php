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
 * Library of functions supporting multivariate analysis operations 
 * and user-created feature matrices.
 */



/*
 * 
 * FEATURE MATRIX OBJECT FUNCTIONS
 * 
 */




/**
 * Get a list of all feature matrices.
 * 
 * Returns an array consisting of stdClass objects, each of which
 * contains the MySQL fields for a single saved feature matrix.
 * 
 * The array is ordered alphabetically by savename, but the ID numbers of the matrices are
 * also available (given as array keys).
 * 
 * @param $corpus  If not an empty value, only feature matrices from the given corpus will be returned.
 *                 Default: empty (all corpora).
 * @param $user    If not an empty value, only feature matrices belonging to the given user will be returned.
 *                 Default: empty (all users).
 * @return array   Array containing object list.
 */
function list_feature_matrices($corpus = NULL, $user = NULL)
{
	$list = array();
	
	if (empty($corpus))
	{
		if (!empty($user))
			$where = ' where user = \'' . mysql_real_escape_string($user) . '\' ';
	}
	else
	{
		$where = ' where corpus = \'' . mysql_real_escape_string($corpus) . '\' ';
		if (!empty($user))
			$where .= ' and user = \'' . mysql_real_escape_string($user) . '\' ';	
	}
	
	$result = do_mysql_query("select * from saved_matrix_info $where order by savename asc");	
	
	while (false !== ($o = mysql_fetch_object($result)))
		$list[$o->id] = $o;
	
	return $list;
}


/**
 * Delete a specified feature matrix - identified by unique integer ID.
 */
function delete_feature_matrix($id)
{
	$id = (int)$id;
	
	/* first, delete the actual data table. */
	$table = feature_matrix_id_to_tablename($id);
	do_mysql_query("drop table if exists $table");
	
	/* now, delete all the rows containing information about this fm's variables. */
	do_mysql_query("delete from saved_matrix_features where matrix_id = $id");
	
	/* finally, delete the database row itself. */
	do_mysql_query("delete from saved_matrix_info where id = $id");
}

///**
// * Translates a feature matrix description to an ID number.
// * Returns false if no matching entry found. 
// */
//function lookup_feature_matrix_id($corpus, $user, $savename)
//{
//	//TODO
//	
//}



/**
 * Gets a DB object corresponding to the specified feature matrix.
 * Returns false if no matching entry found.
 */
function get_feature_matrix($id)
{
	return mysql_fetch_object(do_mysql_query("select * from saved_matrix_info where id = " . (int) $id));
}


/**
 * Translates a feature matrix ID code to the mysql tablename 
 * that contains the data for that feature matrix.
 */
function feature_matrix_id_to_tablename($id)
{
	return 'featmatrix_' . str_pad(base_convert(dechex($id), 16, 36), 10, "0", STR_PAD_LEFT);
	
}

/**
 * Creates a feature matrix object in the MySQL database.
 * 
 * Note this function *does not* populate the actual database table that contains the matrix. 
 * 
 * Nor does it add any rows to the variables table.
 * 
 * @return the ID number of the saved feature matrix we have just created.
 */
function save_feature_matrix_info($savename, $user, $corpus, $subcorpus, $unit)
{
	$savename = mysql_real_escape_string($savename);
	$user = mysql_real_escape_string($user);
	$corpus = mysql_real_escape_string($corpus);
	/* different cos we know it will be either integer ID, or empty value which is stoerd as empty string. */
	$subcorpus = (empty($subcorpus)? '' : (int)$subcorpus);
	$unit = mysql_real_escape_string($unit);
	
	$t = time();
	
	do_mysql_query("insert into saved_matrix_info
						(savename, user, corpus, subcorpus, unit, create_time)
					values
						('$savename', '$user', '$corpus', '$subcorpus', '$unit', $t)");

	return get_mysql_insert_id();
}

/**
 * Creates a feature table entry linked to the specified matric.
 * 
 * @param $feature_spec   An stdClass containing the following members:
 * 	                      TODO document tjhis.
 * 
 * @return the ID number of the newly created feature table entry.
 */
function add_feature_to_matrix($matrix_id, $feature_spec)
{
	/* safety! */
	$matrix_id = (int)$matrix_id;
	// TODO.
// how the spec is created.	
//			$o->type = 'from-saved-query';
//			$o->qname = $v;
//			$o->label = $record['save_name'];
//			$o->source_info = 'Query = ';
//			$o->source_info .= (empty($record['simple_query']) ? $record['cqp_query'] : $record['simple_query']);
	switch($feature_spec->type)
	{
		/* actually, maybe we don't need cases. Might just be the same - use source infor and label.*/
	case 'from-saved-query':
		$label = mysql_real_escape_string($feature_spec->label);
		$info  = mysql_real_escape_string($feature_spec->source_info);
		
		do_mysql_query("insert into saved_matrix_features (matrix_id, label, source_info) 
							values ($matrix_id, '$label', '$info')");
		
		break;
	// TODO more.
	default:
		exiterror("Unrecognised type of feature could not be created.");
		break;	
	}
	
	//TODO no return???
}

function make_feature_matrix_create_statement()
{
	
	//TODO
}

function populate_feature_matrix()
{
	//TODO
}

/**
 * Returns a string containing an instruction to run in R
 * that will create the feature matrix desired as an R matrix.
 * 
 * Note that the function assumes a blank workspace that it can
 * do what it likes to.
 * TODO -- modify so that it can operate in a workspace that may already
 * contain objects with the names it wants to use. 
 * 
 * @param int $id  ID of the feature matrix you want to gety a string for.
 * TODO -- or, better to pass in a DB object? 
 */
function insert_feature_matrix_to_r($rface, $id, $desired_object_name)
{
	$id = (int) $id;
	
	$result = do_mysql_query("select label from saved_matrix_features where matrix_id = $id");
	$label_array = array();
	while (false !== ($r = mysql_fetch_row($result)))
		$label_array[] = '`'. $r[0] . '`';
	
	$labels = implode(',', $label_array);
	
	$obj_id_list = array();

	/* NB, this assumes that object IDs are OK R object identifiers */
	$result = do_mysql_query("select obj_id, $labels from " . feature_matrix_id_to_tablename($id));
	while (false !== ($r = mysql_fetch_object($result)))
	{
		$cmd = $r->obj_id . ' <- c(';
		$obj_id_list[] = $r->obj_id;
		foreach($label_array as $l)
		{
			$l = str_replace('`','', $l);
			$cmd .= $r->$l . ",";
		}
		$cmd = rtrim($cmd, ',');
		$cmd .= ')';
		$rface->execute($cmd);
	}
	
	$rface->execute("$desired_object_name <- data.frame(t(cbind( " . implode(',', $obj_id_list) . ")))");
	$rface->execute("names_vec = c(" . str_replace("`", "'", $labels) . ")");
	$rface->execute("names($desired_object_name) <- names_vec");
	
}



/**
 * Get feature matrix as text table(usually for download).
 * 
 * If the second argument is true (default = false) it will be returned as a string.
 * 
 * Otherwise, it will be written out via echo (And the return is Boolean true.)
 */
function print_feature_matrix_as_text_table($id, $return_string = false)
{
	global $User;
	
	$result = do_mysql_query("select * from `" . feature_matrix_id_to_tablename($id) . "`");
	
	$eol = get_user_linefeed($User->username);
	
	$header = '';
	
	for ($i = 0, $n = mysql_num_fields($result); $i < $n ; $i++)
		$header .= mysql_field_name($result, $i). "\t";
	$header = rtrim($header, "\t") . $eol;
	
	if ($return_string)
	{
		$s = $header;
		while (false !== ($r = mysql_fetch_row($result)))
			$s .= implode("\t", $r) . $eol;
		return $s;
	}
	else
	{
		echo $header;
		while (false !== ($r = mysql_fetch_row($result)))
			echo implode("\t", $r), $eol;
		return true;
	}
}




/**
 * Lists all the variables in a given feature matrix.
 *
 * @return array  An array of database objects (representing variables from the feature matrix).
 */
function feature_matrix_list_variables($id)
{
	$result = do_mysql_query("select * from saved_matrix_features where matrix_id = ". (int) $id);
	
	$list = array();
	
	while (false !== ($o = mysql_fetch_object($result)))
		$list[] = $o;

	return $list;	
}

/**
 * Lists all the data objects in a given feature matrix.
 * 
 * @param int $id  The matrix to interrogate (ID number). 
 * @return array   An array of strings (object labels)
 */
function feature_matrix_list_objects($id)
{
	// TODO -- correct field name??????????
	$result = do_mysql_query("select obj_id from " . feature_matrix_id_to_tablename($id));
	
	$list = array();
	
	while (false !== ($r = mysql_fetch_row($result)))
		$list[] = $r[0];
	
	return $list;
}




