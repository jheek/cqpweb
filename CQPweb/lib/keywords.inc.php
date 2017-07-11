<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 *
 * See http://cwb.sourceforge.net/cqpweb.php
 *
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modifyCQPWEB_STARTUP_DONT_CHECK_URLTEST
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



/*
 * note: this script emits nothing on stdout until the last minute, because it can alternatively
 * write a plaintext file as HTTP attachment 
 */




/* ------------ *
 * BEGIN SCRIPT *
 * ------------ */

// TODO: the left join for "comp" function may be quite slow. It is worth doing a time-test on the db


/* initialise variables from settings files  */
require('../lib/environment.inc.php');


/* include function library files */
require("../lib/library.inc.php");
require('../lib/html-lib.inc.php');
require("../lib/exiterror.inc.php");
require("../lib/metadata.inc.php");
require("../lib/user-lib.inc.php");
require("../lib/subcorpus.inc.php");
require("../lib/freqtable.inc.php");
require('../lib/rface.inc.php');
require("../lib/cwb.inc.php");         // needed?
require("../lib/cqp.inc.php");



cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP);


/*
 * =================================
 * Interface / output mode variables
 * ================================= 
 */



/* do we want a nice HTML table or a downloadable table? */
if (isset($_GET['tableDownloadMode']) && $_GET['tableDownloadMode'] == 1)
	$download_mode = true;
else
	$download_mode = false;

	

/* per page and page numbers */

if (isset($_GET['pageNo']))
	$_GET['pageNo'] = $page_no = prepare_page_no($_GET['pageNo']);
else
	$page_no = 1;

$per_page = prepare_per_page(isset($_GET['pp']) ? $_GET['pp'] : NULL);   /* filters out any invalid options */

/* note use of same variables as used in a concordance; we assume per-page always to be (potentially) present in the GET string. */

/* and this is the string that will be used to implement pagination in the SQL queries below: */
$limit_string = ($download_mode ? '' : ("LIMIT ". ($page_no-1) * $per_page . ', ' . $per_page));



/*
 * ===================================
 * Procedural / statistical parameters
 * ===================================
 */


/* overall mode of script */

if (isset($_GET['kwMethod']) && $_GET['kwMethod'] == 'Show unique items on list' )
	$mode = 'comp';
else
	$mode = 'key'; /* which we may change to lock, below */


/* do we want all keywords, only positive, or only negative, or lockwords? */

if ( isset($_GET['kwWhatToShow']) && in_array($_GET['kwWhatToShow'], array('allKey', 'onlyPos', 'onlyNeg', 'lock')))
	$what_to_show = $_GET['kwWhatToShow'];
else
	exiterror("The system could not detect whether you selected positive keywords, negative keywords, or lockwords.");

if ('lock' == $what_to_show)
	$mode = 'lock';


/* attribute to compare  (plus get its description) */

if (!isset($_GET['kwCompareAtt']) )
	$att_for_comp = 'word';
else
	$att_for_comp = $_GET['kwCompareAtt'];

$att_desc = get_corpus_annotations();	
$att_desc['word'] = 'Word';

/* if the script has been fed an attribute that doesn't exist for this corpus, failsafe to 'word' */
if (! array_key_exists($att_for_comp, $att_desc) )
	$att_for_comp = 'word';



/* minimum frequencies */

if (!isset($_GET['kwMinFreq1']) )
	$minfreq[1] = 5;
else
	$minfreq[1] = (int)$_GET['kwMinFreq1'];	
if ($minfreq[1] < 1)
	$minfreq[1] = 1;

if (!isset($_GET['kwMinFreq2']) )
	$minfreq[2] = 5;
else
	$minfreq[2] = (int)$_GET['kwMinFreq2'];	
if ($minfreq[2] < 0)
	$minfreq[2] = 0;




/* statistic to use: check by keys of mapper of column headings */

$stat_sort_col_head = array (
	'LL'=>'Log likelihood',
	'LR_UN'=>'Log Ratio',
	'LR_LL'=>'Log Ratio',
	'LR_CI'=>'Log Ratio',
	'CI'=>'Conf interval',
	'comp'=>'DUMMY VALUE'
	);

if (!isset($_GET['kwStatistic']) )
	exiterror("No statistic was specified!");
if (! array_key_exists($_GET['kwStatistic'], $stat_sort_col_head))
	exiterror("An invalid statistic was specified!");
	
$statistic = $_GET['kwStatistic'];

/* override statistic if we are not in keyword mode */
if ($mode == 'comp')
	$statistic = 'comp';

/* if we are in lockwords mode, check we are using a compatible statistic */
if ($mode == 'lock')
{
	switch ($statistic)
	{
	case 'LL':
		exiterror('Lockword calculations cannot be performed with the log-likelihood statistic. '
			. 'Try Log Ratio (unfiltered or with Confidence Interval filter) instead.');
	case 'LR_LL':
		exiterror('Lockword calculations cannot be performed with a log-likelihood filter. '
			. 'Use unfiltered Log Ratio, or Log Ratio with Confidence Interval filter, instead.');
	}
}

// Longterm would-be-nice TODO : use JavaScript to disable selection of this pair of options in the initial page.



/* the significance threshold */

if ( !isset($_GET['kwAlpha']) )
	$_GET['kwAlpha'] = '0.05';
	
$alpha = (float)$_GET['kwAlpha'];

if ($alpha > 1.0)
	$alpha = 1.0;

if ($alpha >= 1.0 && $statistic == 'LR_CI')
	exiterror('You asked for Log Ratio with a Confidence Interval filter, but you did not specify the size of Confidence Interval...');
	// TODO use JavaScript in the form to check for this on the client side as well...

/* do we adjjust the threshold? */
$familywise_adjust = (isset($_GET['kwFamilywiseCorrect']) && $_GET['kwFamilywiseCorrect']==='Y');




/* in compare  mode, we also need ... */

$empty = 'f2';
if ($_GET['kwEmpty'] == 'f1')
	$empty = 'f1';
$title_bar_index = (int)substr($empty, 1, 1);
$title_bar_index_other = ($title_bar_index == 1 ? 2 : 1);

/* in keywords/lockwords mode the above just go unused. */



/* the two tables to compare */

$subcorpus = array();
$table_base = array();
$table_desc = array();
$table_foreign = array();

if (isset($_GET['kwTable1']) )
	list($subcorpus[1], $table_base[1], $table_desc[1], $table_foreign[1]) = parse_keyword_table_parameter($_GET['kwTable1']);
else
	exiterror("No frequency list was specified (table 1)!");
	
if (isset($_GET['kwTable2']) )
	list($subcorpus[2], $table_base[2], $table_desc[2], $table_foreign[2]) = parse_keyword_table_parameter($_GET['kwTable2']);
else
	exiterror("No frequency list was specified (table 2)!");

if ($table_base[1] === false || $table_base[2] === false)
	exiterror("CQPweb could not interpret the tables you specified!");


/* check we've got two DIFFERENT tables */
if ($table_base[1] == $table_base[2])
	exiterror("The two frequency lists you have chosen are identical!");

/* check that the first table isn't foreign */
if ($table_foreign[1] === true)
	exiterror("A foreign frequency list was specified for frequency list (1)!");
	

/* get a string to put into linked queries with the subcorpus; also, touch subcorpus frequency lists */
foreach(array(1, 2) as $i)
{
	$restrict_string[$i] = '';
	$cmp = 'freq_corpus_';
	if ($subcorpus[$i] == '__entire_corpus')
		/* this is the home corpus (or a foreign corpus), so no restrict needed */
		/* if foreign, will be set to false later */
		;
	else
	{
		/* this is a subcorpus -- home or foreign, so a restrict is needed */
		$restrict_string[$i] = '&del=begin&t=~sc~'. $subcorpus[$i] . '&del=end';
		/* if foreign, will be set to false later */
		
		/* and we should touch it */
		touch_freqtable($table_base[$i]);
	}
}
/* and cos we already know the first table is NOT foreign... */
if ($table_foreign[2])
	$restrict_string[2] = false;








/*
 * =============================================================================
 * Done with parameters. Now, start building bits and pieces for the calulation.
 * =============================================================================
 */





/* create the full table names */

$table_name[1] = "{$table_base[1]}_$att_for_comp";
$table_name[2] = "{$table_base[2]}_$att_for_comp";

/* if the second table is foreign, check that a frequency table exists for the right attribute. */
if ($table_foreign[2])
	if (1 < mysql_num_rows(do_mysql_query("show tables like '{$table_name[2]}'")))
		exiterror("The corpus or subcorpus you selected for list (2) does not seem to have tags called &ldquo;$att_for_com&rdquo;");



/* get the totals of tokens and types for each of the 2 tables */

foreach (array(1, 2) as $i)
{
	$result = do_mysql_query("select sum(freq) from {$table_name[$i]}");

	if (mysql_num_rows($result) < 1)
		exiterror("sum(freq) not found in from {$table_name[$i]}, 0 rows returned from MySQL.");		
	list($corpus_tokens[$i]) = mysql_fetch_row($result);
	if (NULL === $corpus_tokens[$i])
		exiterror("sum(freq) not found in from {$table_name[$i]}, null value returned from MySQL.");		

	list($corpus_types[$i]) = mysql_fetch_row(do_mysql_query("select count(*) from {$table_name[$i]}"));
}


/* now we have total types, we can do this. */

$adjusted_alpha = correct_alpha_for_familywise($alpha, $corpus_types[1], $familywise_adjust ? 'Šidák' : NULL);


/* and now get the threshold or the Z value component of the Relative Risk conf interval measure */

if ($statistic != 'LR_CI')
{
	switch ($adjusted_alpha)
	{
	/* Optimise for some frequently-used cases.
	 * 
	 * Normally, doing == comparison with floats is a Bad Thing.
	 * But, since the case statements have the same origin as
	 * the input values (casting a string to float), we should be OK.
	 * And if cosmic rays strike, then we just call R anyway -
	 * we do not get an incorrect answer.
	 * 
	 * The number of sig figs is based on the default in R. 
	 */
	case (float)'0.05':			$threshold =  3.841459;		break;
	case (float)'0.01':			$threshold =  6.634897;		break;
	case (float)'0.001':		$threshold =  10.82757;		break;
	case (float)'0.0001':		$threshold =  15.13671;		break;
	case (float)'0.00001':		$threshold =  19.51142;		break;
	case (float)'0.000001':		$threshold =  23.92813;		break;
	case (float)'0.0000001':	$threshold =  28.37399;		break;
	case (float)'1.0':			$threshold =  0;			break;
	
	/* 
	 * 
	 */
	default:
		/* skip the call to R if we are using a stat that does not need a Chi-Sq distribution threshold */
		if (!in_array($statistic, array('LR_UN')))
		{
			/* R code example: qchisq(0.05, df=1, lower.tail=FALSE) */
			$r = new RFace($Config->path_to_r);
			list($threshold) = $r->read_execute(sprintf("qchisq(%E, df=1, lower.tail=FALSE)", $adjusted_alpha));
			unset($r);
		}
		else
			$threshold = 0.0;
		break;
	}
}
else
{
	/* optimise for some frequently-used cases.
	 * alpha here changes to the CI width (0.05 = 2.5% each way...) 
	 */

	switch ($adjusted_alpha)
	{
	case (float)'0.05':			$Z_unit =  1.959964;		break; /* 95% CI; in R: qnorm(0.025, lower.tail=FALSE) evals to 1.959964 */
	case (float)'0.01':			$Z_unit =  2.575829;		break; /* 99% CI; etc. */
	case (float)'0.001':		$Z_unit =  3.290527;		break;
	case (float)'0.0001':		$Z_unit =  3.890592;		break;
	case (float)'0.00001':		$Z_unit =  4.417173;		break;
	case (float)'0.000001':		$Z_unit =  4.891638;		break;
	case (float)'0.0000001':	$Z_unit =  5.326724;		break;

	default:
		$r = new RFace($Config->path_to_r);
//		show_var($d=sprintf("qnorm(%E, lower.tail=FALSE)", $adjusted_alpha));
		list($Z_unit) = $r->read_execute(sprintf("qnorm(%E, lower.tail=FALSE)", $adjusted_alpha/2.0));
		unset($r);
		break;
	}
}








/* assemble the main SQL query */

	/*

	Compare similar variable definitions in colloc-lib.inc.php
		
	2-by-2 contingency table
	
	--------------------------------
	|        | Col 1 | Col 2 |  T  |
	--------------------------------
	| Row 1  | $O11  | $O12  | $R1 |
	|        | $E11  | $E12  |     |
	--------------------------------
	| Row 2  | $O21  | $O22  | $R2 |
	|        | $E21  | $E22  |     |
	--------------------------------
	| Totals | $C1   | $C2   | $N  |
	--------------------------------
	
	N   = total tokens in both (sub)corpora
	C1  = frequency of the item across both (sub)corpora
	C2  = frequency of items that aren't the item across both (sub)corpora 
	R1  = total tokens in Corpus1
	R2  = total tokens in Corpus2
	O11 = how many of item there are in Corpus1
	O12 = how many items that aren't the item there are in Corpus1
	O21 = how many of item there are in Corpus2
	O22 = how many items other than the item there are in Corpus2
	E11 = expected values (proportion of item that would belong in Corpus1 if item were spread evenly)
	E12 =     "    "      (proportion of item that would belong in Corpus2 if item were spread evenly)
	E21 =     "    "      (proportion of other items that would belong in Corpus1 if item were spread evenly)
	E22 =     "    "      (proportion of other items that would belong in Corpus2 if item were spread evenly)
	
	*/

$N   = $corpus_tokens[1] + $corpus_tokens[2];
$R1  = "{$corpus_tokens[1]}";
$R2  = "{$corpus_tokens[2]}";
$O11 = "(1e0 * IFNULL({$table_name[1]}.freq, 0))";
$O21 = "(1e0 * IFNULL({$table_name[2]}.freq, 0))";
$O12 = "($R1 - $O11)";
$O22 = "($R2 - $O21)";
$C1  = "($O11 + $O21)";
$C2  = "($N - $C1)"; 
$E11 = "($R1 * $C1 / $N)";
$E12 = "($R1 * $C2 / $N)";
$E21 = "($R2 * $C1 / $N)";
$E22 = "($R2 * $C2 / $N)";




switch ($statistic)
{
	case 'LL':
	
		$extrastat_present = false;
		switch($what_to_show)
		{
		case 'onlyPos':
			$show_only_clause = "and freq1 > E1";
			break;
		case 'onlyNeg':
			$show_only_clause = "and freq1 < E1";
			break;
		case 'allKey':
			$show_only_clause = '';
			break;
		}
		$sql = "select 
			{$table_name[1]}.item as item,
			$O11 as freq1, 
			$O21 as freq2, 
			$E11 as E1, 
			$E21 as E2, 
			2 * ( IF($O11 > 0, $O11 * log($O11 / $E11), 0) 
				+ IF($O21 > 0, $O21 * log($O21 / $E21), 0)
				+ IF($O12 > 0, $O12 * log($O12 / $E12), 0)
				+ IF($O22 > 0, $O22 * log($O22 / $E22), 0)
				)
				as sortstat
			from {$table_name[1]}, {$table_name[2]}
			
			where {$table_name[1]}.item = {$table_name[2]}.item 
			and   $O11 >= {$minfreq[1]}
			and   $O21 >= {$minfreq[2]}
			
			having sortstat >= $threshold
			$show_only_clause
			order by sortstat desc 
			$limit_string
			";
		$using = "using log-likelihood statistic, significance cut-off " 
			. (100.0 * $alpha) 
			. "% (" . ($familywise_adjust ? 'adjusted ' : '') . "LL threshold = " . round($threshold, 2) . ");";
		break;



	case 'LR_LL':
	case 'LR_UN':
		
		/* Log Ratio unfiltered shows LL for info, so we use the same formula as for Log Ratio + LL filter, with minor tweaks */ 
		
		$extrastat_present = true;
		
		switch($what_to_show)
		{
		case 'onlyPos':
			$show_only_clause = "having (freq1 / $R1) > (freq2 / $R2)";
			$order_by_clause  = "order by sortstat desc";
			break;
		case 'onlyNeg':
			$show_only_clause = "having (freq1 / $R2) < (freq2 / $R1)";
			$order_by_clause  = "order by abs(sortstat) desc";
			break;
		case 'allKey':
			$show_only_clause = '';
			$order_by_clause  = 'order by abs(sortstat) desc';
			break;
		case 'lock':
			$show_only_clause = '';
			$order_by_clause  = 'order by abs(sortstat) asc';
			break;
		}
		/* ADD FILTER to the above.,*/
		if ($statistic == 'LR_LL')
		{
			/* KEY items mode only see above*/
			if (empty($show_only_clause))
				$show_only_clause = "having extrastat >= $threshold";
			else
				$show_only_clause = str_replace('having', "having extrastat >= $threshold and", $show_only_clause);
		}
		
		$sql = "select
			{$table_name[1]}.item as item,
			$O11 as freq1, 
			$O21 as freq2, 
			$E11 as E1, 
			$E21 as E2, 
			log2( ($O11 / $R1) / (IF($O21 > 0, $O21, 0.5) / $R2) ) as sortstat,
			2 * ( IF($O11 > 0, $O11 * log($O11 / $E11), 0) 
				+ IF($O21 > 0, $O21 * log($O21 / $E21), 0)
				+ IF($O12 > 0, $O12 * log($O12 / $E12), 0)
				+ IF($O22 > 0, $O22 * log($O22 / $E22), 0)
				)
				as extrastat

			from {$table_name[1]} left join {$table_name[2]}
			on {$table_name[1]}.item =  {$table_name[2]}.item
 
			where $O11 >= {$minfreq[1]}
			and   $O21 >= {$minfreq[2]}
			
			$show_only_clause
			$order_by_clause 
			$limit_string
			";
		if ($statistic == 'LR_LL')
			$using = 'using Log Ratio (with ' 
				. rtrim(sprintf('%.20f',(100.0 * $alpha)), '0')
				. '% significance filter, ' 
				. ($familywise_adjust ? 'adjusted ' : '') 
				. 'LL threshold = ' . round($threshold, 2) . ');';
		else
			$using = 'using Log Ratio (no filter applied, LL shown for information);';
		break;
	
	
	case 'LR_CI':
	
		/* Log Ratio with CI filter is a bit different .... */
	
		$extrastat_present = true;
		
		switch($what_to_show)
		{
		/* this switch adds the filter; the CI's relationship to 0 also determines positive versus negative keyness */
		case 'onlyPos':
			$show_only_clause = "having CI_lower >= 0";
			$order_by_clause  = "order by sortstat desc";
			break;
		case 'onlyNeg':
			$show_only_clause = "having CI_upper <= 0";
			$order_by_clause  = "order by abs(sortstat) desc";
			break;
		case 'allKey':
			$show_only_clause = 'having CI_lower >= 0 or CI_upper <= 0';
			$order_by_clause  = 'order by abs(sortstat) desc';
			break;
		case 'lock':
			$show_only_clause = 'having CI_lower <= 0 and CI_upper >= 0';
			$order_by_clause  = 'order by abs(sortstat) asc';
			break;
		}
		
		/* to stop the main stat formula getting too complex ... */
		$fragment_RRF    = "(($O11 / $R1) / (IF($O21 > 0, $O21, 0.5) / $R2))";
		$fragment_CIhalf = "($Z_unit * SQRT( ($O12 / ($R1 * IF($O11 > 0, $O11, 0.5))) + ($O22 / ($R2 * IF($O11 > 0, $O11, 0.5))) ))";

		$sql = "select
			{$table_name[1]}.item as item,
			$O11 as freq1, 
			$O21 as freq2, 
			$E11 as E1, 
			$E21 as E2, 
			log2( $fragment_RRF ) as sortstat,
			log2( exp(log($fragment_RRF) - $fragment_CIhalf) ) as CI_lower,
			log2( exp(log($fragment_RRF) + $fragment_CIhalf) ) as CI_upper,
			'CONFINTERVAL' as extrastat

			from {$table_name[1]} left join {$table_name[2]}
			on {$table_name[1]}.item =  {$table_name[2]}.item
 
			where $O11 >= {$minfreq[1]}
			and   $O21 >= {$minfreq[2]}
			
			$show_only_clause
			$order_by_clause 
			$limit_string
			";

		$using = 'using Log Ratio (filtered by ' 
			. (100.0 * (1.0-$alpha))
			. '% confidence interval' 
			. ($familywise_adjust ? (', adjusted to '.(100.0 * (1.0-$adjusted_alpha)).'%') : '') 
			. ');';
		break;

	

	case 'comp':
		/* we are in compare mode, not keyword mode */
		if ($empty == "f2")
		{
			$a = 2;		$b = 1;
		}
		else
		{
			$b = 2;		$a = 1;
		}

		$sql = "SELECT {$table_name[$a]}.item, {$table_name[$a]}.freq as freq$a, 0 as freq$b 
			FROM  {$table_name[$a]} left join {$table_name[$b]} on {$table_name[$a]}.item = {$table_name[$b]}.item 
			where {$table_name[$b]}.freq is NULL 
			order by {$table_name[$a]}.freq desc 
			$limit_string";
		break;
	
	default:
		exiterror("Undefined statistic!");
}

$result = do_mysql_query($sql);

$n = mysql_num_rows($result);


$next_page_exists = ( $n == $per_page ? true : false );


/* calculate the description line */
switch ($mode)
{
case 'key':
	$description = 'Key' . ($att_for_comp == 'word' ? '' : ' ') 
		. strtolower($att_desc[$att_for_comp]) . ' list for '
		. "{$table_desc[1]} compared to {$table_desc[2]};<br>$using"
		. ( ($minfreq[1] > 1 || $minfreq[2] > 0) 
				? "<br>items must have minimum frequency {$minfreq[1]} in list #1 and {$minfreq[2]} in list #2."
				: "<br>no frequency minima." )
		;
	break;
	
case 'comp':
	$description = 'Items which occur in  ' 
		. $table_desc[$title_bar_index]
		. ' but not in ' 
		. $table_desc[$title_bar_index_other]
		. ', sorted by frequency'
		;
	break;
	
case 'lock':
	$description = 'Lock' . ($att_for_comp == 'word' ? '' : ' ') 
		. strtolower($att_desc[$att_for_comp]) . ' list for '
		. "{$table_desc[1]} compared to {$table_desc[2]};<br>$using"
		. ( ($minfreq[1] > 1 && $minfreq[2] > 0) 
				? "<br>items must have minimum frequency {$minfreq[1]} in list #1 and {$minfreq[2]} in list #2."
				: "<br>no frequency minima." )
		;
	break;
	
default:
	/* it shouldn't be able to get to here, but if it does, */
	exiterror('Keywords function: unreachable mode was reached!!');
	break;
}
switch ($what_to_show)
{
case 'onlyPos':
	$description .= '<br>Showing positively key items only.';
	break;
case 'onlyNeg':
	$description .= '<br>Showing negatively key items only.';
	break;
}

/* print the result */

if ($download_mode)
{
	keywords_write_download($att_desc[$att_for_comp], $description, $result, $corpus_tokens);
}
else
{
	echo print_html_header($Corpus->title, $Config->css_path);

	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="4">
				<?php echo $description; ?>
			</th>
		</tr>
		<?php echo print_keywords_control_row($page_no, $next_page_exists, $what_to_show); ?>
	</table>
	
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" rowspan="2">No.</th>
			<th class="concordtable" rowspan="2" width="25%"><?php echo $att_desc[$att_for_comp]; ?></th>
			<th class="concordtable" colspan="2">In <?php echo $table_desc[1]; ?>:</th>
			<?php if ('comp'!=$mode) 
			{
			?>
				<th class="concordtable" colspan="2">In <?php echo $table_desc[2]; ?>:</th>
				<th class="concordtable" rowspan="2">+/-</th>
				<th class="concordtable" rowspan="2"><?php echo $stat_sort_col_head[$statistic]; ?></th>

			<?php
				if ($extrastat_present)
					echo "\t\t\t\t<th class=\"concordtable\" rowspan=\"2\">" . $stat_sort_col_head[($statistic == 'LR_CI'?'CI':'LL')] . '</th>';
					/* extra stat is always log likelihood or confidence interval, though the "CI" entry in the array is a dirty hack */
			}
			?>
		</tr>
		<tr>
			<th class="concordtable">Frequency<br>(absolute)</th>
			<th class="concordtable">Frequency<br>(per mill)</th>
			<?php if ('comp'!=$mode) { ?>
				<th class="concordtable">Frequency<br>(absolute)</th>
				<th class="concordtable">Frequency<br>(per mill)</th>
			<?php } ?>
		</tr>
	
	<?php
	
	/* this is the number SHOWN on the first line; the value of $i is (relatively speaking) 1 less than this */
	$begin_at = (($page_no - 1) * $per_page) + 1; 

	for ( $i = 0 ; $i < $n ; $i++ )
		echo "\n\t<tr>"
			, print_keyword_line(mysql_fetch_object($result), ($begin_at + $i), $att_for_comp, $restrict_string, $corpus_tokens)
			, "\t</tr>"
 			;

	echo "\n\n</table>\n\n";
	
	echo print_html_footer('keywords');
}



cqpweb_shutdown_environment();



/* ------------- *
 * end of script *
 * ------------- */







/**
 * Print a single line of the keyword data display (returns HTML string for printing).
 * 
 * @param stdClass $data           Databse object for one row of the key-items query result.
 * @param int      $line_number    Line number to print
 * @param string   $att_for_comp   Comparision attribute (for links to queries).
 * @param array    $restricts      The code for URL-format "restrictions" for the two (sub)corpora. At keys 1 and 2. 
 * @param array    $corpus_size    Szie of corpus (for calculating freq per million). Array of two ints at keys 1 and 2.
 * @return string                  An HTML stirng
 */
function print_keyword_line($data, $line_number, $att_for_comp, $restricts, $corpus_size)
{
	global $Corpus;
	/* the format of "data" is as follows
	object(stdClass)(6) {
	  ["item"]=>
	  ["freq1"]=>
	  ["freq2"]=>
	  ["E1"]=>
	  ["E2"]=>
	  ["sortstat"]=>
	  ["extrastat"]=>         (only in some modes)
	  ["CI_upper"]=>          (only when "extrastat" is set to string "CONFINTERVAL")
	  ["CI_lower"]=>          (ditto)
	unless mode is comparison in which case we only have item and freq1, freq2.
	*/

	if (isset($data->sortstat)) /* which is to say, if mode is keyword rather than comparison */
	{
		/* td classes for this line */
		if ( $data->freq1 > $data->E1 )
		{
			/* positively key */
			$plusminus = '+';
			$leftstyle = 'concordgeneral';
			$rightstyle = 'concordgrey';
		}
		else
		{
			/* negatively key */
			$plusminus = '-';
			$leftstyle = 'concordgrey';
			$rightstyle = 'concordgeneral';
		}
	}
	else
		$leftstyle = $rightstyle = 'concordgeneral';
	
	/* links do not appear if restricts[1|2] == false */
	$target = CQP::escape_metacharacters($data->item);
	
	/* Note use of '%c' or not in links below depends on corpus-level settings */
	
	$link[1] = ( $restricts[1] !== false && $data->freq1 > 0
		? 'href="concordance.php?theData=' 
			. urlencode("[$att_for_comp=\"{$target}\"{$Corpus->cqp_query_default_flags}]")
			. $restricts[1] 
			. '&qmode=cqp&uT=y"'
		: '' ) ;
	$link[2] = ( $restricts[2] !== false && $data->freq2 > 0
		? 'href="concordance.php?theData=' 
			. urlencode("[$att_for_comp=\"{$target}\"{$Corpus->cqp_query_default_flags}]")
			. $restricts[2] 
			. '&qmode=cqp&uT=y"'
		: '' ) ;
	
	
	$string = '';	
	
	$string .= "\n\t\t<td class=\"concordgeneral\" align=\"center\">$line_number</td>";
	$string .= "\n\t\t<td class=\"$leftstyle\"><b>{$data->item}</b></td>";
	$string .= "\n\t\t<td class=\"$leftstyle\"  align=\"center\"><a $link[1]>"
		. number_format((float)$data->freq1) . '</a></td>';
	$string .= "\n\t\t<td class=\"$leftstyle\"  align=\"center\">" 
		. number_format(1000000*($data->freq1)/$corpus_size[1] , 2). "</td>";
	if (isset($data->sortstat)) 
	{
		$string .= "\n\t\t<td class=\"$rightstyle\" align=\"center\"><a $link[2]>" 
			. number_format((float)$data->freq2) . '</a></td>';
		$string .= "\n\t\t<td class=\"$rightstyle\" align=\"center\">" 
			. number_format(1000000*($data->freq2)/$corpus_size[2] , 2). "</td>";
	}
	if (isset($plusminus))
	{
		$string .= "\n\t\t<td class=\"concordgrey\" align=\"center\">$plusminus</td>";
		$string .= "\n\t\t<td class=\"concordgrey\" align=\"center\">" . round($data->sortstat, 2) . '</td>';
	}
	if (isset($data->extrastat))
		$string .= "\n\t\t<td class=\"concordgrey\" align=\"center\">" 
			. ( 'CONFINTERVAL' == $data->extrastat ? (round($data->CI_lower, 2).', '.round($data->CI_upper, 2)) : round($data->extrastat, 2) ) 
			. '</td>';
	$string .= "\n";
	
	return $string;
}


function print_keyword_line_plaintext($data, $line_number, $eol, $corpus_size)
{
	/* simpler version of above for plaintext mode	*/

	if (isset($data->sortstat)) /* which is to say, if mode is keyword rather than comparison */
		$plusminus = ($data->freq1 > $data->E1 ? '+' : '-');
	
	$string = "$line_number\t{$data->item}\t{$data->freq1}\t" 
		. number_format(1000000*($data->freq1)/$corpus_size[1] , 2, '.', '');
	if (isset($plusminus))
		$string .= "\t{$data->freq2}\t" 
			. number_format(1000000*($data->freq2)/$corpus_size[2] , 2, '.', '') 
			. "\t$plusminus\t" 
			. round($data->sortstat, 2);
	if (isset($data->extrastat))
		$string .= "\t" . ( 'CONFINTERVAL' == $data->extrastat ? (round($data->CI_lower, 2).', '.round($data->CI_upper, 2)) : round($data->extrastat, 2) ) ;
	$string .= $eol;
	
	return $string;
}


function keywords_write_download($att_desc, $description, $result, $corpus_size)
{
	global $User;
	$eol = get_user_linefeed($User->username);
	$description = preg_replace('/&[lr]dquo;/', '"', $description);
	$description = str_replace("<br>", $eol, $description);

	header("Content-Type: text/plain; charset=utf-8");
	header("Content-disposition: attachment; filename=key_item_list.txt");
	echo "$description$eol";
	echo "__________________$eol$eol";
	echo "Number\t$att_desc\tFreq";


	if (substr($description, 0, 3) == 'Key' || substr($description, 0, 4) == 'Lock')
	{
		echo " 1\tFreq 1 (per mill)\tFreq 2\tFreq 2 (per mill)\t+/-";
		if ( 'extrastat' == mysql_field_name($result, mysql_num_fields($result)-1) )
			echo "\tStat 1.\tStat 2.";
		else
			echo "\tStat.";	
	}
			
	echo "$eol$eol";


	for ($i = 1; ($r = mysql_fetch_object($result)) !== false ; $i++ )
		echo print_keyword_line_plaintext($r, $i, $eol, $corpus_size);
}



//TODO: add an html parameter, and use it. 
function parse_keyword_table_parameter($par)
{
	global $Corpus;
	
	/* set the values that kick in if nothing else is found */
	$subcorpus = '';
	$base = false;
	$desc = '';
	$foreign = false;


	/* --whole of rest of function is one big if-else ladder-- */
	
	/*
	 * Note that in the parameters being parsed,
	 * 
	 * sc = local subcorpus; represented by its integer ID.
	 * pc = public corpus; represented by its name
	 * ps = public subcorpus; represented by the corresponding freqtable_name. 
	 * 
	 * The returned values are:
	 * 0 => subcorpus integer id, or string "__entire_corpus". 
	 * 1 => base of freqtable table names
	 * 2 => printable HTML desc of the corpus/subcorpus
	 * 3 => bool "foreign": is the corpus/subcorpus the same as $Corpus, i.e. the corpus we are in right now?
	 */
	
	if ($par == '__entire_corpus')
	{
		$subcorpus = "__entire_corpus";
		$base = "freq_corpus_" . $Corpus->name;
		$desc = "whole &ldquo;" . escape_html($Corpus->title) . "&rdquo;";
	}
	
	/* it's a subcorpus in this corpus */
	else if (substr($par, 0, 3) == 'sc~')
	{
		if (0 < preg_match('/sc~(\d+)/', $par, $m))
		{
			$sc_record = Subcorpus::new_from_id($m[1]);
			if (false === $sc_record)
				exiterror("The subcorpus you selected could not be found on the system!");
			$subcorpus = $sc_record->id;
// 			if (($base = get_subcorpus_freqtable($subcorpus)) == false)
			if (false == ($base = $sc_record->get_freqtable_base()))
				exiterror_general("The subcorpus you selected has no frequency list! Please compile the frequency list and try again.\n");
			$desc = "subcorpus &ldquo;{$sc_record->name}&rdquo;";
		}
	}
	
	/* foreign (public) corpus freqlist */
	else if (substr($par, 0, 3) == 'pc~')
	{
		$foreign = true;
		if (0 < preg_match('/pc~(\w+)/', $par, $m))
		{
			$subcorpus = "__entire_corpus";
			$base = "freq_corpus_{$m[1]}";
			
			$r = mysql_fetch_row(do_mysql_query("select public_freqlist_desc from corpus_info where corpus = '{$m[1]}'"));
			$desc = "corpus &ldquo;$r[0]&ldquo;";
		}
	}
	
	/* foreign (public) subcorpus freqlist : whole thing after ~ is the return val */
	else if (substr($par, 0, 3) == 'ps~')
	{
		$foreign = true;
		if (0 < preg_match('/ps~(\w+)/', $par, $m))
		{
			$base = $m[1];

			$r = mysql_fetch_assoc(do_mysql_query("select corpus, subcorpus from saved_freqtables where freqtable_name = '{$m[1]}'"));

			$sc_record = Subcorpus::new_from_id($r['subcorpus']);
			if (false === $sc_record)
				exiterror("The public subcorpus you selected could not be found on the system!");

			$subcorpus = (int) $r['subcorpus'];
			$desc = "subcorpus &ldquo;{$sc_record->name}&rdquo; from corpus &ldquo;{$r['corpus']}&rdquo;";
		}	
	}
	
	/* implied "else": nothing has matched  -- default values at top of function get returned. */


	return array($subcorpus, $base, $desc, $foreign);	
}




function print_keywords_control_row($page_no, $next_page_exists, $what_to_show)
{
	$marker = array( 'first' => '|&lt;', 'prev' => '&lt;&lt;', 'next' => "&gt;&gt;" );
	
	/* work out page numbers */
	$nav_page_no['first'] = ($page_no == 1 ? 0 : 1);
	$nav_page_no['prev']  = $page_no - 1;
	$nav_page_no['next']  = ( (! $next_page_exists) ? 0 : $page_no + 1);
	/* all page numbers that should be dead links are now set to zero  */
	
	$string = "\n<tr>\n";


	foreach ($marker as $key => $m)
	{
		$string .= '<td align="center" class="concordgrey"><b><a class="page_nav_links" ';
		$n = $nav_page_no[$key];
		if ( $n != 0 )
			/* this should be an active link */
			$string .= 'href="keywords.php?'
				. url_printget(array(
					array('pageNo', "$n")
					) )
				. '"';
		$string .= ">$m</b></a></td>";
	}
	
		

	$string .= 
		'<form action="redirect.php" method="get">
			<td class="concordgrey">
				<select name="redirect">
					<option value="newKeywords">New Keyword calculation</option>
					<option value="downloadKeywords" selected="selected">Download whole list</option>
					'
					. ( $what_to_show != 'allKey'  ? '<option value="showAll" >Show all keywords</option>'           : '' ) . PHP_EOL
					. ( $what_to_show != 'onlyPos' ? '<option value="showPos" >Show only positive keywords</option>' : '' ) . PHP_EOL
					. ( $what_to_show != 'onlyNeg' ? '<option value="showNeg" >Show only negative keywords</option>' : '' ) . PHP_EOL
					. ( $what_to_show != 'lock'    ? '<option value="showLock">Show lockwords</option>'              : '' ) . PHP_EOL 
					. '
					<option value="newQuery">New Query</option>

				</select>
				' .  url_printinputs() /* which includes uT */ . '
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="submit" value="Go!" />
			</td>
		</form>
	</tr>
	';

	return $string;
}





