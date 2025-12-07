<?php

namespace MeuMouse\Cm_Precheckout\Core;

use MeuMouse\Cm_Precheckout\Helpers\Utils;
use MeuMouse\Cm_Precheckout\Admin\Options_Library;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * AJAX Handlers
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Ajax {

    /**
     * Constructor
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize AJAX hooks
     * 
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {
        // Frontend AJAX
        $frontend_actions = array(
            'get_product_data',
            'get_courses',
            'get_stones',
            'add_to_cart',
        );
        
        foreach ( $frontend_actions as $action ) {
            add_action( 'wp_ajax_cm_precheckout_' . $action, array( $this, $action ) );
            add_action( 'wp_ajax_nopriv_cm_precheckout_' . $action, array( $this, $action ) );
        }
        
        // Admin AJAX (require authentication)
        $admin_actions = array(
            'save_course_order',
            'save_stone_order',
            'upload_image',
            'delete_image',
            'save_product_steps',
            'get_step_template',
            'get_materials_config',
            'save_materials_config',
            'set_default_options',
            'toggle_option',
            'create_step_container',
            'get_step_action',
        );
        
        foreach ( $admin_actions as $action ) {
            add_action( 'wp_ajax_cm_precheckout_' . $action, array( $this, $action ) );
        }
    }


    /**
     * Verify AJAX request
     * 
     * @since 1.0.0
     * @param string $context 'frontend' or 'admin'
     * @param string $capability Capability required for admin context.
     * @return bool
     */
    private function verify_request( $context = 'frontend', $capability = 'manage_options' ) {
        $nonce_action = $context === 'admin' ? 'cm_precheckout_admin_nonce' : 'cm_precheckout_nonce';
        
        if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'Erro de segurança. Por favor, atualize a página.', 'cm-precheckout' ) 
            ));

            return false;
        }
        
        if ( $context === 'admin' && ! current_user_can(  $capability ) ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'Permissão negada.', 'cm-precheckout' ) 
            ));

            return false;
        }
        
        return true;
    }


    /**
     * Get product data for pre-checkout
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function get_product_data() {
        $this->verify_request( 'frontend' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        
        if ( ! $product_id ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'ID do produto não fornecido.', 'cm-precheckout' ) 
            ));
        }

        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'Produto não encontrado.', 'cm-precheckout' ) 
            ));
        }

        // Check if pre-checkout is enabled
        if ( ! Utils::is_precheckout_enabled( $product_id ) ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'Pré-checkout não está ativo para este produto.', 'cm-precheckout' ) 
            ));
        }

        // Format materials using Utils
        $materials = Utils::get_product_materials( $product_id );
        $formatted_materials = array();
        
        foreach ( $materials as $material ) {
            $formatted_materials[ $material ] = array(
                'id' => $material,
                'name' => Utils::get_material_label( $material ),
                'price' => Utils::get_material_price( $product_id, $material ),
            );
        }

        // Get personalization settings using Utils
        $personalization = Utils::get_product_personalization( $product_id );

        // Prepare response
        $response = array(
            'success' => true,
            'data' => array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'currency' => get_woocommerce_currency_symbol(),
                'currency_code' => get_woocommerce_currency(),
                'materials' => $formatted_materials,
                'size_selectors' => $personalization['size_selectors'],
                'ring_sizes' => Utils::get_ring_sizes(),
                'personalization' => $personalization,
                'product_type' => $product->get_type(),
                'stock_status' => $product->get_stock_status(),
                'max_purchase' => $product->get_max_purchase_quantity(),
                'min_purchase' => $product->get_min_purchase_quantity(),
            ),
        );

        wp_send_json_success( $response );
    }


    /**
     * Get courses list
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function get_courses() {
        $this->verify_request( 'frontend' );

        $courses = Utils::get_courses();
        $formatted_courses = array();
        
        foreach ( $courses as $index => $course ) {
            $formatted_courses[] = array(
                'id' => $index,
                'name' => sanitize_text_field( $course['name'] ),
                'default_stone_color' => isset( $course['default_stone_color'] ) ? 
                    sanitize_hex_color( $course['default_stone_color'] ) : '#000000',
                'emblem_1' => array(
                    'id' => isset( $course['emblem_1'] ) ? absint( $course['emblem_1'] ) : 0,
                    'url' => isset( $course['emblem_1'] ) ? wp_get_attachment_url( $course['emblem_1'] ) : '',
                ),
                'emblem_2' => array(
                    'id' => isset( $course['emblem_2'] ) ? absint( $course['emblem_2'] ) : 0,
                    'url' => isset( $course['emblem_2'] ) ? wp_get_attachment_url( $course['emblem_2'] ) : '',
                ),
            );
        }

        wp_send_json_success( array(
            'courses' => $formatted_courses,
            'count' => count( $formatted_courses ),
        ));
    }


    /**
     * Get stones list
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function get_stones() {
        $this->verify_request( 'frontend' );

        $stones = Utils::get_stones();
        $formatted_stones = array();
        
        foreach ( $stones as $index => $stone ) {
            $formatted_stones[] = array(
                'id' => $index,
                'name' => sanitize_text_field( $stone['name'] ),
                'icon_url' => isset( $stone['icon_url'] ) ? esc_url_raw( $stone['icon_url'] ) : '',
                'color' => isset( $stone['color'] ) ? sanitize_hex_color( $stone['color'] ) : '#cccccc',
            );
        }

        wp_send_json_success( array(
            'stones' => $formatted_stones,
            'count' => count( $formatted_stones ),
        ));
    }


    /**
     * Add customized product to cart
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function add_to_cart() {
        $this->verify_request( 'frontend' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
        
        if ( ! $product_id ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'ID do produto não fornecido.', 'cm-precheckout' ) 
            ));
        }

        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'Produto não encontrado.', 'cm-precheckout' ) 
            ));
        }

        // Validate quantity
        $this->validate_quantity( $product, $quantity );

        // Sanitize customization data using Utils
        $customization = Utils::sanitize_customization_data( $_POST );

        // Calculate additional price
        $additional_price = $this->calculate_additional_price( $product_id, $customization );
        
        // Prepare cart item data
        $cart_item_data = array(
            'cm_precheckout_customization' => $customization,
            'cm_precheckout_additional_price' => $additional_price,
        );

        try {
            $cart_item_key = WC()->cart->add_to_cart(
                $product_id,
                $quantity,
                0, // variation_id
                array(), // variation
                $cart_item_data
            );

            if ( $cart_item_key ) {
                $this->update_cart_item_price( $cart_item_key, $additional_price );
                
                do_action( 'cm_precheckout_after_add_to_cart', $cart_item_key, $product_id, $quantity, $customization );
                
                wp_send_json_success( array(
                    'message' => esc_html__( 'Produto adicionado ao carrinho com sucesso!', 'cm-precheckout' ),
                    'cart_item_key' => $cart_item_key,
                    'cart_url' => wc_get_cart_url(),
                    'checkout_url' => wc_get_checkout_url(),
                ));
            } else {
                wp_send_json_error( array( 
                    'message' => esc_html__( 'Erro ao adicionar produto ao carrinho.', 'cm-precheckout' ) 
                ));
            }
            
        } catch ( \Exception $e ) {
            wp_send_json_error( array( 
                'message' => $e->getMessage() 
            ));
        }
    }


    /**
     * Validate product quantity
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param WC_Product $product
     * @param int $quantity
     * @return void
     */
    private function validate_quantity( $product, $quantity ) {
        $min_quantity = $product->get_min_purchase_quantity();
        $max_quantity = $product->get_max_purchase_quantity();
        
        if ( $quantity < $min_quantity ) {
            wp_send_json_error( array( 
                'message' => sprintf( 
                    esc_html__( 'Quantidade mínima é %d.', 'cm-precheckout' ), 
                    $min_quantity 
                )
            ));
        }
        
        if ( $max_quantity > 0 && $quantity > $max_quantity ) {
            wp_send_json_error( array( 
                'message' => sprintf( 
                    esc_html__( 'Quantidade máxima é %d.', 'cm-precheckout' ), 
                    $max_quantity 
                )
            ));
        }
    }


    /**
     * Calculate additional price based on customization
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $product_id
     * @param array $customization
     * @return float
     */
    private function calculate_additional_price( $product_id, $customization ) {
        $additional_price = 0;
        
        // Material price
        if ( isset( $customization['material'] ) ) {
            $additional_price += Utils::get_material_price( $product_id, $customization['material'] );
        }
        
        // Name engraving price
        if ( isset( $customization['names'] ) && ! empty( $customization['names'] ) ) {
            $engraving_price = get_post_meta( $product_id, '_cm_precheckout_engraving_price', true );
            if ( $engraving_price ) {
                $additional_price += floatval( $engraving_price ) * count( array_filter( $customization['names'] ));
            }
        }
        
        // Stone price
        if ( isset( $customization['stone'] ) ) {
            $stone_prices = get_post_meta( $product_id, '_cm_precheckout_stone_prices', true );
            if ( is_array( $stone_prices ) && isset( $stone_prices[ $customization['stone'] ] ) ) {
                $additional_price += floatval( $stone_prices[ $customization['stone'] ] );
            }
        }
        
        return apply_filters( 'cm_precheckout_calculate_additional_price', $additional_price, $product_id, $customization );
    }


    /**
     * Update cart item price
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $cart_item_key
     * @param float $additional_price
     * @return void
     */
    private function update_cart_item_price( $cart_item_key, $additional_price ) {
        if ( $additional_price > 0 ) {
            $cart_item = WC()->cart->get_cart_item( $cart_item_key );
            
            if ( $cart_item ) {
                $original_price = $cart_item['data']->get_price();
                $new_price = $original_price + $additional_price;
                
                $cart_item['data']->set_price( $new_price );
                
                // Store original price for reference
                WC()->cart->cart_contents[ $cart_item_key ]['cm_precheckout_original_price'] = $original_price;
                WC()->cart->set_session();
            }
        }
    }


    /**
     * Save courses order
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function save_course_order() {
        $this->verify_request( 'admin' );

        $order = isset( $_POST['order'] ) ? array_map( 'absint', $_POST['order'] ) : array();
        
        if ( empty( $order ) ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'Nenhuma ordem fornecida.', 'cm-precheckout' ) 
            ));
        }

        $options = get_option( 'cm_precheckout_options', array());
        $courses = isset( $options['courses'] ) ? $options['courses'] : array();
        $reordered_courses = array();

        foreach ( $order as $index ) {
            if ( isset( $courses[ $index ] ) ) {
                $reordered_courses[] = $courses[ $index ];
            }
        }
        
        $options['courses'] = $reordered_courses;
        update_option( 'cm_precheckout_options', $options );
        
        // Clear cache
        Utils::clear_cache();
        
        wp_send_json_success( array(
            'message' => esc_html__( 'Ordem dos cursos salva com sucesso!', 'cm-precheckout' ),
        ));
    }


    /**
     * Save stones order
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function save_stone_order() {
        $this->verify_request( 'admin' );

        $order = isset( $_POST['order'] ) ? array_map( 'absint', $_POST['order'] ) : array();
        
        if ( empty( $order ) ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'Nenhuma ordem fornecida.', 'cm-precheckout' ) 
            ));
        }

        $options = get_option( 'cm_precheckout_options', array());
        $stones = isset( $options['stones'] ) ? $options['stones'] : array();
        $reordered_stones = array();

        foreach ( $order as $index ) {
            if ( isset( $stones[ $index ] ) ) {
                $reordered_stones[] = $stones[ $index ];
            }
        }
        
        $options['stones'] = $reordered_stones;
        update_option( 'cm_precheckout_options', $options );
        
        // Clear cache
        Utils::clear_cache();
        
        wp_send_json_success( array(
            'message' => esc_html__( 'Ordem das pedras salva com sucesso!', 'cm-precheckout' ),
        ));
    }


    /**
     * Upload image via AJAX
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function upload_image() {
        $this->verify_request( 'admin' );

        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $uploadedfile = $_FILES['file'];
        $upload_overrides = array( 
            'test_form' => false 
        );

        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            $attachment = array(
                'guid'           => $movefile['url'], 
                'post_mime_type' => $movefile['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );
            
            $attach_id = wp_insert_attachment( $attachment, $movefile['file'] );
            
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            
            $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );
            
            wp_send_json_success( array(
                'id' => $attach_id,
                'url' => $movefile['url'],
                'thumbnail' => wp_get_attachment_image_url( $attach_id, 'thumbnail' ),
                'message' => esc_html__( 'Imagem enviada com sucesso!', 'cm-precheckout' ),
            ));
        } else {
            wp_send_json_error( array( 
                'message' => $movefile['error'] 
            ));
        }
    }

    
    /**
     * Delete image via AJAX
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function delete_image() {
        $this->verify_request( 'admin' );

        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        
        if ( ! $attachment_id ) {
            wp_send_json_error( array( 
                'message' => esc_html__( 'ID do anexo não fornecido.', 'cm-precheckout' ) 
            ));
        }

        $deleted = wp_delete_attachment( $attachment_id, true );
        
        if ( $deleted ) {
            wp_send_json_success( array(
                'message' => esc_html__( 'Imagem deletada com sucesso!', 'cm-precheckout' ),
            ));
        } else {
            wp_send_json_error( array( 
                'message' => esc_html__( 'Erro ao deletar imagem.', 'cm-precheckout' ) 
            ));
        }
    }


    /**
     * Save product steps data via AJAX.
     *
     * @since 1.0.0
     * @return void
     */
    public function save_product_steps() {
        $this->verify_request( 'admin', 'edit_products' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $steps      = isset( $_POST['steps'] ) ? json_decode( wp_unslash( $_POST['steps'] ), true ) : array();

        if ( ! $product_id ) {
            wp_send_json_error( array(
                'message' => esc_html__( 'Produto inválido.', 'cm-precheckout' ),
            ));
        }

        $sanitized_steps = array();
        $steps_order     = array();

        if ( is_array( $steps ) ) {
            foreach ( $steps as $step ) {
                $step_id   = sanitize_key( $step['id'] ?? '' );
                $step_name = sanitize_text_field( $step['name'] ?? '' );
                $step_icon = sanitize_text_field( $step['icon'] ?? '' );

                if ( empty( $step_id ) || empty( $step_name ) ) {
                    continue;
                }

                $actions = array();

                if ( ! empty( $step['actions'] ) && is_array( $step['actions'] ) ) {
                    foreach ( $step['actions'] as $action ) {
                        $action_key = sanitize_text_field( $action['key'] ?? '' );

                        if ( empty( $action_key ) ) {
                            continue;
                        }

                        $actions[] = array(
                            'key' => $action_key,
                            'required' => ! empty( $action['required'] ),
                            'display_name' => sanitize_text_field( $action['display_name'] ?? '' ),
                            'additional_message' => sanitize_textarea_field( $action['additional_message'] ?? '' ),
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

        update_post_meta( $product_id, '_cm_precheckout_steps_data', $sanitized_steps );
        update_post_meta( $product_id, '_cm_precheckout_steps', array_values( array_unique( $steps_order ) ) );

        wp_send_json_success( array(
            'message' => esc_html__( 'Etapas salvas com sucesso!', 'cm-precheckout' ),
        ));
    }


    /**
     * Get step template for product
     * 
     * @since 1.0.0
     * @return void
     */
    public function get_step_template() {
        $this->verify_request( 'admin', 'edit_products' );

        $step_key = sanitize_text_field($_POST['step_key']);
        $product_id = absint($_POST['product_id']);
        
        $options_library = new Options_Library();
        $option = $options_library->get_option_by_key($step_key);
        
        if ($option) {
            ob_start();
            ?>
            <div class="step-item" data-key="<?php echo esc_attr($option['key']); ?>">
                <span class="dashicons dashicons-move step-handle"></span>
                <span class="dashicons <?php echo esc_attr($option['icon']); ?> step-icon"></span>
                <div class="step-content">
                    <h4 class="step-title"><?php echo esc_html($option['name']); ?></h4>
                    <p class="step-description">
                        <?php 
                        $config = isset($option['config']) ? $option['config'] : array();
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
            
            wp_send_json_success(array(
                'html' => ob_get_clean()
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Opção não encontrada.', 'cm-precheckout')
            ));
        }
    }


    /**
     * Create an empty step container.
     *
     * @since 1.0.0
     * @return void
     */
    public function create_step_container() {
        $this->verify_request( 'admin', 'edit_products' );

        $step_name = sanitize_text_field( $_POST['step_name'] ?? '' );
        $step_icon = sanitize_text_field( $_POST['step_icon'] ?? '' );

        if ( empty( $step_name ) ) {
            wp_send_json_error( array(
                'message' => esc_html__( 'Informe um nome para a etapa.', 'cm-precheckout' ),
            ));
        }

        $options_library = new Options_Library();
        $library_options = $options_library->get_enabled_options();

        $step = array(
            'id' => uniqid( 'step_', true ),
            'name' => $step_name,
            'icon' => $step_icon,
            'actions' => array(),
        );

        wp_send_json_success( array(
            'html' => $this->render_step_container_html( $step, $library_options ),
        ));
    }


    /**
     * Render action markup for a step.
     *
     * @since 1.0.0
     * @return void
     */
    public function get_step_action() {
        $this->verify_request( 'admin', 'edit_products' );

        $option_key          = sanitize_text_field( $_POST['option_key'] ?? '' );
        $display_name        = sanitize_text_field( $_POST['display_name'] ?? '' );
        $additional_message  = sanitize_textarea_field( $_POST['additional_message'] ?? '' );
        $required            = ! empty( $_POST['required'] );

        if ( empty( $option_key ) ) {
            wp_send_json_error( array(
                'message' => esc_html__( 'Opção inválida.', 'cm-precheckout' ),
            ));
        }

        $options_library = new Options_Library();
        $library_options = $options_library->get_enabled_options();

        if ( ! isset( $library_options[ $option_key ] ) ) {
            wp_send_json_error( array(
                'message' => esc_html__( 'Opção não encontrada.', 'cm-precheckout' ),
            ));
        }

        $action = array(
            'key' => $option_key,
            'required' => $required,
            'display_name' => ! empty( $display_name ) ? $display_name : $library_options[ $option_key ]['name'],
            'additional_message' => $additional_message,
        );

        wp_send_json_success( array(
            'html' => $this->render_step_action_html( $action, $library_options ),
        ));
    }


    /**
     * Set default options
     * 
     * @since 1.0.0
     * @return void
     */
    public function set_default_options() {
        $this->verify_request('admin', 'edit_products');

        $type = sanitize_text_field($_POST['type']);
        
        $default_options = new Default_Options();
        $defaults = $default_options->get_defaults($type);
        
        $options = get_option('cm_precheckout_options', array());
        $options[$type] = $defaults;
        
        update_option('cm_precheckout_options', $options);
        
        // Clear cache
        Utils::clear_cache();
        
        wp_send_json_success(array(
            'message' => sprintf(
                esc_html__('%s padrão aplicados com sucesso!', 'cm-precheckout'),
                $type === 'courses' ? esc_html__('Cursos', 'cm-precheckout') : esc_html__('Pedras', 'cm-precheckout')
            )
        ));
    }

    
    /**
     * Render step template HTML
     * 
     * @since 1.0.0
     * @param array $option
     * @param int $product_id
     * @return string
     */
    private function render_step_template($option, $product_id) {
        ob_start();
        ?>
        <div class="step-item" data-key="<?php echo esc_attr($option['key']); ?>">
            <span class="dashicons dashicons-move step-handle"></span>
            <span class="dashicons <?php echo esc_attr($option['icon']); ?> step-icon"></span>
            <div class="step-content">
                <h4 class="step-title"><?php echo esc_html($option['name']); ?></h4>
                <p class="step-description">
                    <?php 
                    $config = isset($option['config']) ? $option['config'] : array();
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
        return ob_get_clean();
    }

    /**
     * Get materials configuration
     * 
     * @since 1.0.0
     * @return void
     */
    public function get_materials_config() {
        $this->verify_request('admin', 'edit_products');

        $product_id = absint($_POST['product_id']);
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(array(
                'message' => esc_html__('Produto não encontrado.', 'cm-precheckout')
            ));
        }

        $materials = Utils::get_product_materials($product_id);
        $materials_config = get_post_meta($product_id, '_cm_precheckout_materials_config', true);
        $materials_config = is_array($materials_config) ? $materials_config : array();
        
        ob_start();
        ?>
        <form id="materials-config-form">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            
            <table class="form-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Material', 'cm-precheckout'); ?></th>
                        <th><?php esc_html_e('Vincular a Variação', 'cm-precheckout'); ?></th>
                        <th><?php esc_html_e('Preço Adicional', 'cm-precheckout'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($materials)): ?>
                        <tr>
                            <td colspan="3">
                                <?php esc_html_e('Nenhum material configurado para este produto.', 'cm-precheckout'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($materials as $material_key): 
                            $material_label = Utils::get_material_label($material_key);
                            $config = isset($materials_config[$material_key]) ? $materials_config[$material_key] : array();
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($material_label); ?></strong>
                                    <input type="hidden" 
                                        name="materials[<?php echo esc_attr($material_key); ?>][key]" 
                                        value="<?php echo esc_attr($material_key); ?>">
                                </td>
                                <td>
                                    <?php if ($product->is_type('variable')): 
                                        $variations = $product->get_available_variations();
                                        ?>
                                        <select name="materials[<?php echo esc_attr($material_key); ?>][variation_id]" 
                                                class="variation-select">
                                            <option value=""><?php esc_html_e('Nenhuma', 'cm-precheckout'); ?></option>
                                            <?php foreach ($variations as $variation): 
                                                $variation_obj = wc_get_product($variation['variation_id']);
                                                if ($variation_obj):
                                                    $attributes = $variation_obj->get_variation_attributes();
                                                    $attribute_label = implode(', ', $attributes);
                                                    ?>
                                                    <option value="<?php echo esc_attr($variation['variation_id']); ?>"
                                                        <?php selected($config['variation_id'] ?? '', $variation['variation_id']); ?>>
                                                        <?php echo esc_html($attribute_label); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <p class="description">
                                            <?php esc_html_e('Disponível apenas para produtos variáveis', 'cm-precheckout'); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="number" 
                                        name="materials[<?php echo esc_attr($material_key); ?>][additional_price]" 
                                        value="<?php echo esc_attr($config['additional_price'] ?? 0); ?>"
                                        step="0.01" 
                                        min="0" 
                                        class="small-text">
                                    <?php echo get_woocommerce_currency_symbol(); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        <?php
        
        wp_send_json_success(array(
            'html' => ob_get_clean()
        ));
    }

    /**
     * Save materials configuration
     * 
     * @since 1.0.0
     * @return void
     */
    public function save_materials_config() {
        $this->verify_request('admin');

        $product_id = absint($_POST['product_id']);
        
        if (isset($_POST['materials']) && is_array($_POST['materials'])) {
            $materials_config = array();
            
            foreach ($_POST['materials'] as $material_key => $config) {
                $sanitized_config = array(
                    'variation_id' => isset($config['variation_id']) ? absint($config['variation_id']) : '',
                    'additional_price' => isset($config['additional_price']) ? floatval($config['additional_price']) : 0,
                );
                
                $materials_config[sanitize_text_field($material_key)] = $sanitized_config;
            }
            
            update_post_meta($product_id, '_cm_precheckout_materials_config', $materials_config);
            
            // Also update material prices for legacy compatibility
            $material_prices = array();
            foreach ($materials_config as $material_key => $config) {
                if ($config['additional_price'] > 0) {
                    $material_prices[$material_key] = $config['additional_price'];
                }
            }
            update_post_meta($product_id, '_cm_precheckout_material_prices', $material_prices);
            
            wp_send_json_success(array(
                'message' => esc_html__('Configurações salvas com sucesso!', 'cm-precheckout')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Nenhuma configuração fornecida.', 'cm-precheckout')
            ));
        }
    }


    /**
     * Toggle option enabled/disabled
     * 
     * @since 1.0.0
     * @return void
     */
    public function toggle_option() {
        $this->verify_request('admin');

        $key = sanitize_text_field($_POST['key']);
        $enabled = $_POST['enabled'] === 'true';
        
        $options_library = new Options_Library();
        $options = $options_library->get_library_options();
        
        if (isset($options[$key])) {
            $options[$key]['enabled'] = $enabled;
            update_option('cm_precheckout_library_options', $options);
            
            wp_send_json_success(array(
                'message' => $enabled ? 
                    esc_html__('Opção ativada com sucesso!', 'cm-precheckout') : 
                    esc_html__('Opção desativada com sucesso!', 'cm-precheckout')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Opção não encontrada.', 'cm-precheckout')
            ));
        }
    }


    /**
     * Render step container HTML.
     *
     * @since 1.0.0
     * @param array $step Step data.
     * @param array $library_options Options library list.
     * @return string
     */
    private function render_step_container_html( $step, $library_options ) {
        ob_start();

        $step_id   = isset( $step['id'] ) ? $step['id'] : uniqid( 'step_', true );
        $step_name = isset( $step['name'] ) ? $step['name'] : '';
        $step_icon = isset( $step['icon'] ) ? $step['icon'] : '';
        $actions   = isset( $step['actions'] ) && is_array( $step['actions'] ) ? $step['actions'] : array();
        ?>
        <div class="cm-precheckout-step" data-step-id="<?php echo esc_attr( $step_id ); ?>" data-step-name="<?php echo esc_attr( $step_name ); ?>" data-step-icon="<?php echo esc_attr( $step_icon ); ?>">
            <div class="cm-precheckout-step__header">
                <span class="dashicons dashicons-move cm-precheckout-step__handle"></span>
                <div class="cm-precheckout-step__title">
                    <?php if ( ! empty( $step_icon ) ) : ?>
                        <span class="dashicons <?php echo esc_attr( $step_icon ); ?> cm-precheckout-step__icon"></span>
                    <?php endif; ?>
                    <strong class="cm-precheckout-step__name"><?php echo esc_html( $step_name ); ?></strong>
                </div>
                <div class="cm-precheckout-step__actions">
                    <button type="button" class="button button-secondary cm-precheckout-add-action"><?php esc_html_e( 'Adicionar ação', 'cm-precheckout' ); ?></button>
                    <button type="button" class="button button-link-delete cm-precheckout-remove-step"><?php esc_html_e( 'Remover etapa', 'cm-precheckout' ); ?></button>
                </div>
            </div>
            <div class="cm-precheckout-step__body">
                <div class="cm-precheckout-step__actions-list">
                    <?php
                    if ( ! empty( $actions ) ) {
                        foreach ( $actions as $action ) {
                            echo $this->render_step_action_html( $action, $library_options );
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }


    /**
     * Render action item HTML.
     *
     * @since 1.0.0
     * @param array $action Action data.
     * @param array $library_options Options library list.
     * @return string
     */
    private function render_step_action_html( $action, $library_options ) {
        $action_key = isset( $action['key'] ) ? $action['key'] : '';

        if ( empty( $action_key ) || ! isset( $library_options[ $action_key ] ) ) {
            return '';
        }

        $library_option     = $library_options[ $action_key ];
        $display_name       = $action['display_name'] ?? $library_option['name'];
        $is_required        = ! empty( $action['required'] );
        $additional_message = $action['additional_message'] ?? '';

        ob_start();
        ?>
        <div class="cm-precheckout-action" data-action-key="<?php echo esc_attr( $action_key ); ?>" data-action-required="<?php echo esc_attr( $is_required ? '1' : '0' ); ?>" data-action-display-name="<?php echo esc_attr( $display_name ); ?>" data-action-message="<?php echo esc_attr( $additional_message ); ?>">
            <div class="cm-precheckout-action__info">
                <span class="dashicons <?php echo esc_attr( $library_option['icon'] ); ?>"></span>
                <div>
                    <p class="cm-precheckout-action__name"><?php echo esc_html( $display_name ); ?></p>
                    <p class="cm-precheckout-action__meta">
                        <?php
                        echo esc_html( $library_option['name'] );
                        echo ' · ';
                        echo $is_required ? esc_html__( 'Obrigatório', 'cm-precheckout' ) : esc_html__( 'Opcional', 'cm-precheckout' );
                        ?>
                    </p>
                </div>
            </div>
            <div class="cm-precheckout-action__controls">
                <?php if ( 'material_selection' === $action_key ) : ?>
                    <button type="button" class="button configure-materials"><?php esc_html_e( 'Configurar', 'cm-precheckout' ); ?></button>
                <?php endif; ?>
                <button type="button" class="button button-secondary cm-precheckout-edit-action"><?php esc_html_e( 'Editar ações', 'cm-precheckout' ); ?></button>
                <button type="button" class="button cm-precheckout-remove-action"><?php esc_html_e( 'Remover', 'cm-precheckout' ); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}