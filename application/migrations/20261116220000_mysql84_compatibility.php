<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_mysql84_compatibility extends CI_Migration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function up()
	{
		error_log('Migrating mysql84_compatibility');

		// 1. Update foreign key ospos_sales_items_taxes_ibfk_1 to composite (sale_id, item_id, line)
		// MySQL 8.4 rejects referencing non-unique keys on parent tables.
		$check_sql = "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS "
			. "WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '" . $this->db->dbprefix('sales_items_taxes') . "' "
			. "AND CONSTRAINT_NAME = 'ospos_sales_items_taxes_ibfk_1'";
		$fk = $this->db->query($check_sql)->row();
		if(!empty($fk))
		{
			$this->db->query('ALTER TABLE `' . $this->db->dbprefix('sales_items_taxes') . '` DROP FOREIGN KEY `ospos_sales_items_taxes_ibfk_1`');
		}

		$this->db->query('ALTER TABLE `' . $this->db->dbprefix('sales_items_taxes') . '` '
			. 'ADD CONSTRAINT `ospos_sales_items_taxes_ibfk_1` FOREIGN KEY (`sale_id`, `item_id`, `line`) '
			. 'REFERENCES `' . $this->db->dbprefix('sales_items') . '` (`sale_id`, `item_id`, `line`)');

		// 2. Update ospos_people composite index prefix lengths so index key length does not exceed 3072 bytes under utf8mb4
		$check_idx = "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS "
			. "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $this->db->dbprefix('people') . "' "
			. "AND INDEX_NAME = 'first_name'";
		$idx = $this->db->query($check_idx)->row();
		if(!empty($idx) && $idx->cnt > 0)
		{
			$this->db->query('ALTER TABLE `' . $this->db->dbprefix('people') . '` DROP INDEX `first_name`');
		}

		$this->db->query('ALTER TABLE `' . $this->db->dbprefix('people') . '` '
			. 'ADD INDEX `first_name` (`first_name`(191), `last_name`(191), `email`(191), `phone_number`(191))');

		error_log('Migrated mysql84_compatibility');
	}

	public function down()
	{
	}
}
?>
