<?php
/**
 * @var array  $shopify
 * @var string $controller_name
 */
?>

<?= form_open('config/saveShopify/', ['id' => 'shopify_config_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']) ?>
    <div id="config_wrapper">
        <fieldset id="config_info">

            <div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
            <div id="integrations_header"><?= lang('Config.shopify_configuration') ?></div>
            <ul id="shopify_error_message_box" class="error_message_box"></ul>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Config.shopify_shop_url'), 'shopify_shop_url', ['class' => 'control-label col-xs-3 col-sm-2']) ?>
                <div class="col-xs-8 col-sm-6 col-md-5 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-addon input-sm">
                            <span class="glyphicon glyphicon-home"></span>
                        </span>
                        <?= form_input([
                            'name'        => 'shopify_shop_url',
                            'id'          => 'shopify_shop_url',
                            'class'       => 'form-control input-sm',
                            'value'       => $shopify['shop_url'],
                            'placeholder' => 'example.myshopify.com',
                        ]) ?>
                    </div>
                </div>
                <div class="col-xs-1 col-sm-4 col-md-5 col-lg-6">
                    <p class="form-control-static">
                        <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="right" title="<?= lang('Config.shopify_shop_url_tooltip') ?>"></span>
                    </p>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Config.shopify_api_key'), 'shopify_api_key', ['class' => 'control-label col-xs-3 col-sm-2']) ?>
                <div class="col-xs-8 col-sm-6 col-md-5 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-addon input-sm">
                            <span class="glyphicon glyphicon-cloud"></span>
                        </span>
                        <?= form_input([
                            'name'        => 'shopify_api_key',
                            'id'          => 'shopify_api_key',
                            'class'       => 'form-control input-sm',
                            'value'       => $shopify['api_key'],
                            'placeholder' => lang('Config.shopify_api_key'),
                        ]) ?>
                    </div>
                </div>
                <div class="col-xs-1 col-sm-4 col-md-5 col-lg-6">
                    <p class="form-control-static">
                        <a href="https://help.shopify.com/en/manual/apps/app-types/custom-apps" target="_blank">
                            <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="right" title="<?= lang('Config.shopify_tooltip') ?>"></span>
                        </a>
                    </p>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Config.shopify_api_secret_key'), 'shopify_api_secret_key', ['class' => 'control-label col-xs-3 col-sm-2']) ?>
                <div class="col-xs-8 col-sm-6 col-md-5 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-addon input-sm">
                            <span class="glyphicon glyphicon-lock"></span>
                        </span>
                        <?= form_password([
                            'name'        => 'shopify_api_secret_key',
                            'id'          => 'shopify_api_secret_key',
                            'class'       => 'form-control input-sm',
                            'value'       => $shopify['api_secret_key'],
                            'placeholder' => lang('Config.shopify_api_secret_key'),
                        ]) ?>
                    </div>
                </div>
                <div class="col-xs-1 col-sm-4 col-md-5 col-lg-6">
                    <p class="form-control-static">
                        <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="right" title="<?= lang('Config.shopify_api_secret_key_tooltip') ?>"></span>
                    </p>
                </div>
            </div>

            <?= form_submit([
                'name'  => 'submit_shopify',
                'id'    => 'submit_shopify',
                'value' => lang('Common.submit'),
                'class' => 'btn btn-primary btn-sm pull-right',
            ]) ?>

        </fieldset>
    </div>
<?= form_close() ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        const checkShopifyCredentials = function() {
            const shopUrl = $('#shopify_shop_url').val();
            const apiKey = $('#shopify_api_key').val();
            const apiSecretKey = $('#shopify_api_secret_key').val();

            if (shopUrl && (apiKey || apiSecretKey)) {
                $.post("<?= "{$controller_name}/checkShopifyCredentials" ?>", {
                        'shopify_shop_url': shopUrl,
                        'shopify_api_key': apiKey,
                        'shopify_api_secret_key': apiSecretKey
                    },
                    function(response) {
                        $.notify({
                            message: response.message
                        }, {
                            type: response.success ? 'success' : 'danger'
                        });
                    },
                    'json'
                );
            }
        };

        $('#shopify_shop_url, #shopify_api_key, #shopify_api_secret_key').change(checkShopifyCredentials);

        $('#shopify_config_form').validate($.extend(form_support.handler, {
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        $.notify({
                            message: response.message
                        }, {
                            type: response.success ? 'success' : 'danger'
                        })
                    },
                    dataType: 'json'
                });
            },

            errorLabelContainer: '#shopify_error_message_box'
        }));
    });
</script>
