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
 * This file contains functions supporting modal dialogs (i.e. centred popup boxes, with the window behind greyed out.)
 */

/* TODO: if we attach "please wait" to all form submits, then this should go in always. */


function greyout_on()
{
	var d = $("#div_for_greyout");

	if (d.length < 1)
	{
		greyout_setup();
		d = $("#div_for_greyout");
	}

	d.show();
}


function greyout_off()
{
	$("#div_for_greyout").hide();
}

function greyout_z_index()
{
	return Number($("#div_for_greyout").css("z-index"));
}


function greyout_setup()
{
	if (window.greyout_has_been_set_up)
		return;

	/* this styling makes a div grey-out the whole browser window. */
	var stylecode = "style=\"" +
		/* its size */
		"display:none; position:fixed; z-index:1000; top:0; left:0; height:100%; width:100%;" + 
		/* its coloring (note use of rgba so as not to affect children; note also #888888 fallback for old browsers) */
		" background-color:#888888; background-color: rgba(0, 0, 0, 0.5)" +
		"\"";

	/* create and insert.... note that it starts off as hidden, see style above. */
	$("<div id=\"div_for_greyout\" " + stylecode + "></div>").appendTo($("body"));

	window.greyout_has_been_set_up = true;
}

/* TODO generalise this function */
function greyout_and_throbber(msg)
{
	greyout_on();
	
	var t = $(
		"<table class='concordtable''><tr><td class='concordgeneral' align='center'><p>" +
		(msg ? msg : "<p>CQPweb is processing your request. Please wait.</p>") +
		"<p><img src='../css/img/throbber.gif'></p>"+
		"</td></tr></table>"
		);

	t.css({
			position: 'absolute',
			left: '50%',
			top: '50%',
			transform: 'translate(-50%, -50%)',
			'background-color': 'white'   /* i.e. provide a white backdrop over the greyout, like the base layer of the page. */
		});
	
	t.appendTo($("#div_for_greyout"));

}
