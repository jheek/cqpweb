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






/* initialise variables from settings files  */
require('../lib/environment.inc.php');


/* include function library files */
require('../lib/library.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/metadata.inc.php');


cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP);




/* initialise variables from $_GET */

if (empty($_GET["text"]) )
	exiterror_general("No text was specified for metadata-view! Please reload CQPweb.");
else 
	$text_id = cqpweb_handle_enforce($_GET["text"]);
	

$result = do_mysql_query("SELECT * from text_metadata_for_{$Corpus->name} where text_id = '$text_id'");

if (mysql_num_rows($result) < 1)
	exiterror_general("The database doesn't appear to contain any metadata for text $text_id.");

$metadata = mysql_fetch_row($result);


/*
 * Render!
 */

echo print_html_header($Corpus->title . ': viewing text metadata -- CQPweb', $Config->css_path, array('modal', 'textmeta'));

?>

<table class="concordtable" width="100%">
	<tr>
		<th colspan="2" class="concordtable">Metadata for text <em><?php echo $text_id; ?></em></th>
	</tr>

<?php

$n = count($metadata);

for ( $i = 0 ; $i < $n ; $i++ )
{
	$att = metadata_expand_attribute(mysql_field_name($result, $i), $metadata[$i]);
	
	/* this expansion is hardwired */
	if ($att['field'] == 'text_id')
		$att['field'] =  'Text identification code';
	/* this expansion is hardwired */
	if ($att['field'] == 'words')
		$att['field'] =  'No. words in text';
	/* don't show the CQP delimiters for the file */
	if ($att['field'] == 'cqp_begin' || $att['field'] == 'cqp_end')
		continue;

	/* don't allow empty cells */
	if (empty($att['value']))
		$show = '&nbsp;';
	else
		$show = $att['value'];
		
	
	
	/* if the value is a URL, convert it to a link;
	 * also allow audio, image, video, YouTube embeds */
	if (false !== strpos($show, ':') )
	{
		list($prefix, $url) = explode(':', $show, 2);
		
		switch($prefix)
		{
		case 'http':
		case 'https':
		case 'ftp':
			/* pipe is used as a delimiter between URL and linktext to show. */
			if (false !== strpos($show, '|'))
			{
				list($url, $linktext) = explode('|', $show);
				$show = '<a target="_blank" href="'.$url.'">'.$linktext.'</a>';
			}
			else
				$show = '<a target="_blank" href="'.$show.'">'.$show.'</a>';
			break;
			
		case 'youtube':
			/* if it's a YouTube URL of one of two kinds, extract the ID; otherwise, it should be a code already */
			if (false !== strpos($url, 'youtube.com'))
			{
				/* accept EITHER a standard yt URL, OR a yt embed URL. */
				if (preg_match('|(?:https?://)(?:www\.)youtube\.com/watch\?.*?v=(.*)[\?&/]?|i', $url, $m))
					$ytid = $m[1]; 
				else if (preg_match('|(?:https?://)(?:www\.)youtube\.com/embed/(.*)[\?/]?|i', $url, $m))
					$ytid = $m[1];
				else
					/* should never be reached unless bad URL used */
					$ytid = $url;
			}
			else
				$ytid = $url;
			$show = '<iframe width="640" height="480" src="http://www.youtube.com/embed/' . $ytid . '" frameborder="0" allowfullscreen></iframe>';
			break;
			
		case 'video':
			/* we do not specify height and width: we let the video itself determine that. */
			$show = '<video src="' . $url . '" controls preload="metadata"><a target="_blank" href="' . $url . '">[Click here for videofile]</a></video>';
			break;
			
		case 'audio':
			$show = '<audio src="' . $url . '" controls><a target="_blank" href="' . $url . '">[Click here for audiofile]</a></audio>';
			break;
		
		case 'image':
			/* Dynamic popup layer: see textmeta.js */
			$show = '<a class="menuItem" href="" onClick="textmeta_add_iframe(&quot;' . $url . '&quot;); return false;">[Click here to display]</a>';
			break;
					
		default;
			/* unrecognised prefix: treat as just normal value-content */
			break;
		}
	}
	
	echo '<tr><td class="concordgrey">' , $att['field']
		, '</td><td class="concordgeneral">' , $show
		, (isset($js) ? $js : '')
		, "</td></tr>\n"
		;
}



echo '</table>';

echo print_html_footer('textmeta');

cqpweb_shutdown_environment();


/* 
 * one support function for the above
 */

function get_js_for_embedded_image($url)
{
	$js = <<<END_OF_JS




END_OF_JS;
	
	return  $js;
}

