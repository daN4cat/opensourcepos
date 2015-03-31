<?php
class Item extends CI_Model
{
	/*
	Determines if a given item_id is an item
	*/
	function exists($item_id)
	{
		$this->db->from('items');
		$this->db->where('item_id',$item_id);
		$query = $this->db->get();

		return ($query->num_rows()==1);
	}
	
	function item_number_exists($item_number,$item_id='')
	{
		$this->db->from('items');
		$this->db->where('item_number', $item_number);
		if (!empty($item_id))
		{
			$this->db->where('item_id !=', $item_id);
		}
		$query=$this->db->get();
		return ($query->num_rows()==1);
	}
	
	function get_total_rows()
	{
		$this->db->from('items');
		$this->db->where('deleted',0);
		return $this->db->count_all_results();
	}
	
	function get_found_rows($search,$stock_location_id=-1,$low_inventory=0,$is_serialized=0,$no_description=0,$search_custom=0,$is_deleted=0)
	{
		$this->db->from("items");
		$this->db->join('items_sizes', 'items_sizes.size_id = items.size_id', 'left');
		$this->db->join('items_categories','items_categories.category_id=items.category_id', 'left');
		if ($stock_location_id > -1)
		{
			$this->db->join('item_quantities','item_quantities.item_id=items.item_id');
			$this->db->where('location_id',$stock_location_id);
		}
		if (!empty($search)) 
		{
			if ($search_custom==0)
			{
				$this->db->where("(name LIKE '%" . $search . "%' OR " .
					"item_number LIKE '" . $search . "%' OR " .
					$this->db->dbprefix('items').".item_id LIKE '" . $search . "%' OR " .
				"category_name LIKE '%" . $search . "%')");
			}
			else
			{
				$this->db->or_like('custom1',$search);
				$this->db->or_like('custom2',$search);
				$this->db->or_like('custom3',$search);
				$this->db->or_like('custom4',$search);
				$this->db->or_like('custom5',$search);
				$this->db->or_like('custom6',$search);
				$this->db->or_like('custom7',$search);
				$this->db->or_like('custom8',$search);
				$this->db->or_like('custom9',$search);
				$this->db->or_like('custom10',$search);
			}
		}
		$this->db->where('items.deleted', $is_deleted);
		if ($low_inventory !=0 )
		{
			$this->db->where('quantity <=', 'reorder_level');
		}
		if ($is_serialized !=0 )
		{
			$this->db->where('is_serialized', 1);
		}
		if ($no_description!=0 )
		{
			$this->db->where('description','');
		}
		return $this->db->get()->num_rows();
	}

	/*
	Returns all the items
	*/
	function get_all($stock_location_id=-1, $rows = 0, $limit_from = 0)
	{
		$this->db->from('items');
		$this->db->join('items_sizes', 'items_sizes.size_id = items.size_id', 'left');
		$this->db->join('items_categories','items_categories.category_id=items.category_id', 'left');
		if ($stock_location_id > -1)
		{
			$this->db->select('items.*, items_categories.*, item_quantities.*, items_sizes.*');
			$this->db->select('GROUP_CONCAT(quantity, unit_name SEPARATOR \' \') AS quantity, 
					GROUP_CONCAT(initial_quantity, unit_name SEPARATOR \' \') AS initial_quantity', FALSE);
			$this->db->join('item_quantities','item_quantities.item_id=items.item_id');
			$this->db->join('item_units','item_units.unit_id=item_quantities.unit_id');
			$this->db->where('location_id',$stock_location_id);
			$this->db->group_by('item_quantities.item_id');
		}
		$this->db->where('items.deleted',0);
		$this->db->order_by("items.name","asc");
		if ($rows > 0) {
			$this->db->limit($rows, $limit_from);
		}
		return $this->db->get();
	}
	
	/*
	Gets information about a particular item
	*/
	function get_info($item_id)
	{
		$this->db->from('items');
		$this->db->where('item_id',$item_id);
		$this->db->join('items_sizes', 'items_sizes.size_id = items.size_id', 'left');
		$this->db->join('items_categories','items_categories.category_id=items.category_id', 'left');
		
		$query = $this->db->get();

		if($query->num_rows()==1)
		{
			return $query->row();
		}
		else
		{
			//Get empty base parent object, as $item_id is NOT an item
			$item_obj=new stdClass();

			//Get all the fields from items table
			$fields = $this->db->list_fields('items');

			foreach ($fields as $field)
			{
				$item_obj->$field='';
			}

			return $item_obj;
		}
	}

	/*
	Get an item id given an item number
	*/
	function get_item_id($item_number)
	{
		$this->db->from('items');
		$this->db->where('item_number',$item_number);
        $this->db->where('deleted',0); // Parq 131226
        
		$query = $this->db->get();

		if($query->num_rows()==1)
		{
			return $query->row()->item_id;
		}

		return false;
	}

	/*
	Gets information about multiple items
	*/
	function get_multiple_info($item_ids)
	{
		$this->db->from('items');
		$this->db->where_in('item_id',$item_ids);
		$this->db->order_by('item_id', 'asc');
		return $this->db->get();
	}

	/*
	Inserts or updates a item
	*/
	function save(&$item_data,$item_id=false)
	{
		if (!$item_id or !$this->exists($item_id))
		{
			if($this->db->insert('items',$item_data))
			{
				$item_data['item_id']=$this->db->insert_id();
				return true;
			}
			return false;
		}

		$this->db->where('item_id', $item_id);
		return $this->db->update('items',$item_data);
	}

	/*
	Updates multiple items at once
	*/
	function update_multiple($item_data,$item_ids)
	{
		$this->db->where_in('item_id',$item_ids);
		return $this->db->update('items',$item_data);
	}

	/*
	Deletes one item
	*/
	function delete($item_id)
	{
		$this->db->where('item_id', $item_id);
		return $this->db->update('items', array('deleted' => 1));
	}

	/*
	Deletes a list of items
	*/
	function delete_list($item_ids)
	{
		$this->db->where_in('item_id',$item_ids);
		return $this->db->update('items', array('deleted' => 1));
 	}

 	/*
	Get search suggestions to find items
	*/
	function get_search_suggestions($search,$limit=25)
	{
		$suggestions = array();

		$this->db->from('items');
		$this->db->like('name', $search);
		$this->db->where('deleted',0);
		$this->db->order_by("name", "asc");
		$by_name = $this->db->get();
		foreach($by_name->result() as $row)
		{
			$suggestions[]=$row->name;
		}

		$this->db->from('items');
		$this->db->join('items_categories','items_categories.category_id=items.category_id', 'left');
		$this->db->where('items.deleted',0);
		$this->db->distinct();
		$this->db->like('category_name', $search);
		$this->db->order_by('category_name', 'asc');
		$by_category = $this->db->get();
		foreach($by_category->result() as $row)
		{
			$suggestions[]=$row->description;
		}

		$this->db->from('items');
		$this->db->like('item_number', $search);
		$this->db->where('deleted',0);
		$this->db->order_by("item_number", "asc");
		$by_item_number = $this->db->get();
		foreach($by_item_number->result() as $row)
		{
			$suggestions[]=$row->item_number;
		}
/** GARRISON ADDED 4/21/2013 **/
	//Search by description
		$this->db->from('items');
		$this->db->like('description', $search);
		$this->db->where('deleted',0);
		$this->db->order_by("description", "asc");
		$by_name = $this->db->get();
		foreach($by_name->result() as $row)
		{
			$suggestions[]=$row->name;
		}
/** END GARRISON ADDED **/

/** GARRISON ADDED 4/22/2013 **/
	//Search by custom fields
/* 		$this->db->from('items');
		$this->db->like('custom1', $search);
		$this->db->or_like('custom2', $search);
		$this->db->or_like('custom3', $search);
		$this->db->or_like('custom4', $search);
		$this->db->or_like('custom5', $search);
		$this->db->or_like('custom6', $search);
		$this->db->or_like('custom7', $search);
		$this->db->or_like('custom8', $search);
		$this->db->or_like('custom9', $search);
		$this->db->or_like('custom10', $search);
		$this->db->where('deleted',0);
		$this->db->order_by("name", "asc");
		$by_name = $this->db->get();
		foreach($by_name->result() as $row)
		{
			$suggestions[]=$row->name;
		} */
/** END GARRISON ADDED **/		
		
	//only return $limit suggestions
		if(count($suggestions > $limit))
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}
		return $suggestions;

	}

	function get_item_search_suggestions($search,$limit=25)
	{
		$suggestions = array();

		$this->db->from('items');
		$this->db->where('deleted',0);
		$this->db->like('name', $search);
		$this->db->order_by("name", "asc");
		$by_name = $this->db->get();
		foreach($by_name->result() as $row)
		{
			$suggestions[]=$row->item_id.'|'.$row->name;
		}

		$this->db->from('items');
		$this->db->where('deleted',0);
		$this->db->like('item_number', $search);
		$this->db->order_by("item_number", "asc");
		$by_item_number = $this->db->get();
		foreach($by_item_number->result() as $row)
		{
			$suggestions[]=$row->item_id.'|'.$row->item_number;
		}
/** GARRISON ADDED 4/21/2013 **/
	//Search by description
		$this->db->from('items');
		$this->db->where('deleted',0);
		$this->db->like('description', $search);
		$this->db->order_by("description", "asc");
		$by_description = $this->db->get();
		foreach($by_description->result() as $row)
		{
			$suggestions[]=$row->item_id.'|'.$row->name;
		}
/** END GARRISON ADDED **/	
		/** GARRISON ADDED 4/22/2013 **/		
	//Search by custom fields
/* 		$this->db->from('items');
		$this->db->where('deleted',0);
		$this->db->like('custom1', $search);
		$this->db->or_like('custom2', $search);
		$this->db->or_like('custom3', $search);
		$this->db->or_like('custom4', $search);
		$this->db->or_like('custom5', $search);
		$this->db->or_like('custom6', $search);
		$this->db->or_like('custom7', $search);
		$this->db->or_like('custom8', $search);
		$this->db->or_like('custom9', $search);
		$this->db->or_like('custom10', $search);
		$this->db->order_by("name", "asc");
		$by_description = $this->db->get();
		foreach($by_description->result() as $row)
		{
			$suggestions[]=$row->item_id.'|'.$row->name;
		} */
		/** END GARRISON ADDED **/
		
		//only return $limit suggestions
		if(count($suggestions > $limit))
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}
		return $suggestions;
	}

	function get_custom_suggestions($field,$search)
	{
		$suggestions = array();
		$this->db->distinct();
		$this->db->select('custom'.$field);
		$this->db->from('items');
		$this->db->like('custom'.$field, $search);
		$this->db->where('deleted', 0);
		$this->db->order_by("custom".$field, "asc");
		$by_category = $this->db->get();
		foreach($by_category->result() as $row)
		{
			$row = (array) $row;
			$suggestions[]=$row['custom'.$field];
		}
	
		return $suggestions;
	}
	
	/*
	 Persform a search on items
	*/
	function search($search,$stock_location_id=-1,$low_inventory=0,$is_serialized=0,$no_description=0,$search_custom=0,$deleted=0,$rows = 0,$limit_from = 0)
	{
		$this->db->from("items");
		$this->db->join('items_sizes', 'items_sizes.size_id = items.size_id', 'left');
		$this->db->join('items_categories','items_categories.category_id=items.category_id', 'left');
		if ($stock_location_id > -1)
		{
			$this->db->select('items.*, items_categories.*, item_quantities.*, items_sizes.*');
			$this->db->select('GROUP_CONCAT(quantity, unit_name SEPARATOR \' \') AS quantity,
				GROUP_CONCAT(initial_quantity, unit_name SEPARATOR \' \') AS initial_quantity', FALSE);
			$this->db->join('item_quantities','item_quantities.item_id=items.item_id');
			$this->db->join('item_units','item_units.unit_id=item_quantities.unit_id');
			$this->db->where('location_id',$stock_location_id);
			$this->db->group_by('item_quantities.item_id');
		}
		if (!empty($search)) 
		{
			if ($search_custom==0)
			{
				$this->db->where("(name LIKE '%" . $search . "%' OR " .
					"item_number LIKE '" . $search . "%' OR " .
					$this->db->dbprefix('items').".item_id LIKE '" . $search . "%' OR " .
					"category_name LIKE '%" . $search . "%')");
			}
			else
			{
				$this->db->or_like('custom1',$search);
				$this->db->or_like('custom2',$search);
				$this->db->or_like('custom3',$search);
				$this->db->or_like('custom4',$search);
				$this->db->or_like('custom5',$search);
				$this->db->or_like('custom6',$search);
				$this->db->or_like('custom7',$search);
				$this->db->or_like('custom8',$search);
				$this->db->or_like('custom9',$search);
				$this->db->or_like('custom10',$search);
			}
		}
		$this->db->where('items.deleted', $deleted);
		if ($low_inventory !=0 )
		{
			$this->db->where('quantity <=', 'reorder_level');
		}
		if ($is_serialized !=0 )
		{
			$this->db->where('is_serialized', 1);
		}
		if ($no_description!=0 )
		{
			$this->db->where('description','');
		}
		$this->db->order_by('items.name', "asc");
		if ($rows > 0) {
			$this->db->limit($rows, $limit_from);
		}
		return $this->db->get();
	}
	
	/*
	 * changes the cost price of a given item
	 * calculates the average price between received items and items on stock
	 * $item_id : the item which price should be changed
	 * $items_received : the amount of new items received
	 * $new_price : the cost-price for the newly received items
	 * $old_price (optional) : the current-cost-price
	 * 
	 * used in receiving-process to update cost-price if changed
	 * caution: must be used there before item_quantities gets updated, otherwise average price is wrong!
	 * 
	 */
	function change_cost_price($item_id, $items_received, $new_price, $old_price = null)
	{
		if($old_price === null)
		{
			$item_info = $this->get_info($item['item_id']);
			$old_price = $item_info->cost_price;
		}

		$this->db->from('item_quantities');
		$this->db->select_sum('quantity');
        $this->db->where('item_id',$item_id);
		$this->db->join('stock_locations','stock_locations.location_id=item_quantities.location_id');
        $this->db->where('stock_locations.deleted',0);
		$old_total_quantity = $this->db->get()->row()->quantity;

		$total_quantity = $old_total_quantity + $items_received;
		$average_price = bcdiv(bcadd(bcmul($items_received, $new_price), bcmul($old_total_quantity, $old_price)), $total_quantity);

		$data = array('cost_price' => $average_price);
		
		return $this->save($data, $item_id);
	}
    
}
?>