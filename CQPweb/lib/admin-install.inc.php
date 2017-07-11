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
 * This file contains functions used in the installation of CQPweb corpora
 * (not including textmetadata installation!)
 * 
 * It should generally not be included into scripts unless the user is a sysadmin.
 */




/**
 * Just a little object to hold info on the install corpus parsed from GET;
 * NOT an independent module in any way, shape or form, just a way to simplify
 * variable parsing.
 * 
 * It is used in only one place - the function install_new_corpus.
 * 
 * @see install_new_corpus.
 */
class CQPwebNewCorpusInfo
{
	public $corpus_mysql_name;
	public $corpus_cwb_name;
	
	public $already_cwb_indexed;
	
	public $main_script_is_r2l;

	/** ORDERED array of statements to create/adjust the corpus_info entry. */
	public $corpus_info_mysql_insert;
	
	public $css_path;
	public $title;
	
	/** p-attribute string bits for the cwb-encode command line */
	public $p_attributes;
	/** array of statements to create p-attribute entries in the DB */
	public $p_attributes_mysql_insert;
	public $primary_p_attribute;
	/** s-attribute string bits for the cwb-encode command line */
	public $s_attributes;
	/** array of statements to create s-attribute entries in the DB */
	public $s_attributes_mysql_insert;
	
	public $file_list;
	
	
	/* constructor is sole public function */
	function __construct()
	{
		global $Config;
		
		/* first thing: establish which mode we are dealing with */
		$this->already_cwb_indexed = ($_GET['admFunction'] === 'installCorpusIndexed'); 
		
		/* array initialisation *
		 * ******************** */
		$this->p_attributes = array();
		$this->s_attributes = array();
		$this->p_attributes_mysql_insert = array();
		$this->s_attributes_mysql_insert = array();


		/* get each thing from GET *
		 * *********************** */
		
		/* the corpus name: first, the cwb name */
		$this->corpus_cwb_name = strtolower($_GET['corpus_cwb_name']);
		if (! cqpweb_handle_check($this->corpus_cwb_name))
			exiterror("That corpus name is invalid. You must specify a corpus name using only letters, numbers and underscore");		
		if (substr($this->corpus_cwb_name, -6) == '__freq')
			exiterror('Error: Corpus CWB names cannot end in __freq!!');
		
		/* mysql name : from now on (May 2015), MUST be identical to the CWB name */
		$this->corpus_mysql_name = $this->corpus_cwb_name;
		/* check for reserved words */
		if (in_array($this->corpus_mysql_name, $Config->cqpweb_reserved_subdirs))
			exiterror("The following corpus names are not allowed: " . implode(' ', $Config->cqpweb_reserved_subdirs));



		/* ***************** */
		
		/* the data source: a cwb index, OR a  */
		
		if ($this->already_cwb_indexed)
		{
			/* check that the corpus registry file exists, that the corpus datadir exists,
			 * in the process, getting the "override" directories, if they exist */
			
			$use_normal_regdir = (bool)$_GET['corpus_useDefaultRegistry'];
			$registry_file = "{$Config->dir->registry}/{$this->corpus_cwb_name}";
			
			if ( ! $use_normal_regdir)
			{
				$orig_registry_file = 
					'/' 
					. trim(trim($_GET['corpus_cwb_registry_folder']), '/')
					. '/' 
					. $this->corpus_cwb_name;
				if (is_file($registry_file))
					exiterror("A corpus by that name already exists in the CQPweb registry!");
				if (!is_file($orig_registry_file))
					exiterror("The specified CWB registry file does not seem to exist in that location.");
				/* the next check is probably a bit paranoid, but just in case ... */
				if (!is_readable($orig_registry_file))
					exiterror("The specified CWB registry file cannot be read (suggestion: check file ownership/permissions).");
				
				/* we have established that the desired registry file does not exist and the original we are importing from does,
				 * so we can now import the registry file into CQPweb's registry */
				copy($orig_registry_file, $registry_file);
			}
			else
			{
				if (!is_file($registry_file))
					exiterror("The specified CWB corpus does not seem to exist in CQPweb's registry.");
			}
			
			$regdata = file_get_contents($registry_file);
			
			if (1 > preg_match("/\bHOME\s+(\/[^\n\r]+)\s/", $regdata, $m) )
			{
				if (! $use_normal_regdir)
					unlink($registry_file);
				exiterror("A data-directory path could not be found in the registry file for the CWB corpus you specified."
					. "\n\nEither the data-directory is unspecified, or it is specified with a relative path (an absolute path is needed).");
			}
			$test_datadir = $m[1];
			
			if (!is_dir($test_datadir))
				exiterror("The data directory specified in the registry file [$test_datadir] could not be found.");
			
			/* check that <text> and <text_id> are s-attributes */
			if (preg_match('/\bSTRUCTURE\s+text\b/', $regdata) < 1  || preg_match('/\bSTRUCTURE\s+text_id\b/', $regdata) < 1)
				exiterror("Pre-indexed corpora require s-attributes text and text_id!!");
		}
		else /* ie if this is NOT an already indexed corpus */
		{
			preg_match_all('/includeFile=([^&]*)&/', $_SERVER['QUERY_STRING'], $m, PREG_PATTERN_ORDER);
			
			$this->file_list = array();
			
			foreach($m[1] as $file)
			{
				$path = "{$Config->dir->upload}/$file";
				if (is_file($path))
					$this->file_list[] = $path;
				else
					exiterror("One of the files you selected seems to have been deleted.");
			}
			
			if (empty($this->file_list))
				exiterror("You must specify at least one file to include in the corpus!");		
		}

	

		/* ******************* */
		
		/* p-attributes */
		
		if ($this->already_cwb_indexed)
		{
			preg_match_all("/ATTRIBUTE\s+(\w+)\s*[#\n]/", $regdata, $m, PREG_PATTERN_ORDER);
			foreach($m[1] as $p)
			{
				if ($p == 'word')
					continue;
				$this->p_attributes[] = $p;
				$this->p_attributes_mysql_insert[] = $this->get_p_att_mysql_insert($p, '', '', '', false);
			}
				
			/* note that no "primary" annotation is created if we are loading in an existing corpus; 
			 * instead, the primary annotation can be set later.
			 * note also that cwb_external applies EVEN IF the indexed corpus was already in this directory
			 * (its sole use is to prevent deletion of data that CQPweb did not create)
			 */
			$this->corpus_info_mysql_insert[] =
				"insert into corpus_info (corpus, primary_annotation, cwb_external) 
				values ('{$this->corpus_mysql_name}', NULL, 1)";
		}
		else
			$this->load_p_atts_based_on_get();


		/* ******************* */
		
		/* s-attributes */
		
		if ($this->already_cwb_indexed)
		{
			$all_att_handles = array();
			
			preg_match_all("/STRUCTURE\s+(\w+)\s*?(#\s*\[annotations\])?\s*?\n/", $regdata, $m, PREG_SET_ORDER);
			
			/* first pass fills the list of handles, so that on the next pass, we can check for family-heads. */
			foreach($m as $structure)
				$all_att_handles[] = $structure[1];
			
			/* second pass works out the SQL for each s-attribute from the registry file, assuming all are free-text. */
			foreach($m as $structure)
			{
				/* HANDLE */
				$s = $structure[1];
				
				/* DATATYPE: none for -S, for -V it is freetext, unless it's text_id, in which case unique id */
				if (empty($structure[2]))
					$dt = METADATA_TYPE_NONE; 
				else
					$dt = ($s == 'text_id' ? METADATA_TYPE_UNIQUE_ID : METADATA_TYPE_FREETEXT);

				/* FAMILY */
				$att_family = $s;
				if (false !== strpos($s, '_'))
				{
					list($poss_fam) = explode('_', $s);
					if (in_array($poss_fam, $all_att_handles))
						$att_family = $poss_fam;
				}
				
				/* note: we do not actually need the s_attributes array in this case, as it is only used for the cwb-encode command line;
				 * but we DO need to build a list of insert statements for the XML metadata table. */
				$this->s_attributes_mysql_insert[] = $this->get_s_att_mysql_insert($s, $att_family, "Structure ``$s''", $dt);
			}
		}
		else
			$this->load_s_atts_based_on_get();



		/* ******************* */
		
		/* everything else! */
		$this->load_corpus_info_based_on_get();
		/* note, this has to be the last action in the constructor, so that all the 
		 * statements are appended AFTER creation of the entry in the corpus_info table. */
		
	} /* end constructor */


	private function load_corpus_info_based_on_get()
	{
		/* this method should not run until the initial statement in the array has been created (the "insert" statement) */
		if (1 != count($this->corpus_info_mysql_insert))
			exiterror("Critical code error: can't create corpus_info updates as there is no INSERT statement!");
		
		/* FIRST: prepare remaining corpus-level info from get */
		
		$this->title               = mysql_real_escape_string($_GET['corpus_description']);
		$this->main_script_is_r2l  = ( (isset($_GET['corpus_scriptIsR2L'])    && $_GET['corpus_scriptIsR2L']    === '1') ? '1'      : '0');
		$this->encode_charset      = ( (isset($_GET['corpus_encodeIsLatin1']) && $_GET['corpus_encodeIsLatin1'] === '1') ? 'latin1' : 'utf8' );
		/* note that the charset is only used for cwb-encode, not for the corpus_info statements. */		
		
		/* The CSS entry is a bit more involved.... */
		if ($_GET['cssCustom'] == 1)
		{
			/* escape single quotes in the address because it will be embedded in a single-quoted string */ 
			$this->css_path = addcslashes($_GET['cssCustomUrl'], "'");
			/* only a silly URL would have ' in it anyway, so this is for safety */
			
			// TODO poss XSS vulnerability - as this URL is sent back to the client eventually. 
			// Is there *any* way to make this safe? (Assuming an attacker has gained access to the script that processes this form)
		}
		else
		{
			/* we assume no single quotes in names of builtin CSS files */ 
			$this->css_path = "../css/{$_GET['cssBuiltIn']}";
			if (! is_file($this->css_path))
				$this->css_path = '';
			/* the is_file check means that only files actually on the server can be used, ergo XSS is protected against. */
		}
		
		/* OK, now we can assemble the SQL update. */
		$this->corpus_info_mysql_insert[] 
			= "update corpus_info 
					set
						cqp_name = '" . strtoupper($this->corpus_mysql_name) . "',
						main_script_is_r2l = {$this->main_script_is_r2l},
						title = '{$this->title}',
						css_path = '{$this->css_path}'
					where corpus = '{$this->corpus_mysql_name}'";

	}	/* end method load_corpus_info_based_on_get() */


	private function load_s_atts_based_on_get()
	{
		if (!isset($_GET['useXmlTemplate']))
			exiterror("Critical error: missing parameter useXmlTemplate");

		if ('~~customSs' == $_GET['useXmlTemplate'])
		{
			/* custom s-attributes */
			
			/* note this code draws on what is done in template setup, EXCEPT instead of building variables to
			 * create a template in the DB, we build variables for corpus indexing. */

			for ( $i = 1, $a_ix = 0; !empty($_GET["customSHandle$i"]) ; $i++, $a_ix++ )
			{
				$handle = cqpweb_handle_enforce($_GET["customSHandle$i"]);
				$description = $_GET["customSDesc$i"];
				if ($handle == '__HANDLE')
					exiterror("Invalid s-attribute handle: " . $_GET["customSHandle$i"] . " .");
				if (64 < strlen($handle))
					exiterror("Overlong s-attribute handle ``{$handle}'' (must be 64 characters or less).");
				if (255 < strlen($description))
					exiterror("Overlong s-attribute description ``{$description}'' (must be 255 characters (bytes) or less).");
				
				$this->s_attributes_mysql_insert[] 
					= $this->get_s_att_mysql_insert($handle, $handle, mysql_real_escape_string($description), METADATA_TYPE_NONE);

//				De-activated because we do not want nested regions to be deleted,
//				we want them to be treated as "close region and open new one"			
//				$encode_str = $handle . ':0';
				$encode_str = $handle;

				/* attributes of the element */
				for ( $j = 1, $family = $handle; !empty($_GET["customSHandleAtt{$i}_$j"]) ; $j++)
				{
					/* grab and check handle and desc */
					$att_handle = cqpweb_handle_enforce($_GET["customSHandleAtt{$i}_$j"]);
					$att_desc = $_GET["customSDescAtt{$i}_$j"];
					if ($att_handle == '__HANDLE')
						exiterror("Invalid s-attribute handle: " . $_GET["customSHandleAtt{$i}_$j"] . " .");
					if (64 < strlen($att_handle))
						exiterror("Overlong s-attribute handle ``{$att_handle}'' (must be 64 characters or less).");
					if (255 < strlen($att_desc))
						exiterror("Overlong s-attribute description ``{$att_desc}'' (must be 255 characters (bytes) or less).");
		
					/* check the datatype: what is allowed here must track what is allowed in an XML template */
					$dt = (int)$_GET["customSTypeAtt{$i}_$j"];
					switch($dt)
					{
					case METADATA_TYPE_CLASSIFICATION:
					case METADATA_TYPE_FREETEXT:
					case METADATA_TYPE_UNIQUE_ID:
					case METADATA_TYPE_IDLINK:
					case METADATA_TYPE_DATE:
						break;
					default:
						exiterror("Invalid attribute datatype supplied  for attribute ``{$att_handle}''!");
					}
					
					/* ok, we can now add the bits n pieces... */
					$encode_str .= '+' . $att_handle;
					$this->s_attributes_mysql_insert[]
						= $this->get_s_att_mysql_insert($handle.'_'.$att_handle, $handle, mysql_real_escape_string($att_desc), $dt);
				}
				
				/* we now have the complete string for cwb-encode so add to array.... */
				$this->s_attributes[] = $encode_str;
			}
		}
		else
		{
			/* s-attributes from XML template */
			
			$template_id = (int)$_GET['useXmlTemplate'];
			
			$t_list = list_xml_templates();
			
			if (!array_key_exists($template_id, $t_list))
				exiterror("Critical error: nonexistent annotation template specified.");
			
			$attributes = $t_list[$template_id]->attributes;

			for ( $q = 1 ; isset($attributes[$q]) ; $q++ )
			{
				if ($attributes[$q]->att_family == $attributes[$q]->handle)
// see note above for why we no longer use :0
//					$this->s_attributes[$attributes[$q]->handle] = $attributes[$q]->handle . ':0';
					$this->s_attributes[$attributes[$q]->handle] = $attributes[$q]->handle;
				else
				{
					$unfamilied_handle = preg_replace("|^{$attributes[$q]->att_family}_|", '', $attributes[$q]->handle);
					$this->s_attributes[$attributes[$q]->att_family] .= '+' .  $unfamilied_handle;
				}

				$this->s_attributes_mysql_insert[] 
					= $this->get_s_att_mysql_insert(
							$attributes[$q]->handle,
							$attributes[$q]->att_family, 
							mysql_real_escape_string($attributes[$q]->description),
							$attributes[$q]->datatype
						);
			}
			
			/* erase the keys added to the array of cwb-encode specifications */
			$this->s_attributes = array_values($this->s_attributes); // TODO or outside the if-else?
		}
		
	}	/* end method load_s_atts_based_on_get() */

	
	
	private function load_p_atts_based_on_get()
	{
		if (!isset($_GET['useAnnotationTemplate']))
			exiterror("Critical error: missing parameter useAnnotationTemplate");
		

		if ('~~customPs' == $_GET['useAnnotationTemplate'])
		{
			/* custom p-attributes */
			
			for ( $q = 1 ; isset($_GET["customPHandle$q"]) ; $q++ )
			{
				$cand = cqpweb_handle_enforce($_GET["customPHandle$q"]);
				if ($cand === '__HANDLE')
					continue;

				if (isset($_GET["customPfs$q"] ) && $_GET["customPfs$q"] === '1')
				{
					$cand .= '/';
					$fs = 1;
				}
				else
					$fs = 0;

				$this->p_attributes[] = $cand;
				
				$cand = str_replace('/', '', $cand);
				
				$this->p_attributes_mysql_insert[] 
					= $this->get_p_att_mysql_insert(
							$cand, 
							mysql_real_escape_string($_GET["customPDesc$q"]), 
							mysql_real_escape_string($_GET["customPTagset$q"]), 
							mysql_real_escape_string($_GET["customPurl$q"]),
							$fs 
						);
				
				if (isset($_GET['customPPrimary']) && (int)$_GET['customPPrimary'] == $q)
					$this->primary_p_attribute = $cand;
			}
		}
		else
		{
			/* p-attributes from annotation template */
			
			$template_id = (int)$_GET['useAnnotationTemplate'];
			
			$t_list = list_annotation_templates();
			
			if (!array_key_exists($template_id, $t_list))
				exiterror("Critical error: nonexistent annotation template specified.");
			
			$attributes = $t_list[$template_id]->attributes;
			
			for ( $q = 1 ; isset($attributes[$q]) ; $q++ )
			{
				$this->p_attributes[] = $attributes[$q]->handle . ($attributes[$q]->is_feature_set ? '/' : '') ;
				
				$this->p_attributes_mysql_insert[]
					= $this->get_p_att_mysql_insert(
							$attributes[$q]->handle, 
							mysql_real_escape_string($attributes[$q]->description), 
							mysql_real_escape_string($attributes[$q]->tagset), 
							mysql_real_escape_string($attributes[$q]->external_url),
							$attributes[$q]->is_feature_set
						);
			}
			
			if (! empty($t_list[$template_id]->primary_annotation))
				$this->primary_p_attribute = $t_list[$template_id]->primary_annotation;
		}

		if (isset ($this->primary_p_attribute))
			$this->corpus_info_mysql_insert[] =
				"insert into corpus_info (corpus, primary_annotation) 
					values ('{$this->corpus_mysql_name}', '{$this->primary_p_attribute}')";
		else
			$this->corpus_info_mysql_insert[] =
				"insert into corpus_info (corpus, primary_annotation) 
					values ('{$this->corpus_mysql_name}', NULL)";
	
	} /* end method load_p_atts_based_on_get() */


	
	private function get_p_att_mysql_insert($tag_handle, $description, $tagset, $url, $feature_set)
	{
		/* assumes everything already made safe with mysql_real_escape_string or equiv */
		return
			"insert into annotation_metadata 
				(corpus, handle, description, tagset, external_url, is_feature_set) 
					values 
				('{$this->corpus_mysql_name}', '$tag_handle', '$description', '$tagset', '$url', is_feature_set)"
			;
	}
	
	
	private function get_s_att_mysql_insert($handle, $att_family, $description, $datatype)
	{
		return
			"insert into xml_metadata 
					(  corpus,                       handle,    att_family,    description,   datatype) 
						values 
					('{$this->corpus_mysql_name}', '$handle', '$att_family', '$description', $datatype)
			";
	}

} /* end class (CQPwebNewCorpusInfo) */





function install_new_corpus()
{
	global $Config;

	/* note that most of the overall setup time is taken up by other processes (cwb-encode etc.), so this is only rarely needed. */
	php_execute_time_unlimit();
	
	$info = new CQPwebNewCorpusInfo;
	/* we need both case versions here */
	$corpus = $info->corpus_cwb_name;
	$CORPUS = strtoupper($corpus);

	/* check whether corpus already exists */
	$existing_corpora = list_corpora();
	if ( in_array($info->corpus_mysql_name, $existing_corpora) )
		exiterror("Corpus `$corpus' already exists on the system. " 
			. "Please specify a different name for your new corpus.");

	/* =============================================================================== *
	 * create web symlink FIRST, so that if indexing fails, deletion should still work *
	 * =============================================================================== */

	$newdir = '../' . $info->corpus_mysql_name;
	
	if (file_exists($newdir))
	{
		if (is_dir($newdir))
			recursive_delete_directory($newdir);
		else
			unlink($newdir);
	}

	/* v 3.2: we change from  mkdir to symlink */
	symlink("exe", $newdir);
	chmod($newdir, 0775);
	/* script stubs no longer need to be created, and we no longer create a settings file. */
	

	/* mysql table inserts */
	foreach ($info->corpus_info_mysql_insert as &$s)
		do_mysql_query($s);
	foreach ($info->p_attributes_mysql_insert as &$s)
		do_mysql_query($s);
	foreach ($info->s_attributes_mysql_insert as &$s)
		do_mysql_query($s);


	/* ============================================================================ *
	 * CWB setup comes after the MySQL ops; if it fails, deletion should still work *
	 * ============================================================================ */
	
	if ($info->already_cwb_indexed)
		;
	else
	{
		/* cwb-create the file */
		$datadir = "{$Config->dir->index}/$corpus";
		if (is_dir($datadir))
			recursive_delete_directory($datadir);
		mkdir($datadir, 0775);

		/* run the commands one by one */
		
		$encode_command 
			= "{$Config->path_to_cwb}cwb-encode -xsB -c {$info->encode_charset} -d $datadir -f "
			. implode(' -f ', $info->file_list)
			. " -R \"{$Config->dir->registry}/$corpus\" "
			. ( empty($info->p_attributes) ? '' : (' -P ' . implode(' -P ', $info->p_attributes)) )
			. ' -S ' . implode(' -S ', $info->s_attributes)
			. ' 2>&1'
			;
			/* NB don't need possibility of no S-atts because there is always text+id */
			/* NB the 2>&1 works on BOTH Win32 AND Unix */

		$exit_status_from_cwb = 0;
		/* NB this array collects both the commands used and the output sent back (via stderr, stdout) */
		$output_lines_from_cwb = array($encode_command);

		exec($encode_command, $output_lines_from_cwb, $exit_status_from_cwb);
		if ($exit_status_from_cwb != 0)
			exiterror("cwb-encode reported an error! Corpus indexing aborted. <pre>"
				. implode("\n", $output_lines_from_cwb) 
				. '</pre>');

		chmod("{$Config->dir->registry}/$corpus", 0664);

		$output_lines_from_cwb[] = $makeall_command = "{$Config->path_to_cwb}cwb-makeall -r \"{$Config->dir->registry}\" -V $CORPUS 2>&1";
		exec($makeall_command, $output_lines_from_cwb, $exit_status_from_cwb);
		if ($exit_status_from_cwb != 0)
			exiterror("cwb-makeall reported an error! Corpus indexing aborted. <pre>"
				. implode("\n", $output_lines_from_cwb)
				. '</pre>');

		/* use a separate array for the compression utilities (merged into main output block later) */
		$compression_output = array();
		$compression_output[] = $huffcode_command = "{$Config->path_to_cwb}cwb-huffcode -r \"{$Config->dir->registry}\" -A $CORPUS 2>&1";
		exec($huffcode_command, $compression_output, $exit_status_from_cwb);
		if ($exit_status_from_cwb != 0)
			exiterror("cwb-huffcode reported an error! Corpus indexing aborted. <pre>"
				. implode("\n", array_merge($output_lines_from_cwb,$compression_output)) 
				. '</pre>');

		$compression_output[] = $compress_rdx_command = "{$Config->path_to_cwb}cwb-compress-rdx -r \"{$Config->dir->registry}\" -A $CORPUS 2>&1";
		exec($compress_rdx_command, $compression_output, $exit_status_from_cwb);
		if ($exit_status_from_cwb != 0)
			exiterror("cwb-compress-rdx reported an error! Corpus indexing aborted. <pre>"
				. implode("\n", array_merge($output_lines_from_cwb,$compression_output)) 
				. '</pre>');

		foreach($compression_output as $line)
		{
			$output_lines_from_cwb[] = $line;
			if (0 < preg_match('/!! You can delete the file <(.*)> now/', $line, $m))
				if (is_file($m[1]))
					unlink($m[1]);
		}


		/*
		 * Finally, we save the entire output blob in a mysql table that preserves its contents.
		 * The "finished" screen has a link which slide-downs the content of this field 
		 * to display the output from CWB. This allows you to see, f'rinstance, any dodgy messages 
		 * about XML elements that were droppped or encoded as literals.
		 */
		$txt = trim(mysql_real_escape_string(implode("\n", $output_lines_from_cwb)));
		if (!empty($txt))
			do_mysql_query("update corpus_info set indexing_notes = '$txt' where corpus = '$corpus'");

	} /* end else (from if cwb index already exists) */


	/* ================================================= *
	 * post-installation datatype checks on S-attributes *
	 * ================================================= */

	/* We cannot check s-attribute validity before the S-attributes actually exist. 
	 * Now, we should check each one - and if validity check fails, switch the datatype 
	 * to the most permissive (i.e. FREETEXT). And we log that in the indexing notes.
	 */
	foreach(get_xml_all_info($corpus) as $x)
	{
		$ok = true;
		switch($x->datatype)
		{
		case METADATA_TYPE_NONE:
		case METADATA_TYPE_FREETEXT:
		case METADATA_TYPE_IDLINK:
			/* no check needed */
			break;

		case METADATA_TYPE_DATE:
			if ( ! xml_index_is_valid_date($corpus, $x->handle))
				$ok = false;
			break;

		case METADATA_TYPE_CLASSIFICATION:
			if (xml_index_has_handles($corpus, $x->handle))
				/* extra step - load categories into the database. */
				setup_xml_classification_categories($corpus, $x->handle);
			else
				$ok = false;
			break;

		case METADATA_TYPE_UNIQUE_ID:
			if ( ! xml_index_has_handles($corpus, $x->handle))
				$ok = false;
			if ( ! xml_index_is_unique($corpus, $x->handle))
				$ok = false;
			break;

		default:
			/* not reached */
			exiterror("A bad XML datatype has been inserted into the database, somehow! Please report this as a bug.");
		}
		if (!$ok)
		{
			change_xml_datatype($corpus, $x->handle, METADATA_TYPE_FREETEXT);
			list($notes) = mysql_fetch_row(do_mysql_query("select indexing_notes from corpus_info where corpus = '$corpus'"));
			$notes = "$notes\n\nPost-indexing problem: contents of s-attribute {$x->handle} was not compatible with type ``"
				. $Config->metadata_type_descriptions[$x->datatype] . "''. It has been converted to datatype ``Free Text''.";
			$notes = mysql_real_escape_string($notes);
			do_mysql_query("update corpus_info set indexing_notes = '$notes' where corpus = '$corpus'");
		}
	}
	
	
	/* make sure execute.php takes us to a nice results screen */
	$_GET['locationAfter'] = "index.php?thisF=installCorpusDone&newlyInstalledCorpus={$info->corpus_mysql_name}&uT=y";

}
/* end of function "install_new_corpus" */

