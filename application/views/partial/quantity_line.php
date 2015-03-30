<?php
        	if($item['is_serialized']==1 && !$item['unit_validation_required'])
        	{
        		echo $item['quantity'];
        		echo form_hidden('quantity',$item['quantity']);
        		echo form_hidden('unit_id',$item['unit_id']);
        	}
        	else
        	{
        		if (count($item['item_units']) > 1)
        		{
        			if ($item['unit_validation_required'] && count($item['item_units'] == 2))
	        		{
	        			// how do we know whether order is the same as in .. ?
		        		foreach($item['item_units'] as $unit_id => $unit_details)
						{
							$index = array_search($unit_id, $item['unit_ids']);
				            echo form_input(array('name'=>'quantities[]','value'=>$item['quantities'][$index],'size'=>'2'));
				            echo $unit_details['unit_name'];
				            echo form_hidden('unit_ids[]',$unit_id);
						}
        			}
        			else
        			{
        				echo form_input(array('name'=>'quantity','value'=>$item['quantity'],'size'=>'2'));
        				echo $item['unit_name'];
	        			echo form_dropdown('unit_id',$item['item_units'],$item['unit_id'],'id="unit_id" onchange="$(this).parents("form").submit();"');
        			}
        		}
        		else
        		{
        			echo form_input(array('name'=>'quantity','value'=>$item['quantity'],'size'=>'3'));
        			echo $item['unit_name'];
        			echo form_hidden('unit_id',$item['unit_id']);
        		}
        	}
			?>