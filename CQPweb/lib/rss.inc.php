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
 * This script generates an RSS feed containing the system messages. 
 * This is to make distributing alerts about downtime etc. a bit easier.
 */

require('../lib/environment.inc.php');
include("../lib/library.inc.php");
include("../lib/exiterror.inc.php");



cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP | CQPWEB_STARTUP_DONT_CHECK_URLTEST, RUN_LOCATION_RSS);

/* switch to text-mode errors, as they will  be sent out in an XML file */
$Config->debug_messages_textonly = true;


/* setup RSS variables
 *
 * We would normally do this in "defaults", but since these are so script-specific,
 * we'll do it here.
 */
if (! (isset($rss_feed_available) && $rss_feed_available) )
	exit();

if (!isset($rss_link))
	$rss_link = url_absolutify('..');

if (!isset($rss_description))
	$rss_description = 'Messages from the CQPweb server\'s administrator.';

if (!isset($rss_feed_title))
	$rss_feed_title = 'CQPweb System Messages';

// TODO, the above need moving into the Config object.

/* use output buffering because we want to serve as quick-and-easily as possible */
ob_start();

/* before anything else ... note type is text/xml not HTML */
header('Content-Type: text/xml; charset=utf-8');

/* this is to prevent ? > or < ? being dealt with as PHP delimiters;
 * that shouldn't happen as PHP is supposed to interleave with XML,
 * but in (at least) some versions, this is not working out right. 
 */
echo '<' , '?xml version="1.0" ?' , '>';

?>

<rss version="2.0">
<channel> 
	<title><?php echo $rss_feed_title; ?></title>     
	<link><?php echo $rss_link; ?></link> 
	<description><?php echo $rss_description; ?></description> 

<?php

$result = do_mysql_query("select * from system_messages order by timestamp desc");
if (mysql_num_rows($result) == 0)
{
	echo <<<END_OF_DUMMY_ITEM

	<item>
		<title>No messages at the moment!</title>
		<link>$rss_link?fakeArgumentFromRss=Dummy</link>
		<description>There are no messages from the CQPweb server just now.</description>
		<guid>no_messages_just_now</guid>
	</item>

END_OF_DUMMY_ITEM;

}
else 
{
	$i = 0;
	while (false != ($r = mysql_fetch_object($result)))
	{
		$i++;
		$r->timestamp = date(DATE_RSS, strtotime($r->timestamp));
		$r->content = htmlentities(str_replace("\n", "&nbsp;<br/>\n\t\t", $r->content));
		echo <<<ITEM_COMPLETE
	<item>
		<title>{$r->header}</title>
		<link>$rss_link</link>
		<description>{$r->content}</description>
		<pubDate>{$r->timestamp}</pubDate>
		<guid>{$r->message_id}</guid>
	</item>

ITEM_COMPLETE;
	}
}


cqpweb_shutdown_environment();

?>
</channel>
</rss>
<?php ob_end_flush(); ?>