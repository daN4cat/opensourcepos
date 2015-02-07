<?php

define("ITEM_SIZE_TABLE_NAME", "items_sizes");
define("ITEM_SIZE_ID", "id");
define("ITEM_SIZE_PK", ITEM_SIZE_TABLE_NAME . "." . ITEM_SIZE_ID);
define("ITEM_SIZE_SIZE", "size");
define("ITEM_SIZE_ITEM_SIZE_CATEGORY", "item_size_category_id");
define("ITEM_SIZE_ITEM_SIZE_CATEGORY_FK", ITEM_SIZE_TABLE_NAME . "." . ITEM_SIZE_ITEM_SIZE_CATEGORY);

define("ITEM_SIZE_CATEGORY_TABLE_NAME", "items_sizes_categories");
define("ITEM_SIZE_CATEGORY_ID", "id");
define("ITEM_SIZE_CATEGORY_PK", ITEM_SIZE_CATEGORY_TABLE_NAME . "." . ITEM_SIZE_CATEGORY_ID);

class Item_sizes extends CI_Model
{
	function get_sizes_by_subcategory_id($subcategory_id) {
		$this->db->select(ITEM_SIZE_PK . " AS " . ITEM_SIZE_ID . ", " . ITEM_SIZE_SIZE);
		$this->db->from(ITEM_SIZE_TABLE_NAME);
		$this->db->join(SUBCATEGORY_TABLE_NAME, SUBCATEGORY_ITEM_SIZE_CATEGORY_FK . " = " . ITEM_SIZE_ITEM_SIZE_CATEGORY_FK);
		$this->db->where(SUBCATEGORY_PK, $subcategory_id);
		$this->db->order_by(ITEM_SIZE_ID, "asc");
		return $this->db->get()->result_array();		
	}
}