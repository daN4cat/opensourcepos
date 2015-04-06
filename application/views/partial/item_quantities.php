<?php
foreach($stock_locations as $location_id=>$location_detail)
{
	foreach($item_units as $unit_id => $unit_detail)
	{
	if ($item_id > -1) : ?>
    <div class="field_row clearfix">
    <?php echo form_label($this->lang->line('items_quantity').' '.$location_detail[$unit_id]['location_name'] .':', 'quantities[]',
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
    <?php endif; ?>
	<?php if ($unit_validation_required && $item_id > -1) : ?>
 	<div class="field_row clearfix">
    <?php echo form_label($this->lang->line('items_initial_quantity').' '.$location_detail[$unit_id]['location_name'] .':', 
                            'initial_quantities[]',
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
   	</div>
   	<?php endif; ?>
   	<?php if ($unit_detail['unit_conversion']) :?>
    <div class="field_row clearfix">
    <?php echo form_label($this->lang->line('items_conversion_rate').' '.$location_detail[$unit_id]['location_name'] .':', 
    		'conversion_rates', array('class'=>'required wide')); ?>
    	<div class='form_field'>
    	<?php echo form_input(array(
    		'name'=>'conversion_rate',
    		'size'=>'8',
    		'class'=>'quantity',
    		'value'=>$location_detail[$unit_id]['conversion_rate'])
    	);?>
    	<?php echo $unit_detail['unit_name'] . '/' . $last_unit;?>
    	</div>
    	<div class='form_field'>
    	<?php echo form_input(array(
    		'name'=>'conversion_margin',
    		'class'=>'quantity',
    	    'type'=>'number',
    		'max'=>100,
    		'min'=>0,
    		'style' =>'width:40px',
    		'value'=>$location_detail[$unit_id]['conversion_margin'])
    	);?>
    	% / <?php echo $unit_detail['unit_name'];?>
    	</div>
    </div>
    <?php endif; ?>
	<?php
	$last_unit = $unit_detail['unit_name'];
	}
}
?>