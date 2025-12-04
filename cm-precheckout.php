<?php

/**
 * Plugin Name: 			Camila Maehler - Pré Checkout
 * Description: 			Extensão que adiciona funcionalidades ao processo de compras no WooCommerce.
 * Requires Plugins: 		woocommerce
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/?utm_source=wordpress&utm_medium=plugins_list&utm_campaign=cm_precheckout
 * Version: 				1.0.0
 * WC requires at least: 	10.0.0
 * WC tested up to: 		10.3.5
 * Requires PHP: 			7.4
 * Tested up to:      		6.9
 * Text Domain: 			cm-precheckout
 * Domain Path: 			/languages
 * 
 * @author					MeuMouse.com
 * @copyright 				2025 MeuMouse.com
 * @license 				Proprietary - See license.md for details
 */

use MeuMouse\Cm_Precheckout\Core\Plugin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

$autoload = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

$plugin = new Plugin();
$plugin->init();