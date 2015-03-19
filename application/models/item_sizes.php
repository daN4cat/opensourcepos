<?php

class Item_sizes extends CI_Model
{
	function get_sizes_by_category_id($item_category_id) {
		$this->db->select('items_sizes.id as id, size');
		$this->db->from('items_sizes');
		$this->db->join('items_categories', 'items_categories.item_size_category_id = items_sizes.item_size_category_id');
		$this->db->where('items_categories.id', $item_category_id);
		$this->db->order_by('items_sizes.id', 'asc');
		return $this->db->get()->result_array();
	}
}