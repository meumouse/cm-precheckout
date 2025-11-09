<?php

/**
 * Plugin Name: 			CM Pré-checkout
 * Description: 			Extensão que adiciona funcionalidades ao processo de compras no WooCommerce.
 * Requires Plugins: 		woocommerce
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/?utm_source=wordpress&utm_medium=plugins_list&utm_campaign=cm_precheckout
 * Version: 				1.0.0
 * WC requires at least: 	10.0.0
 * WC tested up to: 		10.3.4
 * Requires PHP: 			7.4
 * Tested up to:      		6.8.3
 * Text Domain: 			cm-precheckout
 * Domain Path: 			/languages
 * 
 * @author					MeuMouse.com
 * @copyright 				2025 MeuMouse.com
 * @license 				Proprietary - See license.md for details
 */

namespace MeuMouse\Cm_Precheckout;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cm_Precheckout
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Cm_Precheckout {

	/**
	 * The single instance of Cm_Precheckout class
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Plugin slug
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $slug = 'cm-precheckout';

	/**
	 * Plugin version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $version = '1.0.0';

	/**
	 * Plugin initiated
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $initiated = false;

	/**
	 * Construct the plugin
	 * 
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function __construct() {
		// hook before plugin init
		do_action('Cm_Precheckout/Before_Init');

		// initialize plugin
		add_action( 'init', array( $this, 'init' ), 99 );

		// set compatibility with HPOS
		add_action( 'before_woocommerce_init', array( $this, 'declare_woo_compatibility' ) );
	}


	/**
     * Setup WooCommerce High-Performance Order Storage (HPOS) compatibility
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function declare_woo_compatibility() {
        if ( defined('WC_VERSION') && version_compare( WC_VERSION, '7.1', '>' ) ) {
			if ( class_exists( FeaturesUtil::class ) ) {
				/**
				 * Setup compatibility with HPOS/Custom order table feature of WooCommerce
				 * 
				 * @since 1.0.0
				 */
				FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );

				/**
				 * Display incompatible notice with WooCommerce checkout blocks
				 * 
				 * @since 3.8.0
				 */
				FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
			}
		}
    }


	/**
	 * Checker dependencies before activate plugin
	 * 
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function init() {
		// define constants
		$this->setup_constants();

		// load Composer
		require_once CM_PRECHECKOUT_PATH . 'vendor/autoload.php';

		// initialize classes
		new \MeuMouse\Cm_Precheckout\Core\Init;
	}


	/**
	 * Define constants
	 * 
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function setup_constants() {
		$base_file = __FILE__;
		$base_dir = plugin_dir_path( $base_file );
		$base_url = plugin_dir_url( $base_file );

		$constants = array(
			'CM_PRECHECKOUT_BASENAME' => plugin_basename( $base_file ),
			'CM_PRECHECKOUT_FILE' => $base_file,
			'CM_PRECHECKOUT_PATH' => $base_dir,
			'CM_PRECHECKOUT_INC_PATH' => $base_dir . 'inc/',
			'CM_PRECHECKOUT_URL' => $base_url,
			'CM_PRECHECKOUT_ASSETS' => $base_url . 'assets/',
			'CM_PRECHECKOUT_ABSPATH' => dirname( $base_file ) . '/',
			'CM_PRECHECKOUT_SETTINGS_TABS_DIR' => $base_dir . 'inc/Views/Settings/Tabs/',
			'CM_PRECHECKOUT_SLUG' => self::$slug,
			'CM_PRECHECKOUT_VERSION' => self::$version,
			'CM_PRECHECKOUT_ADMIN_EMAIL' => get_option('admin_email'),
			'CM_PRECHECKOUT_DEV_MODE' => true,
		);

		// iterate for each constant item
		foreach ( $constants as $key => $value ) {
			if ( ! defined( $key ) ) {
				define( $key, $value );
			}
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


	/**
	 * Ensures only one instance of Cm_Precheckout class is loaded or can be loaded
	 *
	 * @since 1.0.0
	 * @return object | Cm_Precheckout instance
	 */
	public static function run() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
}

/**
 * Initialise the plugin
 * 
 * @since 1.0.0
 * @return object Cm_Precheckout
 */
Cm_Precheckout::run();