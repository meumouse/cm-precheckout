<?php

namespace MeuMouse\Cm_Precheckout\Views\Frontend;

use MeuMouse\Cm_Precheckout\Admin\Options_Library;
use MeuMouse\Cm_Precheckout\Helpers\Utils;

/**
 * Shortcode for Pre-checkout Button
 * 
 * @since 1.0.0
 * @version 1.1.0
 * @package MeuMouse.com
 */
class Shortcodes {
    
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
        
        // Register shortcode
        add_shortcode('cm_pre_checkout', array($this, 'render_shortcode'));

        // Ajax handlers
        add_action('wp_ajax_cm_precheckout_get_product_data', array($this, 'get_product_data'));
        add_action('wp_ajax_nopriv_cm_precheckout_get_product_data', array($this, 'get_product_data'));
        
        add_action('wp_ajax_cm_precheckout_process_step', array($this, 'process_step'));
        add_action('wp_ajax_nopriv_cm_precheckout_process_step', array($this, 'process_step'));
    }

    /**
     * Render shortcode
     * 
     * @since 1.0.0
     * @version 1.1.0
     * @param array $atts
     * @return string
     */
    public function render_shortcode($atts) {
        global $product;
        
        // Get product from attributes if not global
        if (!$product && isset($atts['product_id'])) {
            $product = wc_get_product($atts['product_id']);
        }
        
        if (!$product) {
            return '';
        }

        $product_id = $product->get_id();
        
        // Check if precheckout is active for this product
        $precheckout_active = get_post_meta($product_id, '_cm_precheckout_active', true);
        
        // If precheckout is not active, show default WooCommerce add to cart
        if (!$precheckout_active || $precheckout_active !== 'yes') {
            ob_start();
            woocommerce_template_single_add_to_cart();
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div class="cm-precheckout-wrapper">
            <button type="button" 
                    class="button cm-precheckout-button" 
                    data-product-id="<?php echo esc_attr($product_id); ?>">
                <?php esc_html_e('Personalizar anel', 'cm-precheckout'); ?>
            </button>
            
            <!-- Fallback to default add to cart for mobile/JS disabled -->
            <div class="cm-precheckout-fallback" style="display: none;">
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </div>
        <?php
        
        // Add modal HTML
        $this->render_modal($product_id);
        
        return ob_get_clean();
    }

    /**
     * Render modal with dynamic steps
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param int $product_id
     * @return void
     */
    private function render_modal($product_id) {
        // Get product steps
        $product_steps = get_post_meta($product_id, '_cm_precheckout_steps', true);
        $product_steps = is_array($product_steps) ? $product_steps : array();
        
        // Get all library options
        $library_options = $this->options_library->get_library_options();
        ?>
        <div id="cm-precheckout-modal" class="cm-precheckout-modal" style="display: none;">
            <div class="cm-precheckout-modal-overlay"></div>
            <div class="cm-precheckout-modal-content">
                <button type="button" class="cm-precheckout-close">&times;</button>
                
                <div class="cm-precheckout-steps-progress">
                    <!-- Progress bar will be populated by JavaScript -->
                </div>
                
                <div class="cm-precheckout-steps">
                    <?php
                    // Render steps based on product configuration
                    $step_count = 0;
                    foreach ($product_steps as $step_key) {
                        if (isset($library_options[$step_key])) {
                            $step_count++;
                            $option = $library_options[$step_key];
                            $this->render_step_content($option, $step_count, $product_id);
                        }
                    }
                    
                    // Always add summary step at the end
                    if (isset($library_options['summary'])) {
                        $this->render_step_content($library_options['summary'], $step_count + 1, $product_id);
                    }
                    ?>
                </div>
                
                <div class="cm-precheckout-navigation">
                    <button type="button" class="button prev-step" style="display: none;">
                        <?php esc_html_e('Voltar', 'cm-precheckout'); ?>
                    </button>
                    <button type="button" class="button next-step">
                        <?php esc_html_e('Próximo', 'cm-precheckout'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render step content
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param array $option
     * @param int $step_number
     * @param int $product_id
     * @return void
     */
    private function render_step_content($option, $step_number, $product_id) {
        $config = isset($option['config']) ? $option['config'] : array();
        $required = isset($config['required']) ? $config['required'] : false;
        $display_name = isset($config['display_name']) ? $config['display_name'] : $option['name'];
        $additional_message = isset($config['additional_message']) ? $config['additional_message'] : '';
        ?>
        <div class="cm-precheckout-step step-<?php echo $step_number; ?> step-<?php echo esc_attr($option['key']); ?>"
             data-step="<?php echo $step_number; ?>"
             data-key="<?php echo esc_attr($option['key']); ?>"
             data-required="<?php echo $required ? 'true' : 'false'; ?>"
             <?php echo $step_number === 1 ? 'active' : ''; ?>>
            
            <div class="step-header">
                <h3>
                    <span class="step-number"><?php echo $step_number; ?></span>
                    <?php echo esc_html($display_name); ?>
                    <?php if ($additional_message): ?>
                        <span class="step-tooltip" title="<?php echo esc_attr($additional_message); ?>">
                            <span class="dashicons dashicons-info"></span>
                        </span>
                    <?php endif; ?>
                </h3>
            </div>
            
            <div class="step-content">
                <?php
                switch ($option['key']) {
                    case 'material_selection':
                        $this->render_material_selection($product_id);
                        break;
                        
                    case 'size_selection':
                        $this->render_size_selection($product_id);
                        break;
                        
                    case 'personalization':
                        $this->render_personalization($product_id);
                        break;
                        
                    case 'file_upload':
                        $this->render_file_upload($config);
                        break;
                        
                    case 'summary':
                        $this->render_summary();
                        break;
                        
                    default:
                        // Allow custom steps via filter
                        echo apply_filters('cm_precheckout_render_step_' . $option['key'], '', $product_id, $config);
                        break;
                }
                ?>
            </div>
            
            <?php if ($option['key'] !== 'summary'): ?>
                <div class="step-validation" style="display: none;">
                    <p class="validation-message error"></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render material selection step
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param int $product_id
     * @return void
     */
    private function render_material_selection($product_id) {
        $materials = Utils::get_product_materials($product_id);
        $materials_config = get_post_meta($product_id, '_cm_precheckout_materials_config', true);
        $materials_config = is_array($materials_config) ? $materials_config : array();
        
        // Get product variations if product is variable
        $product = wc_get_product($product_id);
        $variations = $product->is_type('variable') ? $product->get_available_variations() : array();
        ?>
        <div class="material-options">
            <?php if (empty($materials)): ?>
                <p class="no-materials"><?php esc_html_e('Nenhum material disponível para este produto.', 'cm-precheckout'); ?></p>
            <?php else: ?>
                <?php foreach ($materials as $material_key): 
                    $material_label = Utils::get_material_label($material_key);
                    $material_config = isset($materials_config[$material_key]) ? $materials_config[$material_key] : array();
                    $variation_id = isset($material_config['variation_id']) ? $material_config['variation_id'] : '';
                    ?>
                    <div class="material-option" data-material="<?php echo esc_attr($material_key); ?>">
                        <input type="radio" 
                               id="material_<?php echo esc_attr($material_key); ?>" 
                               name="material" 
                               value="<?php echo esc_attr($material_key); ?>"
                               data-variation-id="<?php echo esc_attr($variation_id); ?>">
                        <label for="material_<?php echo esc_attr($material_key); ?>">
                            <?php echo esc_html($material_label); ?>
                            <?php if ($variation_id && $product->is_type('variable')): 
                                $variation = wc_get_product($variation_id);
                                if ($variation): ?>
                                    <span class="material-price"><?php echo $variation->get_price_html(); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="quantity-selector">
            <label><?php esc_html_e('Quantidade', 'cm-precheckout'); ?></label>
            <div class="quantity-input">
                <button type="button" class="quantity-minus">-</button>
                <input type="number" 
                       name="quantity" 
                       value="1" 
                       min="1" 
                       max="99" 
                       class="cm-quantity" />
                <button type="button" class="quantity-plus">+</button>
            </div>
        </div>
        <?php
    }

    /**
     * Render size selection step
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param int $product_id
     * @return void
     */
    private function render_size_selection($product_id) {
        $size_selectors = get_post_meta($product_id, '_cm_precheckout_size_selectors', true);
        $size_selectors = $size_selectors ? absint($size_selectors) : 1;
        $ring_sizes = Utils::get_ring_sizes();
        ?>
        <div class="size-selectors">
            <?php for ($i = 1; $i <= $size_selectors; $i++): ?>
                <div class="size-selector-group">
                    <label for="size_<?php echo $i; ?>">
                        <?php echo sprintf(esc_html__('Tamanho %d', 'cm-precheckout'), $i); ?>
                    </label>
                    <select id="size_<?php echo $i; ?>" name="sizes[]" class="size-select">
                        <option value=""><?php esc_html_e('Selecione o tamanho', 'cm-precheckout'); ?></option>
                        <?php foreach ($ring_sizes as $size => $label): ?>
                            <option value="<?php echo esc_attr($size); ?>">
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endfor; ?>
        </div>
        
        <p class="size-help">
            <a href="#size-guide" class="show-size-guide">
                <?php esc_html_e('Como medir seu dedo?', 'cm-precheckout'); ?>
            </a>
        </p>
        
        <div id="size-guide" class="size-guide" style="display: none;">
            <!-- Size guide content -->
            <h4><?php esc_html_e('Guia de Tamanhos', 'cm-precheckout'); ?></h4>
            <p><?php esc_html_e('Instruções para medir o dedo...', 'cm-precheckout'); ?></p>
        </div>
        <?php
    }

    /**
     * Render personalization step
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param int $product_id
     * @return void
     */
    private function render_personalization($product_id) {
        $enable_name_engraving = get_post_meta($product_id, '_cm_precheckout_enable_name_engraving', true) === 'yes';
        $name_fields = get_post_meta($product_id, '_cm_precheckout_name_fields', true);
        $name_fields = $name_fields ? absint($name_fields) : 1;
        
        $enable_course_change = get_post_meta($product_id, '_cm_precheckout_enable_course_change', true) === 'yes';
        $enable_stone_sample = get_post_meta($product_id, '_cm_precheckout_enable_stone_sample', true) === 'yes';
        $enable_emblems_sample = get_post_meta($product_id, '_cm_precheckout_enable_emblems_sample', true) === 'yes';
        $default_course = get_post_meta($product_id, '_cm_precheckout_default_course', true);
        ?>
        <div class="personalization-options">
            <?php if ($enable_name_engraving): ?>
                <div class="name-engraving-section">
                    <h4><?php esc_html_e('Gravação de Nomes', 'cm-precheckout'); ?></h4>
                    <?php for ($i = 1; $i <= $name_fields; $i++): ?>
                        <div class="name-field">
                            <label for="name_<?php echo $i; ?>">
                                <?php echo sprintf(esc_html__('Nome %d', 'cm-precheckout'), $i); ?>
                            </label>
                            <input type="text" 
                                   id="name_<?php echo $i; ?>" 
                                   name="names[]" 
                                   class="name-input" 
                                   placeholder="<?php esc_attr_e('Digite o nome', 'cm-precheckout'); ?>">
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($enable_course_change || $enable_stone_sample || $enable_emblems_sample): ?>
                <div class="course-stone-section">
                    <?php if ($enable_course_change): ?>
                        <div class="course-selector">
                            <h4><?php esc_html_e('Curso', 'cm-precheckout'); ?></h4>
                            <select name="course" class="course-select">
                                <option value=""><?php esc_html_e('Selecione o curso', 'cm-precheckout'); ?></option>
                                <!-- Will be populated via JavaScript -->
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($enable_stone_sample): ?>
                        <div class="stone-selector">
                            <h4><?php esc_html_e('Cor da Pedra', 'cm-precheckout'); ?></h4>
                            <div class="stone-options">
                                <!-- Will be populated via JavaScript -->
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($enable_emblems_sample): ?>
                        <div class="emblems-preview">
                            <h4><?php esc_html_e('Emblemas', 'cm-precheckout'); ?></h4>
                            <div class="emblems-container">
                                <!-- Will be populated via JavaScript -->
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="additional-notes">
                <label for="notes"><?php esc_html_e('Observações Adicionais', 'cm-precheckout'); ?></label>
                <textarea id="notes" name="notes" rows="3" 
                          placeholder="<?php esc_attr_e('Alguma observação especial para seu pedido?', 'cm-precheckout'); ?>"></textarea>
            </div>
        </div>
        <?php
    }

    /**
     * Render file upload step
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param array $config
     * @return void
     */
    private function render_file_upload($config) {
        $allowed_types = isset($config['allowed_types']) ? $config['allowed_types'] : 'jpg,jpeg,png,pdf,doc,docx';
        $max_size = isset($config['max_size']) ? absint($config['max_size']) : 5;
        ?>
        <div class="file-upload-section">
            <div class="file-upload-area">
                <input type="file" 
                       id="customization_file" 
                       name="customization_file" 
                       accept="<?php echo esc_attr(str_replace(',', ',.', $allowed_types)); ?>"
                       data-max-size="<?php echo $max_size; ?>">
                <label for="customization_file" class="file-upload-label">
                    <span class="dashicons dashicons-upload"></span>
                    <span class="upload-text">
                        <?php esc_html_e('Clique para enviar um arquivo', 'cm-precheckout'); ?>
                    </span>
                    <span class="file-size-info">
                        <?php echo sprintf(esc_html__('Máx: %dMB | Tipos: %s', 'cm-precheckout'), $max_size, $allowed_types); ?>
                    </span>
                </label>
                <div class="file-preview" style="display: none;">
                    <span class="file-name"></span>
                    <button type="button" class="remove-file"><?php esc_html_e('Remover', 'cm-precheckout'); ?></button>
                </div>
            </div>
            
            <p class="file-description">
                <?php esc_html_e('Envie arquivos como logos, imagens de referência ou documentos para personalização.', 'cm-precheckout'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render summary step
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    private function render_summary() {
        ?>
        <div class="order-summary">
            <div class="summary-header">
                <h4><?php esc_html_e('Resumo do Seu Pedido', 'cm-precheckout'); ?></h4>
            </div>
            
            <div class="summary-items">
                <div class="summary-item product-summary">
                    <span class="label"><?php esc_html_e('Produto:', 'cm-precheckout'); ?></span>
                    <span class="value product-name"></span>
                </div>
                <div class="summary-item material-summary">
                    <span class="label"><?php esc_html_e('Material:', 'cm-precheckout'); ?></span>
                    <span class="value"></span>
                </div>
                <div class="summary-item quantity-summary">
                    <span class="label"><?php esc_html_e('Quantidade:', 'cm-precheckout'); ?></span>
                    <span class="value"></span>
                </div>
                <div class="summary-item sizes-summary">
                    <span class="label"><?php esc_html_e('Tamanhos:', 'cm-precheckout'); ?></span>
                    <span class="value"></span>
                </div>
                <div class="summary-item names-summary">
                    <span class="label"><?php esc_html_e('Nomes:', 'cm-precheckout'); ?></span>
                    <span class="value"></span>
                </div>
                <div class="summary-item course-summary">
                    <span class="label"><?php esc_html_e('Curso:', 'cm-precheckout'); ?></span>
                    <span class="value"></span>
                </div>
                <div class="summary-item stone-summary">
                    <span class="label"><?php esc_html_e('Pedra:', 'cm-precheckout'); ?></span>
                    <span class="value"></span>
                </div>
                <div class="summary-item file-summary">
                    <span class="label"><?php esc_html_e('Arquivo:', 'cm-precheckout'); ?></span>
                    <span class="value"></span>
                </div>
                <div class="summary-item notes-summary">
                    <span class="label"><?php esc_html_e('Observações:', 'cm-precheckout'); ?></span>
                    <span class="value"></span>
                </div>
            </div>
            
            <div class="summary-totals">
                <div class="summary-item subtotal">
                    <span class="label"><?php esc_html_e('Subtotal:', 'cm-precheckout'); ?></span>
                    <span class="value product-price"></span>
                </div>
                <div class="summary-item options-total">
                    <span class="label"><?php esc_html_e('Opções:', 'cm-precheckout'); ?></span>
                    <span class="value options-price">R$ 0,00</span>
                </div>
                <div class="summary-item total">
                    <span class="label"><?php esc_html_e('Total:', 'cm-precheckout'); ?></span>
                    <span class="value total-price"></span>
                </div>
            </div>
        </div>
        
        <div class="terms-agreement">
            <label>
                <input type="checkbox" name="agree_terms" value="1">
                <?php printf(
                    esc_html__('Eu concordo com os %stermos e condições%s', 'cm-precheckout'),
                    '<a href="' . esc_url(get_permalink(get_option('woocommerce_terms_page_id'))) . '" target="_blank">',
                    '</a>'
                ); ?>
            </label>
        </div>
        <?php
    }

    /**
     * Get product data via AJAX
     * 
     * @since 1.0.0
     * @version 1.1.0
     * @return void
     */
    public function get_product_data() {
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_die();
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_die();
        }
        
        // Get product steps
        $product_steps = get_post_meta($product_id, '_cm_precheckout_steps', true);
        $product_steps = is_array($product_steps) ? $product_steps : array();
        
        // Get materials configuration
        $materials_config = get_post_meta($product_id, '_cm_precheckout_materials_config', true);
        $materials_config = is_array($materials_config) ? $materials_config : array();
        
        $data = array(
            'id' => $product_id,
            'title' => $product->get_name(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'currency' => get_woocommerce_currency_symbol(),
            'steps' => $product_steps,
            'materials_config' => $materials_config,
            'materials' => Utils::get_product_materials($product_id),
            'size_selectors' => get_post_meta($product_id, '_cm_precheckout_size_selectors', true) ?: 1,
            'enable_name_engraving' => get_post_meta($product_id, '_cm_precheckout_enable_name_engraving', true) === 'yes',
            'name_fields' => get_post_meta($product_id, '_cm_precheckout_name_fields', true) ?: 1,
            'enable_course_change' => get_post_meta($product_id, '_cm_precheckout_enable_course_change', true) === 'yes',
            'enable_stone_sample' => get_post_meta($product_id, '_cm_precheckout_enable_stone_sample', true) === 'yes',
            'enable_emblems_sample' => get_post_meta($product_id, '_cm_precheckout_enable_emblems_sample', true) === 'yes',
            'default_course' => get_post_meta($product_id, '_cm_precheckout_default_course', true),
            'product_type' => $product->get_type(),
            'is_virtual' => $product->is_virtual(),
            'is_downloadable' => $product->is_downloadable(),
        );
        
        wp_send_json_success($data);
    }

    /**
     * Process step via AJAX
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function process_step() {
        // Verify nonce
        if (!check_ajax_referer('cm_precheckout_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => esc_html__('Erro de segurança. Por favor, atualize a página.', 'cm-precheckout')
            ));
        }

        $step = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '';
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $data = isset($_POST['data']) ? $_POST['data'] : array();

        switch ($step) {
            case 'validate_material':
                $this->validate_material_selection($product_id, $data);
                break;
                
            case 'validate_size':
                $this->validate_size_selection($product_id, $data);
                break;
                
            case 'validate_file':
                $this->validate_file_upload($data);
                break;
                
            case 'calculate_total':
                $this->calculate_order_total($product_id, $data);
                break;
                
            default:
                // Allow custom step processing via filter
                $result = apply_filters('cm_precheckout_process_step_' . $step, array(), $product_id, $data);
                if (!empty($result)) {
                    wp_send_json_success($result);
                } else {
                    wp_send_json_error(array(
                        'message' => esc_html__('Ação não suportada.', 'cm-precheckout')
                    ));
                }
                break;
        }
    }

    /**
     * Validate material selection
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param int $product_id
     * @param array $data
     * @return void
     */
    private function validate_material_selection($product_id, $data) {
        if (empty($data['material'])) {
            wp_send_json_error(array(
                'message' => esc_html__('Por favor, selecione um material.', 'cm-precheckout')
            ));
        }

        $materials = Utils::get_product_materials($product_id);
        
        if (!in_array($data['material'], $materials)) {
            wp_send_json_error(array(
                'message' => esc_html__('Material selecionado não está disponível.', 'cm-precheckout')
            ));
        }

        wp_send_json_success(array(
            'message' => esc_html__('Material selecionado com sucesso!', 'cm-precheckout'),
            'material_label' => Utils::get_material_label($data['material'])
        ));
    }

    /**
     * Validate size selection
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param int $product_id
     * @param array $data
     * @return void
     */
    private function validate_size_selection($product_id, $data) {
        if (empty($data['sizes']) || !is_array($data['sizes'])) {
            wp_send_json_error(array(
                'message' => esc_html__('Por favor, selecione pelo menos um tamanho.', 'cm-precheckout')
            ));
        }

        $size_selectors = get_post_meta($product_id, '_cm_precheckout_size_selectors', true) ?: 1;
        $ring_sizes = Utils::get_ring_sizes();

        // Validate each size
        foreach ($data['sizes'] as $index => $size) {
            if ($index >= $size_selectors) break;
            
            if (empty($size)) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        esc_html__('Por favor, selecione o tamanho %d.', 'cm-precheckout'),
                        $index + 1
                    )
                ));
            }

            if (!isset($ring_sizes[$size])) {
                wp_send_json_error(array(
                    'message' => esc_html__('Tamanho selecionado é inválido.', 'cm-precheckout')
                ));
            }
        }

        wp_send_json_success(array(
            'message' => esc_html__('Tamanhos selecionados com sucesso!', 'cm-precheckout')
        ));
    }

    /**
     * Validate file upload
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param array $data
     * @return void
     */
    private function validate_file_upload($data) {
        // This would be called after actual file upload
        // For now, just validate the data structure
        if (isset($data['file_name']) && !empty($data['file_name'])) {
            wp_send_json_success(array(
                'message' => esc_html__('Arquivo enviado com sucesso!', 'cm-precheckout')
            ));
        } else {
            wp_send_json_success(array(
                'message' => esc_html__('Nenhum arquivo enviado.', 'cm-precheckout'),
                'skipped' => true
            ));
        }
    }

    /**
     * Calculate order total
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param int $product_id
     * @param array $data
     * @return void
     */
    private function calculate_order_total($product_id, $data) {
        $product = wc_get_product($product_id);
        $base_price = $product->get_price();
        $options_total = 0;

        // Calculate material price
        if (!empty($data['material'])) {
            $material_price = Utils::get_material_price($product_id, $data['material']);
            $options_total += $material_price;
        }

        // Calculate engraving price
        if (!empty($data['names']) && is_array($data['names'])) {
            $engraving_price = get_post_meta($product_id, '_cm_precheckout_engraving_price', true);
            if ($engraving_price) {
                $filled_names = count(array_filter($data['names']));
                $options_total += floatval($engraving_price) * $filled_names;
            }
        }

        // Calculate stone price
        if (!empty($data['stone'])) {
            $stone_prices = get_post_meta($product_id, '_cm_precheckout_stone_prices', true);
            if (is_array($stone_prices) && isset($stone_prices[$data['stone']])) {
                $options_total += floatval($stone_prices[$data['stone']]);
            }
        }

        $quantity = isset($data['quantity']) ? max(1, intval($data['quantity'])) : 1;
        $subtotal = $base_price * $quantity;
        $total = $subtotal + ($options_total * $quantity);

        wp_send_json_success(array(
            'base_price' => wc_price($base_price),
            'subtotal' => wc_price($subtotal),
            'options_total' => wc_price($options_total * $quantity),
            'total' => wc_price($total),
            'raw' => array(
                'base_price' => $base_price,
                'subtotal' => $subtotal,
                'options_total' => $options_total * $quantity,
                'total' => $total,
            )
        ));
    }
}