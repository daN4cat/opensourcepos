<?php $i = 0; ?>
<?php foreach($item_units as $unit => $unit_data ) { ?>
<?php $unit_id = $unit_data['unit_id']; ?>
<?php $unit_name = $unit_data['unit_name']; ?>
<div class="field_row clearfix" style="<?php echo $unit_data['deleted'] ? 'display:none;' : 'display:block;' ?>">    
<?php echo form_label($this->lang->line('config_item_unit').' ' .++$i. ':', 'item_unit_'.$i ,array('class'=>'required wide')); ?>
    <div class='form_field'>
    <?php $form_data = array(
        'name'=>'item_unit_'.$unit_id,
        'id'=>'item_unit_'.$unit_id,
    	'class'=>'item_unit valid_chars required',
        'value'=>$unit_name); 
    	$unit_data['deleted'] && $form_data['disabled'] = 'disabled';
    	echo form_input($form_data);
    ?>
    </div>
    <img class="add_item_unit" src="<?php echo base_url('images/plus.png'); ?>" />
    <img class="remove_item_unit" src="<?php echo base_url('images/minus.png'); ?>" />
</div>
<?php } ?>
