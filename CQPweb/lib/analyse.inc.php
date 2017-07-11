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
 * page scripting the interface for corpus analysis. 
 * 
 * Currently only allows multivariate analysis, but hopefully will allow
 * others later, including custom analysis.
 */

/* include defaults and settings */
require('../lib/environment.inc.php');


/* library files */
require('../lib/library.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/cache.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/xml.inc.php');
require('../lib/multivariate.inc.php');
require('../lib/rface.inc.php');
require('../lib/cwb.inc.php');
require('../lib/cqp.inc.php');


cqpweb_startup_environment();


//temp shortcut -- will eventually be a switch on a parameter here
output_analysis_factanal();


/* shutdown and end script */
cqpweb_shutdown_environment();

/*
 * =============
 * END OF SCRIPT
 * =============
 */ 

/** 
 * Support function converting R output lines to an HTML blob
// TODO this whole function is a dirty hack. Get the info bit by bit form the R object instead.
 */
function create_rendered_factanal($lines_from_r)
{
	for ($i = 0, $n = count($lines_from_r) ; $i < $n ; $i++ )
	{
		switch (trim($lines_from_r[$i]))
		{
		case 'Uniquenesses:':
			$ix_uniqueness_begin = $i + 1;
			break;
		case 'Loadings:':
			$ix_loadings_label = $i;
			break;
		default:
			/* things that need a substring test... */
			if ('Factor1' == substr($lines_from_r[$i], 0, 7))
			{
				if (($i - 1) == $ix_loadings_label)
					$ix_loadings_begin = $i + 1;
				else
				{
					$ix_loadings_end = $i -1;
					$ix_more_loadings_begin = $i + 1;
					$lines_from_r[$i+1] = str_replace('SS loadings', 'Sum-of-Squares&nbsp;loadings', $lines_from_r[$i+1]);
					$lines_from_r[$i+2] = str_replace('Proportion Var', 'Proportion&nbsp;of&nbsp;variance&nbsp;explained', $lines_from_r[$i+2]);
					$lines_from_r[$i+3] = str_replace('Cumulative Var', 'Cumulative&nbsp;variance&nbsp;explained', $lines_from_r[$i+3]);
				}
			}
			else if ('Test of the hypothesis' == substr($lines_from_r[$i], 0, 22))
				$sigtest_html = "<p><b>{$lines_from_r[$i]}</b></p><p>{$lines_from_r[$i+1]}</p><p>{$lines_from_r[$i+2]}</p>";
			break; 
		}
	}
	
	preg_match ('|The p-value is (.*?)<|', $sigtest_html, $m);
	if (isset($m[1]))
	{
		$p = (float)$m[1];
		$sigtest_html .= "<p>Interpretation: This solution probably <b>" . ($p > 0.05 ? 'does' : 'does not') . "</b> fit the data very well.</p>";
	}
		
	
	
//show_var($ix_loadings_begin); 
//show_var($ix_loadings_end); 
//show_var($ix_uniqueness_begin); 

//exit;
	
	/* build uniquenesses table */
	$colspan = ( $ix_loadings_end - $ix_loadings_begin)  + 1;
	$unique_html = <<<END
	&nbsp;<br>
	<table class="concordtable" align="center">
		<tr>
			<th class="concordtable" colspan="$colspan">Uniquenesses</th>
		</tr>
		<tr>
			FEATURES_HERE
		</tr>
		<tr>
			UNIQUENESSES_HERE
		</tr>
	</table>
	
END;
	$row_first  = '<td class="concordgeneral">' 
		. preg_replace("|\s+|", '</td><td class="concordgeneral">', $lines_from_r[$ix_uniqueness_begin])   
		. "</td>";
	$row_second = '<td class="concordgeneral">' 
		. preg_replace("|\s+|", '</td><td class="concordgeneral">', $lines_from_r[$ix_uniqueness_begin+1]) 
		. "</td>";
	$unique_html = str_replace('FEATURES_HERE', $row_first, $unique_html);
	$unique_html = str_replace('UNIQUENESSES_HERE', $row_second, $unique_html);
	
	/* build loadings tables */
	$colspan = 8; // TODO - N of factors + 1.
	$loadings_html = <<<END
	<br>&nbsp;<br>
	<table class="concordtable">
		<tr>
			<th class="concordtable" colspan="$colspan">Feature loadings</th>
		</tr>
END;
//dirty hack!
	$lines_from_r[$ix_loadings_begin-1] = ' '. $lines_from_r[$ix_loadings_begin-1];
	for ($i = $ix_loadings_begin-1 ; $i <= $ix_loadings_end; $i++)
		$loadings_html .= "\n<tr><td class=\"concordgeneral\">". preg_replace('|\s+|', '</td><td class="concordgeneral">', $lines_from_r[$i]) . "</tr>\n";
	$loadings_html .= <<<END
	</table>
END;
	
	$extra_html = <<<END
	<br>&nbsp;<br>
	<table class="concordtable">
		<tr>
			<th class="concordtable" colspan="$colspan">Factor analysis info</th>
		</tr>
END;
	$lines_from_r[$ix_more_loadings_begin-1] = ' '. $lines_from_r[$ix_more_loadings_begin-1];
	for ($i = $ix_more_loadings_begin-1; $i < $ix_more_loadings_begin + 3 ; $i++)
	{
//		show_var($extra_html);
		$extra_html .= "\n<tr><td class=\"concordgeneral\">". preg_replace('|\s+|', '</td><td class="concordgeneral">', $lines_from_r[$i]) . "</tr>\n";
	}	
	$extra_html .= <<<END
	</table>
END;

	
//	$html = $unique_html . $loadings_html . $extra_html . $sigtest_html;
	$html = $extra_html . $sigtest_html . $loadings_html . $unique_html;
	
$html .= '<hr><br><!--<pre>' . implode( "\n", $lines_from_r) . '</pre>-->';
	return $html;
}

/**
 * User interface function for factor analysis.
 * 
 * TODO an as-text version? For download.
 * TODO tidy up all the HTML.
 * 
 * TODO move to multivariate-interface.inc.php at some point, when there is more than one kind of "analysis"
 */
function output_analysis_factanal()
{
	global $Corpus;
	global $Config;
	

	// TODO - using R's factanal() manually, work out the minimum number of features needed for factor analysis.
	// Then check the matrix for this number of features and print an error message if absent.
	


	/* get matrix info object */
	
	if ( (!isset($_GET['matrix'])) || $_GET['matrix'] === '')
		exiterror("No feature matrix was specified for the analysis.");
	if (false === ($matrix = get_feature_matrix((int) $_GET['matrix'])))
		exiterror("The specified feature matrix does not exist on the system.");
	
	// TODO put here the check for the minimum number fo features in the matrix.


	/* import the matrix to R in raw form */
	$r = new RFace();
	insert_feature_matrix_to_r($r, $matrix->id, 'mydata');
	
	$op = array();
	
	// TODO maybe parameterise the "max number of factors" as an "advanced" option on the query page 
	foreach (array(2,3,4,5,6,7) as $i)
	{
		// TODO make rotation type an "advanced" option on the query page 
		// (advanced options to be hidden behind a JavaScript button of course)
		$r->execute("out = factanal(mydata, $i, rotation=\"varimax\")");
		// TODO arguments to pritn - do I want them to be thus?
		// digits = 2 probably correct, but sort=TRUE??????
		$op[$i] =  create_rendered_factanal( $r->execute("print(out, cutoff = 0, digits = 2, sort = TRUE)") );
	}


	
	/* ready to render */
	
	echo print_html_header($Corpus->title, $Config->css_path, array('modal', 'analyse-md'));
	
	
	?>

	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Analyse Corpus: Multidimensional Analysis of Feature Matrix
				&ldquo;<?php echo $matrix->savename; ?>&rdquo; 
			</th>
		</tr>
		<tr>
			<td class="concorderror">
				&nbsp;<br>
				<b>This function is currently under development</b>. 
				The statistical output is displayed, but not in fully cleaned-up form.
				<br>&nbsp;<br>
				The analysis is currently performed for the range 2 to 7 factors.
				<br>&nbsp;
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				&nbsp;<br>
				Use the buttons below to display different solutions.
				<br>&nbsp;<br>
				
				<?php
				
				foreach($op as $i => $solution)
					echo '<button id="solButton', $i, '" type="button">Show ', $i, '&ndash;factor solution</button>'
				
				?> 
				<br>&nbsp;<br>
			</td>
		</tr>
	</table>
			
		
	<?php
	
	foreach($op as $i => $solution)
	{
		// TODO - evenutally, solution will become an stdClass whose members correspond
		// to those of the R object (or, at least, which use regexen to slice up the print() output.)
		//
		// We can then insert formatting around and between the different bits (e.g. to render the tables
		// as actual HTML tables.
		echo "\n\t<table class=\"concordtable\" width=\"100%\" id=\"solution$i\">"
			, "\n\t\t<tr>"
			, "\n\t\t\t<th class=\"concordtable\">"
			, "Factor Analysis Output for $i factors</th>"
			, "\n\t\t</tr>\n\t\t<tr>"
			, "\n\t\t\t<td class=\"concordgeneral\">"
//			, "\n<pre>"
			, $solution
//			, "\n</pre>
			, "\n\t\t\t</td>\n\t\t</tr>"
			, "\n\t</table>\n"
			;
	}

	echo print_html_footer('hello');
}

