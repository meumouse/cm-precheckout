<?php

namespace MeuMouse\Cm_Precheckout\Core;

/**
 * Enqueue assets
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse\Cm_Precheckout
 */
class Assets {

    /**
     * Constructor
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct() {
        // Frontend assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        // Admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }


    /**
     * Enqueue frontend assets
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Only load on product pages
        if ( ! is_product() ) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'cm-precheckout-frontend',
            CM_PRECHECKOUT_ASSETS . 'frontend/css/frontend.css',
            array(),
            CM_PRECHECKOUT_VERSION,
            'all'
        );

        // JS
        wp_enqueue_script(
            'cm-precheckout-frontend',
            CM_PRECHECKOUT_ASSETS . 'frontend/js/frontend.js',
            array( 'jquery' ),
            CM_PRECHECKOUT_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'cm-precheckout-frontend',
            'cm_precheckout',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'cm_precheckout_nonce' ),
                'i18n' => array(
                    'select_material' => esc_html__( 'Por favor, selecione um material', 'cm-precheckout' ),
                    'select_size' => esc_html__( 'Por favor, selecione um tamanho', 'cm-precheckout' ),
                    'adding_to_cart' => esc_html__( 'Adicionando ao carrinho...', 'cm-precheckout' ),
                    'added_to_cart' => esc_html__( 'Adicionado ao carrinho!', 'cm-precheckout' ),
                )
            )
        );
    }

    
    /**
     * Enqueue admin assets
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $hook
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        global $post_type;
        
        // Load on product edit screen
        if ( $hook == 'post.php' && $post_type == 'product' ) {
            wp_enqueue_style(
                'cm-precheckout-admin',
                CM_PRECHECKOUT_ASSETS . 'admin/css/admin.css',
                array(),
                CM_PRECHECKOUT_VERSION,
                'all'
            );
        }

        // Load on our settings pages
        if ( strpos( $hook, 'cm-precheckout' ) !== false ) {
            wp_enqueue_media();
            
            wp_enqueue_style(
                'cm-precheckout-admin',
                CM_PRECHECKOUT_ASSETS . 'admin/css/admin.css',
                array(),
                CM_PRECHECKOUT_VERSION,
                'all'
            );
            
            wp_enqueue_script(
                'cm-precheckout-admin',
                CM_PRECHECKOUT_ASSETS . 'admin/js/admin.js',
                array( 'jquery', 'media-upload', 'media-views' ),
                CM_PRECHECKOUT_VERSION,
                true
            );
        }
    }
}