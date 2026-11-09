<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Migration helper
 */

function execute_script($path)
{
	$CI =& get_instance();

	$version = preg_replace("/(.*_)?(.*).sql/", "$2", $path);
	error_log("Migrating to $version (file: $path)");

	$sql = file_get_contents($path);

	/*
	CI migration only allows you to run one statement at a time.
	This small script splits the statements allowing you to run them all in one go.
	*/

	$sqls = explode(';', $sql);
	array_pop($sqls);

	$ignored_errors = array(1050, 1051, 1054, 1060, 1061, 1062, 1146);

	foreach($sqls as $statement)
	{
		$statement = trim($statement);
		if(empty($statement))
		{
			continue;
		}

		$statement = $statement . ';';

		if(!$CI->db->simple_query($statement))
		{
			$error = $CI->db->error();
			if(!empty($error['code']) && !in_array($error['code'], $ignored_errors))
			{
				error_log('error: ' . $error['code'] . ' - ' . $error['message']);
			}
		}
	}

	error_log("Migrated to $version");
}

?>
