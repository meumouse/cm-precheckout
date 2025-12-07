<?php

namespace MeuMouse\Cm_Precheckout\Admin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Product Tab for Pre-checkout Settings
 * 
 * @since 1.0.0
 * @package MeuMouse.com
 */
class Product_Tab {
    
    /**
     * Options library instance
     * 
     * @since 1.0.0
     * @var Options_Library
     */
    private $options_library;

    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->options_library = new Options_Library();
        
        // Add custom tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_tab'), 99, 1);

        // Add tab content
        add_action('woocommerce_product_data_panels', array($this, 'add_tab_content'));

        // Save tab data
        add_action('woocommerce_process_product_meta', array($this, 'save_tab_data'), 10, 2);
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
     * @return void
     */
    public function add_tab_content() {
        global $post;
        
        $product = wc_get_product($post->ID);
        $precheckout_active = get_post_meta($post->ID, '_cm_precheckout_active', true);
        $library_options = $this->options_library->get_enabled_options();
        $library_options = is_array($library_options) ? $library_options : array();

        $product_steps_data = get_post_meta($post->ID, '_cm_precheckout_steps_data', true);
        $product_steps_data = is_array($product_steps_data) ? $product_steps_data : $this->build_default_steps_data($post->ID, $library_options);
        
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
                        <?php if ( ! empty( $product_steps_data ) ) :
                            foreach ( $product_steps_data as $step ) {
                                $this->render_step_container( $step, $library_options );
                            }
                        endif; ?>
                    </div>
                    
                    <input type="hidden" id="product_steps_data" name="_cm_precheckout_steps_data" value="<?php echo wp_json_encode( $product_steps_data ); ?>">
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
                <!-- Add Step Modal -->
                <div id="cm-precheckout-step-modal" class="cm-modal" style="display: none;">
                    <div class="cm-modal-overlay"></div>
                    <div class="cm-modal-content">
                        <div class="cm-modal-header">
                            <h3><?php esc_html_e('Adicionar nova etapa', 'cm-precheckout'); ?></h3>
                            <button type="button" class="cm-modal-close">&times;</button>
                        </div>
                        <div class="cm-modal-body">
                            <p>
                                <label for="cm-precheckout-step-name"><?php esc_html_e('Nome da etapa', 'cm-precheckout'); ?></label>
                                <input type="text" id="cm-precheckout-step-name" class="widefat" placeholder="<?php esc_attr_e('Ex: Escolha do material', 'cm-precheckout'); ?>">
                            </p>
                            <p>
                                <label for="cm-precheckout-step-icon"><?php esc_html_e('Classe do ícone (opcional)', 'cm-precheckout'); ?></label>
                                <input type="text" id="cm-precheckout-step-icon" class="widefat" placeholder="dashicons-admin-generic">
                                <span class="description"><?php esc_html_e('Use uma classe Dashicon para exibir um ícone.', 'cm-precheckout'); ?></span>
                            </p>
                        </div>
                        <div class="cm-modal-footer">
                            <button type="button" class="button button-secondary cm-modal-close"><?php esc_html_e('Cancelar', 'cm-precheckout'); ?></button>
                            <button type="button" class="button button-primary" id="cm-precheckout-save-step"><?php esc_html_e('Adicionar etapa', 'cm-precheckout'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Action Modal -->
                <div id="cm-precheckout-action-modal" class="cm-modal" style="display: none;">
                    <div class="cm-modal-overlay"></div>
                    <div class="cm-modal-content">
                        <div class="cm-modal-header">
                            <h3 id="cm-precheckout-action-modal-title"><?php esc_html_e('Configurar ação', 'cm-precheckout'); ?></h3>
                            <button type="button" class="cm-modal-close">&times;</button>
                        </div>
                        <div class="cm-modal-body">
                            <p>
                                <label for="cm-precheckout-action-key"><?php esc_html_e('Opção da biblioteca', 'cm-precheckout'); ?></label>
                                <select id="cm-precheckout-action-key" class="widefat"></select>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" id="cm-precheckout-action-required">
                                    <?php esc_html_e('Obrigatório', 'cm-precheckout'); ?>
                                </label>
                            </p>
                            <p>
                                <label for="cm-precheckout-action-display-name"><?php esc_html_e('Nome de exibição', 'cm-precheckout'); ?></label>
                                <input type="text" id="cm-precheckout-action-display-name" class="widefat">
                            </p>
                            <p>
                                <label for="cm-precheckout-action-message"><?php esc_html_e('Mensagem adicional', 'cm-precheckout'); ?></label>
                                <textarea id="cm-precheckout-action-message" class="widefat" rows="3"></textarea>
                                <span class="description"><?php esc_html_e('Esta mensagem será exibida como tooltip no frontend.', 'cm-precheckout'); ?></span>
                            </p>
                        </div>
                        <div class="cm-modal-footer">
                            <button type="button" class="button button-secondary cm-modal-close"><?php esc_html_e('Cancelar', 'cm-precheckout'); ?></button>
                            <button type="button" class="button button-primary" id="cm-precheckout-save-action"><?php esc_html_e('Salvar ação', 'cm-precheckout'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    

    /**
     * Save tab data
     * 
     * @since 1.0.0
     * @param int $post_id | Post ID
     * @param object $post
     * @return void
     */
    public function save_tab_data($post_id, $post) {
        // Save active status
        $active = isset($_POST['_cm_precheckout_active']) ? 'yes' : 'no';
        update_post_meta($post_id, '_cm_precheckout_active', $active);

        // Save steps data
        if (isset($_POST['_cm_precheckout_steps_data'])) {
            $decoded_steps = json_decode(stripslashes($_POST['_cm_precheckout_steps_data']), true);
            $sanitized_steps = array();
            $steps_order = array();

            if (is_array($decoded_steps)) {
                foreach ($decoded_steps as $step) {
                    $step_id = sanitize_key($step['id'] ?? '');
                    $step_name = sanitize_text_field($step['name'] ?? '');
                    $step_icon = sanitize_text_field($step['icon'] ?? '');

                    if (empty($step_id) || empty($step_name)) {
                        continue;
                    }

                    $actions = array();
                    if (!empty($step['actions']) && is_array($step['actions'])) {
                        foreach ($step['actions'] as $action) {
                            $action_key = sanitize_text_field($action['key'] ?? '');

                            if (empty($action_key)) {
                                continue;
                            }

                            $actions[] = array(
                                'key' => $action_key,
                                'required' => !empty($action['required']),
                                'display_name' => sanitize_text_field($action['display_name'] ?? ''),
                                'additional_message' => sanitize_textarea_field($action['additional_message'] ?? ''),
                            );

                            $steps_order[] = $action_key;
                        }
                    }

                    $sanitized_steps[] = array(
                        'id' => $step_id,
                        'name' => $step_name,
                        'icon' => $step_icon,
                        'actions' => $actions,
                    );
                }
            }

            update_post_meta($post_id, '_cm_precheckout_steps_data', $sanitized_steps);
            update_post_meta($post_id, '_cm_precheckout_steps', array_values(array_unique($steps_order)));
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

    /**
     * Build default steps data based on legacy configuration
     *
     * @since 1.0.0
     * @param int   $product_id   Product ID.
     * @param array $library_options Library options.
     * @return array
     */
    private function build_default_steps_data($product_id, $library_options) {
        $stored_steps = get_post_meta($product_id, '_cm_precheckout_steps', true);
        $stored_steps = is_array($stored_steps) ? $stored_steps : array();

        if (empty($stored_steps)) {
            $stored_steps = array_keys($library_options);
        }

        $steps_data = array();

        foreach ($stored_steps as $index => $step_key) {
            if (!isset($library_options[$step_key])) {
                continue;
            }

            $option = $library_options[$step_key];
            $steps_data[] = array(
                'id' => 'step_' . ($index + 1),
                'name' => $option['name'],
                'icon' => $option['icon'],
                'actions' => array(
                    array(
                        'key' => $option['key'],
                        'required' => !empty($option['config']['required']),
                        'display_name' => $option['config']['display_name'] ?? $option['name'],
                        'additional_message' => $option['config']['additional_message'] ?? '',
                    ),
                ),
            );
        }

        return $steps_data;
    }


    /**
     * Render step container
     *
     * @since 1.0.0
     * @param array $step Step data.
     * @param array $library_options Options library list.
     * @return void
     */
    private function render_step_container($step, $library_options) {
        $step_id = isset($step['id']) ? $step['id'] : uniqid('step_');
        $step_name = isset($step['name']) ? $step['name'] : '';
        $step_icon = isset($step['icon']) ? $step['icon'] : '';
        $actions = isset($step['actions']) && is_array($step['actions']) ? $step['actions'] : array(); ?>

        <div class="cm-precheckout-step" data-step-id="<?php echo esc_attr($step_id); ?>" data-step-name="<?php echo esc_attr($step_name); ?>" data-step-icon="<?php echo esc_attr($step_icon); ?>">
            <div class="cm-precheckout-step__header">
                <span class="dashicons dashicons-move cm-precheckout-step__handle"></span>
                <div class="cm-precheckout-step__title">
                    <?php if (!empty($step_icon)): ?>
                        <span class="dashicons <?php echo esc_attr($step_icon); ?> cm-precheckout-step__icon"></span>
                    <?php endif; ?>
                    <strong class="cm-precheckout-step__name"><?php echo esc_html($step_name); ?></strong>
                </div>
                <div class="cm-precheckout-step__actions">
                    <button type="button" class="button button-secondary cm-precheckout-add-action"><?php esc_html_e('Adicionar ação', 'cm-precheckout'); ?></button>
                    <button type="button" class="button button-link-delete cm-precheckout-remove-step"><?php esc_html_e('Remover etapa', 'cm-precheckout'); ?></button>
                </div>
            </div>
            <div class="cm-precheckout-step__body">
                <div class="cm-precheckout-step__actions-list">
                    <?php
                    if (!empty($actions)) {
                        foreach ($actions as $action) {
                            $this->render_step_action($action, $library_options);
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * Render action item
     *
     * @since 1.0.0
     * @param array $action Action data.
     * @param array $library_options Options library list.
     * @return void
     */
    private function render_step_action($action, $library_options) {
        $action_key = isset($action['key']) ? $action['key'] : '';

        if (empty($action_key) || !isset($library_options[$action_key])) {
            return;
        }

        $library_option = $library_options[$action_key];
        $display_name = $action['display_name'] ?? $library_option['name'];
        $is_required = !empty($action['required']);
        $additional_message = $action['additional_message'] ?? '';
        ?>
        <div class="cm-precheckout-action" data-action-key="<?php echo esc_attr($action_key); ?>" data-action-required="<?php echo esc_attr($is_required ? '1' : '0'); ?>" data-action-display-name="<?php echo esc_attr($display_name); ?>" data-action-message="<?php echo esc_attr($additional_message); ?>">
            <div class="cm-precheckout-action__info">
                <span class="dashicons <?php echo esc_attr($library_option['icon']); ?>"></span>
                <div>
                    <p class="cm-precheckout-action__name"><?php echo esc_html($display_name); ?></p>
                    <p class="cm-precheckout-action__meta">
                        <?php
                        echo esc_html($library_option['name']);
                        echo ' · ';
                        echo $is_required ? esc_html__('Obrigatório', 'cm-precheckout') : esc_html__('Opcional', 'cm-precheckout');
                        ?>
                    </p>
                </div>
            </div>
            <div class="cm-precheckout-action__controls">
                <?php if ('material_selection' === $action_key) : ?>
                    <button type="button" class="button configure-materials"><?php esc_html_e('Configurar', 'cm-precheckout'); ?></button>
                <?php endif; ?>
                <button type="button" class="button button-secondary cm-precheckout-edit-action"><?php esc_html_e('Editar ações', 'cm-precheckout'); ?></button>
                <button type="button" class="button cm-precheckout-remove-action"><?php esc_html_e('Remover', 'cm-precheckout'); ?></button>
            </div>
        </div>
        <?php
    }
}