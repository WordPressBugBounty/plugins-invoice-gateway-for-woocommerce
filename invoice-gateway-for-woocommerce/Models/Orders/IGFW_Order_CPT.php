<?php
namespace IGFW\Models\Orders;

use IGFW\Abstracts\Abstract_Main_Plugin_Class;
use IGFW\Helpers\Helper_Functions;
use IGFW\Helpers\Plugin_Constants;
use IGFW\Interfaces\Model_Interface;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Model that houses the logic of wc order cpt.
 * Private Model.
 *
 * @since 1.0.0
 */
class IGFW_Order_CPT implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of Bootstrap.
     *
     * @since 1.0.0
     * @access private
     * @var Bootstrap
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.0.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.0.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

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

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;

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
     * @return Bootstrap
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {

        if ( ! self::$_instance instanceof self ) {
            self::$_instance = new self( $main_plugin, $constants, $helper_functions );
        }

        return self::$_instance;
    }

    /**
     * Add order invoice meta box.
     *
     * @since 1.0.0
     * @since 1.1.2 Added check for custom orders table usage.
     * @access public
     */
    public function add_order_invoice_meta_box() {
        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'igfw-order-invoice',
            __( 'Order Invoice', 'invoice-gateway-for-woocommerce' ),
            array( $this, 'view_order_invoice_meta_box' ),
            $screen,
            'side',
            'default'
        );
    }

    /**
     * Order invoice meta box.
     *
     * @since 1.0.0
     * @access public
     */
    public function view_order_invoice_meta_box() {

        include $this->_constants->VIEWS_ROOT_PATH() . 'order' . DIRECTORY_SEPARATOR . 'view-order-invoice-meta-box.php';
    }

    /**
     * Add invoice number field.
     *
     * @since 1.0.0
     * @since 1.1.2 added Order object as parameter as it is needed for custom orders table usage.
     * @access public
     */
    public function add_invoice_number_field() {
        global $theorder;

        if ( get_option( 'igfw_enable_purchase_order_number' ) == 'yes' ) {

            woocommerce_wp_text_input(
                array(
					'id'        => Plugin_Constants::Purchase_Order_Number,
					'style'     => 'width: 100%;',
					'label'     => __( 'Purchase Order Number', 'invoice-gateway-for-woocommerce' ),
					'type'      => 'text',
					'data_type' => 'text',
                ),
                $theorder
            );

        }

        woocommerce_wp_text_input(
            array(
				'id'          => Plugin_Constants::Invoice_Number,
				'style'       => 'width: 100%;',
				'label'       => __( 'Invoice Number', 'invoice-gateway-for-woocommerce' ),
				'description' => __( '<br>Enter the Invoice ID from your accounting system for tracking purposes', 'invoice-gateway-for-woocommerce' ),
				'type'        => 'text',
				'data_type'   => 'text',
            ),
            $theorder
        );

        wp_nonce_field( 'igfw_action_save_invoice_number', 'igfw_nonce_save_invoice_number' );
    }

    /**
     * Save invoice number.
     *
     * @since 1.0.0
     * @since 1.1.2 Add HPOS Compatibility.
     * @access public
     *
     * @param int           $order_id Order id. Id of the order.
     * @param WC_Order|null $order    Order object.
     */
    public function save_invoice_number( $order_id, $order = null ) {

        // Check nonce.
        if ( isset( $_POST['igfw_nonce_save_invoice_number'] ) && wp_verify_nonce( $_POST['igfw_nonce_save_invoice_number'], 'igfw_action_save_invoice_number' ) ) {

            if ( null === $order ) {
                $order = wc_get_order( $order_id );
            }

            $new_invoice_number      = isset( $_POST[ Plugin_Constants::Invoice_Number ] ) ?
                filter_var(
                    trim(
                        sanitize_text_field(
                            $_POST[ Plugin_Constants::Invoice_Number ]
                        )
                    ),
                    FILTER_SANITIZE_STRING
                ) :
                '';
            $existing_invoice_number = $order->get_meta( Plugin_Constants::Invoice_Number, true );

            $this->_log_invoice_number_activity( $new_invoice_number, $existing_invoice_number, $order_id );

            $order->update_meta_data( Plugin_Constants::Invoice_Number, $new_invoice_number );

            if ( isset( $_POST['igfw_purchase_order_number'] ) ) {

                $new_invoice_number      = isset( $_POST[ Plugin_Constants::Purchase_Order_Number ] ) ?
                    filter_var(
                        trim(
                            sanitize_text_field( $_POST[ Plugin_Constants::Purchase_Order_Number ] )
                        ),
                        FILTER_SANITIZE_STRING
                    ) :
                    '';
                $existing_invoice_number = $order->get_meta( Plugin_Constants::Purchase_Order_Number, true );

                $this->_log_invoice_number_activity( $new_invoice_number, $existing_invoice_number, $order_id, 'purchase order number' );

                $order->update_meta_data( Plugin_Constants::Purchase_Order_Number, $new_invoice_number );

            }
        }
    }

    /**
     * Log invoice number activity.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $new_invoice_number      New invoice number.
     * @param string $existing_invoice_number Current invoice number.
     * @param int    $post_id                 Post (Order) id.
     */
    private function _log_invoice_number_activity( $new_invoice_number, $existing_invoice_number, $post_id, $type = 'invoice number' ) {

        if ( $new_invoice_number == $existing_invoice_number ) {
            return;
        }

        $order = wc_get_order( $post_id );
        $user  = wp_get_current_user();

        if ( is_a( $order, 'WC_Order' ) ) {

            if ( $new_invoice_number != '' && $existing_invoice_number == '' ) {
                $order->add_order_note( sprintf( __( '%1$s added %2$s %3$s.', 'invoice-gateway-for-woocommerce' ), $user->display_name, $type, $new_invoice_number ) );
            } elseif ( $new_invoice_number == '' && $existing_invoice_number != '' ) {
                $order->add_order_note( sprintf( __( '%1$s removed %2$s %3$s.', 'invoice-gateway-for-woocommerce' ), $user->display_name, $type, $existing_invoice_number ) );
            } elseif ( $new_invoice_number != $existing_invoice_number ) {
                $order->add_order_note( sprintf( __( '%1$s updated %2$s from %3$s to %4$s.', 'invoice-gateway-for-woocommerce' ), $user->display_name, $type, $existing_invoice_number, $new_invoice_number ) );
            }
        }
    }

    /**
     * Add Purchase Order Number to checkout posted data.
     *
     * @since 1.1.2
     * @access public
     *
     * @param array $data Posted data.
     */
    public function add_purchase_number_number_to_checkout_posted_data( $data ) {
        // phpcs:disable WordPress.Security.NonceVerification
        if ( get_option( 'igfw_enable_purchase_order_number' ) === 'yes' &&
            ( isset( $data['payment_method'] ) && 'igfw_invoice_gateway' === $data['payment_method'] ) &&
            ( isset( $_REQUEST[ Plugin_Constants::Purchase_Order_Number ] ) && ! empty( $_REQUEST[ Plugin_Constants::Purchase_Order_Number ] ) )
        ) {
            $data[ Plugin_Constants::Purchase_Order_Number ] = sanitize_text_field( $_REQUEST[ Plugin_Constants::Purchase_Order_Number ] );
        }
        // phpcs:enable WordPress.Security.NonceVerification
        return $data;
    }

    /**
     * Save Purchase Order Number after order is processed.
     *
     * @since 1.1.2
     * @access public
     *
     * @param WC_Order $order WC_Order Object.
     * @param array    $data  Posted data.
     */
    public function maybe_save_purchase_number_number_on_checkout( $order, $data ) {
        if ( isset( $data[ Plugin_Constants::Purchase_Order_Number ] ) && ! empty( $data[ Plugin_Constants::Purchase_Order_Number ] ) ) {
            $order->update_meta_data( Plugin_Constants::Purchase_Order_Number, $data[ Plugin_Constants::Purchase_Order_Number ] );
        }
    }

    /**
     * Show invoice payment gateway on free orders.
     *
     * @since 1.1.3
     * @access public
     *
     * @param bool    $needs_payment Returns bolean value if the order needs payment.
     * @param WC_Cart $cart          WC Cart object.
     */
    public function show_invoice_payment_gateway_on_free_orders( $needs_payment, $cart ) {

        $enabled_payment_gateways = WC()->payment_gateways->get_available_payment_gateways();

        if ( ( isset( $enabled_payment_gateways['igfw_invoice_gateway'] ) &&
            $cart->get_total( 'edit' ) == 0 ) && // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual 
            apply_filters( 'igfw_show_invoice_gateway_on_free_orders', true )
        ) {
            WC()->payment_gateways->payment_gateways                         = array();
            WC()->payment_gateways->payment_gateways['igfw_invoice_gateway'] = $enabled_payment_gateways['igfw_invoice_gateway'];

            $needs_payment = true;
        }
        return $needs_payment;
    }

    /**
     * Execute url coupon model.
     *
     * @inherit IGFW\Interfaces\Model_Interface
     *
     * @since 1.0.0
     * @access public
     */
    public function run() {

        add_action( 'add_meta_boxes', array( $this, 'add_order_invoice_meta_box' ) );
        add_action( 'igfw_invoice_gateway_meta_box', array( $this, 'add_invoice_number_field' ) );
        add_action( 'woocommerce_new_order', array( $this, 'save_invoice_number' ), 10, 2 );
        add_action( 'woocommerce_update_order', array( $this, 'save_invoice_number' ), 10, 2 );

        // Order Processed.
        add_filter( 'woocommerce_checkout_posted_data', array( $this, 'add_purchase_number_number_to_checkout_posted_data' ), 10, 1 );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'maybe_save_purchase_number_number_on_checkout' ), 10, 2 );

        add_filter( 'woocommerce_cart_needs_payment', array( $this, 'show_invoice_payment_gateway_on_free_orders' ), 10, 2 );
    }

}
