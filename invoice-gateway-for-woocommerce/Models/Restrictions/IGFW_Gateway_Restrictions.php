<?php
/**
 * IGFW Gateway Restrictions class file.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Models/Restrictions
 * @since 1.1.6
 */

namespace IGFW\Models\Restrictions;

use IGFW\Abstracts\Abstract_Main_Plugin_Class;
use IGFW\Helpers\Helper_Functions;
use IGFW\Helpers\Plugin_Constants;
use IGFW\Interfaces\Model_Interface;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Model that houses the invoice gateway usage restrictions.
 *
 * Simple, non-dollar gating only: existing-unpaid / max unpaid count /
 * min-max order amount / allowed roles. Dollar credit limits and
 * overdue-aging restrictions are Wholesale Payments territory.
 *
 * @since 1.1.6
 */
class IGFW_Gateway_Restrictions implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that holds the single main instance of IGFW_Gateway_Restrictions.
     *
     * @since 1.1.6
     * @access private
     * @var IGFW_Gateway_Restrictions
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

    /**
     * Per-request cache of unpaid invoice-order counts, keyed by user ID.
     *
     * The available-gateways filter re-fires on every cart/shipping AJAX
     * recalculation, so the order query must not repeat within a request.
     *
     * @since 1.1.6
     * @access private
     * @var array
     */
    private $unpaid_count_cache = array();

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
     * @return IGFW_Gateway_Restrictions
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {

        if ( ! self::$instance instanceof self ) {
            self::$instance = new self( $main_plugin, $constants, $helper_functions );
        }

        return self::$instance;
    }

    /**
     * Get the order statuses that count as "unpaid" for restriction purposes.
     *
     * @since 1.1.6
     * @access private
     *
     * @return string[] Unprefixed status slugs.
     */
    private function get_unpaid_invoice_statuses() {
        return (array) apply_filters(
            'igfw_unpaid_invoice_statuses',
            array_unique( array( get_option( 'igfw_default_order_status', 'on-hold' ), 'pending' ) )
        );
    }

    /**
     * Count a customer's unpaid invoice-gateway orders (per-request cached).
     *
     * @since 1.1.6
     * @access private
     *
     * @param int $user_id WordPress user ID.
     * @return int Number of unpaid invoice-gateway orders.
     */
    private function get_customer_unpaid_invoice_count( $user_id ) {

        if ( isset( $this->unpaid_count_cache[ $user_id ] ) ) {
            return $this->unpaid_count_cache[ $user_id ];
        }

        // Paginated query so the count comes from found_rows — no need to
        // materialize every matching ID just to count them.
        $result = wc_get_orders(
            array(
                'type'           => 'shop_order',
                'customer_id'    => $user_id,
                'payment_method' => 'igfw_invoice_gateway',
                'status'         => $this->get_unpaid_invoice_statuses(),
                'limit'          => 1,
                'paginate'       => true,
                'return'         => 'ids',
            )
        );

        $this->unpaid_count_cache[ $user_id ] = (int) $result->total;

        return $this->unpaid_count_cache[ $user_id ];
    }

    /**
     * Get the customer-facing restriction message.
     *
     * @since 1.1.6
     * @access private
     *
     * @return string Filterable message.
     */
    private function get_restriction_message() {

        $message = get_option( 'igfw_restriction_message', '' );

        if ( '' === trim( (string) $message ) ) {
            $message = __( 'Invoice payment is currently unavailable for your account.', 'invoice-gateway-for-woocommerce' );
        }

        return apply_filters( 'igfw_gateway_restriction_message', $message );
    }

    /**
     * Evaluate whether the current customer is restricted from the invoice gateway.
     *
     * History-based rules (existing unpaid, max unpaid count) apply to logged-in
     * customers only — guests have no reliable identity. Role gating blocks
     * guests whenever an allowed-roles list is configured. Amount rules apply to
     * everyone, measured against the live cart total or — on the order-pay
     * endpoint — the total of the order being paid.
     *
     * @since 1.1.6
     * @access private
     *
     * @return string|null Restriction reason slug, or null when not restricted.
     */
    private function get_restriction_reason() {

        if ( 'yes' !== get_option( 'igfw_enable_gateway_restrictions', 'no' ) ) {
            return null;
        }

        $user_id = get_current_user_id();

        // Role / login gating.
        $allowed_roles = array_filter( (array) get_option( 'igfw_allowed_roles', array() ) );

        if ( ! empty( $allowed_roles ) ) {
            if ( ! $user_id ) {
                return 'roles';
            }

            $user = get_userdata( $user_id );

            if ( ! $user || ! array_intersect( (array) $user->roles, $allowed_roles ) ) {
                return 'roles';
            }
        }

        // Order amount gating. On the order-pay endpoint the cart is usually
        // empty, so the order being paid is the relevant basis — otherwise a
        // configured minimum would always block and a maximum could never
        // block. Everywhere else, the live cart total is what the customer
        // sees at checkout.
        $amount_basis = null;

        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            $order        = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
            $amount_basis = $order ? (float) $order->get_total() : null;
        } elseif ( function_exists( 'WC' ) && WC()->cart ) {
            $amount_basis = (float) WC()->cart->get_total( 'edit' );
        }

        if ( null !== $amount_basis ) {
            $min_amount = get_option( 'igfw_min_order_amount', '' );
            $max_amount = get_option( 'igfw_max_order_amount', '' );

            if ( '' !== $min_amount && $amount_basis < (float) $min_amount ) {
                return 'min_amount';
            }

            if ( '' !== $max_amount && $amount_basis > (float) $max_amount ) {
                return 'max_amount';
            }
        }

        // History gating — logged-in customers only.
        if ( $user_id ) {
            $restrict_existing = 'yes' === get_option( 'igfw_restrict_existing_unpaid', 'no' );
            $max_unpaid        = absint( get_option( 'igfw_max_unpaid_invoices', 0 ) );

            if ( $restrict_existing || $max_unpaid > 0 ) {
                $unpaid_count = $this->get_customer_unpaid_invoice_count( $user_id );

                if ( $restrict_existing && $unpaid_count >= 1 ) {
                    return 'existing_unpaid';
                }

                if ( $max_unpaid > 0 && $unpaid_count >= $max_unpaid ) {
                    return 'max_unpaid';
                }
            }
        }

        return null;
    }

    /**
     * Remove the invoice gateway from the available gateways when restricted.
     *
     * Single late-priority callback covers the classic checkout and the Blocks
     * checkout (the Store API derives its methods from this same filter).
     * Operates only on the passed array — never re-queries availability inside
     * this filter.
     *
     * @since 1.1.6
     * @access public
     *
     * @param array $gateways Available payment gateways.
     * @return array Gateways without the invoice gateway when restricted.
     */
    public function maybe_remove_invoice_gateway( $gateways ) {

        if ( ! isset( $gateways['igfw_invoice_gateway'] ) ) {
            return $gateways;
        }

        // Leave wp-admin screens alone (manual order editing etc.).
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $gateways;
        }

        if ( null !== $this->get_restriction_reason() ) {
            unset( $gateways['igfw_invoice_gateway'] );
        }

        return $gateways;
    }

    /**
     * Whether the customer-facing restriction message should be shown/thrown.
     *
     * True only when the merchant has the invoice gateway enabled AND the
     * current customer is restricted — so we never explain the absence of a
     * gateway the merchant never offered in the first place.
     *
     * @since 1.1.6
     * @access private
     *
     * @return bool
     */
    private function should_render_restriction_notice() {

        $gateway_settings = get_option( 'woocommerce_igfw_invoice_gateway_settings', array() );

        if ( empty( $gateway_settings['enabled'] ) || 'yes' !== $gateway_settings['enabled'] ) {
            return false;
        }

        return null !== $this->get_restriction_reason();
    }

    /**
     * Explain the restriction on the classic checkout payment section.
     *
     * Classic checkout only. The Blocks checkout has no passive server-rendered
     * notice slot, and this plugin deliberately ships no Blocks JS for the
     * restriction feature (gateway removal alone covers both surfaces), so on
     * Blocks the gateway simply does not appear — without this explanatory
     * message. Enforcement is identical on both surfaces; only the explanation
     * is classic-only.
     *
     * @since 1.1.6
     * @access public
     */
    public function maybe_render_restriction_notice() {

        if ( $this->should_render_restriction_notice() ) {
            wc_print_notice( $this->get_restriction_message(), 'notice' );
        }
    }

    /**
     * Defense-in-depth: reject a restricted invoice-gateway payment on the Store API.
     *
     * In the normal Blocks flow this is a last resort: maybe_remove_invoice_gateway()
     * has already dropped the gateway from the cart-derived method list, so WooCommerce
     * rejects a restricted request with its own generic "payment method disabled" error
     * before this callback's custom message is reached. The custom message here only
     * surfaces for a direct/custom Store API client that names the gateway without
     * deriving its method list from the cart. Kept as a genuine guard, not the primary
     * messaging path.
     *
     * @since 1.1.6
     * @access public
     *
     * @param PaymentContext $payment_context Payment context.
     * @param PaymentResult  $payment_result  Payment result.
     *
     * @throws \Exception When the customer is restricted from the invoice gateway.
     */
    public function block_restricted_store_api_payment( PaymentContext $payment_context, PaymentResult &$payment_result ) {

        if ( 'igfw_invoice_gateway' !== $payment_context->payment_method ) {
            return;
        }

        if ( $this->should_render_restriction_notice() ) {
            throw new \Exception( esc_html( $this->get_restriction_message() ) );
        }
    }

    /**
     * Execute the gateway restrictions model.
     *
     * @inherit IGFW\Interfaces\Model_Interface
     *
     * @since 1.1.6
     * @access public
     */
    public function run() {

        // SECURITY / behavior: priority 99 must stay LATE so this removal runs
        // after every gateway has registered itself (the invoice gateway adds
        // itself via its own woocommerce_available_payment_gateways filter at
        // the default priority 10). Lowering it risks the gateway re-adding
        // itself after we remove it, defeating the restriction on both the
        // classic and Blocks (Store API) checkout.
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'maybe_remove_invoice_gateway' ), 99 );
        add_action( 'woocommerce_review_order_before_payment', array( $this, 'maybe_render_restriction_notice' ) );
        add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'block_restricted_store_api_payment' ), 9, 2 );
    }
}
