<?php

class Item_sizes extends CI_Model
{
	function get_sizes_by_category_id($category_id) {
		$this->db->from('items_sizes');
		$this->db->join('items_sizes_categories', 'items_sizes_categories.size_id = items_sizes.size_id');
		$this->db->where('category_id', $category_id);
		$this->db->order_by('items_sizes.size_id', 'asc');
		return $this->db->get()->result_array();
	}
}