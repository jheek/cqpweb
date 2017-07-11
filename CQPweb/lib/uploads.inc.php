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
 * Puts an uploaded file into the upload area.
 * 
 * Some of the parameters are not used, but are passed through in case later changes need them. 
 * 
 * Returns an absolute path to the new file. The name of the new file may have been extended by "_"
 * if necessary to avoid a clash with an existing file.
 * 
 * @param string $original_name  The name from the client machine:       normally $_FILES[$name]['name'].
 * @param string $file_type      The file type (MIME, if present):       normally $_FILES[$name]['type'].
 * @param int    $file_size      The file size in bytes:                 normally $_FILES[$name]['size'].
 * @param string $temp_path      The location it was uploaded to:        normally $_FILES[$name]['tmp_name'].
 * @param int    $error_code     The error code from the upload process: normally $_FILES[$name]['error'].
 * @param bool   $user_upload    Default false; if true, the file goes into the present user's upload folder
 *                               rather than the main folder (which is sysadmin only).
 */
function uploaded_file_to_upload_area($original_name, $file_type, $file_size, $temp_path, $error_code, $user_upload = false)
{
	global $Config;
	global $User;

	/* Check for upload errors; convert back to int: execute.inc.php may have turned it to a string */
	switch ($error_code = (int)$error_code )
	{
	case UPLOAD_ERR_OK:
		break;
	case UPLOAD_ERR_INI_SIZE:
	case UPLOAD_ERR_FORM_SIZE:
		exiterror_general('That file is too big to upload! Contact your system administrator.');
	default:
		exiterror_general('The file did not upload correctly! Please try again.');
	}
	
	/* We've checked the global restriction, now check the restriction for ordinary users 
	 * (only superusers can upload REALLY BIG files).
	 * 		TODO make this variable - a user privilege
	 */
	if (!$User->is_admin())
	{
		/* normal user limit is 2MB */
		if ((int)$file_size > 2097152)
			exiterror_general('That file is too big to upload! Contact your system administrator.');
	}
	
	/* check the directory exists for user-uploaded files */
	if ($user_upload)
	{	
		if (!is_dir("{$Config->dir->upload}/usr"))
			mkdir("{$Config->dir->upload}/usr", 0775);
		if (!is_dir("{$Config->dir->upload}/usr/{$User->username}"))
			mkdir("{$Config->dir->upload}/usr/{$User->username}", 0775);
	}
	
	/* find a new name - a file that does not exist */
	for ($filename = basename($original_name); 1 ; $filename = '_' . $filename)
	{
		$new_path = $Config->dir->upload . '/' . ($user_upload ? "usr/{$User->username}/" : '' ) . "$filename";
		if ( ! file_exists($new_path) )
			break;
	}
	
	if (move_uploaded_file($temp_path, $new_path)) 
		chmod($new_path, 0664);
	else
		exiterror_general("The file could not be processed! Possible file upload attack.");
	
	return $new_path;
}

/**
 * Change linebreaks in the named file in the upload area to Unix-style.
 * 
 * TODO windows compatability will require some major changes here here.
 */
function uploaded_file_fix_linebreaks($filename)
{
	global $Config;

	$path = "{$Config->dir->upload}/$filename";
	
	if (!file_exists($path))
		exiterror_general('Your request could not be completed - that file does not exist.');
	
	$intermed_path = "{$Config->dir->upload}/__________uploaded_file_fix_linebreaks________temp_________datoa__________.___";
	
	$source = fopen($path, 'r');
	$dest = fopen($intermed_path, 'w');
	
	/* check for initial UTF8-BOM */
	$first = fgets($source);
	if (substr($first, 0, 3) == "\xef\xbb\xbf")
		$first = substr($first, 3);
	fputs($dest, str_replace("\r\n", "\n", $first));
	
	while ( false !== ($line = fgets($source)))
		fputs($dest, str_replace("\r\n", "\n", $line));
	fclose($source);
	fclose($dest);
	
	unlink($path);
	rename($intermed_path, $path);
	chmod($path, 0664);
}


// TODO - account for files in the usr directory
function uploaded_file_delete($filename)
{	
	global $Config;

	$path = "{$Config->dir->upload}/$filename";
	
	if (!file_exists($path))
		exiterror_general('Your request could not be completed - that file does not exist.');
	
	unlink($path);
}

// TODO - account for files in the usr directory
function uploaded_file_gzip($filename)
{
	global $Config;

	$path = "{$Config->dir->upload}/$filename";
	
	if (!file_exists($path))
		exiterror_general('Your request could not be completed - that file does not exist.');

	$zip_path = $path . '.gz';
	
	$in_file = fopen($path, "rb");
	if (!$out_file = gzopen ($zip_path, "wb"))
	{
		exiterror_general('Your request could not be completed - compressed file could not be opened.');
	}

	php_execute_time_unlimit();
	while (!feof ($in_file)) 
	{
		$buffer = fgets($in_file, 4096);
		gzwrite($out_file, $buffer, 4096);
	}
	php_execute_time_relimit();

	fclose ($in_file);
	gzclose ($out_file);
	
	unlink($path);
	chmod($zip_path, 0666);
}


// TODO - account for files in the usr directory
function uploaded_file_gunzip($filename)
{
	global $Config;

	$path = "{$Config->dir->upload}/$filename";
	
	if (!file_exists($path))
		exiterror_general('Your request could not be completed - that file does not exist.');
	
	if (preg_match('/(.*)\.gz$/', $filename, $m) < 1)
		exiterror_general('Your request could not be completed - that file does not appear to be compressed.');

	$unzip_path = "{$Config->dir->upload}/{$m[1]}";
	
	$in_file = gzopen($path, "rb");
	$out_file = fopen($unzip_path, "wb");

	php_execute_time_unlimit();
	while (!gzeof($in_file)) 
	{
		$buffer = gzread($in_file, 4096);
		fwrite($out_file, $buffer, 4096);
	}
	php_execute_time_relimit();

	gzclose($in_file);
	fclose ($out_file);
			
	unlink($path);
	chmod($unzip_path, 0666);
}


// TODO - account for files in the usr directory
function uploaded_file_view($filename)
{
	global $Config;
	
	$path = "{$Config->dir->upload}/$filename";

	if (!file_exists($path))
		exiterror_general('Your request could not be completed - that file does not exist.');

	$fh = fopen($path, 'r');
	
	$bytes_counted = 0;
	$data = '';
	
	while ((!feof($fh)) && $bytes_counted <= 102400)
	{
		$line = fgets($fh, 4096);
		$data .= $line;
		$bytes_counted += strlen($line);
	}

	fclose($fh);
	
	$data = escape_html($data);
	
	/*
	 * Note, it is purposeful that we are not using the write HTML function,
	 * because the idea is to keep the HTML very simple (no JavaScript, etc.). 
	 */
	header('Content-Type: text/html; charset=utf-8');
	?>
	<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>CQPweb: viewing uploaded file</title>
		</head>
		<body>
			<h1>Viewing uploaded file <i><?php echo $filename;?></i></h1>
			<p>NB: for very long files only the first 100K is shown
			<hr/>
			<pre>
			<?php echo "\n" . $data; ?>
			</pre>
		</body>
	</html>
	<?php
	exit();
}


