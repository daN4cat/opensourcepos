<div id="page_title"><?php echo $this->lang->line('config_unit_configuration'); ?></div>
<?php
echo form_open('config/save_units/',array('id'=>'unit_config_form'));
?>
    <div id="config_wrapper">
        <fieldset id="config_info">
            <div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>
            <ul id="unit_error_message_box" class="error_message_box"></ul>
            <legend><?php echo $this->lang->line("config_unit_info"); ?></legend>
            
            <div id="item_units">
				<?php $this->load->view('partial/item_units', array('item_units' => $item_units, 'default_unit_id' => $default_unit_id)); ?>
			</div>
            
            <?php 
            echo form_submit(array(
                'name'=>'submit',
                'id'=>'submit',
                'value'=>$this->lang->line('common_submit'),
                'class'=>'submit_button float_right')
            );
            ?>
        </fieldset>
    </div>
<?php
echo form_close();
?>


<script type='text/javascript'>

//validation and submit handling
$(document).ready(function()
{
	var unit_count = <?php echo sizeof($item_units); ?>;

	var hide_show_remove = function() 
	{
		if ($("input[name*='item_unit']").length > 1)
		{
			$(".remove_item_unit").show();
		} 
		else
		{
			$(".remove_item_unit").hide();
		}
	};

	var add_item_unit = function() 
	{
		var id = $(this).parent().find('input').attr('id');
		id = id.replace(/.*?_(\d+)$/g, "$1");
		var block = $(this).parent().clone(true);
		var new_block = block.insertAfter($(this).parent());
		var new_block_id = 'item_unit_' + ++id;
		$(new_block).find('label').html("<?php echo $this->lang->line('config_item_unit'); ?> " + ++unit_count + ": ").attr('for', new_block_id);
		$(new_block).find('input').attr('id', new_block_id).removeAttr('disabled').attr('name', new_block_id).val('');
		hide_show_remove();
	};

	var remove_item_unit = function() 
	{
		$(this).parent().remove();
		hide_show_remove();
	};

	var init_add_remove_quantities = function() 
	{
		$('.add_item_unit').click(add_item_unit);
		$('.remove_item_unit').click(remove_item_unit);
		hide_show_remove();
	};
	init_add_remove_quantities();

	var duplicate_found = false;
	// run validator once for all fields
	$.validator.addMethod('item_unit' , function(value, element) 
	{
		var value_count = 0;
		$("input[name*='item_unit']").each(function() {
			value_count = $(this).val() == value ? value_count + 1 : value_count; 
		});
		return value_count < 2;
    }, "<?php echo $this->lang->line('config_item_unit_duplicate'); ?>");

    $.validator.addMethod('item_unit_valid_chars', function(value, element)
	{
		return !(/^[a-zA-Z0-9]+$/.test(value));
    }, "<?php echo $this->lang->line('config_item_unit_invalid_chars'); ?>");
	
	$('#unit_config_form').validate({
		submitHandler:function(form)
		{
			$(form).ajaxSubmit({
			success:function(response)
			{
				if(response.success)
				{
					set_feedback(response.message,'success_message',false);		
				}
				else
				{
					set_feedback(response.message,'error_message',true);		
				}
				$("#item_units").load('<?php echo site_url("config/item_units");?>', init_add_remove_quantities);
			},
			dataType:'json'
		});

		},
		errorLabelContainer: "#unit_error_message_box",
 		wrapper: "li",
		rules: 
		{
    		item_unit: {
        		required:true,
				item_unit: true,
				item_unit_valid_chars: true
    		}
   		},
		messages: 
		{
     		item_unit:"<?php echo $this->lang->line('config_item_unit_required'); ?>"
		}
	});
});
</script>
