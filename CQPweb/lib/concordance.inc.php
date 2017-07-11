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
 * This file contains the code for (a) doing and (b) displaying a corpus query as a concordance. 
 */



/* initialise variables from settings files  */
require('../lib/environment.inc.php');

/* include function library files */
require('../lib/library.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/concordance-lib.inc.php');
require('../lib/concordance-post.inc.php');
require('../lib/ceql.inc.php');
require('../lib/metadata.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/cache.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/db.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/plugins.inc.php');
require('../lib/xml.inc.php');
require("../lib/cwb.inc.php");
require("../lib/cqp.inc.php");

cqpweb_startup_environment();



/* Load user macros! */
user_macro_loadall($User->username);






/* ------------------------------- *
 * initialise variables from $_GET *
 * and perform initial fiddling    *
 * ------------------------------- */



/* *************** *
 * QUERY VARIABLES *
 * *************** */

/* qname is the overriding variable --- is it set?
 * If it is, then we are accessing an already-existing query in order to 
 * display it (or rather, an approrpiate subset of it).
 * 
 * In this case, we don't need $theData or anything like that.
 * 
 * If qname is not set, this is a new query (but in may be in cache, 
 * in which case we will get a query name from the database). 
 */
if (isset($_GET['qname']))
{
	$qname = safe_qname_from_get();
	/* we did some pre-checks before calling the safe-qname function to allow 
	 * the case where qname is absent to pass through, which normally would be Wrong */
	$incoming_qname_specified = true;
}
else
	$incoming_qname_specified = false;



/* Handling of theData && qmode.
 *
 * "theData" is the contents of a query, either in CQP-syntax, or in
 * the CEQL simple-syntax formalism. The qmode parameter indicates which
 * of these it is. If a new query is to be performed, both these parameters
 * are indispensible.
 */
if (! $incoming_qname_specified )
{
	if (isset($_GET['theData']))
		$theData = prepare_query_string($_GET['theData']);
	else
		exiterror_parameter('The content of the query was not specified!');

	if (isset($_GET['qmode']))
		$qmode = prepare_query_mode($_GET['qmode'], true);
	else
		exiterror_parameter('No query mode was specified!');
}
else
{
	/* theData & qmode are optional: set them to NULL if not present. 
	 * Note that they are ignored UNLESS qname turns out not to be cached after all */
	if (isset($_GET['theData']))
		$theData = prepare_query_string($_GET['theData']);
	else
		$theData = NULL;

	if (isset($_GET['qmode']))
		$qmode = prepare_query_mode($_GET['qmode'], false);
	else
		$qmode = NULL;
}
/* stop "theData" & "qmode" from being passed to any other script */
unset($_GET['theData']);
unset($_GET['qmode']);
/* $case_sensitive is only used if this is a new query */
$case_sensitive = ($qmode === 'sq_nocase' ? false : true);


/* *******************************************************************************************************************************************************
 * TEMP DEBUG CODE
 * --------------
the old version fo the "subcorpus" detection allowed for two patterns which, to the best of my knowledge, DONT HAPPEN.
The ONLY pattern wqhich should happen is  
	&del=begin&t=~sc~35&del=end&...
But, two other patterns seem to be allowed for by the code:
	&subcorpus=35&...
and
	&del=begin&t=subcorpus~35&del=end&...
i.e. the first format, but interpreted as 'twere restrictions, leading to (subcorpus='35') as a text-metadata-restrictions string.
BUT the latter should not any longer be possible, due to the new subcorpus flag (&t=~sc~)
So this debug code asserts that there is not _GET[subcorpus]
*/
assert_options(ASSERT_BAIL, 1);
assert(!array_key_exists('subcorpus', $_GET), "CRITICAL BUG: subcorpus key exists in _GET. Please report this error to Andrew Hardie. ");
/* end temp debug code ***********************************************************************************************************************************/


// if ($User->is_admin()) show_var($_SERVER['QUERY_STRING']);
$qscope = QueryScope::new_from_url($_SERVER['QUERY_STRING'], true); 
// if ($User->is_admin()) show_var($qscope);

if (QueryScope::TYPE_EMPTY == $qscope->type)
{
	/* the user specified restrictions that exclude the entirety of the corpus. */
	say_sorry('no_scope');
}
/* 
 * Note that the query scope (subcorpus or restrictions) will be overwritten below 
 * if a named query is retrieved from cache.
 */


/* 
 * Variable $postprocess describes all the postprocesses applied to a query; it 
 * always starts as an empty string, but it may be added to later by new-postprocess,
 * or an existing postprocessor string may be loaded from memory. 
 */
$postprocess = '';

/* load variables for new postprocesses */
$new_postprocess = false;
if (isset($_GET['newPostP']) && $_GET['newPostP'] !== '')
{
	$new_postprocess = new CQPwebPostprocess();
	if ( ! $new_postprocess->parsed_ok() )
		exiterror_parameter('The parameters for query postprocessing could not be loaded!');
	unset($_GET['pageNo']);
	/* so that we know it will go to page 1 of the postprocessed query */
}




/* ******************* *
 * RENDERING VARIABLES *
 * ******************* */

/* In a multi-page concordance: which page to display. Note: parsed value overwrites GET. */
if (isset($_GET['pageNo']))
	$_GET['pageNo'] = $page_no = prepare_page_no($_GET['pageNo']);
else
	$page_no = 1;


/* In a multi-page concordance: how many hits per page. 
 * 
 * Note that &pp=count indicates that rather than show ANY 
 * hits, we should just display how many hits there were,
 * plus print out the command bar to allow additional analysis.
 */
if (isset($_GET['pp']))
	$per_page = prepare_per_page($_GET['pp']);   /* filters out any invalid options */
else
	$per_page = $Config->default_per_page;
	
if ($per_page == 'count')
{
	$count_hits_then_cease = true;
	$per_page = $Config->default_per_page;
}
else
	$count_hits_then_cease = false;
if ($per_page == 'all')
{
	$show_all_hits = true;
	$per_page = $Config->default_per_page;
}
else
	$show_all_hits = false;


/* viewMode can be either kwic or line. It is sanitised below. */
if (isset($_GET['viewMode']))
	$viewMode = $_GET['viewMode'];
else
	$viewMode = ( (bool) $User->conc_kwicview ? 'kwic' : 'line' ) ;
	
/* there is an override... when translation is showing, only line mode is possible */
if ($Corpus->visualise_translate_in_concordance)
	$viewMode = 'line';


/* set kwic variables */
if ($viewMode == "kwic") 
{
	$reverseViewMode = "line";
	$reverseViewButtonText = "Line View";
}
else
{
	$viewMode = "line";
	$reverseViewMode = "kwic";
	$reverseViewButtonText = "KWIC View";
}



/* the $program variable: filtered by a switch to admit only OK values;
 * note this is only used for the RENDERING of the query */
if(empty($_GET['program']))
	$program = 'search';
else
{
	switch($_GET['program'])
	{
	case 'collocation':	/* TODO is program==collocation actually a thing? */
	case 'sort':
	case 'lookup':
	case 'categorise':
		$program = $_GET['program'];
		break;
	default:
		$program = 'search';
		break;
	}
}





/* ----------------------- *
 * gather some corpus info *
 * ----------------------- */

$primary_tag_handle = $Corpus->primary_annotation;




/* ----------------------------------------------------------------------------- *
 * This is the section which runs two separate tracks:                           *
 * a track for a query that is in cache and another track for a query that isn't *
 * ----------------------------------------------------------------------------- */

$start_time = microtime(true);



/* start by assuming that an old query can be dug up */
$run_new_query = false;
/* this will, or will not, be disproven later on     */

/* and set $num_of_solutions so it fails-safe to 0   */
$num_of_solutions = 0;

/* and flag a history insertion as NOT done (this variable will be set to true when it is) */
$history_inserted = false;



/* ------------------------------------------------------------------------ *
 * START OF CHUNK THAT CHECKS THE CACHE AND PREPARES THE QUERY IF NO RESULT *
 * ------------------------------------------------------------------------ */


if ( $incoming_qname_specified )
{
	/* TRACK FOR CACHED QUERY WITH QNAME IN THE GET REQUEST */
	
	/* check the cache */

	$cache_record = QueryRecord::new_from_qname($qname);

	if  ( $cache_record === false || 0 == ($num_of_solutions = $cache_record->hits()) )
	{
		/* if query not found in cache, JUMP TRACKS */
		unset($cache_record);
		$incoming_qname_specified = false;	

		/* check the now-compulsory variables */
		if (empty($theData))
			exiterror_parameter('The content of the query was not specified (and the named query was not in cache)!');	

		if (empy($qmode))
			exiterror_parameter('No query mode was specified (and the named query was not in cache)!');
	}
	else
	{
		/* the cached file has been found and it DOESN'T contain 0 solutions */

		/* touch the query, updating its "time" to now */
		if ($cache_record->saved == CACHE_STATUS_UNSAVED)
			$cache_record->touch();
		
		/* take info from the cache record, and copy it to script variables */
		$qmode = $cache_record->query_mode;
		unset($_GET['qmode']);
			
		$cqp_query = $cache_record->cqp_query;
		$simple_query = $cache_record->simple_query;
// 		$subcorpus = $cache_record->subcorpus;
		unset($_GET['subcorpus']); // TODO is this necessary??????????? here or anywhere?
// 		$restrictions = $cache_record->restrictions;
		
		/* overwrite the previously-established $qscope if we have loaded a cached query */
// 		$qscope = QueryScope::new_by_unserialise(empty($subcorpus) ? $restrictions : $subcorpus);
		$qscope = $cache_record->qscope;
		
		$postprocess = $cache_record->postprocess;

		unset($theData);
		
		/* next stop on this track is POSTPROCESS then DISPLAYING THE QUERY */
	}
}


/* this can't be an ELSE, because of the possibility of a track switch in preceding IF */
if ( ! $incoming_qname_specified )
{
	/* TRACK FOR A QUERY WHERE THE QNAME WAS NOT SPECIFIED */
	
	/* derive the $cqp_query and $simple_query variables and put the query into history */
	if ($qmode == 'cqp')
	{
		$simple_query = '';
		$cqp_query = $theData;
	}
	else /* if this is a simple query */
	{
		/* keep a record of the simple query */
		$simple_query = $theData;

		/* convert the simple query to a CQP query */
		if (false === ($cqp_query = process_simple_query($theData, $case_sensitive, $ceql_errors)))
		{
			/* if conversion fails, add to history & then add syntax error code */
			history_insert($Config->instance_name, $query, $qscope->serialise(), $query, ($case_sensitive ? 'sq_case' : 'sq_nocase'));
			history_update_hits($Config->instance_name, -1);
			
			/* and then call an error with the array of diagnostic strings from CEQL. */
			exiterror_general($ceql_errors);
		}
	}
	/* either way, $theData is no longer needed */
	unset($theData);
	
	/* we now have the query in CQP-syntax: the query can now go into history. */
	history_insert($Config->instance_name, $cqp_query, $qscope->serialise(), $simple_query, $qmode);
	$history_inserted = true;
	
	
	/* look in the cache for a query that matches this one on crucial parameters */

	$cache_record = QueryRecord::new_from_params($Corpus->name, $cqp_query, $qscope);

	if  ( $cache_record === false || 0 == ($num_of_solutions = $cache_record->hits()) )
	{
		/* query is not found in cache at all - therefore, it needs to be run anew,
		 * and said new query inserted into cache with a brand-new qname.
		 * Queries with no solutions are also re-run.
		 */
		$run_new_query = true;
	}
	else
	{
		/* we have a query in the cache with the same cqp_query, subc., restr., & postp.! */

		/* take info from the cache record, and copy it to script variables */
		
		/* note: cqp_query (and restrictions/subcorpus) were what we matched on, so no need to copy */
		$qname = $cache_record->qname;

		/* the other two are slightly complicated */
		/* If the cache record already contains a simple_query, then it will be identical to
		 * simple_query, so no need to update that way. Rather, update the other way 
		 * (supply a simple query that generates the CQP query where none is available). */
		if (!empty($simple_query) && empty($cache_record->simple_query))
		{
			$cache_record->simple_query = $simple_query;
			$cache_record->save();
		}
				
		/* qmode shouldn't be updated, because this was, after all, a "new" query */
		/* so regardless of the qmode of the cached query, this instance has its own qmode */	
		
		/* touch the query, updating its "time" to now */
		if ($cache_record->saved == CACHE_STATUS_UNSAVED)
			$cache_record->touch();
		/* next stop on this track is POSTPROCESS then DISPLAYING THE QUERY */
	}
}


/* we now know if it's a new query, and can check whether to apply the user's auto-randomise function;
 * but this is only applied if no other postprocess has been asked for. */
if ($run_new_query && ! $new_postprocess && ! $User->conc_corpus_order)
{
	$_GET['newPostP'] = 'rand';
	$new_postprocess = new CQPwebPostprocess();
	/* no need to check whether it parsed correctly, cos we know it did! */
	$_GET['pageNo'] = $page_no = 1;
	/* so that we know the display will go to page 1 of the postprocessed query */
}







/* ---------------------------------------------------------- *
 * START OF MAIN CHUNK THAT RUNS THE QUERY AND GETS SOLUTIONS *
 * ---------------------------------------------------------- */
if ($run_new_query)
{
	/* if we are here, it is a brand new query -- not saved or owt like that. Ergo: */
	$qname = qname_unique($Config->instance_name);

	/* delete a cache file with this name if it exists */
	cqp_file_unlink($qname);
	
	/* set restrictions / activate subcorpus */
	$qscope->insert_to_cqp(); 

	// echo "Executing query: " . "$qname = $cqp_query";
	/* this is the business end */
	$cqp->execute("$qname = $cqp_query");

	/* now that we have the query, find out its size */
	// echo "Result size: " . $cqp->querysize($qname);
	if (0 == ($num_of_solutions = $cqp->querysize($qname)) )
	{
		/* no solutions: update the history, then send the user a message and exit */
		if ($history_inserted)
			history_update_hits($Config->instance_name, 0);
		
		say_sorry(); /* note that this exits() the script! */
	}
	
	/* otherwise, save the query file to disk, then create a cache record. */

	$cqp->execute("save $qname");

	$num_of_texts = count( $cqp->execute("group $qname match text_id") );
	/* note that this field in the record always refers to the ORIGINAL num of texts
	 * so, it is OK to set it here and not anywhere else (as postprocesses don't affect it) */
	
	/* put the query in the cache and get a cache-record object.*/
	$cache_record = QueryRecord::create(
			$qname, 
			$User->username, 
			$Corpus->name, 
			$qmode, 
			$simple_query, 
			$cqp_query, 
			$qscope,
// 			$restrictions, 
// 			$subcorpus,
			$num_of_solutions, 
			$num_of_texts
			);
	$cache_record->save();
}
else
{
	/* if ! $run_new_query, do nothing. The query has been retrieved from cache. */
}

/* set flag in history for query completed */
if ($history_inserted)
	history_update_hits($Config->instance_name, $num_of_solutions);
	/* IF this query created a record, update it (To overwrite the default "run error" value, which is -3) */

/* -------------------------------------------------------- *
 * END OF MAIN CHUNK THAT RUNS THE QUERY AND GETS SOLUTIONS *
 * -------------------------------------------------------- */

/* --------------------------------------------- *
 * End of section which runs two separate tracks *
 * --------------------------------------------- */



/* ----------------------- *
 * START OF POSTPROCESSING *
 * ----------------------- */

// echo "postprocess";die;
/* note that, for reasons of auto-thinning queries for users with restricted access, all this bit is inside a once-only loop */ 
while (true)
{
	if ($new_postprocess)
	{
		/* Add the new postprocess to the existing  postprocessor string, and look it up
		 * by parameter (using cqp_query, query_scope, postprocessor string)  */
		
		$postprocess = $new_postprocess->add_to_postprocess_string($postprocess);
		
		$check_cache_record = QueryRecord::new_from_params($Corpus->name, $cqp_query, $qscope, $postprocess);

		
		/*	If it exists, the orig qname is replaced by this one */
		if ( false !== $check_cache_record)
		{
			/* dump the cache record retrieved or created above and use this one */
			$cache_record = $check_cache_record;
			$qname = $cache_record->qname;
			
			/* PLUS change variable settings, as we did before (see above) for original-query-matched */
			if (!empty($simple_query) && empty($cache_record->simple_query))
			{
				$cache_record->simple_query = $simple_query;
				$cache_record->save();
			}
		
			if ($cache_record->saved == CACHE_STATUS_UNSAVED)
				$cache_record->touch();
		}
		/* If it doesn't exist, the postprocess is applied to the qname (ergo the qname is replaced) */
		else
		{
			/* do_postprocess: returns false if the postprocess did not work. */
			if ( ! $cache_record->do_postprocess($new_postprocess))
				say_sorry('empty_postproc');
				/* which exits the program */

			/* calling the above method re-sets cr->postprocess and cr->hits_left etc.
			 * in the new query that is created; also touches the time, caches, and sets the new query to unsaved. */
			$qname = $cache_record->qname;
			
			/* and, because this means we are dealing with a query new-created in cache... */
			$run_new_query = true;
			/* so that it won't say the answer was retrieved from cache in the heading */
		}
// TEMP DEBUG CHECK: $postprocess should either way now be identical to the contents of the object's postprocess strign
if ($postprocess !== $cache_record->postprocess) {show_var($postprocess); /*show_var($cache_record);*/ exiterror("CRITICAL POSTPROCESS BUG IN ".__FILE__." line ".__LINE__);}
	} /* endif $new_postprocess */
	
	/* get the highlight-positions table */
	$highlight_show_tag = false; /* which the next call *may* change to true */
	$highlight_positions_array = $cache_record->get_highlight_position_table($highlight_show_tag);
	
	/* even if tags are to be shown, don't do so if no primary annotation is specified, or if we are lgossing the text */
	$highlight_show_tag = ( $highlight_show_tag &&  !empty($primary_tag_handle)  && !$Corpus->visualise_gloss_in_concordance );
	
	
	/* --------------------- *
	 * END OF POSTPROCESSING *
	 * --------------------- */
	
	
	$time_taken = round(microtime(true) - $start_time, 3);
	
	
	
	/* for safety, put the new qname into _GET; if a function looks there, it'll find the right qname */
	$_GET['qname'] = $qname;
	/* this is the qname of the cached query which the rest of the script will render */
	
	
	
	/* whatever happened above, $num_of_solutions contains the number of solutions in the original query.
	 * BUT a postprocess can reduce the num of solutions that get rendered and thus the number of pages.
	 * num_of_solutions_final == the number of solutions all AFTER postprocessing.
	 */
	
	$num_of_solutions_final = $cache_record->hits();

	/* we can now check if there are too many hits in the query for a restricted access query! 
	 * if there are, we need to set up for a *reduce the query via random thin* postprocess, then
	 * use a "continue" in this while-true-break to repeat the application of postprocessing. */
	if (PRIVILEGE_TYPE_CORPUS_RESTRICTED >= $Corpus->access_level)
	{
		/* we only need to look at the section searched initially, 
		 * because we don't care about subsequent distribution-postproc reductions. */
		$restrict_access_max = max_hits_when_restricted($cache_record->get_tokens_searched_initially());

		if ($num_of_solutions_final > $restrict_access_max)
		{
			/* we DO need to thin down the concordance result to a smaller n of hits! 
			 * To do this: set new values to trigger a THIN of the query. */
			$_GET['newPostP'] = 'thin';
			$_GET['newPostP_thinTo'] = $restrict_access_max;
			$_GET['newPostP_thinHitsBefore'] = $num_of_solutions_final;
			$_GET['newPostP_thinReallyRandom'] = 0; /* we use REPRODUCIBLE thin so what users get to see is deterministic. */
			/* We do not need to remove any existing newPostP parameters: they were all cleared out 
			 * the last time the CQPwebPostprocess object was constructed (or, there were none to begin 
			 * with && the CQPwebPostprocess constructor has never been called).  */
			$new_postprocess = new CQPwebPostprocess();
			$_GET['pageNo'] = $page_no = 1;
			/* so that we know the display will go to page 1 of the postprocessed query */
			continue;
		}
	}
	
	/* otherwise (or, AFTER the re-loop) we just proceed to break out of the while-true-break "loop" and carry on ..... */
	break;
}



/* so we can work out how many pages there are (also == the # of the last page) */
if ($show_all_hits)
	$per_page = $num_of_solutions_final;
	/* which will make the next statement set $num_of_pages to 1 */

$num_of_pages = (int)($num_of_solutions_final / $per_page) + (($num_of_solutions_final % $per_page) > 0 ? 1 : 0 );

/* make sure current page number is within feasible scope */
if ($page_no > $num_of_pages)
	$_GET['pageNo'] = $page_no = $num_of_pages;







/* ----------------------- *
 * DISPLAY THE CONCORDANCE *
 * ----------------------- */
	
	//show_var($cache_record);


/* if program is word-lookup, we don't display here - we go straight to freqlist. */
if ($program == 'lookup')
{
	$showtype = ($_GET['lookupShowWithTags'] == 0 ? 'concBreakdownWords' : 'concBreakdownBoth');
	header("Location: redirect.php?redirect=$showtype&qname=$qname&pp=$per_page&uT=y");
	cqpweb_shutdown_environment();
	exit();
}


/* begin HTML page.... */

echo print_html_header($Corpus->title . " -- CQPweb Concordance", $Config->css_path);


/* print table headings && control lines */

echo "\n<table class=\"concordtable\" width=\"100%\">\n";

echo '<tr><th colspan="8" class="concordtable">' 
	, $cache_record->print_solution_heading()
	, format_time_string($time_taken, $run_new_query)
	, "</th></tr>\n\n"
	;

// Longterm-TODO:  (low priority) make the control row contain only the extra-action menu iff $count_hits_then_cease 
$control_row = print_control_row($cache_record);

if ($program == "sort")
{
	/* if the query being displayed is sorted, then we need to put the sort position 
	 * into the control row, and we also need a second control row for the sort. */
	$sort_pos_recall = 0;
	$sort_control_row = print_sort_control($primary_tag_handle, $cache_record->postprocess, $sort_pos_recall);
	echo add_sortposition_to_control_row($control_row, $sort_pos_recall), $sort_control_row;
}
else
	echo $control_row;


/* having done the control row, it is time to exit if we are in count-hits mode */
if ($count_hits_then_cease)
{
	echo '</table>';
	echo print_html_footer('concordance');
	cqpweb_shutdown_environment();
	exit();
}
	


/* set up CQP options for the concordance display */
$cqp->execute("set Context {$Corpus->conc_scope} " . ($Corpus->conc_scope_is_based_on_s ? $Corpus->conc_s_attribute : 'words'));

/* what p-attributes to show? (annotations) */
if ($Corpus->visualise_gloss_in_concordance)
	$cqp->execute("show +word +{$Corpus->visualise_gloss_annotation} ");
else
	$cqp->execute('show +word ' . (empty($primary_tag_handle) ? '' : "+$primary_tag_handle "));
	/* note that $primary_tag_handle should only be empty in an unannotated corpus. */

/* what inline s-attributes to show? (xml elements) */
$xml_tags_to_show = xml_visualisation_s_atts_to_show();
if ( ! empty($xml_tags_to_show) )
	$cqp->execute('show +' . implode(' +', $xml_tags_to_show));

/* what corpus location attributes to show? */
$cqp->execute('set PrintStructures "' 
				// TODO. Will this work along with XML visualisation? Should it be one or the other?
				// TODO. does it work along with position labels???
				. ($Corpus->visualise_translate_in_concordance ? "{$Corpus->visualise_translate_s_att} " : '') 
				. 'text_id'
				. ($Corpus->visualise_position_labels ? " {$Corpus->visualise_position_label_attribute}" : '')
				. '"');

$cqp->execute("set LeftKWICDelim '--%%%--'");
$cqp->execute("set RightKWICDelim '--%%%--'");




/* what number does the concordance start and end at? */
/* conc_ = numbers that are shown */
/* batch_ = numbers for CQP, which are one less */
$conc_start = (($page_no - 1) * $per_page) + 1; 
$conc_end = $conc_start + $per_page - 1;
if ($conc_end > $num_of_solutions_final)
	$conc_end = $num_of_solutions_final;

$batch_start = $conc_start - 1;
$batch_end = $conc_end - 1;

/* get an array containing the lines of the query to show this time */
$kwic = $cqp->execute("cat $qname $batch_start $batch_end");

/* get a table of corpus positions */
// $table = $cqp->dump($qname, $batch_start, $batch_end);
// This is a match / matchend / target / anchor table, formerly used (in BNCweb) for something to do with rendering
// but not used in CQPweb.
// just in case:
$table = array();

/* n = number of concordances we have to display in this run of the script */
$n = count($kwic);




?>

</table>

<table class="concordtable" width="100%">
<?php

if ($program == 'categorise')
{
	echo '<form action="redirect.php" method="get">';
	
	/* and note, in this case we will need info on categories for the drop-down controls */ 
	$list_of_categories = catquery_list_categories($qname);
	$category_table = catquery_get_categorisation_table($qname, $conc_start, $conc_start+$n-1);
}


/* column headings */
echo '<tr><th class="concordtable">No</th><th class="concordtable">Filename</th><th class="concordtable"'
	, ( $viewMode == 'kwic' ? ' colspan="3"' : '' )
	, ">Solution $conc_start to $conc_end &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Page $page_no / $num_of_pages</th>"
	, ($program == 'categorise' ? '<th class="concordtable">Category</th>' : '')
	, "</tr>\n\n"
	;





/* --------------------------- *
 * concordance line print loop *
 * --------------------------- */


for ( $i = 0, $b = ($highlight_positions_array !== false) ; $i < $n ; $i++ )
{
	$highlight_position = ($b ? (int)$highlight_positions_array[$conc_start + $i - 1] : 1000000);
	
	$line = print_concordance_line($kwic[$i], $table, ($conc_start + $i), $highlight_position, $highlight_show_tag);

	$categorise_column = '';
	if ($program == 'categorise') 
	{
		/* lookup what category this line has, and then build a box for it */
		$categorise_column = '<td align="center" class="concordgeneral">';
		$categorise_column .= '<select name="cat_' . ($conc_start + $i) . '">';
		
		if ($category_table[$conc_start + $i] === NULL)
			$categorise_column .= '<option select="selected"> </option>';

		foreach($list_of_categories as $thiscat)
		{
			$select =  ($category_table[$conc_start + $i] == $thiscat) ? ' selected="selected"' : '' ; 
			$categorise_column .= "<option$select>$thiscat</option>";
		}
		
		$categorise_column .= '</select></td>';
	}
	
	echo "\n<tr>", $line, $categorise_column, "</tr>\n";
}
/* end of concordance line print loop */


/* the categorise control row */
if ($program == 'categorise')
	echo print_categorise_control()
		, '<input type="hidden" name="redirect" value="categorise"/>'
		, '<input type="hidden" name="pageNo" value="' , $page_no , '"/>'
		, '<input type="hidden" name="qname" value="' , $qname , '"/>'
		, '<input type="hidden" name="uT" value="y"/>'
		, '</form>'
		;

/* finish off table */
echo "\n\n</table>\n";

// Longterm-TODO more listfiles/categorise  controls???


/* show the control row again at the bottom if there are more than 15 lines on screen */
if ($num_of_solutions_final > 15 && $per_page > 15)
	echo "\n<table class=\"concordtable\" width=\"100%\">\n", $control_row, "\n</table>\n";



	
/* Based on the program, vary the helplink. */
if ($program == 'sort' || $program == 'categorise')
	$helplink = $program;
else
	$helplink = 'concordance';

echo print_html_footer($helplink);


/* clear out old stuff from the query cache (left till here to increase speed for user) */
delete_cache_overflow();

/* and update the last restrictions (ditto) */
$qscope->commit_last_restriction();

cqpweb_shutdown_environment();


/* and we're done! */


