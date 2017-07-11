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
 * This file contains a chunk of script which is included at the start of the Adminhome program.
 * 
 * What it does is as follows: if one of a set of actions has been requested, it calls the execute.php rpgoram
 * to carry out the action. It then redirects to a followup pager (so that the rest of the Adminhome program 
 * does not run - if a render of the Adminhome is needed, it takes place after the redirect).
 */




/* check for an uploaded file */
if (!empty($_FILES))
{
	/* in this case, there will be no $_GET: so create what will be needed */
	$_GET['admFunction'] = 'uploadFile';
	$_GET['uT'] = 'y';
}



/* code block that diverts up the various "actions" that may enter adminhome, so that they go to execute.php */

$_GET['admFunction'] = (isset($_GET['admFunction']) ? $_GET['admFunction'] : (isset($_POST['admFunction']) ? $_POST['admFunction'] : false));

switch($_GET['admFunction'])
{
	/* 
	 * NB. some cases go the same "action" places as various other scripts
	 * and therefore include "redirect" instead of "execute".
	 * 
	 * Actions that are too complex to go via "execute" can instead be sent
	 * via "admin-do".
	 */
	
	
	case 'execute':
		/* general case for when it's all already set up */
		require('../lib/execute.inc.php');
		exit();
		
	case 'resetSystemSecurity':
		$_GET['function'] = 'restore_system_security';
		require('../lib/execute.inc.php');
		exit();
		
	case 'uploadFile':
		$_GET['function'] = 'uploaded_file_to_upload_area';
		$_GET['args'] = $_FILES['uploadedFile']['name'] . '#' . $_FILES['uploadedFile']['type'] . '#' 
			. $_FILES['uploadedFile']['size'] . '#' . $_FILES['uploadedFile']['tmp_name'] . '#' 
			. $_FILES['uploadedFile']['error'];
		$_GET['locationAfter'] = 'index.php?thisF=uploadArea&uT=y';
		require('../lib/execute.inc.php');
		exit();
	
	case 'fileView':
		$_GET['function'] = 'uploaded_file_view';
		$_GET['args'] = $_GET['filename'];
		require('../lib/execute.inc.php');
		exit();

	case 'fileCompress':
		$_GET['function'] = 'uploaded_file_gzip';
		$_GET['args'] = $_GET['filename'];
		$_GET['locationAfter'] = 'index.php?thisF=uploadArea&uT=y';
		require('../lib/execute.inc.php');
		exit();

	case 'fileDecompress':
		$_GET['function'] = 'uploaded_file_gunzip';
		$_GET['args'] = $_GET['filename'];
		$_GET['locationAfter'] = 'index.php?thisF=uploadArea&uT=y';
		require('../lib/execute.inc.php');
		exit();
		
	case 'fileFixLinebreaks':
		$_GET['function'] = 'uploaded_file_fix_linebreaks';
		$_GET['args'] = $_GET['filename'];
		$_GET['locationAfter'] = 'index.php?thisF=uploadArea&uT=y';
		require('../lib/execute.inc.php');
		exit();
		
	case 'fileDelete':
		$_GET['function'] = 'uploaded_file_delete';
		$_GET['args'] = $_GET['filename'];
		$_GET['locationAfter'] = 'index.php?thisF=uploadArea&uT=y';
		require('../lib/execute.inc.php');
		exit();

	case 'installCorpus':
	case 'installCorpusIndexed':
		$_GET['function'] = 'install_new_corpus';
		/* in this case there is no point sending parameters;      */
		/* the function is better off just getting them from $_get */
		$_GET['locationAfter'] = 'XX'; //the function itself sets this 
		require('../lib/execute.inc.php');
		exit();


		/* as with previous, the function gets its "parameters" from _GET */

	
	case 'deleteCorpus':
		if ($_GET['sureyouwantto'] !== 'yes')
		{
			/* default back to non-function-execute-mode */
			foreach ($_GET as $k=>$v) unset($_GET[$k]);
			break;
		}
		$_GET['function'] = 'delete_corpus_from_cqpweb';
		$_GET['args'] = $_GET['corpus'];
		$_GET['locationAfter'] = 'index.php';
		require('../lib/execute.inc.php');
		exit();
	
	
	case 'newCorpusCategory':
		$_GET['function'] = 'add_corpus_category';
		/* there is just a chance a legit category label might contain #, so replace with UTF-8 sharp U+266f */
		$_GET['args'] = str_replace('#',"\xE2\x99\xAF",$_GET['newCategoryLabel']) . '#' . $_GET['newCategoryInitialSortKey'];
		$_GET['locationAfter'] = 'index.php?thisF=manageCorpusCategories&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'newUser':
		$_GET['redirect'] = 'newUser';
		$_GET['userFunctionFromAdmin'] = 1;
		unset($_GET['admFunction']);
		require('../lib/redirect.inc.php');
		exit();
	
	
	case 'newBatchOfUsers':
		$_GET['function'] = 'add_batch_of_users';
		$_GET['args'] = trim($_GET['newUsername']) .'#'. $_GET['sizeOfBatch'] . '#' . trim($_GET['newPassword']) . '#' . trim($_GET['batchAutogroup']);
		$_GET['locationAfter'] = 'index.php?thisF=userAdmin&uT=y';
		require('../lib/execute.inc.php');
		exit();
	
	
	case 'resetUserPassword':
		$_GET['redirect'] = 'resetUserPassword';
		$_GET['userFunctionFromAdmin'] = 1;
		unset($_GET['admFunction']);
		require('../lib/redirect.inc.php');
		exit();	

		
	case 'deleteUser':
		if (!isset($_GET['sureyouwantto']) || $_GET['sureyouwantto'] !== 'yes')
		{
			/* default back to non-function-execute-mode */
			foreach ($_GET as $k=>$v) unset($_GET[$k]);
			break;
		}
		$_GET['function'] = 'delete_user';
		$_GET['args'] = $_GET['userToDelete'] ;
		$_GET['locationAfter'] = 'index.php?thisF=userAdmin&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'addUserToGroup':
		$_GET['function'] = 'add_user_to_group';
		$_GET['args'] = $_GET['userToAdd'] .'#' . $_GET['groupToAddTo'] ;
		$_GET['locationAfter'] = 'index.php?thisF=groupMembership&uT=y';
		require('../lib/execute.inc.php');
		exit();

		
	case 'removeUserFromGroup':
		$_GET['function'] = 'remove_user_from_group';
		$_GET['args'] = $_GET['userToRemove'] .'#' . $_GET['groupToRemoveFrom'] ;
		$_GET['locationAfter'] = 'index.php?thisF=groupMembership&uT=y';
		require('../lib/execute.inc.php');
		exit();
		
		
	case 'newGroup':
		$_GET['function'] = 'add_new_group';
		$_GET['args'] = $_GET['groupToAdd'] . '#' . str_replace('#',"\xE2\x99\xAF",$_GET['newGroupDesc']) . '#' . $_GET['newGroupAutojoinRegex'];
		$_GET['locationAfter'] = 'index.php?thisF=groupAdmin&uT=y';
		require('../lib/execute.inc.php');
		exit();		


	case 'updateGroupInfo':
		$_GET['function'] = 'update_group_info';
		$_GET['args'] = $_GET['groupToUpdate'] . '#' . str_replace('#',"\xE2\x99\xAF",$_GET['newGroupDesc']) . '#' . $_GET['newGroupAutojoinRegex'];
		$_GET['locationAfter'] = 'index.php?thisF=groupAdmin&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'groupRegexRerun':
		$_GET['function'] = 'reapply_group_regex';
		$_GET['args'] = $_GET['group'];
		$_GET['locationAfter'] = 'index.php?thisF=groupMembership&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'groupRegexApplyCustom':
		$_GET['function'] = 'apply_custom_group_regex';
		$_GET['args'] = $_GET['group'] . '#' . $_GET['regex'];
		$_GET['locationAfter'] = 'index.php?thisF=groupMembership&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'generateDefaultPrivileges':
		if ($_GET['corpus'] == '~~all~~')
		{
			$_GET['function'] = 'create_all_default_privileges';
			$_GET['args'] = '';
		}
		else
		{
			$_GET['function'] = 'create_corpus_default_privileges';
			$_GET['args'] = $_GET['corpus'];
		}
		$_GET['locationAfter'] = 'index.php?thisF=privilegeAdmin&uT=y'; 
		require('../lib/execute.inc.php');
		exit();
		
	
	case 'deletePrivilege':
		$_GET['function'] = 'delete_privilege';
		$_GET['args'] = (string)(int)$_GET['privilege'];
		$_GET['locationAfter'] = 'index.php?thisF=privilegeAdmin&uT=y'; 
		require('../lib/execute.inc.php');
		exit();


	case 'newGrantToUser':
		$_GET['function'] = 'grant_privilege_to_user';
		$_GET['args'] = $_GET['user'] . '#' . (int)$_GET['privilege'];
		$_GET['locationAfter'] = 'index.php?thisF=userGrants&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'newGrantToGroup':
		$_GET['function'] = 'grant_privilege_to_group';
		$_GET['args'] = $_GET['group'] . '#' . (int)$_GET['privilege'];
		$_GET['locationAfter'] = 'index.php?thisF=groupGrants&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'removeUserGrant':
		$_GET['function'] = 'remove_grant_from_user';
		$_GET['args'] = $_GET['user'] . '#' . (int)$_GET['privilege'];
		$_GET['locationAfter'] = 'index.php?thisF=userGrants&uT=y';
		require('../lib/execute.inc.php');
		exit();

	case 'removeGroupGrant':
		$_GET['function'] = 'remove_grant_from_group';
		$_GET['args'] = $_GET['group'] . '#' . (int)$_GET['privilege'];
		$_GET['locationAfter'] = 'index.php?thisF=groupGrants&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'cloneGroupGrants':
		$_GET['function'] = 'clone_group_grants';
		$_GET['args'] = $_GET['groupCloneFrom'] . '#' . $_GET['groupCloneTo'];
		$_GET['locationAfter'] = 'index.php?thisF=groupGrants&uT=y';
		require('../lib/execute.inc.php');
		exit();
	
		
		
	case 'addSystemMessage':
		$_GET['function'] = 'add_system_message';
		$_GET['args'] = $_GET['systemMessageHeading']. '#' . $_GET['systemMessageContent'];
		$_GET['locationAfter'] = 'index.php?thisF=systemMessages&uT=y';
		require('../lib/execute.inc.php');
		exit();


// TODO move this to metadata-admion at some point.
	case 'variableMetadata':
		$_GET['function'] = 'add_variable_corpus_metadata';
		$_GET['args'] = $_GET['corpus'] . '#' . $_GET['variableMetadataAttribute'] . '#' . $_GET['variableMetadataValue'];
		$_GET['locationAfter'] = '../'. $_GET['corpus'] .'/index.php?thisQ=manageMetadata&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'regenerateCSS':
		$_GET['function'] = 'cqpweb_regenerate_css_files';
		$_GET['locationAfter'] = 'index.php?thisF=skins&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'transferStylesheetFile':
		$_GET['function'] = 'cqpweb_import_css_file';
		if (!isset($_GET['cssFile']))
		{
			header("Location: index.php?thisF=skins&uT=y");
			exit();
		}	
		$_GET['args'] = $_GET['cssFile'];
		$_GET['locationAfter'] = 'index.php?thisF=skins&uT=y';
		require('../lib/execute.inc.php');
		exit();


//	case 'updateCategoryDescriptions':
//		$update_text_metadata_values_descriptions_info['corpus'] = $_GET['corpus'];
//		$update_text_metadata_values_descriptions_info['actions'] = array();
//		foreach($_GET as $key => &$val_desc)
//		{
//			if (substr($key, 0, 5) !== 'desc-')
//				continue;
//			list($junk, $field, $val_handle) = explode('-', $key);
//			$update_text_metadata_values_descriptions_info['actions'][] = array (
//				'field_handle' => $field,
//				'value_handle' => $val_handle,
//				'new_desc' => $val_desc
//				);
//		}
//		$_GET['function'] = 'update_text_metadata_values_descriptions';
//		$_GET['locationAfter'] = '../' . $_GET['corpus'] .'/index.php?thisQ=manageCategories&uT=y';
//		require('../lib/execute.inc.php');
//		exit();
//

// 	case 'updateCorpusMetadata':
// 		$_GET['function'] = 'update_corpus_visible';
// 		$_GET['args'] = ($_GET['updateVisible'] ? '1' : '0') . '#' .  $_GET['corpus'];
// 		$_GET['locationAfter'] = 'index.php?thisF=showCorpora&uT=y';
// 		require('../lib/execute.inc.php');
// 		exit();


	case 'newMappingTable':
		if(strpos($_GET['newMappingTableCode'], '#') !== false)
		{
			$_GET['args'] = "You cannot use the \"hash\" character in a mapping table.";
			// Actually this is a lie. You can, should you really want to do something that bonkers.
			// the problem is, rather, that then it can't be passed to execute.inc.php ,
			// because hash is an argument separator.
			$_GET['function'] = 'exiterror_general';
		}
		else
		{
			$_GET['function'] = 'add_tertiary_mapping_table';
			$_GET['locationAfter'] = 'index.php?thisF=mappingTables&showExisting=1&uT=y';
			$_GET['args'] = $_GET['newMappingTableId'].'#'.$_GET['newMappingTableName'].'#'.$_GET['newMappingTableCode'] ;
		}
		require('../lib/execute.inc.php');
		exit();


	case 'newAnnotationTemplate':
		$_GET['function'] = 'interactive_load_annotation_template';
		$_GET['locationAfter'] = 'index.php?thisF=annotationTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'deleteAnnotationTemplate':
		$_GET['function'] = 'delete_annotation_template';
		$_GET['args'] = $_GET['toDelete'];
		$_GET['locationAfter'] = 'index.php?thisF=annotationTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'loadDefaultAnnotationTemplates':
		$_GET['function'] = 'load_default_annotation_templates';
		$_GET['locationAfter'] = 'index.php?thisF=annotationTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'newXmlTemplate':
		$_GET['function'] = 'interactive_load_xml_template';
		$_GET['locationAfter'] = 'index.php?thisF=xmlTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'deleteXmlTemplate':
		$_GET['function'] = 'delete_xml_template';
		$_GET['args'] = $_GET['toDelete'];
		$_GET['locationAfter'] = 'index.php?thisF=xmlTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'loadDefaultXmlTemplates':
		$_GET['function'] = 'load_default_xml_templates';
		$_GET['locationAfter'] = 'index.php?thisF=xmlTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'newMetadataTemplate':
		$_GET['function'] = 'interactive_load_metadata_template';
		$_GET['locationAfter'] = 'index.php?thisF=metadataTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'deleteMetadataTemplate':
		$_GET['function'] = 'delete_metadata_template';
		$_GET['args'] = $_GET['toDelete'];
		$_GET['locationAfter'] = 'index.php?thisF=metadataTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'loadDefaultMetadataTemplates':
		$_GET['function'] = 'load_default_metadata_templates';
		$_GET['locationAfter'] = 'index.php?thisF=metadataTemplates&uT=y';
		require('../lib/execute.inc.php');
		exit();


	case 'deleteCacheLeakFiles':
		$_GET['function'] = 'delete_stray_cache_file'; 
		$_GET['args'] = array();
		/* fill array from form entries */
		foreach($_GET as $k => $v)
		{
			if ('1' === $v && 'fn_' === substr($k, 0, 3))
			{
				$_GET['args'][] = substr($k, 3);
				unset($_GET[$k]);
			}
		}
		$_GET['locationAfter'] = 'index.php?thisF=queryCacheControl&uT=y';
		require('../lib/execute.inc.php');
		exit();
		
		
	case 'deleteCacheLeakDbEntries':
		$_GET['function'] = 'delete_stray_cache_entry';
		$_GET['args'] = array();
		/* fill array from form entries */
		foreach($_GET as $k => $v)
		{
			if ('1' === $v && 'qn_' === substr($k, 0, 3))
			{
				$_GET['args'][] = substr($k, 3);
				unset($_GET[$k]);
			}
		}
		$_GET['locationAfter'] = 'index.php?thisF=queryCacheControl&uT=y';
		require('../lib/execute.inc.php');
		exit();
		
		
	case 'deleteFreqtableLeak':
		$_GET['function'] = 'delete_stray_freqtable_part'; 
		$_GET['args'] = array();
		/* fill array from form entries */
		foreach($_GET as $k => $v)
		{
			if ('1' === $v && 'del_' === substr($k, 0, 4))
			{
				$_GET['args'][] = substr($k, 4);
				unset($_GET[$k]);
			}
		}
		$_GET['locationAfter'] = 'index.php?thisF=freqtableCacheControl&uT=y';
		require('../lib/execute.inc.php');
		exit();
	
	
	default:
		/* break and fall through to the rest of adminhome.inc.php */
		break;
}

/* end of big main switch, and thus end of admin-execute */


