<?php

namespace MeuMouse\Cm_Precheckout\Views\Frontend;

/**
 * Shortcode for Pre-checkout Button
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Shortcodes {

    /**
     * Constructor
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct() {
        // Register shortcode
        add_shortcode( 'cm_pre_checkout', array( $this, 'render_shortcode' ) );

        // Ajax handlers
        add_action( 'wp_ajax_cm_precheckout_get_product_data', array( $this, 'get_product_data' ) );
        add_action( 'wp_ajax_nopriv_cm_precheckout_get_product_data', array( $this, 'get_product_data' ) );
    }

    /**
     * Render shortcode
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param array $atts
     * @return string
     */
    public function render_shortcode( $atts ) {
        global $product;
        
        if ( ! $product ) {
            return '';
        }

        // Check if precheckout is active for this product
        $precheckout_active = get_post_meta( $product->get_id(), '_cm_precheckout_active', true );
        
        if ( ! $precheckout_active || $precheckout_active !== 'yes' ) {
            return '';
        }

        ob_start();
        ?>
        <div class="cm-precheckout-wrapper">
            <button type="button" 
                    class="button cm-precheckout-button" 
                    data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
                <?php esc_html_e( 'Personalizar anel', 'cm-precheckout' ); ?>
            </button>
        </div>
        <?php
        
        // Add modal HTML
        $this->render_modal();
        
        return ob_get_clean();
    }

    /**
     * Render modal
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    private function render_modal() {
        ?>
        <div id="cm-precheckout-modal" class="cm-precheckout-modal" style="display: none;">
            <div class="cm-precheckout-modal-overlay"></div>
            <div class="cm-precheckout-modal-content">
                <button type="button" class="cm-precheckout-close">&times;</button>
                
                <div class="cm-precheckout-steps">
                    <!-- Step 1: Material -->
                    <div class="cm-precheckout-step step-1 active">
                        <h3><?php esc_html_e( 'Etapa 1: Material', 'cm-precheckout' ); ?></h3>
                        
                        <div class="material-options">
                            <!-- Will be populated via JavaScript -->
                        </div>
                        
                        <div class="quantity-selector">
                            <label><?php esc_html_e( 'Quantidade', 'cm-precheckout' ); ?></label>
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
                        
                        <button type="button" class="button next-step">
                            <?php esc_html_e( 'Próximo', 'cm-precheckout' ); ?>
                        </button>
                    </div>
                    
                    <!-- Step 2: Ring Sizes -->
                    <div class="cm-precheckout-step step-2">
                        <h3><?php esc_html_e( 'Etapa 2: Seleção de tamanhos de anel', 'cm-precheckout' ); ?></h3>
                        
                        <div class="size-selectors">
                            <!-- Will be populated via JavaScript -->
                        </div>
                        
                        <p class="size-help">
                            <a href="#">
                                <?php esc_html_e( 'Como medir seu dedo?', 'cm-precheckout' ); ?>
                            </a>
                        </p>
                        
                        <button type="button" class="button prev-step">
                            <?php esc_html_e( 'Voltar', 'cm-precheckout' ); ?>
                        </button>
                        <button type="button" class="button next-step">
                            <?php esc_html_e( 'Próximo', 'cm-precheckout' ); ?>
                        </button>
                    </div>
                    
                    <!-- Step 3: Personalization -->
                    <div class="cm-precheckout-step step-3">
                        <h3><?php esc_html_e( 'Etapa 3: Personalização', 'cm-precheckout' ); ?></h3>
                        
                        <div class="personalization-options">
                            <!-- Will be populated via JavaScript -->
                        </div>
                        
                        <button type="button" class="button prev-step">
                            <?php esc_html_e( 'Voltar', 'cm-precheckout' ); ?>
                        </button>
                        <button type="button" class="button next-step">
                            <?php esc_html_e( 'Próximo', 'cm-precheckout' ); ?>
                        </button>
                    </div>
                    
                    <!-- Step 4: Order Summary -->
                    <div class="cm-precheckout-step step-4">
                        <h3><?php esc_html_e( 'Resumo do pedido', 'cm-precheckout' ); ?></h3>
                        
                        <div class="order-summary">
                            <div class="summary-item">
                                <span class="label"><?php esc_html_e( 'Material selecionado:', 'cm-precheckout' ); ?></span>
                                <span class="value material-summary"></span>
                            </div>
                            <div class="summary-item">
                                <span class="label"><?php esc_html_e( 'Quantidade:', 'cm-precheckout' ); ?></span>
                                <span class="value quantity-summary"></span>
                            </div>
                            <div class="summary-item">
                                <span class="label"><?php esc_html_e( 'Tamanhos escolhidos:', 'cm-precheckout' ); ?></span>
                                <span class="value sizes-summary"></span>
                            </div>
                            <div class="summary-item">
                                <span class="label"><?php esc_html_e( 'Curso:', 'cm-precheckout' ); ?></span>
                                <span class="value course-summary"></span>
                            </div>
                            <div class="summary-item">
                                <span class="label"><?php esc_html_e( 'Pedra:', 'cm-precheckout' ); ?></span>
                                <span class="value stone-summary"></span>
                            </div>
                            <div class="summary-item total">
                                <span class="label"><?php esc_html_e( 'Total:', 'cm-precheckout' ); ?></span>
                                <span class="value total-summary"></span>
                            </div>
                        </div>
                        
                        <button type="button" class="button prev-step">
                            <?php esc_html_e( 'Voltar', 'cm-precheckout' ); ?>
                        </button>
                        <button type="button" class="button add-to-cart">
                            <?php esc_html_e( 'Adicionar ao carrinho', 'cm-precheckout' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get product data via AJAX
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function get_product_data() {
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        
        if ( ! $product_id ) {
            wp_die();
        }
        
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            wp_die();
        }
        
        $data = array(
            'id' => $product_id,
            'title' => $product->get_name(),
            'price' => $product->get_price(),
            'currency' => get_woocommerce_currency_symbol(),
            'materials' => get_post_meta( $product_id, '_cm_precheckout_materials', true ),
            'size_selectors' => get_post_meta( $product_id, '_cm_precheckout_size_selectors', true ),
            'enable_name_engraving' => get_post_meta( $product_id, '_cm_precheckout_enable_name_engraving', true ),
            'name_fields' => get_post_meta( $product_id, '_cm_precheckout_name_fields', true ),
            'enable_course_change' => get_post_meta( $product_id, '_cm_precheckout_enable_course_change', true ),
            'enable_stone_sample' => get_post_meta( $product_id, '_cm_precheckout_enable_stone_sample', true ),
            'enable_emblems_sample' => get_post_meta( $product_id, '_cm_precheckout_enable_emblems_sample', true ),
            'default_course' => get_post_meta( $product_id, '_cm_precheckout_default_course', true ),
        );
        
        wp_send_json_success( $data );
    }
}