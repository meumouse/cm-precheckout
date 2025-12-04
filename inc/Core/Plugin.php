<?php

namespace MeuMouse\Cm_Precheckout\Core;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use MeuMouse\Cm_Precheckout\Helpers\Utils;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Plugin core class.
 *
 * @since 1.0.0
 * @package MeuMouse.com
 */
class Plugin {
    
    /**
     * Plugin version.
     * 
     * @since 1.0.0
     * @return string
     */
    public const VERSION = '1.0.0';

    /**
     * Plugin slug.
     * 
     * @since 1.0.0
     * @version 1.5.0
     * @return string
     */
    public const SLUG = 'cm-precheckout';

    /**
     * Plugin instance.
     * 
     * @since 1.0.0
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Get plugin instance
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    
    /**
     * Initialize the plugin.
     *
     * @since 1.0.0
     * @return void
     */
    public function init() {
        // hook before plugin init
        do_action( 'Cm_Precheckout/Before_Init' );

        $this->define_constants();
        $this->check_dependencies();
        $this->load_textdomain();
        $this->setup_hpos_compatibility();
        $this->instance_classes();

        // Register activation/deactivation hooks
        register_activation_hook( CM_PRECHECKOUT_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( CM_PRECHECKOUT_FILE, array( $this, 'deactivate' ) );

        // Clear cache on settings update
        add_action( 'update_option_cm_precheckout_options', array( $this, 'clear_cache_on_settings_update' ), 10, 2 );

        // hook after plugin init
        do_action( 'Cm_Precheckout/After_Init' );
    }


    /**
     * Define plugin constants used across modules.
     *
     * @since 1.0.0
     * @return void
     */
    private function define_constants() {
        $base_file = dirname( __DIR__, 2 ) . '/cm-precheckout.php';
        $base_dir = plugin_dir_path( $base_file );
        $base_url = plugin_dir_url( $base_file );

        $constants = array(
            'CM_PRECHECKOUT_BASENAME'   => plugin_basename( $base_file ),
            'CM_PRECHECKOUT_FILE'       => $base_file,
            'CM_PRECHECKOUT_PATH'       => $base_dir,
            'CM_PRECHECKOUT_INC_PATH'   => $base_dir . 'inc/',
            'CM_PRECHECKOUT_URL'        => $base_url,
            'CM_PRECHECKOUT_ASSETS'     => $base_url . 'assets/',
            'CM_PRECHECKOUT_ABSPATH'    => dirname( $base_file ) . '/',
            'CM_PRECHECKOUT_SLUG'       => self::SLUG,
            'CM_PRECHECKOUT_VERSION'    => self::VERSION,
            'CM_PRECHECKOUT_DEBUG_MODE' => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'CM_PRECHECKOUT_DEV_MODE'   => defined( 'CM_PRECHECKOUT_DEV_MODE' ) ? CM_PRECHECKOUT_DEV_MODE : false,
        );

        foreach ( $constants as $key => $value ) {
            if ( ! defined( $key ) ) {
                define( $key, $value );
            }
        }
    }


    /**
     * Check plugin dependencies
     * 
     * @since 1.0.0
     * @return void
     */
    private function check_dependencies() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }
    }


    /**
     * WooCommerce missing notice
     * 
     * @since 1.0.0
     * @return void
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                printf(
                    esc_html__( 'O plugin %1$s requer %2$s para funcionar. Por favor, instale e ative o WooCommerce.', 'cm-precheckout' ),
                    '<strong>Camila Maehler - Pré Checkout</strong>',
                    '<strong>WooCommerce</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }


    /**
     * Load plugin text domain
     * 
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'cm-precheckout', false, dirname( CM_PRECHECKOUT_BASENAME ) . '/languages' );
    }


    /**
     * Instance classes after load Composer
     * 
     * @since 1.0.0
     * @return void
     */
    public function instance_classes() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Get classmap from Composer
        $classmap_file = CM_PRECHECKOUT_PATH . 'vendor/composer/autoload_classmap.php';
        
        if ( ! file_exists( $classmap_file ) ) {
            return;
        }

        $classmap = include $classmap_file;

        // Ensure classmap is an array
        if ( ! is_array( $classmap ) ) {
            $classmap = array();
        }

        // Filter and instance classes
        $this->instance_filtered_classes( $classmap );
    }


    /**
     * Filter and instance classes
     * 
     * @since 1.0.0
     * @param array $classmap
     * @return void
     */
    private function instance_filtered_classes( $classmap ) {
        $filtered_classes = array_filter( $classmap, function( $class ) {
            // Skip if not in our namespace
            if ( strpos( $class, 'MeuMouse\\Cm_Precheckout\\' ) !== 0 ) {
                return false;
            }

            // Skip abstract classes, interfaces, traits and Plugin class itself
            if ( strpos( $class, 'Abstract' ) !== false ||
                 strpos( $class, 'Interface' ) !== false ||
                 strpos( $class, 'Trait' ) !== false ||
                 $class === 'MeuMouse\\Cm_Precheckout\\Core\\Plugin' ) {
                return false;
            }

            return class_exists( $class );
        } );

        foreach ( $filtered_classes as $class ) {
            $this->safe_instance_class( $class );
        }
    }


    /**
     * Safely instance a class
     * 
     * @since 1.0.0
     * @param string $class
     * @return void
     */
    private function safe_instance_class( $class ) {
        try {
            $reflection = new \ReflectionClass( $class );
            
            if ( ! $reflection->isInstantiable() ) {
                return;
            }

            $constructor = $reflection->getConstructor();
            
            // Only instance classes without required constructor parameters
            if ( $constructor && $constructor->getNumberOfRequiredParameters() > 0 ) {
                return;
            }

            $instance = new $class();
            
            // Call init method if exists
            if ( method_exists( $instance, 'init' ) ) {
                $instance->init();
            }
            
        } catch ( \Exception $e ) {
            if ( defined( 'CM_PRECHECKOUT_DEBUG_MODE' ) && CM_PRECHECKOUT_DEBUG_MODE ) {
                error_log( 'CM Pré-Checkout: Error instancing class ' . $class . ' - ' . $e->getMessage() );
            }
        }
    }


    /**
     * Setup HPOS compatibility.
     *
     * @since 1.0.0
     * @return void
     */
    public function setup_hpos_compatibility() {
        if ( class_exists( FeaturesUtil::class ) ) {
            FeaturesUtil::declare_compatibility( 'custom_order_tables', CM_PRECHECKOUT_FILE, true );
        }
    }


    /**
     * Plugin activation
     * 
     * @since 1.0.0
     * @return void
     */
    public function activate() {
        // Set default options if not exists
        $options = get_option( 'cm_precheckout_options', array() );
        
        if ( empty( $options ) ) {
            $default_options = array(
                'courses' => array(),
                'stones' => array(),
                'version' => self::VERSION,
            );
            
            update_option( 'cm_precheckout_options', $default_options );
        }

        // Create database tables if needed
        $this->create_tables();

        // Clear any transients
        $this->clear_transients();

        do_action( 'cm_precheckout_activated' );
    }


    /**
     * Plugin deactivation
     * 
     * @since 1.0.0
     * @return void
     */
    public function deactivate() {
        // Clear transients and cache
        $this->clear_transients();
        Utils::clear_cache();

        do_action( 'cm_precheckout_deactivated' );
    }


    /**
     * Create database tables
     * 
     * @since 1.0.0
     * @return void
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Example table for storing customizations (if needed)
        $table_name = $wpdb->prefix . 'cm_precheckout_customizations';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_item_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            customization_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_item_id (order_item_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }


    /**
     * Clear transients and cache
     * 
     * @since 1.0.0
     * @return void
     */
    private function clear_transients() {
        // Clear any plugin-specific transients
        $transients = array(
            'cm_precheckout_courses',
            'cm_precheckout_stones',
            'cm_precheckout_materials',
        );
        
        foreach ( $transients as $transient ) {
            delete_transient( $transient );
        }
        
        // Clear object cache
        wp_cache_delete( 'cm_precheckout_options', 'options' );
    }


    /**
     * Clear cache when settings are updated
     * 
     * @since 1.0.0
     * @param mixed $old_value
     * @param mixed $new_value
     * @return void
     */
    public function clear_cache_on_settings_update( $old_value, $new_value ) {
        Utils::clear_cache();
        $this->clear_transients();
    }


    /**
     * Cloning is forbidden
     *
     * @since 1.0.0
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Trapaceando?', 'cm-precheckout' ), '1.0.0' );
    }


    /**
     * Unserializing instances of this class is forbidden
     *
     * @since 1.0.0
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Trapaceando?', 'cm-precheckout' ), '1.0.0' );
    }
}