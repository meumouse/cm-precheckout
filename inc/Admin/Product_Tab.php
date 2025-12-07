<?php

namespace MeuMouse\Cm_Precheckout\Admin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Product Tab for Pre-checkout Settings
 * 
 * @since 1.0.0
 * @version 1.1.0
 * @package MeuMouse.com
 */
class Product_Tab {
    
    /**
     * Options library instance
     * 
     * @since 1.1.0
     * @var Options_Library
     */
    private $options_library;

    /**
     * Constructor
     * 
     * @since 1.0.0
     * @version 1.1.0
     */
    public function __construct() {
        $this->options_library = new Options_Library();
        
        // Add custom tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_tab'), 99, 1);

        // Add tab content
        add_action('woocommerce_product_data_panels', array($this, 'add_tab_content'));

        // Save tab data
        add_action('woocommerce_process_product_meta', array($this, 'save_tab_data'), 10, 2);
        
        // Add AJAX handlers
        add_action('wp_ajax_cm_precheckout_save_product_steps', array($this, 'save_product_steps'));
    }


    /**
     * Add custom tab to product data
     * 
     * @since 1.0.0
     * @version 1.1.0
     * @param array $tabs
     * @return array
     */
    public function add_product_tab($tabs) {
        $tabs['cm_precheckout'] = array(
            'label'    => esc_html__('Etapas pré checkout', 'cm-precheckout'),
            'target'   => 'cm_precheckout_product_data',
            'class'    => array('show_if_simple', 'show_if_variable'),
            'priority' => 80,
        );

        return $tabs;
    }

    
    /**
     * Add tab content
     * 
     * @since 1.0.0
     * @version 1.1.0
     * @return void
     */
    public function add_tab_content() {
        global $post;
        
        $product = wc_get_product($post->ID);
        $precheckout_active = get_post_meta($post->ID, '_cm_precheckout_active', true);
        $product_steps = get_post_meta($post->ID, '_cm_precheckout_steps', true);
        $product_steps = is_array($product_steps) ? $product_steps : array();
        
        // Get enabled options from library
        $library_options = $this->options_library->get_enabled_options();
        
        // Get saved material configurations
        $materials_config = get_post_meta($post->ID, '_cm_precheckout_materials_config', true);
        $materials_config = is_array($materials_config) ? $materials_config : array(); ?>
        
        <div id="cm_precheckout_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_checkbox(array(
                    'id'            => '_cm_precheckout_active',
                    'label'         => esc_html__('Ativar/Desativar pré-checkout', 'cm-precheckout'),
                    'description'   => esc_html__('Marque para ativar o pré-checkout para este produto', 'cm-precheckout'),
                    'value'         => $precheckout_active ? 'yes' : 'no',
                    'default'       => 'yes',
                    'desc_tip'      => true,
                ));
                ?>
            </div>

            <div class="precheckout-options" style="<?php echo $precheckout_active ? '' : 'display: none;'; ?>">
                
                <!-- Dynamic Steps Management -->
                <div class="options_group">
                    <h4 class="fs-4"><?php esc_html_e('Gerenciar Etapas do Pré-Checkout', 'cm-precheckout'); ?></h4>
                    <p class="description"><?php esc_html_e('Arraste e solte para reordenar as etapas. Clique em uma etapa para configurar.', 'cm-precheckout'); ?></p>
                    
                    <div id="product-steps-container" class="product-steps-container">
                        <?php
                        // Display saved steps or default enabled steps
                        if (!empty($product_steps)) {
                            foreach ($product_steps as $step_key) {
                                if (isset($library_options[$step_key])) {
                                    $this->render_step_item($library_options[$step_key], $post->ID);
                                }
                            }
                        } else {
                            // Display all enabled options
                            foreach ($library_options as $option) {
                                $this->render_step_item($option, $post->ID);
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="available-steps">
                        <h5><?php esc_html_e('Etapas disponíveis para adicionar:', 'cm-precheckout'); ?></h5>
                        <div class="steps-list">
                            <?php
                            // Show disabled options
                            $all_options = $this->options_library->get_library_options();
                            $enabled_keys = array_keys($library_options);
                            
                            foreach ($all_options as $key => $option) {
                                if (!in_array($key, $enabled_keys) && $key !== 'summary') {
                                    echo '<div class="available-step" data-key="' . esc_attr($key) . '">';
                                    echo '<span class="dashicons ' . esc_attr($option['icon']) . '"></span>';
                                    echo '<span>' . esc_html($option['name']) . '</span>';
                                    echo '<button type="button" class="button button-small add-step">' . esc_html__('Adicionar', 'cm-precheckout') . '</button>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <input type="hidden" id="product_steps_order" name="_cm_precheckout_steps_order" 
                           value="<?php echo esc_attr(implode(',', array_keys($product_steps ?: $library_options))); ?>">
                </div>
                
                <!-- Materials Configuration Modal -->
                <div id="materials-config-modal" class="cm-modal" style="display: none;">
                    <div class="cm-modal-overlay"></div>
                    <div class="cm-modal-content">
                        <div class="cm-modal-header">
                            <h3><?php esc_html_e('Configurar Materiais', 'cm-precheckout'); ?></h3>
                            <button type="button" class="cm-modal-close">&times;</button>
                        </div>
                        <div class="cm-modal-body">
                            <div id="materials-config-container">
                                <!-- Will be populated via JavaScript -->
                            </div>
                        </div>
                        <div class="cm-modal-footer">
                            <button type="button" class="button button-secondary cm-modal-close">
                                <?php esc_html_e('Cancelar', 'cm-precheckout'); ?>
                            </button>
                            <button type="button" class="button button-primary save-materials-config">
                                <?php esc_html_e('Salvar', 'cm-precheckout'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Toggle precheckout options
                $('#_cm_precheckout_active').change(function() {
                    if ($(this).is(':checked')) {
                        $('.precheckout-options').show();
                    } else {
                        $('.precheckout-options').hide();
                    }
                });

                // Make steps sortable
                $('#product-steps-container').sortable({
                    handle: '.step-handle',
                    update: function(event, ui) {
                        var order = [];
                        $('#product-steps-container .step-item').each(function() {
                            order.push($(this).data('key'));
                        });
                        $('#product_steps_order').val(order.join(','));
                    }
                }).disableSelection();

                // Add step from available steps
                $('.add-step').click(function() {
                    var $step = $(this).closest('.available-step');
                    var key = $step.data('key');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cm_precheckout_get_step_template',
                            nonce: '<?php echo wp_create_nonce("cm_precheckout_admin_nonce"); ?>',
                            step_key: key,
                            product_id: <?php echo $post->ID; ?>
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#product-steps-container').append(response.data.html);
                                $step.remove();
                                
                                // Update order
                                var order = [];
                                $('#product-steps-container .step-item').each(function() {
                                    order.push($(this).data('key'));
                                });
                                $('#product_steps_order').val(order.join(','));
                            }
                        }
                    });
                });

                // Remove step
                $(document).on('click', '.remove-step', function() {
                    var $step = $(this).closest('.step-item');
                    var key = $step.data('key');
                    var name = $step.find('.step-title').text();
                    
                    if (confirm('<?php esc_html_e("Tem certeza que deseja remover esta etapa?", "cm-precheckout"); ?>')) {
                        // Move to available steps
                        var $availableStep = $('<div class="available-step" data-key="' + key + '">' +
                            '<span class="dashicons"></span>' +
                            '<span>' + name + '</span>' +
                            '<button type="button" class="button button-small add-step"><?php esc_html_e("Adicionar", "cm-precheckout"); ?></button>' +
                            '</div>');
                        
                        $('.available-steps .steps-list').append($availableStep);
                        $step.remove();
                        
                        // Update order
                        var order = [];
                        $('#product-steps-container .step-item').each(function() {
                            order.push($(this).data('key'));
                        });
                        $('#product_steps_order').val(order.join(','));
                    }
                });

                // Configure materials
                $(document).on('click', '.configure-materials', function() {
                    var productId = <?php echo $post->ID; ?>;
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cm_precheckout_get_materials_config',
                            nonce: '<?php echo wp_create_nonce("cm_precheckout_admin_nonce"); ?>',
                            product_id: productId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#materials-config-container').html(response.data.html);
                                $('#materials-config-modal').show();
                            }
                        }
                    });
                });

                // Save materials configuration
                $(document).on('click', '.save-materials-config', function() {
                    var formData = $('#materials-config-form').serialize();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData + '&action=cm_precheckout_save_materials_config&product_id=<?php echo $post->ID; ?>&nonce=<?php echo wp_create_nonce("cm_precheckout_admin_nonce"); ?>',
                        success: function(response) {
                            if (response.success) {
                                alert('<?php esc_html_e("Configurações salvas com sucesso!", "cm-precheckout"); ?>');
                                $('#materials-config-modal').hide();
                            }
                        }
                    });
                });

                // Close modal
                $('.cm-modal-close, .cm-modal-overlay').click(function() {
                    $('.cm-modal').hide();
                });

                // Prevent modal close when clicking inside
                $('.cm-modal-content').click(function(e) {
                    e.stopPropagation();
                });
            });
        </script>
        
        <style>
            .product-steps-container {
                margin: 15px 0;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                min-height: 100px;
            }
            .step-item {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 10px;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                cursor: move;
            }
            .step-handle {
                margin-right: 10px;
                color: #a0a5aa;
                cursor: move;
            }
            .step-icon {
                margin-right: 10px;
                color: #0073aa;
            }
            .step-content {
                flex: 1;
            }
            .step-title {
                margin: 0 0 5px 0;
                font-weight: 600;
            }
            .step-description {
                margin: 0;
                font-size: 12px;
                color: #666;
            }
            .step-actions {
                margin-left: 10px;
            }
            .available-steps {
                margin-top: 20px;
            }
            .available-steps h5 {
                margin-bottom: 10px;
            }
            .steps-list {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .available-step {
                background: #fff;
                border: 1px dashed #ccd0d4;
                border-radius: 4px;
                padding: 8px 12px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .available-step .dashicons {
                color: #72777c;
            }
        </style>
        <?php
    }
    
    /**
     * Render step item
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param array $option
     * @param int $product_id
     * @return void
     */
    private function render_step_item($option, $product_id) {
        $config = isset($option['config']) ? $option['config'] : array();
        ?>
        <div class="step-item" data-key="<?php echo esc_attr($option['key']); ?>">
            <span class="dashicons dashicons-move step-handle"></span>
            <span class="dashicons <?php echo esc_attr($option['icon']); ?> step-icon"></span>
            <div class="step-content">
                <h4 class="step-title"><?php echo esc_html($option['name']); ?></h4>
                <p class="step-description">
                    <?php 
                    echo esc_html__('Obrigatório:', 'cm-precheckout') . ' ';
                    echo $config['required'] ? esc_html__('Sim', 'cm-precheckout') : esc_html__('Não', 'cm-precheckout');
                    ?>
                </p>
            </div>
            <div class="step-actions">
                <?php if ($option['key'] === 'material_selection'): ?>
                    <button type="button" class="button button-small configure-materials">
                        <?php esc_html_e('Configurar', 'cm-precheckout'); ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="button button-small remove-step">
                    <?php esc_html_e('Remover', 'cm-precheckout'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    

    /**
     * Save tab data
     * 
     * @since 1.0.0
     * @version 1.1.0
     * @param int $post_id
     * @param object $post
     * @return void
     */
    public function save_tab_data($post_id, $post) {
        // Save active status
        $active = isset($_POST['_cm_precheckout_active']) ? 'yes' : 'no';
        update_post_meta($post_id, '_cm_precheckout_active', $active);

        // Save steps order
        if (isset($_POST['_cm_precheckout_steps_order'])) {
            $steps = explode(',', sanitize_text_field($_POST['_cm_precheckout_steps_order']));
            update_post_meta($post_id, '_cm_precheckout_steps', $steps);
        }

        // Save material configurations if they exist
        if (isset($_POST['_cm_precheckout_materials_config'])) {
            $materials_config = json_decode(stripslashes($_POST['_cm_precheckout_materials_config']), true);
            if (is_array($materials_config)) {
                update_post_meta($post_id, '_cm_precheckout_materials_config', $materials_config);
            }
        }

        // Legacy compatibility - save individual settings for existing products
        $this->save_legacy_settings($post_id);
    }

    /**
     * Save legacy settings for compatibility
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param int $post_id
     * @return void
     */
    private function save_legacy_settings($post_id) {
        // Materials
        if (isset($_POST['_cm_precheckout_materials'])) {
            $materials = array_map('sanitize_text_field', $_POST['_cm_precheckout_materials']);
            update_post_meta($post_id, '_cm_precheckout_materials', $materials);
        }

        // Size selection
        $enable_size_selection = isset($_POST['_cm_precheckout_enable_size_selection']) ? 'yes' : 'no';
        update_post_meta($post_id, '_cm_precheckout_enable_size_selection', $enable_size_selection);

        if (isset($_POST['_cm_precheckout_size_selectors'])) {
            update_post_meta($post_id, '_cm_precheckout_size_selectors', absint($_POST['_cm_precheckout_size_selectors']));
        }

        // Name engraving
        $enable_name_engraving = isset($_POST['_cm_precheckout_enable_name_engraving']) ? 'yes' : 'no';
        update_post_meta($post_id, '_cm_precheckout_enable_name_engraving', $enable_name_engraving);

        if (isset($_POST['_cm_precheckout_name_fields'])) {
            update_post_meta($post_id, '_cm_precheckout_name_fields', absint($_POST['_cm_precheckout_name_fields']));
        }

        // Other options
        $enable_course_change = isset($_POST['_cm_precheckout_enable_course_change']) ? 'yes' : 'no';
        update_post_meta($post_id, '_cm_precheckout_enable_course_change', $enable_course_change);

        $enable_stone_sample = isset($_POST['_cm_precheckout_enable_stone_sample']) ? 'yes' : 'no';
        update_post_meta($post_id, '_cm_precheckout_enable_stone_sample', $enable_stone_sample);

        $enable_emblems_sample = isset($_POST['_cm_precheckout_enable_emblems_sample']) ? 'yes' : 'no';
        update_post_meta($post_id, '_cm_precheckout_enable_emblems_sample', $enable_emblems_sample);

        if (isset($_POST['_cm_precheckout_default_course'])) {
            update_post_meta($post_id, '_cm_precheckout_default_course', sanitize_text_field($_POST['_cm_precheckout_default_course']));
        }
    }

    /**
     * Save product steps via AJAX
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function save_product_steps() {
        // Verify nonce and permissions
        if (!check_ajax_referer('cm_precheckout_admin_nonce', 'nonce', false) || 
            !current_user_can('edit_products')) {
            wp_send_json_error(array(
                'message' => esc_html__('Permissão negada.', 'cm-precheckout')
            ));
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $steps = isset($_POST['steps']) ? array_map('sanitize_text_field', $_POST['steps']) : array();

        if ($product_id && !empty($steps)) {
            update_post_meta($product_id, '_cm_precheckout_steps', $steps);
            
            wp_send_json_success(array(
                'message' => esc_html__('Etapas salvas com sucesso!', 'cm-precheckout')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Dados inválidos.', 'cm-precheckout')
            ));
        }
    }
}