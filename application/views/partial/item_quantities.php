<?php
foreach($stock_locations as $location_id=>$location_detail)
{
	foreach($item_units as $unit_id => $unit_detail)
	{
	?>
    <div class="field_row clearfix">
    <?php echo form_label($this->lang->line('items_quantity').' '.$location_detail[$unit_id]['location_name'] .':', 
                            $location_id.'_quantity',
                            array('class'=>'required wide')); ?>
    	<div class='form_field'>
    	<?php echo form_input(array(
    		'name'=>'quantities[]',
    		'class'=>'quantity',
    		'size'=>'8',
    		'value'=>$location_detail[$unit_id]['quantity'])
    	);?>
    	<?php echo $unit_detail['unit_name'];?>
    	</div>
    </div>
   	<?php if ($unit_detail['unit_conversion']) :?>
    <div class="field_row clearfix">
    <?php echo form_label($this->lang->line('items_initial_quantity').' '.$location_detail[$unit_id]['location_name'] .':', 
                            $location_id.'_initial_quantity',
                            array('class'=>'required wide')); ?>
    	<div class='form_field'>
    	<?php echo form_input(array(
    		'name'=>'initial_quantities[]',
    		'size'=>'8',
    		'class'=>'quantity',
    		'value'=>$location_detail[$unit_id]['initial_quantity'])
    	);?>
    	<?php echo $unit_detail['unit_name'];?>
    	</div>
    	<?php if ($unit_detail['inventory_check']) :?>
    	<div class='form_field'>
    	<?php echo form_input(array(
    		'name'=>'margins[]',
    		'class'=>'quantity',
    	    'type'=>'number',
    		'max'=>100,
    		'min'=>0,
    		'style' => 'width:40px',
    		'value'=>$location_detail[$unit_id]['margin'])
    	);?>
    	% / <?php echo $unit_detail['unit_name'];?>
    	</div>
    	<?php endif; ?>
    </div>
    <?php endif; ?>
	<?php
	}
}
?>