<?php
class Item_units extends CI_Model
{
    function exists($unit_id='')
    {
        $this->db->from('item_units');  
        $this->db->where('unit_id',$unit_id);
        $query = $this->db->get();
        
        return ($query->num_rows()>=1);
    }
    
    function get_undeleted_all()
    {
    	$this->db->from('item_units');
    	$this->db->where('deleted', 0);
    	$item_units = array();
    	foreach($this->db->get()->result_array() as $unit => $unit_data)
    	{
    		$item_units[$unit_data['unit_id']] = $unit_data;
    	}
    	return $item_units;
    }
    
    function get_default_unit()
    {
    	$this->db->from('item_units');
    	$this->db->join('items_units_categories', 'items_units_categories.unit_id=item_units.unit_id');
    	$this->db->order_by('item_units.unit_id');
    	$this->db->where('deleted', 0);
    	$this->db->limit(1);
    	return $this->db->get();
    }
    
    function unit_validation_required($category_id)
    {
    	if (empty($category_id)) 
    	{
    		return false;
    	}
    	
    	$this->db->from('item_units');
    	$this->db->join('items_units_categories', 'items_units_categories.unit_id=item_units.unit_id');
    	$this->db->where('unit_conversion', '1');
		$this->db->where('category_id', $category_id);    

		return $this->db->get()->num_rows() == 2;
   } 
    
    function get_default_unit_id() 
    {
    	return $this->get_default_unit()->row()->unit_id;
    }
    
    function get_all()
    {
        $this->db->from('item_units');
        return $this->db->get();
    }
    
    function get_unit_name($unit_id) 
    {
    	$this->db->from('item_units');
    	$this->db->where('unit_id',$unit_id);
    	return $this->db->get()->row()->unit_name;
    }
    
    function get_unit_details_by_category_id($category_id)
    {
    	$units = $this->get_units_by_category_id($category_id);
    	$item_units = array();
    	foreach($units as $unit => $unit_detail)
    	{
    		$item_units[$unit_detail['unit_id']] = $unit_detail;
    	}
    	return $item_units;
    }
    
    function get_units_by_category_id($category_id)
    {
    	if (empty($category_id))
    	{
			return $this->get_default_unit()->result_array();
    	}
    	else 
    	{
	    	$this->db->from('item_units');
    		$this->db->join('items_units_categories', 'items_units_categories.unit_id=item_units.unit_id');
	    	$this->db->where('category_id', $category_id);
	    	$this->db->order_by('inventory_check', 'desc');
    	}
    	return $this->db->get()->result_array();
    }
    
    function save(&$unit_data,$unit_id) 
    {
    	if (!$this->exists($unit_id))
    	{
   			$this->db->insert('item_units',$unit_data);
   			$unit_id = $this->db->insert_id();
   		}
    	else 
    	{
    		$this->db->where('unit_id', $unit_id);
    		return $this->db->update('item_units',$unit_data);
    	}
    }
    	
    /*
     Deletes one item
    */
    function delete($unit_id)
    {
    	$this->db->where('unit_id', $unit_id);
    	$this->db->update('item_units', array('deleted' => 1));
    }
    
}
?>