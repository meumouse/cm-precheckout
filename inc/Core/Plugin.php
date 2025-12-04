<?php

namespace MeuMouse\Cm_Precheckout\Core;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

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
     * Initialize the plugin.
     *
     * @since 1.0.0
     * @return void
     */
    public function init() {
        // hook before plugin init
		do_action('Cm_Precheckout/Before_Init');

        $this->define_constants();

        // Load text domain
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // set compatibility with WooCommerce HPOS (High-Performance Order Storage)
        add_action( 'before_woocommerce_init', array( $this, 'setup_hpos_compatibility' ) );

        $this->instance_classes();

        // hook before plugin init
		do_action('Cm_Precheckout/After_Init');
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
            'CM_PRECHECKOUT_DEBUG_MODE' => false,
            'CM_PRECHECKOUT_DEV_MODE'   => false,
        );

        foreach ( $constants as $key => $value ) {
            if ( ! defined( $key ) ) {
                define( $key, $value );
            }
        }
    }


    /**
     * Load plugin text domain
     * 
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'cm-precheckout', false,  dirname( CM_PRECHECKOUT_BASENAME ) . '/languages' );
    }


    /**
     * Instance classes after load Composer
     * 
     * @since 1.0.0
     * @return void
     */
    public function instance_classes() {
        if ( ! class_exists('WooCommerce') ) {
            return;
        }

        // get classmap from Composer
        $classmap = include_once CM_PRECHECKOUT_PATH . 'vendor/composer/autoload_classmap.php';

        // ensure classmap is an array
        if ( ! is_array( $classmap ) ) {
            $classmap = array();
        }

        // iterate through classmap and instance classes
        foreach ( $classmap as $class => $path ) {
            // skip classes not in the plugin namespace
            if ( strpos( $class, 'MeuMouse\\Cm_Precheckout\\' ) !== 0 ) {
                continue;
            }

            // skip the Init class to prevent duplicate instances
            if ( strpos( $class, 'MeuMouse\\Cm_Precheckout\\Core\\Plugin' ) !== false ) {
                continue;
            }

            // skip specific utility classes
            if ( $class === 'Composer\\InstalledVersions' ) {
                continue;
            }

            // check if class exists
            if ( ! class_exists( $class ) ) {
                continue;
            }

            // use ReflectionClass to check if class is instantiable
            $reflection = new \ReflectionClass( $class );

            // instance only if class is not abstract, trait or interface
            if ( ! $reflection->isInstantiable() ) {
                continue;
            }

            // check if class has a constructor
            $constructor = $reflection->getConstructor();

            // skip classes that require mandatory arguments in __construct
            if ( $constructor && $constructor->getNumberOfRequiredParameters() > 0 ) {
                continue;
            }

            // safe instance
            $instance = new $class();

            // this is useful for classes that need to run some initialization code
            if ( method_exists( $instance, 'init' ) ) {
                $instance->init();
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