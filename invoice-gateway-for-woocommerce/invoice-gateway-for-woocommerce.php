<?php
/**
 * Plugin Name: Invoice Gateway For WooCommerce
 * Plugin URI: https://rymera.com.au/
 * Description: Provides an invoice gateway for WooCommerce so your customers can check out without immediate payment.
 * Version: 1.1.3
 * Author: Rymera Web Co
 * Author URI: https://rymera.com.au/
 * Requires at least: 5.0
 * Tested up to: 6.3.1
 * WC requires at least: 5.2
 * WC tested up to: 8.1.1
 *
 * Text Domain: invoice-gateway-for-woocommerce
 * Domain Path: /languages/
 *
 * @package  IGFW
 * @category Core
 * @author   Rymera Web Co
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin file path.
 *
 * @since 1.1.3
 */
define( 'IGFW_PLUGIN_FILE', plugin_basename( __FILE__ ) );

use IGFW\Abstracts\Abstract_Main_Plugin_Class;

use IGFW\Interfaces\Model_Interface;

use IGFW\Helpers\Plugin_Constants;
use IGFW\Helpers\Helper_Functions;

use IGFW\Models\Bootstrap;
use IGFW\Models\Script_Loader;
use IGFW\Models\Orders\IGFW_Order_CPT;
use IGFW\Models\Orders\IGFW_Order_Email;

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
        if ( strpos( $class_name, 'IGFW\\' ) === 0 ) {
            $class_file = str_replace( array( '\\', 'IGFW' . DIRECTORY_SEPARATOR ), array( DIRECTORY_SEPARATOR, '' ), $class_name ) . '.php';
            include_once plugin_dir_path( __FILE__ ) . $class_file;
        }
    }
);

/**
 * The main plugin class.
 */
class IGFW extends Abstract_Main_Plugin_Class {


    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Single main instance of Plugin IGFW plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    IGFW
     */
    private static $_instance;

    /**
     * Array of missing external plugins that this plugin is depends on.
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private $_failed_dependencies;




    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * IGFW constructor.
     *
     * @since  1.0.0
     * @access public
     */
    public function __construct() {
        if ( $this->_check_plugin_dependencies() !== true ) {

            // Display notice that plugin dependency is not present.
            add_action( 'admin_notices', array( $this, 'missing_plugin_dependencies_notice' ) );

        } elseif ( $this->_check_plugin_dependency_version_requirements() !== true ) {

            // Display notice that some dependent plugin did not meet the required version.
            add_action( 'admin_notices', array( $this, 'invalid_plugin_dependency_version_notice' ) );

        } else {

            // Lock 'n Load
            $this->_initialize_plugin_components();
            $this->_run_plugin();

        }
    }

    /**
     * Ensure that only one instance of Invoice Gateway For WooCommerce is loaded or can be loaded (Singleton Pattern).
     *
     * @since  1.0.0
     * @access public
     *
     * @return IGFW
     */
    public static function get_instance() {
        if ( ! self::$_instance instanceof self ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Check for external plugin dependencies.
     *
     * @since  1.0.0
     * @access private
     *
     * @return mixed Array if there are missing plugin dependencies, True if all plugin dependencies are present.
     */
    private function _check_plugin_dependencies() {
        // Makes sure the plugin is defined before trying to use it
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->failed_dependencies = array();

        if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

            $this->failed_dependencies[] = array(
                'plugin-key'       => 'woocommerce',
                'plugin-name'      => 'WooCommerce', // We don't translate this coz this is the plugin name
                'plugin-base-name' => 'woocommerce/woocommerce.php',
            );

        }

        return ! empty( $this->failed_dependencies ) ? $this->failed_dependencies : true;
    }

    /**
     * Check plugin dependency version requirements.
     *
     * @since  1.0.0
     * @access private
     *
     * @return boolean True if plugin dependency version requirement is meet, False otherwise.
     */
    private function _check_plugin_dependency_version_requirements() {
        return true;
    }

    /**
     * Add notice to notify users that some plugin dependencies of this plugin is missing.
     *
     * @since  1.0.0
     * @access public
     */
    public function missing_plugin_dependencies_notice() {
        if ( ! empty( $this->failed_dependencies ) ) {

            $admin_notice_msg = '';

            foreach ( $this->failed_dependencies as $failed_dependency ) {

                $failed_dep_plugin_file = trailingslashit( WP_PLUGIN_DIR ) . plugin_basename( $failed_dependency['plugin-base-name'] );

                if ( file_exists( $failed_dep_plugin_file ) ) {
                    $failed_dep_install_text = '<a href="' . wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . esc_url( $failed_dependency['plugin-base-name'] ) . '&amp;plugin_status=all&amp;s', 'activate-plugin_' . esc_url( $failed_dependency['plugin-base-name'] ) ) . '" title="' . __( 'Activate this plugin', 'invoice-gateway-for-woocommerce' ) . '" class="edit">' . __( 'Click here to activate &rarr;', 'invoice-gateway-for-woocommerce' ) . '</a>';
                } else {
                    $failed_dep_install_text = '<a href="' . wp_nonce_url( 'update.php?action=install-plugin&amp;plugin=' . esc_url( $failed_dependency['plugin-key'] ), 'install-plugin_' . esc_url( $failed_dependency['plugin-key'] ) ) . '" title="' . __( 'Install this plugin', 'invoice-gateway-for-woocommerce' ) . '">' . __( 'Click here to install from WordPress.org repo &rarr;', 'invoice-gateway-for-woocommerce' ) . '</a>';
                }

                $admin_notice_msg .= sprintf( __( '<br/>Please ensure you have the <a href="%1$s" target="_blank">%2$s</a> plugin installed and activated.<br/>', 'invoice-gateway-for-woocommerce' ), 'http://wordpress.org/plugins/' . esc_url( $failed_dependency['plugin-key'] ) . '/', esc_url( $failed_dependency['plugin-name'] ) );
                $admin_notice_msg .= $failed_dep_install_text . '<br/>';

            } ?>

            <div class="notice notice-error">
                <p>
                    <?php _e( '<b>Invoice Gateway for WooCommerce</b> plugin missing dependency.<br/>', 'invoice-gateway-for-woocommerce' ); ?>
                    <?php echo $admin_notice_msg; ?>
                </p>
            </div>

            <?php
        }
    }

    /**
     * Add notice to notify user that some plugin dependencies did not meet the required version for the current version of this plugin.
     *
     * @since  1.0.0
     * @access public
     */
    public function invalid_plugin_dependency_version_notice() {
        // Notice message here...
    }

    /**
     * Initialize plugin components.
     *
     * @since  1.0.0
     * @access private
     */
    private function _initialize_plugin_components() {
        $plugin_constants = Plugin_Constants::get_instance();
        $helper_functions = Helper_Functions::get_instance( $plugin_constants );

        Bootstrap::get_instance( $this, $plugin_constants, $helper_functions );
        Script_Loader::get_instance( $this, $plugin_constants, $helper_functions );
        IGFW_Order_CPT::get_instance( $this, $plugin_constants, $helper_functions );
        IGFW_Order_Email::get_instance( $this, $plugin_constants, $helper_functions );
    }

    /**
     * Run the plugin. ( Runs the various plugin components ).
     *
     * @since  1.0.0
     * @access private
     */
    private function _run_plugin() {
        foreach ( $this->__all_models as $model ) {
            if ( $model instanceof Model_Interface ) {
                $model->run();
            }
        }
    }

}

/**
 * Returns the main instance of IGFW to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return IGFW Main instance of the plugin.
 */
function IGFW() {
    return IGFW::get_instance();
}

// Let's Roll!
$GLOBALS['IGFW'] = IGFW();
