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
  * @file This file contains the script that manages the "Quick username search" function on the "Manage Users" page.
  */
 
 /**
  * We only create the function in global space if the element to anchor it on exists.
  */
$(document).ready (function() {
	
	/* create reusable jQuery variables in the document-onReady function's closure to avoid reconstructing on every keyup */
	var input = $("#userQuicksearch");

	/* stop dummy form from submitting, ever */	
	input.parent().submit(function() { return false; });
	
	if ( 0 < input.length ) 
	{	
		/* now we know we are on the user search page, set up the rest of the variables */
		var usernames = $("#userQuicksearchData").val().split("|");
		var results = $("#userQuicksearchResults");
		var anchor = $("#userQuicksearchResultsAnchor");

		/* and create the function that monitors what the user types in the quicksearch box */
		input.keyup( function() 
		{
			var text = input.val().toLowerCase();

			if ( 2 <= text.length )
			{
				results.detach();
				results.empty();
				
				/* build max 20 links from username array */
				var n = 0;
				for (var i = 0 ; i < usernames.length && n <= 20 ; i++)
					if ( text == usernames[i].substr(0, text.length).toLowerCase() )
						results.append('<li><a tabindex="' + (1+(n++)) + '" href="index.php?thisF=userView&username=' + usernames[i] + '&uT=y">' + usernames[i] + '</a></li>');

				/* set position of the ul and re-attach */
				results.appendTo(anchor);
				var coord = get_element_bottom_left_corner_coords(input[0]);
				results.css('left', 20 + coord[0] + input.width());
				results.css('top', coord[1] - (results.height()/2));
			}
			else
				results.detach(); /* removes from display if search string too short */
		} ); 
	}
} );
 
 
