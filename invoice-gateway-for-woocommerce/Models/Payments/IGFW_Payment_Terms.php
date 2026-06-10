<?php
/**
 * IGFW Payment Terms class file.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Models/Payments
 * @since 1.1.6
 */

namespace IGFW\Models\Payments;

use IGFW\Abstracts\Abstract_Main_Plugin_Class;
use IGFW\Helpers\Helper_Functions;
use IGFW\Helpers\Plugin_Constants;
use IGFW\Interfaces\Model_Interface;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Model that houses the payment terms (Net X due dates) and the merchant-facing
 * overdue notification for invoice-gateway orders.
 *
 * Customer-facing payment reminders are deliberately out of scope — they are
 * Wholesale Payments territory.
 *
 * @since 1.1.6
 */
class IGFW_Payment_Terms implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of IGFW_Payment_Terms.
     *
     * @since 1.1.6
     * @access private
     * @var IGFW_Payment_Terms
     */
    private static $instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.1.6
     * @access private
     * @var Plugin_Constants
     */
    private $constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.1.6
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
     * @since 1.1.6
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
     * @since 1.1.6
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return IGFW_Payment_Terms
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {

        if ( ! self::$instance instanceof self ) {
            self::$instance = new self( $main_plugin, $constants, $helper_functions );
        }

        return self::$instance;
    }

    /**
     * Stamp the payment due date when an invoice-gateway order enters the awaiting status.
     *
     * A single status-change hook covers the classic checkout, the Blocks/Store
     * API checkout, and admin-created orders. An existing due date is never
     * overwritten (e.g. when an order bounces back to the awaiting status).
     *
     * @since 1.1.6
     * @access public
     *
     * @param int       $order_id   Order ID.
     * @param string    $old_status Previous status (unprefixed).
     * @param string    $new_status New status (unprefixed).
     * @param \WC_Order $order      Order object.
     */
    public function maybe_stamp_due_date( $order_id, $old_status, $new_status, $order ) {

        if ( ! Helper_Functions::is_payment_terms_enabled() ) {
            return;
        }

        if ( ! $order instanceof \WC_Order ) {
            $order = wc_get_order( $order_id );
        }

        if ( ! $order instanceof \WC_Order || 'igfw_invoice_gateway' !== $order->get_payment_method() ) {
            return;
        }

        if ( get_option( 'igfw_default_order_status', 'on-hold' ) !== $new_status ) {
            return;
        }

        if ( '' !== $order->get_meta( Plugin_Constants::PAYMENT_DUE_DATE, true ) ) {
            return;
        }

        $order->update_meta_data( Plugin_Constants::PAYMENT_DUE_DATE, Helper_Functions::calculate_due_date( $order ) );
        $order->save();
    }

    /**
     * Keep the daily overdue check scheduled in Action Scheduler (idempotent).
     *
     * Self-healing: schedules the recurring action when the feature is on and
     * unschedules it when the feature is turned off. Gated to admin requests
     * and cron — that is plenty to self-heal while keeping the Action
     * Scheduler lookup off the frontend hot path.
     *
     * @since 1.1.6
     * @access public
     */
    public function schedule_overdue_check() {

        if ( ! is_admin() && ! wp_doing_cron() ) {
            return;
        }

        if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_has_scheduled_action' ) ) {
            return;
        }

        $scheduled = as_has_scheduled_action( 'igfw_check_overdue_invoices', array(), 'igfw' );

        if ( Helper_Functions::is_payment_terms_enabled() && ! $scheduled ) {
            as_schedule_recurring_action( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'igfw_check_overdue_invoices', array(), 'igfw' );
        } elseif ( ! Helper_Functions::is_payment_terms_enabled() && $scheduled ) {
            as_unschedule_all_actions( 'igfw_check_overdue_invoices', array(), 'igfw' );
        }
    }

    /**
     * Daily sweep: notify the merchant about overdue invoice-gateway orders.
     *
     * Status-agnostic query by meta (due date passed, reminder not yet sent) —
     * so orders are still caught if the configured awaiting status changes
     * after stamping. Paid/cancelled/refunded/failed orders are flagged with a
     * `0` sentinel so they leave the match set permanently (a failed order
     * that later recovers is not re-reminded). Every processed order drops out
     * of the query, so re-fetching the first page walks the whole backlog in
     * one run — batched 50 at a time, HPOS-safe via wc_get_orders().
     *
     * An order whose notification fails to send is left unflagged so the next
     * daily sweep retries it; a full batch that makes no progress ends the run.
     *
     * @since 1.1.6
     * @access public
     */
    public function check_overdue_invoices() {

        if ( ! Helper_Functions::is_payment_terms_enabled() ) {
            return;
        }

        do {
            $orders = wc_get_orders(
                array(
                    'type'           => 'shop_order',
                    'payment_method' => 'igfw_invoice_gateway',
                    'limit'          => 50,
                    'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                        'relation' => 'AND',
                        array(
                            'key'     => Plugin_Constants::PAYMENT_DUE_DATE,
                            'value'   => time(),
                            'compare' => '<',
                            'type'    => 'NUMERIC',
                        ),
                        array(
                            'key'     => Plugin_Constants::PAYMENT_REMINDER_SENT,
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                )
            );

            $batch_count = count( $orders );
            $progress    = 0;

            foreach ( $orders as $order ) {

                if ( $order->is_paid() || $order->has_status( array( 'cancelled', 'refunded', 'failed' ) ) ) {
                    // Sentinel: closed orders leave the match set without counting as notified.
                    $order->update_meta_data( Plugin_Constants::PAYMENT_REMINDER_SENT, 0 );
                    $order->save();
                    ++$progress;
                    continue;
                }

                if ( ! $this->send_overdue_notification( $order ) ) {
                    continue; // Mail failed — leave unflagged so the next sweep retries.
                }

                $order->add_order_note( __( 'Invoice payment overdue — store admin notified.', 'invoice-gateway-for-woocommerce' ) );
                $order->update_meta_data( Plugin_Constants::PAYMENT_REMINDER_SENT, time() );
                $order->save();

                ++$progress;

                /**
                 * Fires when an invoice-gateway order is detected as overdue.
                 *
                 * Lets advanced users wire their own processor or automation —
                 * no charging logic ships in this plugin.
                 *
                 * @since 1.1.6
                 *
                 * @param \WC_Order $order The overdue order.
                 */
                do_action( 'igfw_invoice_payment_overdue', $order );
            }
        } while ( 50 === $batch_count && $progress > 0 );
    }

    /**
     * Send the merchant-facing overdue notification email.
     *
     * Sent through the WooCommerce mailer so it uses the store's email template.
     * An empty recipient (via the `igfw_payment_overdue_notification_recipient`
     * filter) is treated as "email deliberately disabled" and reported as sent,
     * so the order note, re-send guard, and the `igfw_invoice_payment_overdue`
     * hook still fire exactly once.
     *
     * @since 1.1.6
     * @access public
     *
     * @param \WC_Order $order Overdue order.
     * @return bool True when the email was sent (or deliberately disabled), false on send failure.
     */
    public function send_overdue_notification( $order ) {

        $recipient = Helper_Functions::get_payment_overdue_recipient();

        if ( empty( $recipient ) ) {
            return true;
        }

        $due_date = (int) $order->get_meta( Plugin_Constants::PAYMENT_DUE_DATE, true );

        $subject = apply_filters(
            'igfw_payment_overdue_email_subject',
            sprintf(
                // Translators: %1$s is the site name, %2$s is the order number.
                __( '[%1$s] Invoice payment overdue — order #%2$s', 'invoice-gateway-for-woocommerce' ),
                wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
                $order->get_order_number()
            ),
            $order
        );

        $message = sprintf(
            // Translators: %1$s order number, %2$s customer name, %3$s order total, %4$s due date, %5$s order admin URL.
            __( 'Payment for order #%1$s (%2$s, %3$s) was due on %4$s and has not been received. Review the order: %5$s', 'invoice-gateway-for-woocommerce' ),
            $order->get_order_number(),
            esc_html( trim( $order->get_formatted_billing_full_name() ) ),
            wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ),
            wp_date( wc_date_format(), $due_date ),
            $order->get_edit_order_url()
        );

        $message .= "\n\n" . sprintf(
            // Translators: %s is the Wholesale Payments product URL.
            __( 'Want automated payment chasing, customer reminders, and auto-charge? See Wholesale Payments: %s', 'invoice-gateway-for-woocommerce' ),
            esc_url( Helper_Functions::get_utm_url( 'woocommerce-wholesale-payments', 'igfw', 'upsell', 'overdueemail' ) )
        );

        $message = apply_filters( 'igfw_payment_overdue_email_body', $message, $order );

        $mailer  = WC()->mailer();
        $heading = __( 'Invoice payment overdue', 'invoice-gateway-for-woocommerce' );

        return (bool) $mailer->send(
            $recipient,
            $subject,
            $mailer->wrap_message( $heading, wpautop( wptexturize( $message ) ) )
        );
    }

    /**
     * Execute the payment terms model.
     *
     * @inherit IGFW\Interfaces\Model_Interface
     *
     * @since 1.1.6
     * @access public
     */
    public function run() {

        add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_stamp_due_date' ), 10, 4 );
        add_action( 'init', array( $this, 'schedule_overdue_check' ) );
        add_action( 'igfw_check_overdue_invoices', array( $this, 'check_overdue_invoices' ) );
    }
}
