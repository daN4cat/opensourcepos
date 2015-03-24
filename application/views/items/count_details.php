<?php
echo form_open('items/save_inventory/'.$item_info->item_id,array('id'=>'item_form'));
?>
<fieldset id="inv_item_basic_info">
<legend><?php echo $this->lang->line("items_basic_information"); ?></legend>

<table align="center" border="0" bgcolor="#CCCCCC">
<div class="field_row clearfix">
<tr>
<td>	
<?php echo form_label($this->lang->line('items_item_number').':', 'name',array('class'=>'wide')); ?>
</td>
<td>
	<?php $inumber = array (
		'name'=>'item_number',
		'id'=>'item_number',
		'value'=>$item_info->item_number,
		'style'       => 'border:none',
		'readonly' => 'readonly'
	);
	
		echo form_input($inumber)
	?>
</td>
</tr>
<tr>
<td>	
<?php echo form_label($this->lang->line('items_name').':', 'name',array('class'=>'wide')); ?>
</td>
<td>	
	<?php $iname = array (
		'name'=>'name',
		'id'=>'name',
		'value'=>$item_info->name,
		'style'       => 'border:none',
		'readonly' => 'readonly'
	);
		echo form_input($iname);
		?>
</td>
</tr>
<tr>
<td>	
<?php echo form_label($this->lang->line('items_category').':', 'category',array('class'=>'wide')); ?>
</td>
<td>	
	<?php $cat = array (
		
		'name'=>'category',
		'id'=>'category',
		'value'=>$item_info->category_name,
		'style'       => 'border:none',
		'readonly' => 'readonly'
		);
	
		echo form_input($cat);
		?>
</td>
</tr>
<tr>
<td>
<?php echo form_label($this->lang->line('items_stock_location').':', 'stock_location',array('class'=>'wide')); ?>
</td>
<td>
    <?php 
        echo form_dropdown('stock_location',$stock_locations,current($stock_locations),'id="stock_location" onchange="display_stock(this.value,$(\'#unit_name\').val())"');
    ?> 
</td>
</tr>
<tr>
	<td>
		<?php echo form_label($this->lang->line('items_unit_name').':', 'unit_name',array('class'=>'wide')); ?>
	</td>
	<td>
	    <?php 
	        echo form_dropdown('unit_name',$item_units,current($item_units),'id="unit_name" onchange="display_stock($(\'#stock_location\').val(),this.value)"');
	    ?> 
	</td>
</tr>
<?php foreach($item_units as $unit_id => $unit_name) { ?>

<tr>
	<td>
		<?php echo form_label($this->lang->line('items_current_quantity', $unit_name).':', 'quantity',array('class'=>'wide')); ?>
	</td>
	<td>
		<?php 
		$qty = array (
			'name'=>'quantity',
			'id'=>'quantity_'.$unit_id,
			'value'=>$item_quantities[key($stock_locations)][$unit_id],
			'style'       => 'border:none',
			'readonly' => 'readonly'
			);
		
			echo form_input($qty);
		?>
	</td>
</tr>
<?php } ?>

</div>	
</table>

</fieldset>
<?php 
echo form_close();
?>
<?php
$employee_name = array();
foreach( $inventory_array as $row)
{
    $person_id = $row['trans_user'];
    $employee = $this->Employee->get_info($person_id);
    array_push($employee_name, $employee->first_name." ".$employee->last_name);
}
?>
<table id="inventory_result" border="0" align="center">
<tr bgcolor="#FF0033" align="center" style="font-weight:bold">
	<td colspan="4">Inventory Data Tracking</td>
</tr>
<tr align="center" style="font-weight:bold">
	<td width="15%">Date</td><td width="25%">Employee</td>
	<td width="15%">In/Out Qty</td>
	<td width="45%">Remarks</td>
</tr>
</table>

<script type='text/javascript'>
$(document).ready(function()
{
    display_stock(<?php echo json_encode(key($stock_locations)); ?>, <?php echo json_encode(current(array_keys($item_units))); ?>);
});

function display_stock(location_id,unit_id)
{
    var item_quantities= <?php echo json_encode($item_quantities ); ?>;
    var item_units = <?php echo json_encode($item_units); ?>;
    $.each(item_units, function(index, item_unit) 
    {
        $("quantity_" + index).val(item_quantities[location_id][index]);
    });
    
    var inventory_data = <?php echo json_encode($inventory_array); ?>;
    var employee_data = <?php echo json_encode($employee_name); ?>;
    
    var table = document.getElementById("inventory_result");
    //Remove old query
    var rowCount = table.rows.length;
    for (var index = rowCount; index > 2; index--)
    {
        table.deleteRow(index-1);       
    }
    
    //Add new query
    for (var index = 0; index < inventory_data.length; index++) 
    {                
        var data = inventory_data[index];
        if(data['trans_location'] == location_id && data['trans_unit'] == unit_id)
        {
            var tr = document.createElement('TR');
            tr.setAttribute("bgColor","#CCCCCC");
            tr.setAttribute("align","#center");
            
            var td = document.createElement('TD');
            td.appendChild(document.createTextNode(data['trans_date']));
            tr.appendChild(td);
            
            td = document.createElement('TD');
            td.appendChild(document.createTextNode(employee_data[index]));
            tr.appendChild(td);
            
            td = document.createElement('TD');
            td.setAttribute("align","right");
            td.appendChild(document.createTextNode(data['trans_inventory'] + item_units[data['trans_unit']]));
            tr.appendChild(td);
            
            td = document.createElement('TD');            
            td.appendChild(document.createTextNode(data['trans_comment']));
            tr.appendChild(td);

            table.appendChild(tr);
        }
    }
   
}  
</script>