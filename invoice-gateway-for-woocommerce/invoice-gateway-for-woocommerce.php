<?php
/**
 * Plugin Name: Invoice Gateway For WooCommerce
 * Plugin URI: https://rymera.com.au/
 * Description: Provides an invoice gateway for WooCommerce so your customers can check out without immediate payment.
 * Version: 1.1.4.1
 * Author: Rymera Web Co
 * Author URI: https://rymera.com.au/
 * Requires at least: 5.0
 * Tested up to: 6.7
 * WC requires at least: 5.2
 * WC tested up to: 9.8
 *
 * Text Domain: invoice-gateway-for-woocommerce
 * Domain Path: /languages/
 *
 * @package  IGFW
 * @category Core
 * @author   Rymera Web Co
 */

// Exit if accessed directly.
if (! defined('ABSPATH') ) {
    exit;
}

/**
 * Plugin file path.
 *
 * @since 1.1.3
 */
define('IGFW_PLUGIN_FILE', plugin_basename(__FILE__));

/**
 * Register plugin autoloader.
 *
 * @since 1.0.0
 *
 * @param string $class_name Name of the class to load.
 */
spl_autoload_register(
    function ( $class_name ) {
        // Only do autoload for our plugin files.
        if (strpos($class_name, 'IGFW\\') === 0 ) {
            $class_file = str_replace(array( '\\', 'IGFW' . DIRECTORY_SEPARATOR ), array( DIRECTORY_SEPARATOR, '' ), $class_name) . '.php';
            $file_path  = plugin_dir_path(__FILE__) . $class_file;

            if (file_exists($file_path) ) {
                include_once $file_path;
            }
        }
    }
);

require_once plugin_dir_path(__FILE__) . 'class-igfw.php';

/**
 * Returns the main instance of IGFW to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return IGFW\IGFW Main instance of the plugin.
 */
function igfw()
{
    return IGFW\IGFW::get_instance();
}

// Let's Roll!
$GLOBALS['IGFW'] = igfw();
