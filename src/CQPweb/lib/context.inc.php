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


/** @file 
 * 
 * This file contains the code for showing extended context for a single result.
 */




/* initialise variables from settings files  */
require('../lib/environment.inc.php');

/* include function library files */
require('../lib/library.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/concordance-lib.inc.php');
require('../lib/metadata.inc.php');
require('../lib/cwb.inc.php');
require('../lib/cqp.inc.php');


/*
 * NOTE ON A KNOWN ISSUE
 * =====================
 * 
 * Links to context.php especially often get put into Excel spreadsheets. However, when a hyperlink is clicked in Excel,
 * the resulting HTTP call is done by a built-in Windows/IE component (Hlink.dll), not by the app
 * that is the default handler for URLs. The handling of the link is only passed off to the browser when the HTML is received. 
 * (The reason it does this is because it has to get the document in order to check whether the link is to an editable Office doc.
 * Because of course, the primary use case for HTTP is to edit Office documents on remote servers. What the hell do you 
 * schmucks use it for?)
 * 
 * Hlink will therefore not send CQPweb's cookie back - even if the user is already logged in on the default browser and that
 * browser is open - because cookies are not shared between Hlink.dll and Chrome /Firefox/whatever.
 * CQPweb will, as a result, send an "access denied" redirect, and the access denied URL gets passed to the browser
 * even though the user is actually logged in on that app. 
 * 
 * A typical user agent string from a GET request of this kind will pretend to be MSIE 7. For instance:
 * "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/6.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; 
 * Media Center PC 6.0; .NET4.0C; .NET4.0E; ms-office)"

 * But this user agent could easily crop up if the user is *really* using MSIE v 7. So, we cannot check for it.
 *
 * (Note, this issue affects *any* link in an MS Office link other than Outlook, which, for whatever reason, is immune. 
 * But as noted above it's most critical  for context.php.)
 *  
 * There is, fortunately, a workaround: copy-paste the link from Excel to the browser.
 */



cqpweb_startup_environment();


/* ----------------- *
 * Permissions check *
 * ----------------- */

if (PRIVILEGE_TYPE_CORPUS_RESTRICTED >= $Corpus->access_level)
{
	/*
	 * Context view is only available to those with a NORMAL or FULL level privilege for the present corpus.
	 * A user with only RESTRICTED level privilege is only able to view the concordance (fair-use snippets).
	 */
	echo print_html_header("{$Corpus->title} -- CQPweb query extended context", $Config->css_path);
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Restricted access: extended context cannot be displayed
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p class="spacer">&nbsp;</p>
				<p>
					You only have <b>restricted-level</b> access to this corpus. Extended context cannot be displayed.
					This is usually for copyright or licensing reasons.
				</p>
				<p class="spacer">&nbsp;</p>
			</td>
		</tr>
	</table>
	<?php

	echo print_html_footer('hello');
	cqpweb_shutdown_environment();
	exit;
}


/* ------------------------------- * 
 * initialise variables from $_GET * 
 * and perform initial fiddling    * 
 * ------------------------------- */



/* this script takes all of the GET parameters from concrdance.php */
/* but only qname is absolutely critical, the rest just get passed */
$qname = safe_qname_from_get();
	
/* all scripts that pass on $_GET['theData'] have to do this, to stop arg passing adding slashes */
if (isset($_GET['theData']))
	$_GET['theData'] = prepare_query_string($_GET['theData']);
// TODO arguably, the above should not exist.


/* parameters unique to this script */

if (isset($_GET['batch']))
	$batch = (int)$_GET['batch'];
else
	exiterror('Critical parameter "batch" was not defined!');

/* the show/hide tags button */

if (!isset($_GET['tagshow']))
	$_GET['tagshow'] = 0;

switch ((int) $_GET['tagshow'])
{
case 1:
	$show_tags = true;
	$tagshow_other_value = "0";
	$tagshow_button_text = 'Hide tags';
	break;

default:
	$show_tags = false;
	$tagshow_other_value = "1";
	$tagshow_button_text = 'Show tags';
	break;
}


if (isset($_GET['contextSize']))
	$context_size = (int)$_GET['contextSize'];
else
	$context_size = $Corpus->initial_extended_context;

/* restrict possible values */
if ($context_size > $Corpus->max_extended_context)
	$context_size = $Corpus->max_extended_context;
if ($context_size < $Corpus->initial_extended_context)
	$context_size = $Corpus->initial_extended_context;



/* the alt view parameter */

$use_alt_word_att = false;

if (empty($Corpus->alt_context_word_att))
{
	$alt_word_desc = ''; /* this var not printed in this case, but will be referenced, so give empty value */
	$fullwidth_colspan = 2;
}
else
{
	if (isset($_GET['altview']))
		$use_alt_word_att = (bool)$_GET['altview'];
	/* temp var needed for PHP 5.3 */
	$att_info = get_corpus_annotation_info();
	$alt_word_desc = $att_info[$Corpus->alt_context_word_att]->description;
//	PHP 5.4 +
//	$alt_word_desc = get_corpus_annotation_info()[$Corpus->alt_context_word_att]->description;
 	$fullwidth_colspan = 3;
}

/* the alt view button */

if ($use_alt_word_att)
{
	$altview_other_value = '0';
	$altview_button_text = 'Leave alternative view';
}
else
{
	$altview_other_value = '1';
	$altview_button_text = 'Switch to alternative view (' . $alt_word_desc . ')';
}


$primary_tag_handle = $Corpus->primary_annotation;

$cqp->execute("set Context $context_size words");


if ($Corpus->visualise_gloss_in_context)
	$cqp->execute("show +word +{$Corpus->visualise_gloss_annotation} ");
else
	$cqp->execute("show +word " . (empty($primary_tag_handle) ? '' : "+$primary_tag_handle "));
$cqp->execute("set PrintStructures \"text_id\""); 
$cqp->execute("set LeftKWICDelim '--%%%--'");
$cqp->execute("set RightKWICDelim '--%%%--'");


/* get an array containing the lines of the query to show this time */
$kwic = $cqp->execute("cat $qname $batch $batch");


if ($use_alt_word_att)
{
	/* get  a second kwic with the alt word  */
	
	/* first, reset the "show" by turning off everything turned on above */
	if ($Corpus->visualise_gloss_in_context)
		$cqp->execute("show -{$Corpus->visualise_gloss_annotation} ");
	if (! empty($primary_tag_handle))
		$cqp->execute("show -$primary_tag_handle ");
	$cqp->execute("show -word +{$Corpus->alt_context_word_att}");
	
	$alt_kwic = $cqp->execute("cat $qname $batch $batch");

	/* now, put alternative words in arrays that have the same indexes as the main kwic which we will get later on. */
	list ($alt_lc_s, $alt_node_s, $alt_rc_s) = preg_split("/--%%%--/", preg_replace("/\A\s*\d+: <text_id \w+>:/", '', $alt_kwic[0]));
	$alt_lc = explode(' ', trim($alt_lc_s));
	$alt_rc = explode(' ', trim($alt_rc_s));
	$alt_node = explode(' ', trim($alt_node_s));
}



/* process the single result -- code largely filched from print_concordance_line() */

/* extract the text_id and delete that first bit of the line */
preg_match("/\A\s*\d+: <text_id (\w+)>:/", $kwic[0], $m);
$text_id = $m[1];
$cqp_line = preg_replace("/\A\s*\d+: <text_id \w+>:/", '', $kwic[0]);

/* divide up the CQP line */
list($kwic_lc, $kwic_match, $kwic_rc) = preg_split("/--%%%--/", $cqp_line);	

/* just in case of unwanted spaces (there will deffo be some on the left) ... */
$kwic_rc = trim($kwic_rc);
$kwic_lc = trim($kwic_lc);
$kwic_match = trim($kwic_match);

/* create arrays of words from the incoming variables: split at space */	
$lc = explode(' ', $kwic_lc);
$rc = explode(' ', $kwic_rc);
$node = explode(' ', $kwic_match);

/* how many words in each array? */
$lcCount = count($lc);
$rcCount = count($rc);
$nodeCount = count($node);

//$word_extraction_pattern = (empty($primary_tag_handle) ? false : '/\A(.*)\/(.*?)\z/');

$line_breaker = ( $Corpus->main_script_is_r2l 
							? "</bdo>\n<br/>&nbsp;<br/>\n<bdo dir=\"rtl\">" 
							: "\n<br/>&nbsp;<br/>\n" 
							);

/* left context string */
$lc_string = '';
for ($i = 0; $i < $lcCount; $i++) 
{
	list($word, $tag) = extract_cqp_word_and_tag($lc[$i]);
	
	if ($use_alt_word_att)
		$word = escape_html($alt_lc[$i]);

	if ($i == 0 && preg_match('/\A[.,;:?\-!"\x{0964}\x{0965}]\Z/u', $word))
		/* don't show the first word of left context if it's just punctuation */
		continue;

	$lc_string .= $word . ( $show_tags ? bdo_tags_on_tag($tag) : '' ) . ' ';

	/* break line if this word is an end of sentence punctuation */
	if (preg_match('/\A[.?!\x{0964}]\Z/u', $word) || $word == '...'  )
		$lc_string .= $line_breaker;
}

/* node string */
$node_string = '';
for ($i = 0; $i < $nodeCount; $i++) 
{
	list($word, $tag) = extract_cqp_word_and_tag($node[$i]);
	
	if ($use_alt_word_att)
		$word = escape_html($alt_node[$i]);

	$node_string .= $word . ( $show_tags ? bdo_tags_on_tag($tag) : '' ) . ' ';

	/* break line if this word is an end of sentence punctuation */
	if (preg_match('/\A[.?!\x{0964}]\Z/u', $word) || $word == '...'  )
		$node_string .= $line_breaker;
}

/* rc string */
$rc_string = "";
for ($i = 0; $i < $rcCount; $i++) 
{
	list($word, $tag) = extract_cqp_word_and_tag($rc[$i]);

	if ($use_alt_word_att)
		$word = escape_html($alt_rc[$i]);
	
	$rc_string .= $word . ( $show_tags ? bdo_tags_on_tag($tag) : '' ) . ' ';

	/* break line if this word is an end of sentence punctuation */
	// TODO
	// this is a BAD regex (And see same regex in $lc above, as well as the first-word regex.)
	// potentially better version? need to test it, though
	//	if (preg_match('/\A\p{P}\Z/u', $word) || $word == '...' )
	if (preg_match('/\A[.?!\x{0964}]\Z/u', $word) || $word == '...' )
		$rc_string .= $line_breaker;
}

/* tags for Arabic, etc.: */
$bdo_tag1 = ($Corpus->main_script_is_r2l ? '<bdo dir="rtl">' : '');
$bdo_tag2 = ($Corpus->main_script_is_r2l ? '</bdo>' : '');



/*
 * and we are READY to RENDER .... !
 */

echo print_html_header("{$Corpus->title} -- CQPweb query extended context", $Config->css_path);



?>
<table class="concordtable" width="100%">
	<tr>
		<th colspan="<?php echo $fullwidth_colspan; ?>" class="concordtable">
			Displaying extended context for query match in text <i><?php echo $text_id; ?></i>
		</th>
	</tr>
	<tr>
		<form action="redirect.php" method="get">
			<td width="50%" align="center" class="concordgrey">
				<select name="redirect">
					<option value="fileInfo" selected="selected">
						File info for text <?php echo $text_id; ?>
					</option>
					<?php 
					if ($context_size < $Corpus->max_extended_context)
						echo '<option value="moreContext">More context</option>';
					if ($context_size > $Corpus->initial_extended_context)
						echo '<option value="lessContext">Less context</option>';
					?>
					<option value="backFromContext">Back to main query result</option>
					<option value="newQuery">New query</option>
				</select>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="submit" value="Go!" />
			</td>
		<input type="hidden" name="text" value="<?php echo $text_id; ?>" />
		<?php echo url_printinputs(array(
			array('text', ""), 
			array('contextSize', "$context_size"),
			array('redirect', "")
			)); ?> 
		</form>
		
		
		
		<form action="context.php" method="get">
			<td width="<?php echo ($fullwidth_colspan == 3 ? '25' : '50'); ?>%" align="center" class="concordgrey">
				&nbsp;
				<input type="submit" value="<?php echo $tagshow_button_text; ?>" />
				&nbsp;
			</td>
		<input type="hidden" name="tagshow" value="<?php echo $tagshow_other_value; ?>" />
		<?php echo url_printinputs(array(array('tagshow', ""), array('uT', ""))); ?><input type="hidden" name="uT" value="y"/>
		</form>
		
		<?php 
		
		if (!empty($Corpus->alt_context_word_att))
		{
			?>
			<form action="context.php" method="get">
				<td width="25%" align="center" class="concordgrey">
					&nbsp;
					<input type="submit" value="<?php echo $altview_button_text; ?>" />
					&nbsp;
				</td>
			<input type="hidden" name="altview" value="<?php echo $altview_other_value; ?>" />
			<?php echo url_printinputs(array(array('altview', ""), array("uT", ""))); ?><input type="hidden" name="uT" value="y"/>
			</form>		
			<?php 
		}
		
		?>
		
	</tr>
	
	<tr>
		<td colspan="<?php echo $fullwidth_colspan; ?>" class="concordgeneral">
			<p class="query-match-context" align="<?php echo ($Corpus->main_script_is_r2l ? 'right' : 'left'); ?>">
				<?php echo $bdo_tag1 , $lc_string , '<b>' , $node_string , '</b>' , $rc_string , $bdo_tag2; ?>
			</p>
		</td>
	</tr>
	
</table>

<?php



echo print_html_footer('hello');

cqpweb_shutdown_environment();



/* ------------- *
 * END OF SCRIPT *
 * ------------- */

/* Function that puts tags back into ltr order... */

function bdo_tags_on_tag($tag)
{
	//TODO 
	// this should be "in_context", but right now  extract_cqp_word_and_tag #
	// only uses $Corpus->visualise_gloss_in_concordance
	// so let's keep things consistent. 
	global $Corpus;
	
	return '_<bdo dir="ltr">' . ($Corpus->visualise_gloss_in_concordance ? $tag : substr($tag, 1)) . '</bdo>';
}

