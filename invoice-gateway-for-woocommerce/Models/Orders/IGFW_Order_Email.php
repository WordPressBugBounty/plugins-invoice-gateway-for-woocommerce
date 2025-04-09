<?php
/**
 * IGFW Order Email class file.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Models/Orders
 * @since 1.0.0
 * @since 1.1.4 - Applied PHPCS Rules. Compatibility for PHP 8.2+
 */

namespace IGFW\Models\Orders;

use IGFW\Abstracts\Abstract_Main_Plugin_Class;
use IGFW\Helpers\Helper_Functions;
use IGFW\Helpers\Plugin_Constants;
use IGFW\Interfaces\Model_Interface;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Model that houses the logic of order emails.
 * Public Model.
 *
 * @since 1.0.0
 */
class IGFW_Order_Email implements Model_Interface {

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
        $main_plugin->add_to_public_models( $this );
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

        if ( ! self::$instance instanceof self ) {
            self::$instance = new self( $main_plugin, $constants, $helper_functions );
        }

        return self::$instance;
    }

    /**
     * Add invoice note to admin new order email.
     *
     * @since 1.0.0
     * @since 1.0.1 WC 3.0: Update on how to get order id. Order properties should not be accessed directly.
     * @access public
     *
     * @param WC_Order $order         Order object.
     * @param Boolean  $sent_to_admin Flag that determines if sent to admin or not.
     * @param Boolean  $plain_text    Flag that determines if plain text email.
     * @param WC_Email $email         Email object.
     */
    public function add_invoice_note_to_admin_new_order_email( $order, $sent_to_admin, $plain_text, $email ) {

        if ( $email instanceof \WC_Email_New_Order && $order instanceof \WC_Order ) {

            if ( $order->get_payment_method() === 'igfw_invoice_gateway' ) {

                $invoice_number = $order->get_meta( Plugin_Constants::INVOICE_NUMBER, true );

                if ( '' !== $invoice_number ) {
                    if ( $plain_text ) {
                        printf( "\nInvoice Number: %s\n", esc_html( $invoice_number ) );
                    } else {
                        echo '<span style="color: red; font-weight: 600;"><p>' . sprintf( "\nInvoice Number: %s\n", esc_html( $invoice_number ) ) . '</p></span>';
                    }
                } elseif ( $plain_text ) {
                    // Translators: %1$s is the invoice number.
                    echo esc_html( sprintf( __( 'NOTE: This order requires an invoice. %1$s', 'invoice-gateway-for-woocommerce' ), $invoice_number ) );
                } else {
                    // Translators: %1$s is the invoice number.
                    echo esc_html( sprintf( __( 'NOTE: This order requires an invoice. %1$s', 'invoice-gateway-for-woocommerce' ), $invoice_number ) );
                }

                $po_number = $order->get_meta( Plugin_Constants::PURCHASE_ORDER_NUMBER, true );

                if ( '' !== $po_number && 'yes' === get_option( 'igfw_enable_purchase_order_number' ) ) {
                    if ( $plain_text ) {
                        printf( "\nPurchase Order Number: %s\n", esc_html( $po_number ) );
                    } else {
                        echo '<p>' . sprintf( "\nPurchase Order Number: %s\n", esc_html( $po_number ) ) . '</p>';
                    }
                }
            }
        }
    }

    /**
     * Add "paid by invoice" note on customer completed order email.
     *
     * @since 1.0.0
     * @since 1.0.1 WC 3.0: Update on how to get order id. Order properties should not be accessed directly.
     * @access public
     *
     * @param WC_Order $order         Order object.
     * @param Boolean  $sent_to_admin Flag that determines if sent to admin or not.
     * @param Boolean  $plain_text    Flag that determines if plain text email.
     * @param WC_Email $email         Email object.
     */
    public function add_paid_by_invoice_note_on_customer_completed_order_email( $order, $sent_to_admin, $plain_text, $email ) {

        if ( $email instanceof \WC_Email_Customer_Completed_Order && $order instanceof \WC_Order ) {

            if ( $order->get_payment_method() === 'igfw_invoice_gateway' ) {

                $invoice_number = $order->get_meta( Plugin_Constants::INVOICE_NUMBER, true );

                if ( '' !== $invoice_number ) {

                    if ( $plain_text ) {
                        // Translators: %1$s is the invoice number.
                        echo esc_html( sprintf( __( 'Paid via invoice number: %s', 'invoice-gateway-for-woocommerce' ), $invoice_number ) );
                    } else {
                        // Translators: %1$s is the invoice number.
                        echo esc_html( sprintf( __( '<br><p>Paid via invoice number: <b>%1$s</b></p>', 'invoice-gateway-for-woocommerce' ), $invoice_number ) );
                    }
                }

                $po_number = $order->get_meta( Plugin_Constants::PURCHASE_ORDER_NUMBER, true );

                if ( '' !== $po_number && 'yes' === get_option( 'igfw_enable_purchase_order_number' ) ) {

                    if ( $plain_text ) {
                        // Translators: %1$s is the purchase order number.
                        echo esc_html( sprintf( __( 'Purchase order number: %s', 'invoice-gateway-for-woocommerce' ), $po_number ) );
                    } else {
                        // Translators: %1$s is the purchase order number.
                        echo esc_html( sprintf( __( '<p>Purchase order number: <b>%1$s</b></p>', 'invoice-gateway-for-woocommerce' ), $po_number ) );
                    }
                }
            }
        }
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

        add_action( 'woocommerce_email_order_details', array( $this, 'add_invoice_note_to_admin_new_order_email' ), 9, 4 );
        add_filter( 'woocommerce_email_order_details', array( $this, 'add_paid_by_invoice_note_on_customer_completed_order_email' ), 9, 4 );
    }
}
