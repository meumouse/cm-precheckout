<?php

namespace MeuMouse\Cm_Precheckout\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Enqueue assets
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Assets {

    /**
     * Script handles
     * 
     * @since 1.0.0
     * @var array
     */
    private $handles = array(
        'frontend' => array(
            'css' => 'cm-precheckout-frontend',
            'js' => 'cm-precheckout-frontend',
        ),
        'admin' => array(
            'css' => 'cm-precheckout-admin',
            'js' => 'cm-precheckout-admin',
        ),
    );


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
     * Initialize hooks
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    private function init_hooks() {
        // Frontend assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        // Admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Block editor assets
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
    }


    /**
     * Get asset URL
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $type 'css' or 'js'
     * @param string $context 'frontend' or 'admin'
     * @param string $file File name without extension
     * @return string
     */
    private function get_asset_url( $type, $context, $file ) {
        $base_path = $context === 'admin' ? 'admin/' : 'frontend/';
        
        return CM_PRECHECKOUT_ASSETS . $base_path . $type . '/' . $file . '.' . $type;
    }


    /**
     * Get asset version
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return string
     */
    private function get_asset_version() {
        return defined('CM_PRECHECKOUT_DEV_MODE') && CM_PRECHECKOUT_DEV_MODE ? time() : CM_PRECHECKOUT_VERSION;
    }


    /**
     * Enqueue frontend assets
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Only load on product pages or if shortcode is used
        if ( ! $this->should_load_frontend_assets() ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            $this->handles['frontend']['css'],
            $this->get_asset_url( 'css', 'frontend', 'frontend' ),
            array(),
            $this->get_asset_version(),
            'all'
        );

        // Enqueue JS
        wp_enqueue_script(
            $this->handles['frontend']['js'],
            $this->get_asset_url( 'js', 'frontend', 'frontend' ),
            array( 'jquery', 'wc-add-to-cart' ),
            $this->get_asset_version(),
            true
        );

        // Localize script
        $this->localize_frontend_script();
    }


    /**
     * Check if frontend assets should be loaded
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return bool
     */
    private function should_load_frontend_assets() {
        // Always load on product pages
        if ( is_product() ) {
            return true;
        }

        // Check if shortcode is used on the page
        global $post;

        if ( $post && has_shortcode( $post->post_content, 'cm_pre_checkout' ) ) {
            return true;
        }

        // Check via filter
        return apply_filters( 'cm_precheckout_should_load_frontend_assets', false );
    }


    /**
     * Localize frontend script
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    private function localize_frontend_script() {
        $localization_data = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'cm_precheckout_nonce' ),
            'current_product_id' => is_product() ? get_the_ID() : 0,
            'i18n' => array(
                'select_material' => esc_html__( 'Por favor, selecione um material', 'cm-precheckout' ),
                'select_size' => esc_html__( 'Por favor, selecione um tamanho', 'cm-precheckout' ),
                'adding_to_cart' => esc_html__( 'Adicionando ao carrinho...', 'cm-precheckout' ),
                'added_to_cart' => esc_html__( 'Adicionado ao carrinho!', 'cm-precheckout' ),
                'error_occurred' => esc_html__( 'Ocorreu um erro. Por favor, tente novamente.', 'cm-precheckout' ),
                'loading' => esc_html__( 'Carregando...', 'cm-precheckout' ),
            ),
            'settings' => array(
                'debug_mode' => defined( 'CM_PRECHECKOUT_DEBUG_MODE' ) && CM_PRECHECKOUT_DEBUG_MODE,
                'ajax_timeout' => apply_filters( 'cm_precheckout_ajax_timeout', 30000 ),
            ),
        );

        wp_localize_script(
            $this->handles['frontend']['js'],
            'cm_precheckout',
            $localization_data
        );
    }


    /**
     * Enqueue admin assets
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        // Remove o hook duplicado se existir
        if ( has_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) ) ) {
            remove_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
        }

        // Load on product edit screen
        if ( $this->is_product_edit_screen( $hook ) ) {
            $this->enqueue_product_editor_assets();
        }

        // Load on plugin settings pages
        if ( $this->is_plugin_settings_page( $hook ) ) {
            $this->enqueue_settings_page_assets();
        }
    }


    /**
     * Check if current screen is product edit
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $hook Current admin page hook
     * @return bool
     */
    private function is_product_edit_screen( $hook ) {
        global $post_type;
        
        return ( $hook === 'post.php' || $hook === 'post-new.php' ) && $post_type === 'product';
    }


    /**
     * Check if current screen is plugin settings page
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $hook Current admin page hook
     * @return bool
     */
    private function is_plugin_settings_page( $hook ) {
        return strpos( $hook, 'cm-precheckout' ) !== false;
    }


    /**
     * Enqueue product editor assets
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    private function enqueue_product_editor_assets() {
        wp_enqueue_style(
            $this->handles['admin']['css'],
            $this->get_asset_url( 'css', 'admin', 'admin' ),
            array(),
            $this->get_asset_version(),
            'all'
        );

        wp_enqueue_script(
            $this->handles['admin']['js'],
            $this->get_asset_url( 'js', 'admin', 'admin' ),
            array( 'jquery', 'wp-util' ),
            $this->get_asset_version(),
            true
        );

        $this->localize_admin_script( 'product_editor' );
    }


    /**
     * Enqueue settings page assets
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    private function enqueue_settings_page_assets() {
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Enqueue CSS
        wp_enqueue_style(
            $this->handles['admin']['css'],
            $this->get_asset_url( 'css', 'admin', 'admin' ),
            array(),
            $this->get_asset_version(),
            'all'
        );
        
        // Enqueue JS
        wp_enqueue_script(
            $this->handles['admin']['js'],
            $this->get_asset_url( 'js', 'admin', 'admin' ),
            array( 'jquery', 'media-upload', 'media-views', 'wp-util' ),
            $this->get_asset_version(),
            true
        );

        $this->localize_admin_script( 'settings_page' );
    }


    /**
     * Localize admin script
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $context Context of localization
     * @return void
     */
    private function localize_admin_script( $context ) {
        $localization_data = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'cm_precheckout_admin_nonce' ),
            'context' => $context,
            'i18n' => array(
                'select_image' => esc_html__( 'Selecionar imagem', 'cm-precheckout' ),
                'use_image' => esc_html__( 'Usar esta imagem', 'cm-precheckout' ),
                'remove' => esc_html__( 'Remover', 'cm-precheckout' ),
                'confirm_remove' => esc_html__( 'Tem certeza que deseja remover este item?', 'cm-precheckout' ),
                'saving' => esc_html__( 'Salvando...', 'cm-precheckout' ),
                'saved' => esc_html__( 'Salvo!', 'cm-precheckout' ),
            ),
        );

        wp_localize_script(
            $this->handles['admin']['js'],
            'cm_precheckout_admin',
            $localization_data
        );
    }


    /**
     * Enqueue block editor assets
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function enqueue_block_editor_assets() {
        global $post_type;
        
        if ( $post_type !== 'product' ) {
            return;
        }

        wp_enqueue_script(
            'cm-precheckout-block-editor',
            $this->get_asset_url( 'js', 'admin', 'block-editor' ),
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components' ),
            $this->get_asset_version(),
            true
        );

        wp_localize_script(
            'cm-precheckout-block-editor',
            'cm_precheckout_block_editor',
            array(
                'i18n' => array(
                    'precheckout_settings' => esc_html__( 'Configurações Pré-Checkout', 'cm-precheckout' ),
                ),
            )
        );
    }


    /**
     * Enqueue settings assets (deprecated - use enqueue_admin_assets instead)
     * 
     * @since 1.0.0
     * @deprecated 1.0.0 Use enqueue_admin_assets() instead
     * @version 1.0.0
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_settings_assets( $hook ) {
        // For backward compatibility, just call the main admin assets function
        _deprecated_function( __METHOD__, '1.0.0', 'enqueue_admin_assets' );
        $this->enqueue_admin_assets( $hook );
    }
}