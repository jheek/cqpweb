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
 * A file full of functions that generate handy bits of HTML.
 * 
 * ALL functions in this library *retuirn* a string rather than echoing it.
 * 
 * So, the return value can be echoed (to browser), or stuffed into a variable.
 */



function print_menurow_backend($link_handle, $link_text, $current_query, $http_varname, $script='index.php')
{
	$s = "\n<tr>\n\t<td class=\"";
	if ($current_query != $link_handle)
		$s .= "concordgeneral\">\n\t\t<a class=\"menuItem\""
			. " href=\"$script?$http_varname=$link_handle&uT=y\">";
	else 
		$s .= "concordgrey\">\n\t\t<a class=\"menuCurrentItem\">";
	$s .= "$link_text</a>\n\t</td>\n</tr>\n";
	return $s;
}

// TODO these should NOT use global state. Should use a parameter.

/**
 * Creates a table row for the index-page left-hand-side menu, which is either a link,
 * or a greyed-out entry if the variable specified as $current_query is equal to
 * the link handle. It is returned as a string, -not- immediately echoed.
 *
 * This is the version for the normal user-facing index.
 */
function print_menurow_help($link_handle, $link_text, $script='help.php')
{
	global $thisQ;
	return print_menurow_backend($link_handle, $link_text, $thisQ, 'thisQ', $script);
}
/**
 * Creates a table row for the index-page left-hand-side menu, which is either a link,
 * or a greyed-out entry if the variable specified as $current_query is equal to
 * the link handle. It is returned as a string, -not- immediately echoed.
 *
 * This is the version for the normal user-facing index.
 */
function print_menurow_index($link_handle, $link_text)
{
	global $thisQ;
	return print_menurow_backend($link_handle, $link_text, $thisQ, 'thisQ');
}
/**
 * Creates a table row for the index-page left-hand-side menu, which is either a link,
 * or a greyed-out entry if the variable specified as $current_query is equal to
 * the link handle. It is returned as a string, -not- immediately echoed.
 *
 * This is the version for adminhome.
 */
function print_menurow_admin($link_handle, $link_text)
{
	global $thisF;
	return print_menurow_backend($link_handle, $link_text, $thisF, 'thisF');
}

/**
 * Creates a table row for the index-page left-hand-side menu, which is a section heading
 * containing the label as provided.
 */
function print_menurow_heading($label)
{
	return "\n<tr><th class=\"concordtable\"><a class=\"menuHeaderItem\">$label</a></th></tr>\n\n";
}


/**
 * Print the "about CQPweb" block that appears at the bottom of the menu for both queryhome and userhome.
 * 
 * Returns string (does not echo automatically!) 
 */
function print_menu_aboutblock()
{
	return  print_menurow_heading('About CQPweb') . 
		<<<HERE

<tr>
	<td class="concordgeneral">
		<a class="menuItem" href="../"
			onmouseover="return escape('Go to the main homepage for this CQPweb server')">
			CQPweb main menu
		</a>
	</td>
</tr>
<tr>
	<td class="concordgeneral">
		<a class="menuItem" href="../usr"
			onmouseover="return escape('Account control and your personal settings')">
			Your user page
		</a>
	</td>
</tr>
<tr>
	<td class="concordgeneral">
		<a class="menuItem" target="cqpweb_help_browser" href="help.php"
			onmouseover="return escape('Open the help browser for this corpus')">
			Open help system
		</a>
	</td>
</tr>
<tr>
	<td class="concordgeneral">
		<a class="menuItem" target="_blank" href="http://www.youtube.com/playlist?list=PL2XtJIhhrHNQgf4Dp6sckGZRU4NiUVw1e"
			onmouseover="return escape('CQPweb video tutorials (on YouTube)')">
			Video tutorials
		</a>
	</td>
</tr>
HERE
		. print_menurow_index('who_the_hell', 'Who did it?')
		. print_menurow_index('latest', 'Latest news')
		. print_menurow_index('bugs', 'Report bugs');
}





/**
 * Create the content-specification rows of a metadata-design form.
 * For metadata table install and metadata template creation.
 * 
 * Requires the metadata-embiggen javascript.
 * 
 * TODO should the other 2 embiggenable forms be in the html-lib as well?
 */
function print_embiggenable_metadata_form($nrows = 5)
{
//TODOm paramterise the appearance of primaruy classification, which is not relevant for xml emtadata. 
	global $Config;
	
	/* "None" is impossdible in a metadata table; so is IDLINK (this kind of table is the target of an IDLINK!) */
				//TODO IDLINK *will* be possible for text_metadata! 
	$types_enabled = array(METADATA_TYPE_CLASSIFICATION, METADATA_TYPE_FREETEXT/*, METADATA_TYPE_UNIQUE_ID, METADATA_TYPE_DATE*/);
	/*note that we unique ID and DATE are temporarily disabled; will be reinserted later. */
	
	$options = '';
	foreach ($Config->metadata_type_descriptions as $value => $desc)
		if (in_array($value, $types_enabled))
			$options .= '<option value="' . $value . ($value == METADATA_TYPE_CLASSIFICATION ? '" selected="selected">' : '">') . $desc . '</option>';

	$rows_html = <<<END
	
			<tr>
				<th class="concordtable">&nbsp;</th>
				<th class="concordtable">Handle for this field</th>
				<th class="concordtable">Description for this field</th>
				<th class="concordtable">Datatype of this field</th>
				<th class="concordtable">Which field is the <br>primary classification?</th>
			</tr>
			
END;
 
	for ( $i = 1 ; $i <= $nrows ; $i++ )
		$rows_html .=  "
			<tr>
				<td class=\"concordgeneral\">Field $i</td>
				<td class=\"concordgeneral\" align=\"center\">
					<input type=\"text\" name=\"fieldHandle$i\" maxlength=\"64\" onKeyUp=\"check_c_word(this)\" />
				</td>
				<td class=\"concordgeneral\" align=\"center\">
					<input type=\"text\" name=\"fieldDescription$i\" maxlength=\"255\"/>
				</td>
				<td class=\"concordgeneral\" align=\"center\">
					<select name=\"fieldType$i\" align=\"center\">
						$options
					</select>
				</td>
				<td class=\"concordgeneral\" align=\"center\">
					<input type=\"radio\" name=\"primaryClassification\" value=\"$i\"/>
				</td>
			</tr>
		";

	$rows_html .= <<<END

			<tr id="metadataEmbiggenRow">
				<td colspan="5" class="concordgrey" align="center">
					&nbsp;<br/>
					<a onClick="add_metadata_form_row()" class="menuItem">[Embiggen form]</a>
					<br/>&nbsp;
				</td>
			</tr>

END;

	return $rows_html;
}






/** returns a 4-row uploaded file selector (radio button mode, name of inputs = 'dataFile'). */
function print_uploaded_file_selector()
{
	// make the "include zips" parameterisable in future? 
	// make the directory paramaterisable in future?

	global $Config;
	
	$file_list = scandir($Config->dir->upload);
	
	if (empty($file_list))
		return '<tr><td class="concordgeneral" align="center" colspan="4"><p>There are no files available.</p></td></tr>';

	$rows = '';
		
	foreach ($file_list as &$f)
	{
		$file = "{$Config->dir->upload}/$f";
		
		if (!is_file($file)) continue;
		
		if (substr($f,-3) === '.gz') continue;

		$stat = stat($file);
		
		$rows .= '
		
				<tr>
					<td class="concordgeneral" align="center">
						<input type="radio" name="dataFile" value="' . urlencode($f) . '" /> 
					</td>
					
					<td class="concordgeneral" colspan="2" align="left">' . $f . '</td>
					
					<td class="concordgeneral" align="right">
						' .  number_format(round($stat['size']/1024, 0)) . ' 
					</td>
				
					<td class="concordgeneral" align="center">
						' . date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']) . ' 
					</td>		
				</tr>

		';
	}
	return $rows;
}








/**
 * Creates an HTML page footer for all flavours of CQPweb page.
 * 
 * It takes a single argument: the required help page to link to
 * (see short codes in help.inc.php) -- if an empty value or nothing is passed,
 * no help link will appear. 
 * 
 * If "hello" is passed, a link to the "Hello" page appears.
 * 
 * @return  String containing the HTML page footer.
 * 
 */
function print_html_footer($helplink = false)
{
	global $User;

	$v = CQPWEB_VERSION;

	if (!empty($helplink))
		$help_cell = '<a class="cqpweb_copynote_link" href="help.php?'
			. ($helplink == 'hello' ? 'thisQ=hello' : 'vidreq=' . $helplink) 
			. '" target="cqpweb_help_browser">' 
			. ($helplink ==  'hello' ? 'Help! on CQPweb' : 'Help! for this screen') 
			. '</a>'
			;
	else
		$help_cell = '&nbsp;';

	if (! (isset($User) && $User->logged_in))
		$lognote = 'You are not logged in';
	else
		$lognote = "You are logged in as user [{$User->username}]";

	return <<<RETURN_ME

	<hr/>
	<table class="concordtable" width="100%">
		<tr>
			<td align="left" class="cqpweb_copynote" width="33%">
				CQPweb v$v &#169; 2008-2016
			</td>
			<td align="center" class="cqpweb_copynote" width="33%">
				$help_cell
			</td>
			<td align="right" class="cqpweb_copynote" width="33%">
				$lognote
			</td>
		</tr>
	</table>
	<script language="JavaScript" type="text/javascript" src="../jsc/wz_tooltip.js"></script>
	</body>
</html>

RETURN_ME
	;
	// TODO: get rid of JS insert above once wz_tooltip has been dealt with.
}



/**
 * Create an HTML header (everything from <html> to <body>,
 * which specified the title as provided, embeds a CSS link,
 * and finally imports the specified JavaScript files.
 */
function print_html_header($title, $css_url, $js_scripts = false)
{
	global $Config;
	global $User;
	
	/* also set the generic header (will only be sent when the header is echo'd, though) */
	if (!headers_sent())
		header('Content-Type: text/html; charset=utf-8');
	
	$s = "<html>\n<head>\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" >\n";

	$s .= "\t<title>$title</title>\n";

	/* override the CSS url with accessibility style option if enabled;
	 * isset() check necessary because in case of error this can be called before $User setup. */
	if (isset($User) &&$User->logged_in && $User->css_monochrome)
		$css_url = ($Config->run_location == RUN_LOCATION_MAINHOME ? 'css' : '../css') . '/CQPweb-user-monochrome.css';
	
	if (!empty($css_url))
		$s .= "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"$css_url\" >\n";

	$js_path = ($Config->run_location == RUN_LOCATION_MAINHOME ? 'jsc' : '../jsc');

	if (empty($js_scripts))
		$js_scripts = array('jquery', 'always');
	else
		array_unshift($js_scripts, 'jquery', 'always');
	
	foreach ($js_scripts as $js)
		$s .= "\t<script type=\"text/javascript\" src=\"$js_path/$js.js\"></script>\n";
	
	$s .= "</head>\n<body>\n";
	
	return $s;
}

/**
 * The login form is used in more than one place, so this function 
 * puts the code in just one place.
 */
function print_login_form($location_after = false)
{
	global $Config;
	
	if ($Config->run_location == RUN_LOCATION_USR)
		$pathbegin = '';	
	else if ($Config->run_location == RUN_LOCATION_MAINHOME)
		$pathbegin = 'usr/';
	else
		/* in a corpus, or in adm */
		$pathbegin = '../usr/';
	
	/* pass through a location after, if one was given */
	$input_location_after = (empty($location_after) 
								? '' 
								: '<input type="hidden" name="locationAfter" value="'.escape_html($location_after).'" />'
								);
		
	return <<<HERE

				<form action="{$pathbegin}redirect.php" method="POST">
					<table class="basicbox" style="margin:auto">
						<tr>
							<td class="basicbox">Enter your username:</td>
							<td class="basicbox">
								<input type="text" name="username" width="30" onKeyUp="check_c_word(this)" />
							</td>
						</tr>
						<tr>
							<td class="basicbox">Enter your password:</td>
							<td class="basicbox">
								<input type="password" name="password" width="100"  />
							</td>
						</tr>
						<tr>
							<td class="basicbox">Tick here to stay logged in on this computer:</td>
							<td class="basicbox">
								<input type="checkbox" name="persist" value="1"  />
							</td>
						</tr>
						<tr>
							<td class="basicbox" align="right">
								<input type="submit" value="Click here to log in"  />
							</td>
							<td class="basicbox" align="left">
								<input type="reset" value="Clear form"  />
							</td>
						</tr>
						$input_location_after
						<input type="hidden" name="redirect" value="userLogin" />
						<input type="hidden" name="uT" value="y" />
					</table>
				</form>

HERE;

}


/**
 * Dumps out a reasonably-nicely-formatted representation of an
 * arbitrary MySQL query result.
 * 
 * For debug purposes, or for when we have not yet written the code for a nicer layout.
 * 
 * @param $result  A result resource returned by do_mysql_query().  
 */ 
function print_mysql_result_dump($result)
{
	/* print column headers */
	$table = "\n\n<!-- MYSQL RESULT DUMP -->\n\n" . '<table class="concordtable" width="100%"><tr>';
	for ( $i = 0 ; $i < mysql_num_fields($result) ; $i++ )
		$table .= "<th class='concordtable'>" . mysql_field_name($result, $i) . "</th>";
	$table .= '</tr>';
	
	/* print rows */
	while ( ($row = mysql_fetch_row($result)) !== false )
	{
		$table .= "<tr>";
		foreach ($row as $r)
			$table .= "<td class='concordgeneral' align='center'>$r</td>\n";
		$table .= "</tr>\n";
		/* stop REALLY BIG tables from exhausting PHP's allowed memory:
		 * allow this string to be 50MB long max (Even that's a push!) */
		if (52428800 >= strlen($table))
		{
			$table .= '</table>
					<table class="concordtable" width="100%"><tr><th class="concordtable">Table is too big to show, TRUNCATED!</td></tr>
					';
			break;
		}
	}
	
	$table .= "</table>\n\n";	
	return $table;
}


