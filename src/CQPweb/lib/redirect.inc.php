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
 * This script processes the different commands that can be issued by the 
 * "redirect box" --  little dropdown that contains commands on various pages.
 * 
 * Because this dropdown goes to multiple pages, the redurect script is needed
 * to work out what page we're going to (and sometimes, to provide a filter
 * on the HTTP paramters - since the dropdown poitns to multiple functions,
 * it generates more parameters than any one function actually needs.
 * 
 * Sometimes, a script is "included" from here. In that case, anything in _GET
 * that is not explicitly cleared will be available to the included script.
 * 
 * Other times we use Location, and in that case, only the bits of _GET 
 * explicitly stuck into the URL will be available to the addressed page.
 * 
 * (Note: the only case in which Location is currently used is the "New Query" 
 * option. This could, in fact, be extended where that would make life neater.)
 * 
 */


if (isset($_POST['redirect']) && empty($_GET['redirect']))
	$_GET['redirect'] = $_POST['redirect'];

if ( ! isset($_GET['redirect']))
{
	?>
	
	<html>
	<head><title>Error!</title></head>
	<body>
		<pre>
		
			ERROR: Incorrectly formatted URL, or no redirect parameter provided.
			
			<a href="index.php">Please reload CQPweb</a>.
		</pre>
	</body>
	</html>
	
	<?php
	exit();
}
else
{	
	$redirect_script_redirector = $_GET['redirect'];
	unset ($_GET['redirect']);
	
	/* allow for custom plugins in concordance.php, whose redirect could be ANYTHING */
	if (substr($redirect_script_redirector, 0, 11) == 'CustomPost:')
	{
		$custom_pp_parameter = $redirect_script_redirector;
		$redirect_script_redirector = 'customPostprocess';
	}
	
	
	
	switch($redirect_script_redirector)
	{
	
	/* from more than one control box */
	
	case 'newQuery':
// 		foreach ($_GET as $k=>&$g)
// 			unset($_GET[$k]);
// 		require("../lib/queryhome.inc.php");
		header("Location: index.php");
		break;
	
	
	/* from control box in concordance.php */
	
	case 'thin':
		require("../lib/thin-control.inc.php");
		break;

	case 'breakdown':
		require("../lib/breakdown.inc.php");
		break;

	case 'distribution':
		require("../lib/distribution.inc.php");
		break;

	case 'sort':
		$_GET['program'] = 'sort';
		$_GET['newPostP'] = 'sort';
		$_GET['newPostP_sortPosition'] = 1;
		$_GET['newPostP_sortThinTag'] = '';
		$_GET['newPostP_sortThinTagInvert'] = 0;
		$_GET['newPostP_sortThinString'] = '';
		$_GET['newPostP_sortThinStringInvert'] = 0;
		unset($_GET['pageNo']);
		require("../lib/concordance.inc.php");
		break;
		
	case 'collocations':
		require("../lib/colloc-options.inc.php");
		break;

	case 'download-conc':
		/* nb. also comes from download-tab */
		require("../lib/download-conc.inc.php");
		break;

	case 'categorise':
		if (empty($_GET['categoriseAction']))
			$_GET['categoriseAction'] = 'enterCategories';
		require("../lib/categorise-admin.inc.php");
		break;
		
	case 'saveHits':
		require("../lib/savequery.inc.php");
		break;

	case 'customPostprocess':
		$_GET['newPostP'] = $custom_pp_parameter;
		unset($_GET['pageNo']);
		require("../lib/concordance.inc.php");
		break;


	/* from control box in context.php */
	
	case 'fileInfo':
		require("../lib/textmeta.inc.php");
		break;
		
	case 'moreContext':
		if (isset($_GET['contextSize']))
			$_GET['contextSize'] += 100;
		require("../lib/context.inc.php");
		break;
		
	case 'lessContext':
		if (isset($_GET['contextSize']))
			$_GET['contextSize'] -= 100;
		require("../lib/context.inc.php");
		break;
		
	case 'backFromContext':
		require("../lib/concordance.inc.php");
		break;



	/* from control box in distribution.php */
	
	case 'backFromDistribution':
		require("../lib/concordance.inc.php");
		break;
	
	case 'refreshDistribution':
		require("../lib/distribution.inc.php");
		break;
	
	case 'distributionDownload':
		$_GET['tableDownloadMode'] = 1;
		require("../lib/distribution.inc.php");
		break;
		
		
		
	/* from control box in collocation.php */

	case 'backFromCollocation':
		require("../lib/concordance.inc.php");
		break;

	case 'rerunCollocation':
		require("../lib/collocation.inc.php");
		break;

	case 'collocationDownload':
		$_GET['tableDownloadMode'] = 1;
		require("../lib/collocation.inc.php");
		break;
		


	/* from control box in keywords.php */
	
	case 'newKeywords':
		unset($_GET);
		$_GET['thisQ'] = 'keywords';
		require("../lib/queryhome.inc.php");
		break;

	case 'downloadKeywords':
		$_GET['tableDownloadMode'] = 1;
		require("../lib/keywords.inc.php");
		break;
		
	case 'showAll':
		unset($_GET['redirect']);
		$_GET['kwWhatToShow'] = 'allKey';
		unset($_GET['pageNo']);
		require('../lib/keywords.inc.php');
		break;
		
	case 'showPos':
		unset($_GET['redirect']);
		$_GET['kwWhatToShow'] = 'onlyPos';
		unset($_GET['pageNo']);
		require('../lib/keywords.inc.php');
		break;
		
	case 'showNeg':
		unset($_GET['redirect']);
		$_GET['kwWhatToShow'] = 'onlyNeg';
		unset($_GET['pageNo']);
		require('../lib/keywords.inc.php');
		break;
		
	case 'showLock':
		unset($_GET['redirect']);
		$_GET['kwWhatToShow'] = 'lock';
		unset($_GET['pageNo']);
		require('../lib/keywords.inc.php');
		break;
		
	


	/* from control box in freqlist.php */
	
	case 'newFreqlist':
		unset($_GET);
		$_GET['thisQ'] = 'freqList';
		require("../lib/queryhome.inc.php");
		break;

	case 'downloadFreqList':
		$_GET['tableDownloadMode'] = 1;
		require("../lib/freqlist.inc.php");
		break;



	/* from control box in breakdown.php */
	
	case 'concBreakdownWords':
		unset($_GET['concBreakdownWords']);
		$_GET['concBreakdownOf'] = 'words';
		require("../lib/breakdown.inc.php");
		break;

	case 'concBreakdownAnnot':
		unset($_GET['concBreakdownAnnot']);
		$_GET['concBreakdownOf'] = 'annot';
		require("../lib/breakdown.inc.php");
		break;

	case 'concBreakdownBoth':
		unset($_GET['concBreakdownBoth']);
		$_GET['concBreakdownOf'] = 'both';
		require("../lib/breakdown.inc.php");
		break;

	case 'concBreakdownDownload':
		unset($_GET['concBreakdownDownload']);
		$_GET['tableDownloadMode'] = 1;
		require("../lib/breakdown.inc.php");
		break;

	case 'concBreakdownPositionSort':
		$_GET['newPostP_sortPosition'] = $_GET['concBreakdownAt'];
		/* all rest is shared with node-sort, so fall through.... */
			
	case 'concBreakdownNodeSort':
		/* nb no sanitisation of qname needed, will be done by the Concordance program */
		$_GET['program'] = 'sort';
		$_GET['newPostP'] = 'sort';
		/* this  cvhecks the above...  */
		if (empty($_GET['newPostP_sortPosition']))
			$_GET['newPostP_sortPosition'] = 0;
		$_GET['newPostP_sortThinTag'] = '';
		$_GET['newPostP_sortThinTagInvert'] = 0;
		$_GET['newPostP_sortThinString'] = '';
		$_GET['newPostP_sortThinStringInvert'] = 0;
		$_GET['newPostP_sortThinStringInvert'] = 0;
		$_GET['uT'] = 'y';
		unset($qname);
		require("../lib/concordance.inc.php");
		break;



	

	/* from download-conc */

	case 'download-tab':
		require("../lib/download-tab.inc.php");
		break;
	



	/* from wordlookup */
	
	case 'lookup':
		require('../lib/wordlookup.inc.php');
		break;



	/* from corpus settings page */
	
	case 'adminResetCWBDir':
		$_GET['args'] = $_GET['arg1'] . $_GET['arg2'];
		require('../lib/execute.inc.php');
		break;
		// I THINK the above case is now superfluous, and that the form with that option is GONE. TODO: check.





	/* from various forms & controls in the user account system... */
	
	case 'userLogin':
	case 'userLogout':
	case 'newUser':
	case 'captchaImage':
	case 'ajaxNewCaptchaImage':
	case 'requestPasswordReset':
	case 'resetUserPassword':
	case 'resendVerifyEmail':
	case 'verifyUser':
	case 'remindUsername':
	case 'revisedUserSettings':
	case 'updateUserAccountDetails':
		$_GET['userAction'] = $redirect_script_redirector;
		require("../lib/user-admin.inc.php");
		break;
	



	/* from various forms & controls in the corpus-analysis system... */
	
	case 'buildFeatureMatrix':
	case 'deleteFeatureMatrix':
	case 'downloadFeatureMatrix':
		$_GET['multivariateAction'] = $redirect_script_redirector;
		require("../lib/multivariate-admin.inc.php");
		break;
		




	/* special case */
	
	case 'comingSoon':
		require("../lib/library.inc.php");
		coming_soon_page();
		break;





	default:
		?>
		<html>
		<head><title>Error!</title></head>
		<body>
			<pre>
			
			ERROR: Redirect type unrecognised.
			
			<a href="index.php">Please reload CQPweb</a>.
			</pre>
		</body>
		</html>
		<?php
		break;
	}
	/* end of switch */
}

/*
 * =============
 * END OF SCRIPT
 * =============
 */
