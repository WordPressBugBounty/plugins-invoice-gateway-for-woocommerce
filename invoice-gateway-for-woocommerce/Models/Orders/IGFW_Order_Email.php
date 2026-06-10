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
                        echo esc_html( sprintf( __( 'Paid via invoice number: %1$s', 'invoice-gateway-for-woocommerce' ), $invoice_number ) ) . "\n";
                    } else {
                        // Translators: %1$s is the invoice number.
                        echo wp_kses_post( sprintf( __( '<br><p>Paid via invoice number: <b>%1$s</b></p>', 'invoice-gateway-for-woocommerce' ), $invoice_number ) );
                    }
                }

                $po_number = $order->get_meta( Plugin_Constants::PURCHASE_ORDER_NUMBER, true );

                if ( '' !== $po_number && 'yes' === get_option( 'igfw_enable_purchase_order_number' ) ) {

                    if ( $plain_text ) {
                        // Translators: %1$s is the purchase order number.
                        echo esc_html( sprintf( __( 'Purchase order number: %1$s', 'invoice-gateway-for-woocommerce' ), $po_number ) ) . "\n";
                    } else {
                        // Translators: %1$s is the purchase order number.
                        echo wp_kses_post( sprintf( __( '<p>Purchase order number: <b>%1$s</b></p>', 'invoice-gateway-for-woocommerce' ), $po_number ) );
                    }
                }
            }
        }
    }

    /**
     * Append extra recipient(s) to the New Order email for invoice-gateway orders.
     *
     * Lets the merchant notify additional address(es) (e.g. a finance or shop
     * manager) whenever an order is paid via the invoice gateway, without
     * changing the global New Order recipient for every other order.
     *
     * @since 1.1.6
     * @access public
     *
     * @param string    $recipient Comma-separated recipient list.
     * @param \WC_Order $order     Order object, or null/non-order in the admin email-settings context.
     * @return string Recipient list, with the configured extra address(es) appended (de-duplicated) when applicable.
     */
    public function add_recipients_to_new_order_email( $recipient, $order ) {

        if ( ! $order instanceof \WC_Order || 'igfw_invoice_gateway' !== $order->get_payment_method() ) {
            return $recipient;
        }

        $additional = get_option( 'igfw_additional_new_order_recipients', '' );

        if ( '' === trim( (string) $additional ) ) {
            return $recipient;
        }

        $emails = array_filter( array_map( 'trim', explode( ',', $additional ) ), 'is_email' );

        if ( empty( $emails ) ) {
            return $recipient;
        }

        // Merge with the current recipient list and drop duplicates so an address
        // already on the New Order email isn't listed twice.
        $existing = '' !== (string) $recipient ? array_map( 'trim', explode( ',', (string) $recipient ) ) : array();

        return implode( ',', array_unique( array_merge( $existing, $emails ) ) );
    }

    /**
     * Append the purchase order number to the New Order email subject.
     *
     * Only affects the admin New Order email (the sole consumer of the
     * `woocommerce_email_subject_new_order` filter), and only when the order was
     * paid via the invoice gateway, the PO field is enabled, and a PO number is
     * present. In every other case the subject is returned unchanged.
     *
     * @since 1.1.6
     * @access public
     *
     * @param string    $subject Formatted email subject.
     * @param \WC_Order $order   Order object.
     * @return string Email subject, with the PO number appended when available.
     */
    public function add_po_number_to_new_order_email_subject( $subject, $order ) {

        if ( ! $order instanceof \WC_Order || 'igfw_invoice_gateway' !== $order->get_payment_method() ) {
            return $subject;
        }

        if ( 'yes' !== get_option( 'igfw_enable_purchase_order_number' ) ) {
            return $subject;
        }

        $po_number = $order->get_meta( Plugin_Constants::PURCHASE_ORDER_NUMBER, true );

        if ( '' === $po_number ) {
            return $subject;
        }

        $subject_format = apply_filters(
            'igfw_purchase_order_number_email_subject_label',
            // Translators: %1$s is the original email subject, %2$s is the purchase order number.
            __( '%1$s — PO: %2$s', 'invoice-gateway-for-woocommerce' )
        );

        return sprintf( $subject_format, $subject, $po_number );
    }

    /**
     * Render a Pay Now button on customer invoice/on-hold emails for payable invoice orders.
     *
     * Links to the native order-pay page (order-key gated) so the customer can
     * settle the order with another enabled gateway. Rendered only when the Pay
     * Now feature is on, the order is an invoice-gateway order that still needs
     * payment, and at least one non-invoice gateway is enabled — see
     * Helper_Functions::is_invoice_order_payable().
     *
     * @since 1.1.6
     * @access public
     *
     * @param WC_Order $order         Order object.
     * @param bool     $sent_to_admin Flag that determines if sent to admin or not.
     * @param bool     $plain_text    Flag that determines if plain text email.
     * @param WC_Email $email         Email object.
     */
    public function add_pay_now_button_to_customer_emails( $order, $sent_to_admin, $plain_text = false, $email = null ) {

        if ( $sent_to_admin ) {
            return;
        }

        if ( ! $email instanceof \WC_Email_Customer_Invoice && ! $email instanceof \WC_Email_Customer_On_Hold_Order ) {
            return;
        }

        if ( ! Helper_Functions::is_invoice_order_payable( $order ) ) {
            return;
        }

        $pay_url = $order->get_checkout_payment_url();
        $label   = Helper_Functions::get_pay_now_button_label();

        if ( $plain_text ) {
            printf( "%s: %s\n\n", esc_html( wp_strip_all_tags( $label ) ), esc_url( $pay_url ) );
            return;
        }
        ?>
        <p style="margin: 0 0 24px; text-align: center;">
            <a href="<?php echo esc_url( $pay_url ); ?>" style="display: inline-block; padding: 12px 28px; background-color: #7f54b3; border-radius: 4px; color: #ffffff; font-weight: 600; text-decoration: none;">
                <?php echo esc_html( $label ); ?>
            </a>
        </p>
        <?php
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
        add_filter( 'woocommerce_email_recipient_new_order', array( $this, 'add_recipients_to_new_order_email' ), 10, 2 );
        add_filter( 'woocommerce_email_subject_new_order', array( $this, 'add_po_number_to_new_order_email_subject' ), 10, 2 );
        add_action( 'woocommerce_email_before_order_table', array( $this, 'add_pay_now_button_to_customer_emails' ), 10, 4 );
    }
}
