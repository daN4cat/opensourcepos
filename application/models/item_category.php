<?php

class Item_category extends CI_Model
{
	
	function exists($name) 
	{
		$this->db->from('items_categories');
		$this->db->where('name', $name);
		$query = $this->db->get();
		return ($query->num_rows()==1);
	}

	function get_all() 
	{
		$this->db->from('items_categories');
		$this->db->where('deleted',0);
		return $this->db->get();
	}
	
	function save(&$category_data,$category_id=false)
	{
		if (!$category_id or !$this->exists($category_id))
		{
			if ($this->db->insert('items_categories',$category_data))
			{
				$category_data['category_id']=$this->db->insert_id();
				return true;
			}
				
			return false;
		}
		
		$this->db->where('category_id', $category_id);
		return $this->db->update('items_categories',$category_data);
	}
	
	function get_category_by_name($name)
	{
		$this->db->from('items_categories');
		$this->db->where('name', $name);
		return $this->db->get();
	}
	
	function get_category($category_id) 
	{
		$this->db->where('category_id', $category_id);
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
		$this->db->select("category_id, short_name");
		$this->db->from('items_categories');
		$this->db->where('deleted', 0);
		$this->db->like('short_name', $search);
		$this->db->order_by('short_name', "asc");
		$by_category = $this->db->get();
		foreach($by_category->result() as $row)
		{
			$suggestions[]=$row->category_id . "|" . $row->short_name;
		}
		return $suggestions;
	}
	
}

?>