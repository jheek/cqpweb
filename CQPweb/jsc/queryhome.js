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


/*
 * This file contains the setup and functions for the queryhome interface.
 */




function analysis_switch_tool()
{
	var new_tool = $("#analysisToolChoice").val();

	var callback = function()
	{
		/* get the new analysis tool */
		current_analysis_tool = $("#" + new_tool);
	
		/* and make it appear */
		current_analysis_tool.slideDown("slow");
	};

	/* if an analysis tool is visible, hide it. Then callback the reveal of the new tool. */
	if (current_analysis_tool)
	{
		if (new_tool != current_analysis_tool.attr('id'))
			current_analysis_tool.fadeOut("slow", callback);
	}
	else
		callback();
}




/*
 * The setup function.
 */
$(document).ready (function() {

	/* Setup the corpus analysis interface, if we have that form. */
	if ($("#analysisToolChoiceGo").length > 0)
	{
		window.current_analysis_tool = null;
		$("#analysisToolChoiceGo").click(analysis_switch_tool);

		/* now hide all the forms */
		$("#featureMatrixList").hide();
		$("#featureMatrixDesign").hide();

		/* greyouts on form submit... */
		$("#featureMatrixDesign").submit(function () { greyout_and_throbber(); return true; });
	}


} );



