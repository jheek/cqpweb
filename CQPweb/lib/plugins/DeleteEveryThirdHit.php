<?php

/**
 * This postprocess deletes every third example from the query.
 *
 * Of course, you are not expected to actually want to do this. 
 * It is just to demonstrate what a custom postprocess looks like.
 */
class DeleteEveryThirdHit implements Postprocessor
{
	/* member variables */
	private $config_data;

	
	/* methods */
	public function __construct($path = '')
	{
		/* stash the contents of the config file in a private variable
		 * for use later, presumably (?) */
		if (is_file($path))
			$this->config_data  = file_get_contents($path);
	}
	
	public function postprocess_query($query_array)
	{
		$n = count($query_array);
		for ($i = 0 ; $i < $n; $i++)
		{
			if (0 == $i % 3)
				unset($query_array[$i]);
		}
		return $query_array;
	}
	
	public function get_label()
	{
		return "Delete every third hit from the query!";
	}
	
	public function get_postprocess_description()
	{
		return "amended to delete every third hit";
	}
}

?>