<?php echo form_open('config/save_shopify/', array('id' => 'shopify_config_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal')); ?>
	<div id="config_wrapper">
		<fieldset id="config_info">
			<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>
			<div id="integrations_header"><?php echo $this->lang->line('config_shopify_configuration')?></div>
			<ul id="shopify_error_message_box" class="error_message_box"></ul>

			<div class="form-group form-group-sm">
				<?php echo form_label($this->lang->line('config_shopify_shop_url'), 'shopify_shop_url', array('class' => 'control-label col-xs-2')); ?>
				<div class="col-xs-4">
					<div class="input-group">
						<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-home"></span></span>
						<?php echo form_input(array(
							'name' => 'shopify_shop_url',
							'id' => 'shopify_shop_url',
							'class' => 'form-control input-sm',
							'placeholder' => 'example.myshopify.com',
							'value' => $shopify['shop_url'])); ?>
					</div>
				</div>
				<div class="col-xs-1">
					<label class="control-label">
						<span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="right" title="<?php echo $this->lang->line('config_shopify_shop_url_tooltip'); ?>"></span>
					</label>
				</div>
			</div>

			<div class="form-group form-group-sm">
				<?php echo form_label($this->lang->line('config_shopify_api_key'), 'shopify_api_key', array('class' => 'control-label col-xs-2')); ?>
				<div class="col-xs-4">
					<div class="input-group">
						<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-cloud"></span></span>
						<?php echo form_input(array(
							'name' => 'shopify_api_key',
							'id' => 'shopify_api_key',
							'class' => 'form-control input-sm',
							'value' => $shopify['api_key'])); ?>
					</div>
				</div>
				<div class="col-xs-1">
					<label class="control-label">
						<a href="https://help.shopify.com/en/manual/apps/app-types/custom-apps" target="_blank"><span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="right" title="<?php echo $this->lang->line('config_shopify_tooltip'); ?>"></span></a>
					</label>
				</div>
			</div>

			<div class="form-group form-group-sm">
				<?php echo form_label($this->lang->line('config_shopify_api_secret_key'), 'shopify_api_secret_key', array('class' => 'control-label col-xs-2')); ?>
				<div class="col-xs-4">
					<div class="input-group">
						<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-cloud"></span></span>
						<?php echo form_input(array(
							'name' => 'shopify_api_secret_key',
							'id' => 'shopify_api_secret_key',
							'class' => 'form-control input-sm',
							'value' => $shopify['api_secret_key'])); ?>
					</div>
				</div>
				<div class="col-xs-1">
					<label class="control-label">
						<span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="right" title="<?php echo $this->lang->line('config_shopify_api_secret_key_tooltip'); ?>"></span>
					</label>
				</div>
			</div>

			<?php echo form_submit(array(
				'name' => 'submit_shopify',
				'id' => 'submit_shopify',
				'value' => $this->lang->line('common_submit'),
				'class' => 'btn btn-primary btn-sm pull-right')); ?>
		</fieldset>
	</div>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{
	var check_shopify_api = function() {
		var shop_url = $('#shopify_shop_url').val();
		var api_key = $('#shopify_api_key').val();
		var api_secret_key = $('#shopify_api_secret_key').val();

		if(shop_url && (api_key || api_secret_key)) {
			$.post("<?php echo site_url($controller_name . '/ajax_check_shopify_api_key')?>", {
					'shopify_shop_url': shop_url,
					'shopify_api_key': api_key,
					'shopify_api_secret_key': api_secret_key
				},
				function(response) {
					$.notify({message: response.message}, {type: response.success ? 'success' : 'danger'} );
				},
				'json'
			);
		}
	};

	$('#shopify_shop_url, #shopify_api_key, #shopify_api_secret_key').change(check_shopify_api);

	$('#shopify_config_form').validate($.extend(form_support.handler, {
		submitHandler: function(form) {
			$(form).ajaxSubmit({
				success: function(response) {
					$.notify( { message: response.message }, { type: response.success ? 'success' : 'danger'} )
				},
				dataType: 'json'
			});
		},

		errorLabelContainer: '#shopify_error_message_box'
	}));
});
</script>
