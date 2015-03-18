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
    	return $this->db->get();
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