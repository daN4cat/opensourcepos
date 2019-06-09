<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Boutique extends CI_Migration
{
	public function up()
	{
		execute_script(APPPATH . 'migrations/sqlscripts/3.3.0_to_boutique.sql');
	}
}
?>
