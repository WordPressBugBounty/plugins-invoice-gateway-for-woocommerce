<?php
/**
 * Main plugin class file.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @since 1.0.0
 * @since 1.1.4 - Applied PHPCS Rules. Compatibility for PHP 8.2+
 */

namespace IGFW;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use IGFW\Abstracts\Abstract_Main_Plugin_Class;

use IGFW\Interfaces\Model_Interface;

use IGFW\Helpers\Plugin_Constants;
use IGFW\Helpers\Helper_Functions;
use IGFW\Helpers\Plugin_Installer;

use IGFW\Models\Bootstrap;
use IGFW\Models\Script_Loader;
use IGFW\Models\Orders\IGFW_Order_CPT;
use IGFW\Models\Orders\IGFW_Order_Email;
use IGFW\Models\Blocks\IGFW_Gateway_Blocks_Support;
use IGFW\Models\WPAY_Pointer;
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
    private static $instance;

    /**
     * Array of missing external plugins that this plugin is depends on.
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    protected $failed_dependencies = array();

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * IGFW Constructor.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        // Set plugin constants.
        $this->all_models = array();

        // Check if dependencies are met before proceeding.
        $dependencies_result = $this->check_plugin_dependencies();

        if ( true !== $dependencies_result ) {
            add_action( 'admin_notices', array( $this, 'missing_plugin_dependencies_notice' ) );
        } elseif ( ! $this->check_plugin_dependency_version_requirements() ) {
            add_action( 'admin_notices', array( $this, 'invalid_plugin_dependency_version_notice' ) );
            return;
        } else {
            // Initialize plugin components.
            $this->initialize_plugin_components();

            // Run the plugin.
            $this->run_plugin();
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
        if ( ! self::$instance instanceof self ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Check plugin dependencies. If dependencies are not met, the plugin won't activate.
     *
     * @since  1.0.0
     * @access private
     *
     * @return mixed Array if there are missing plugin dependencies, True if all plugin dependencies are present.
     */
    private function check_plugin_dependencies() {
        // Makes sure the plugin is defined before trying to use it.
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->failed_dependencies = array();

        if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

            $this->failed_dependencies[] = array(
                'plugin-key'       => 'woocommerce',
                'plugin-name'      => 'WooCommerce', // We don't translate this coz this is the plugin name.
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
    private function check_plugin_dependency_version_requirements() {
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

                $admin_notice_msg .= sprintf(
                    /* translators: %1$s: Plugin URL, %2$s: Plugin name */
                    __( '<br/>Please ensure you have the <a href="%1$s" target="_blank">%2$s</a> plugin installed and activated.<br/>', 'invoice-gateway-for-woocommerce' ),
                    'http://wordpress.org/plugins/' . esc_url( $failed_dependency['plugin-key'] ) . '/',
                    esc_url( $failed_dependency['plugin-name'] )
                );
                $admin_notice_msg .= $failed_dep_install_text . '<br/>';

            } ?>

            <div class="notice notice-error">
                <p>
                    <?php esc_html_e( '<b>Invoice Gateway for WooCommerce</b> plugin missing dependency.<br/>', 'invoice-gateway-for-woocommerce' ); ?>
                    <?php echo wp_kses_post( $admin_notice_msg ); ?>
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
     * @since 1.1.4 - Added Checkout Blocks Support. Added Plugin Installer.
     * @access private
     */
    private function initialize_plugin_components() {
        $plugin_constants = Plugin_Constants::get_instance();
        $helper_functions = Helper_Functions::get_instance( $plugin_constants );

        Bootstrap::get_instance( $this, $plugin_constants, $helper_functions );
        Script_Loader::get_instance( $this, $plugin_constants, $helper_functions );
        IGFW_Order_CPT::get_instance( $this, $plugin_constants, $helper_functions );
        IGFW_Order_Email::get_instance( $this, $plugin_constants, $helper_functions );
        IGFW_Gateway_Blocks_Support::get_instance( $this, $plugin_constants );
        Plugin_Installer::get_instance();
        WPAY_Pointer::get_instance( $this, $plugin_constants, $helper_functions );
    }

    /**
     * Run the plugin. ( Runs the various plugin components ).
     *
     * @since  1.0.0
     * @access private
     */
    private function run_plugin() {
        foreach ( $this->all_models as $model ) {
            if ( $model instanceof Model_Interface ) {
                $model->run();
            }
        }
    }

    /**
     * Install plugin models.
     *
     * @since  1.0.0
     * @access private
     *
     * @param Plugin_Constants $plugin_constants  Plugin constants.
     * @param Helper_Functions $helper_functions  Helper functions.
     */
    private function install_plugin_models( $plugin_constants, $helper_functions ) {
        // Install plugin models implementation.
    }
}
