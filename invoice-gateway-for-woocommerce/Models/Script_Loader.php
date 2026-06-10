<?php
/**
 * Script loader model for handling plugin assets.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Models
 * @since 1.0.0
 */

namespace IGFW\Models;

use IGFW\Abstracts\Abstract_Main_Plugin_Class;

use IGFW\Interfaces\Model_Interface;

use IGFW\Helpers\Plugin_Constants;
use IGFW\Helpers\Helper_Functions;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Model that houses the logic of loading plugin scripts.
 * Private Model.
 *
 * @since 1.0.0
 */
class Script_Loader implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Script_Loader.
     *
     * @since 1.0.0
     * @access private
     * @var Script_Loader
     */
    private static $instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.0.0
     * @access private
     * @var Plugin_Constants
     */
    private $constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.0.0
     * @access private
     * @var Helper_Functions
     */
    private $helper_functions;

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Class constructor.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {

        $this->constants        = $constants;
        $this->helper_functions = $helper_functions;

        $main_plugin->add_to_all_plugin_models( $this );
    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Script_Loader
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {

        if ( ! self::$instance instanceof self ) {
            self::$instance = new self( $main_plugin, $constants, $helper_functions );
        }

        return self::$instance;
    }

    /**
     * Load backend js and css scripts.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $handle Hook suffix for the current admin page.
     */
    public function load_backend_scripts( $handle ) {

        global $typenow;

        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        if ( ( ( 'post-new.php' === $handle || 'post.php' === $handle ) && $typenow === $screen ) ||
            ( $handle === $screen && isset( $_GET['action'] ) && ( 'new' === $_GET['action'] || 'edit' === $_GET['action'] ) )
        ) {
            wp_enqueue_style( 'igfw_wc-order_css', $this->constants->css_root_url() . 'order/wc-order.css', array(), Plugin_Constants::VERSION, 'all' );
        }

        $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
        $tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';

        if ( 'wc-settings' === $page && 'igfw_settings' === $tab ) {
            wp_register_script(
                'igfw_plugin_installer',
                $this->constants->build_dir_url() . 'plugin-installer.js',
                array( 'jquery' ),
                Plugin_Constants::VERSION,
                true
            );

            wp_localize_script(
                'igfw_plugin_installer',
                'igfw_plugin_installer',
                array(
                    'ajax_url'              => admin_url( 'admin-ajax.php' ),
                    'nonce'                 => wp_create_nonce( 'igfw_install_plugin' ),
                    'install_error_message' => __( 'An error occurred. Please try again or install the plugin manually.', 'invoice-gateway-for-woocommerce' ),
                    'plugin_activated_text' => __( 'Activate', 'invoice-gateway-for-woocommerce' ),
                    'installing_text'       => __( 'Installing...', 'invoice-gateway-for-woocommerce' ),
                    'go_to_settings_text'   => __( 'Go to Settings', 'invoice-gateway-for-woocommerce' ),
                )
            );

            wp_enqueue_script( 'igfw_plugin_installer' );

            wp_enqueue_style(
                'igfw_plugin_installer',
                $this->constants->build_dir_url() . 'settings.css',
                array(),
                Plugin_Constants::VERSION,
                'all'
            );
        }
    }

    /**
     * Load frontend js and css scripts.
     *
     * @since 1.0.0
     * @since 1.1.4 - Add support for WooCommerce Blocks.
     * @access public
     */
    public function load_frontend_scripts() {
        global $post, $wp;

        // Check if WooCommerce Blocks is active.
        $blocks_support = class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' );

        if ( ! $blocks_support ) {
            return;
        }

        // Get invoice gateway settings.
        $settings = get_option( 'woocommerce_igfw_invoice_gateway_settings', array() );

        // Only proceed if the gateway is enabled.
        if ( empty( $settings['enabled'] ) || 'yes' !== $settings['enabled'] ) {
            return;
        }

        // Better detection for checkout pages - check for is_checkout() and for block content.
        $is_checkout_page = is_checkout() || (
            is_a( $post, 'WP_Post' ) &&
            ( strpos( $post->post_content, 'wp:woocommerce/checkout' ) !== false ||
            strpos( $post->post_content, 'woocommerce/checkout' ) !== false )
        );

        if ( ! $is_checkout_page ) {
            return;
        }

        // Get the build directory asset info.
        $asset_file = $this->constants->plugin_dir_path() . 'build/index.asset.php';
        $asset_data = file_exists( $asset_file )
            ? require $asset_file
            : array(
                'dependencies' => array( 'wp-element', 'wp-i18n', 'wp-html-entities', 'wc-blocks-registry', 'wc-settings' ),
                'version'      => Plugin_Constants::VERSION,
            );

        // Register our script with the proper dependencies from the asset file.
        wp_register_script(
            'igfw-blocks-integration',
            $this->constants->js_root_url() . '../build/index.js',
            array_merge(
                $asset_data['dependencies'],
                array( 'wc-blocks-registry', 'wc-settings', 'wc-blocks-checkout' )
            ),
            $asset_data['version'],
            true
        );

        // Register the style.
        wp_register_style(
            'igfw-blocks-style',
            $this->constants->css_root_url() . '../build/style-index.css',
            array(),
            $asset_data['version']
        );

        wp_localize_script(
            'igfw-blocks-integration',
            'igfw_invoice_gateway',
            array(
                'title'                            => isset( $settings['title'] ) ? $settings['title'] : __( 'Invoice Payment', 'invoice-gateway-for-woocommerce' ),
                'description'                      => isset( $settings['description'] ) ? $settings['description'] : __( 'Pay with an invoice processed through our accounting system.', 'invoice-gateway-for-woocommerce' ),
                'supports'                         => array( 'products', 'shipping_address' ),
                'enableForMethods'                 => isset( $settings['enable_for_methods'] ) ? $settings['enable_for_methods'] : array(),
                'enableForVirtual'                 => isset( $settings['enable_for_virtual'] ) && 'yes' === $settings['enable_for_virtual'],
                'enablePurchaseOrderNumber'        => 'yes' === get_option( 'igfw_enable_purchase_order_number', 'no' ),
                'requirePurchaseOrderNumber'       => Helper_Functions::is_purchase_order_number_required(),
                'purchaseOrderNumberTitle'         => Helper_Functions::get_purchase_order_number_title(),
                'purchaseOrderNumberPlaceholder'   => apply_filters( 'igfw_purchase_order_number_placeholder', __( 'PO Number', 'invoice-gateway-for-woocommerce' ) ),
                'purchaseOrderNumberDesc'          => apply_filters( 'igfw_purchase_order_number_desc', __( 'We will generate and send you an invoice for your order, if you have a PO number, please enter it.', 'invoice-gateway-for-woocommerce' ) ),
                'purchaseOrderNumberRequiredError' => Helper_Functions::get_purchase_order_number_required_error(),
            )
        );

        // Enqueue both script and style.
        wp_enqueue_script( 'igfw-blocks-integration' );
        wp_enqueue_style( 'igfw-blocks-style' );
    }

    /**
     * Execute plugin script loader.
     *
     * @since 1.0.0
     * @access public
     */
    public function run() {
        add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_scripts' ), 10, 1 );
        add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts' ) );
    }
}
