<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Boutique extends CI_Migration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function up()
	{
		execute_script(APPPATH . 'migrations/sqlscripts/3.3.8_to_boutique.sql');
	}

	public function down()
	{

	}
}
?>
