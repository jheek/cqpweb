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
 * General plugin interface that defines the protoype of the constructor,
 * don't actually use this, only the interfaces that inherit from it.
 * 
 * Please note, if you update ANY of the phpdoc blocks for this interface or
 * any that inherit from it, then make sure that the equivalent text in 
 * doc/CQPweb-plugins.html is still correct! 
 */
interface CQPwebPlugin
{
	/**
	 * The constructor function of a Plugin may be passed a single argument.
	 * 
	 * If it is, it is the (absolute or relative) path to a configuration
	 * file, which can be loaded and used (of course, it can be ignored as well.)
	 * 
	 * This argument's default value is an empty string. Any "empty" value,
	 * such as '', NULL, 0 or false, should be interpreted as "no config file".
	 * 
	 * The internal format of the config file, and how it is parsed and the info
	 * stored, is a matter for the plugin to decide. Config files can be anywhere
	 * on the system that is accessible to the username that CQPweb runs under.
	 */
	public function __construct($config_file = '');
}

/**
 * Interface for ScriptSwitcher Plugins.
 * 
 * A ScriptSwitcher Plugin is an object which implements this interface.
 * 
 * It must be able to something sensible with any UTF8 text passed to it.
 * 
 * This will normally mean transliterating it to Latin or other alphabet
 * native/familiar to the user base.
 * 
 * A class implementing this interface can do this however it likes - 
 * internally, by calling a library, by creating a back-end process
 * and piping data back and forth - CQPweb doesn't care.
 * 
 * What you are NOT allowed to do in a plugin is use any of CQPweb's
 * global data. (Or rather, you are ALLOWED to - it's your computer! -
 * I just don't think it would be a good idea at all.)
 * 
 * This interface used to have the much simpler name "Transliterator",
 * but it turns out this clashes with a class added to the "intl"
 * extension in PHP 5.4.
 */
interface ScriptSwitcher extends CQPwebPlugin
{
	/**
	 * This function takes a UTF8 string and returns a UTF8 string.
	 * 
	 * The returned string is the direct equivalent, but with some
	 * (or all) characters from the source writing system converted
	 * to the target writing system.
	 * 
	 * It must be possible to pass a raw string straight from CQP,
	 * and get back a string that is still structured the same
	 * (so CQPweb functions don't need to know about whether or not
	 * transliteration has happened).
	 */
	public function transliterate($string);
}


/**
 * Interface for Annotator Plugins.
 * 
 * An Annotator Plugin is an object that represents a program external
 * to CQPweb that can be used to manage files in some way (e.g. by 
 * tagging them.) 
 */
interface Annotator extends CQPwebPlugin
{
	/**
	 * Process a file (e.g. to tag or tokenise it).
	 * 
	 * Both arguments are relative or absolute paths. The method SHOULD NOT use
	 * CQPweb global variables.
	 * 
	 * The input file MUST NOT be modified.
	 * 
	 * This function should return false if the output file was not 
	 * successfully created.
	 * 
	 * If the output file is partially created or created with errors, it
	 * should be deleted before false is returned.
	 */
	public function process_file($path_to_input_file, $path_to_output_file);
	
	/**
	 * Should return true if either no file has yet been processed, or
	 * the last file was processed successfully.
	 * 
	 * Should return false if the last file was not processed successfully.
	 */
	public function status_ok();
	
	/**
	 * Returns a string describing the last encountered error.
	 * 
	 * If there has been no error, then it can return an empty string,
	 * or a message saying there has been no error. It doesn't matter which.
	 */
	public function error_desc();
	
	/**
	 * Returns the size of the last output file created as an integer count of bytes.
	 * 
	 * If no file has yet been processed, return 0.
	 */
	public function output_size();
	
}

/**
 * Interface for FormatChecker Plugins.
 * 
 * An FormatChecker Plugin is an object capable of checking files for their 
 * compliance with some specified format - like, say for instance, "valid
 * UTF 8 text", "valid XML", "valid CWB input format". It can do this either using
 * internal PHP code, or by calling an external program.
 */
interface FormatChecker extends CQPwebPlugin
{

	/**
	 * Checks the specified file to see if it complies with this FormatChecker's
	 * particular file-formatting rules.
	 * 
	 * The argument can be absolute or relative path but the file it specifies
	 * MUST NOT be changed in any way.
	 * 
	 * Should return true if the file meets all the rules, or false if there is
	 * one or more problems.
	 */
	public function file_is_valid($path_to_input_file);
	
	/**
	 * Returns a string describing the problem that made the FormatChecker
	 * decide that the 
	 * 
	 * Should return false if either (a) no file has yet been processed or
	 * (b) the last file processed did not have any problems in it.
	 */
	public function error_desc();
	
	/**
	 * Returns the integer line number of the location, within the file that was
	 * last checked, where the error described by $this->error_desc
	 * was noticed. 
	 * 
	 * Note that this is NOT necesasarily the place where the error
	 * actually occurred. In some types of format checker, such as an XML parser,
	 * errors may become apparetn well after they actually happened.
	 *
	 * Should return NULL if either (a) the implementing class does not keep track
	 * of the location of errors or (b) the last file processed did not have any
	 * problems in it or (c) no file has been processed yet.
	 * 
	 * The first line of a file is considered to be line 1, not line 0.
	 */ 
	public function error_line_number();
	
	/**
	 * Returns the integer byte offset of the location, within the line given by
	 * $this->error_line_number(), where the error described by $this->error_desc
	 * was noticed.
	 * 
	 * Note it is a byte offset not a character offset in the case of non-8-bit
	 * data.
	 * 
	 * Should return NULL if either (a) the implementing class does not keep track
	 * of the location of errors or (b) the last file processed did not have any
	 * problems in it or (c) no file has been processed yet.
	 */
	public function error_line_byte();
	
	
}

//TODO: helper functions
/**
 * Interface for Postprocess Plugins.
 * 
 * A Postprocess Plugin is an object capable of transmuting a CQP query in some way.
 * These postprosses are "custom" versions of the built-in query postprocessing
 * tools - distribution, collocation, thin, sort and so on.
 * 
 * It does not need to actually interface with CQP in any way - all it needs to do
 * is operate on the integer indexes that the query result consists of.
 *
 * Postprocess helper functions are provided to help access CQP so that the actual
 * content of concordances can be retrieved. Of course the plugin can access CQPweb's
 * internal functions at liberty, if you so choose; it's your funeral!
 */
interface Postprocessor extends CQPwebPlugin
{
	/**
	 * Runs the defined custom postprocess on the query.
	 *
	 * The parameter is an array of arrays. Each inner array contains the four
	 * numbers that result from "dumping" a CQP query. See the CQP documentation
	 * for more info on this. Basically each match is represented by two, three or
	 * four numbers: match, matchend, [target, [keyword]]. The outer array will have sequential
	 * integer indexes beginning at 0.
	 *
	 * The return value should be the same array, but edited as necessary - for example,
	 * entries removed, or expanded, or results added... as appropriate to the purpose
	 * of the custom postprocess.
	 * 
	 * The inner arrays can contain integers or integers-as-strings (both will be OK as
	 * a result of PHP's automatic type juggling). All inner arrays must be of the same
	 * length (i.e. you do not have to supply target and keyword if you don't want to, 
	 * but if you do, every match must have them). The indexes in the inner arrays 
	 * are not important, only the --order-- of the elements; but the outer array 
	 * will be re-sorted, so order in that does not matter.
	 */
	public function postprocess_query($query_array);
	
	/**
	 * Returns the menu label to invoke this postprocess.
	 *
	 * Postprocesses are invoked from the dropdown on the concordance screen. So, custom
	 * postprocesses must tell CQPweb what they want their label on that dropdown to be.
	 * (The corresponding value for the HTML form will always be just Custom_ + the 
	 * classname.)
	 *
	 * This function should return either a string, or any empty value if this postprocess
	 * is not to be displayed in the dropdown menu.
	 * 
	 * The string may not be identical to any of the options present on the built-in dropdown 
	 * menu. (Nothing in particular would go wrong if they were - it would just be 
	 * extremely confusing.)
	 */ 
	public function get_label();
	
	/**
	 * Returns a string to be used in header descriptions in the CQPweb visual interface.
	 * 
	 * This is best phrased as a past participial clause. For example, "manipulated by the 
	 * Jones system", or "reduced in an arbitrary way", or something else. It should be 
	 * compatible with the description returned by ->get_label(), as both are shown to the
	 * user. 
	 * 
	 * Note that this function will often be called on a SEPARATE instance of the object to the 
	 * one that does the actual postprocessing. So, you cannot include particulars about 
	 * a specific run of the postprocessor. It can only be a generic description.  
	 * 
	 * If the empty string '' is returned, then this postprocess will not be mentioned in
	 * the header interface -- but the reduced number of hits will still be shown (it has
	 * to be, to make sense. So on balance, it's better to say something!
	 * 
	 * The optional argument (bool) specified whether the returned value will be printed out
	 * in an HTML context (true, the default) or a plain text context. The class may, but
	 * does not have to, return something different depending on the argument.
	 */
	public function get_postprocess_description($html = true);
}


/**
 * An autoload function is defined for plugin classes.
 * 
 * All plugin classes must be files of the form ClassName.php,
 * within the lib/plugins subdirectory.
 * 
 * Note that in CQPweb, plugins are the ONLY classes that can be
 * autoloaded.
 * 
 * The autoload function therefore doesn't need to be included unless
 * either transliteration or annotation is going to be happening.
 * 
 * The $plugin parameter is, of course, the classname.
 */ 
function __autoload($plugin)
{
	// TODO on ScriptSwitcher plugins: 
	// modify Visualisations management to allow transliteration to be engaged
	// then modify concordance and context to run it on the strings that
	// come from CQP if the ncessary user options (d admin setup choices) are TRUE.
	// end TODO
	
	// TODO on format checker plugins:
	// modify the upload area to give each file a chec format link; that takes you to
	// the little interface that I have sethced out.
	// TODO on annotator pugins:
	// actually, what we need for these is actually pretty similar to format checker
	// so look if there is the possibility of integration.
	// TODO for both: someday, individual non-sysadmin users may want to do this..........
	
	global $plugin_registry;
// TODO under the new system, I do not believe that this should be global any more. 
	
	/* Check it's a valid plugin class, ie one that has been declared.
	 * We do not load plugins willy-nilly. Only those that have been
	 * declared in the configuration. */
	if (false == ($record = retrieve_plugin_info($plugin)) )
		exiterror_general('Attempting to autoload an unknown plugin! Check the configuration.');
	
	/* if the file exists, load it. If not, fall over and die. */
	$file = "../lib/plugins/$plugin.php";
	if (is_file($file))
		require_once($file);
		/* note that apparently, from the PHP manual, this puts it into global scope... */
	else
		exiterror_general('Attempting to load a plugin file that could not be found! Check the configuration.');
	
	if (!class_exists($plugin))
		exiterror_general('Plugin autoload failure, CQPweb aborts.');
	
	/* now that we've got it loaded, check it implements the right interface. */
	switch ($record->type)
	{
	case PLUGIN_TYPE_TRANSLITERATOR:
		if (! in_array('ScriptSwitcher', class_implements($plugin, false)) )
			exiterror_general('Bad plugin! Doesn\'t implement the ScriptSwitcher interface.'
				. ' Check the coding of your plugin ' . $plugin);
		break;
	case PLUGIN_TYPE_ANNOTATOR:
		if (! in_array('Annotator', class_implements($plugin, false)) )
			exiterror_general('Bad plugin! Doesn\'t implement the Annotator interface.'
				. ' Check the coding of your plugin ' . $plugin);
		break;
	case PLUGIN_TYPE_FORMATCHECKER:
		if (! in_array('FormatChecker', class_implements($plugin, false)) )
			exiterror_general('Bad plugin! Doesn\'t implement the FormatChecker interface.'
				. ' Check the coding of your plugin ' . $plugin);
		break;
	case PLUGIN_TYPE_POSTPROCESSOR:
		if (! in_array('Postprocessor', class_implements($plugin, false)) )
			exiterror_general('Bad plugin! Doesn\'t implement the Postprocessor interface.'
				. ' Check the coding of your plugin ' . $plugin);
		break;
	default:
		exiterror_general('Unrecognised type of plugin!'
			. ' Check the declaration of your plugin ' . $plugin);
		break;
	}
	
	/* all done -- assuming we haven't died, the plugin is ready to construct. */
}





?>