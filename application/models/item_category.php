<?php

class Item_category extends CI_Model
{

	function get_all() 
	{
		$this->db->from('items_categories');
		$this->db->where('deleted',0);
		return $this->db->get();
	}
	
	function get_category($category_id) 
	{
		$this->db->where('id', $category_id);
		return $this->db->get('items_categories')->row_array();
	}
	
	/**
	 * Get the id's and abbreviations for all the subcategories whose abbreviations match the given search string.
	 * @param unknown_type $search The search string
	 * @return The matching subcategories' abbreviations
	 */
	function get_category_suggestions($search)
	{
		$suggestions = array();
		$this->db->distinct();
		$this->db->select("id, abbreviation");
		$this->db->from('items_categories');
		$this->db->where('deleted', 0);
		$this->db->like('abbreviation', $search);
		$this->db->order_by('abbreviation', "asc");
		$by_category = $this->db->get();
		foreach($by_category->result() as $row)
		{
			$suggestions[]=$row->id . "|" . $row->abbreviation;
		}
		return $suggestions;
	}
	
}

?>