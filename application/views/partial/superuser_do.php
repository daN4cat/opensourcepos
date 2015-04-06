<ul id="error_message_box" class="error_message_box"></ul>
<?php
echo form_open('employees/superuser_do/',array('id'=>'superuser_do'));
?>
<div class="field_row clearfix">	
<?php echo form_label($this->lang->line('employees_password').':', 'password'); ?>
	<div class='form_field'>
	<?php echo form_password('password', '', 'class ="password"');?>
	</div>
</div>
<?php 
echo form_close();
?>
<script type='text/javascript'>

//validation and submit handling
$(document).ready(function()
{
	$.validator.addMethod("password", function(value, element) 
	{
		return JSON.parse($.ajax(
		{
			  type: 'POST',
			  url: '<?php echo site_url("employees/check_password")?>',
			  data: {'password' : $(element).val() },
			  success: function(response) 
			  {
				  success=response.success;
			  },
			  async:false,
			  dataType: 'json'
        }).responseText).success;
        
    }, '<?php echo $this->lang->line("employees_password_must_match"); ?>');

	$(":password").focus();
    
	$('#superuser_do').validate({
		submitHandler:function(form)
		{
			$(form).ajaxSubmit();
			tb_remove();
		},
		errorLabelContainer: "#error_message_box",
 		wrapper: "li"
	});
});
</script>