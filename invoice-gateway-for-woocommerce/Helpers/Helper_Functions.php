<?php
/**
 * Helper functions for the plugin.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Helpers
 * @since 1.0.0
 * @since 1.1.4 - Applied PHPCS Rules. Compatibility for PHP 8.2+
 */

namespace IGFW\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Model that houses all the helper functions of the plugin.
 *
 * @since 1.0.0
 */
class Helper_Functions {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Helper_Functions.
     *
     * @since 1.0.0
     * @access private
     * @var Helper_Functions
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
     * @param Plugin_Constants $constants Plugin constants object.
     */
    public function __construct( Plugin_Constants $constants ) {

        $this->constants = $constants;
    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.0.0
     * @access public
     *
     * @param Plugin_Constants $constants Plugin constants object.
     * @return Helper_Functions
     */
    public static function get_instance( Plugin_Constants $constants ) {

        if ( ! self::$instance instanceof self ) {
            self::$instance = new self( $constants );
        }

        return self::$instance;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
    */

    /**
     * Write data to plugin log file.
     *
     * @since 1.0.0
     * @access public
     *
     * @param mixed $log Data to log.
     */
    public function write_debug_log( $log ) {
        error_log( "\n[" . current_time( 'mysql' ) . "]\n" . $log . "\n--------------------------------------------------\n", 3, $this->constants->logs_root_path() . 'debug.log' );
    }

    /**
     * Check if current user is authorized to manage the plugin on the backend.
     *
     * @since 1.0.0
     * @access public
     *
     * @param WP_User $user WP_User object.
     * @return boolean True if authorized, False otherwise.
     */
    public function current_user_authorized( $user = null ) {

        // Array of roles allowed to access/utilize the plugin.
        $admin_roles = apply_filters( 'igfw_admin_roles', array( 'administrator' ) );

        if ( is_null( $user ) ) {
            $user = wp_get_current_user();
        }

        if ( $user->ID ) {
            return count( array_intersect( (array) $user->roles, $admin_roles ) ) ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Returns the timezone string for a site, even if it's set to a UTC offset
     *
     * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
     *
     * Reference:
     * http://www.skyverge.com/blog/down-the-rabbit-hole-wordpress-and-timezones/
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Valid PHP timezone string
     */
    public function get_site_current_timezone() {

        // If site timezone string exists, return it.
        $timezone = get_option( 'timezone_string' );
        if ( $timezone ) {
            return $timezone;
        }

        // Get UTC offset, if it isn't set then return UTC.
        $utc_offset = get_option( 'gmt_offset', 0 );
        if ( 0 === $utc_offset ) {
            return 'UTC';
        }

        return $this->convert_utc_offset_to_timezone( $utc_offset );
    }

    /**
     * Conver UTC offset to timezone.
     *
     * @since 1.0.0
     * @access public
     *
     * @param float|int|string $utc_offset UTC offset.
     * @return string valid PHP timezone string
     */
    public function convert_utc_offset_to_timezone( $utc_offset ) {

        // Adjust UTC offset from hours to seconds.
        $utc_offset *= 3600;

        // Attempt to guess the timezone string from the UTC offset.
        $timezone = timezone_name_from_abbr( '', $utc_offset, 0 );
        if ( $timezone ) {
            return $timezone;
        }

        // Last try, guess timezone string manually.
        $is_dst = gmdate( 'I' );

        foreach ( timezone_abbreviations_list() as $abbr ) {
            foreach ( $abbr as $city ) {
                if ( $city['dst'] === $is_dst && $city['offset'] === $utc_offset ) {
                    return $city['timezone_id'];
                }
            }
        }

        // Fallback to UTC.
        return 'UTC';
    }

    /**
     * Get all user roles.
     *
     * @since 1.0.0
     * @access public
     *
     * @global WP_Roles $wp_roles Core class used to implement a user roles API.
     *
     * @return array Array of all site registered user roles. User role key as the key and value is user role text.
     */
    public function get_all_user_roles() {

        global $wp_roles;
        return $wp_roles->get_names();
    }

    /**
     * Check if the plugin is installed.
     *
     * @since 1.1.4
     * @access public
     *
     * @param string $plugin_name The plugin name.
     * @return bool
     */
    public function is_plugin_installed( $plugin_name ) {
        return file_exists( WP_PLUGIN_DIR . '/' . $plugin_name );
    }

    /**
     * Get the URL with UTM parameters.
     *
     * @param string $url_path     URL path from main.
     * @param string $utm_source   UTM source.
     * @param string $utm_medium   UTM medium.
     * @param string $utm_campaign UTM campaign.
     * @param string $site_url     URL - defaults to `https://wholesalesuiteplugin.com/`.
     *
     * @since 1.1.4
     * @return string
     */
    public static function get_utm_url( $url_path = '', $utm_source = 'igfw', $utm_medium = 'action', $utm_campaign = 'default', $site_url = 'https://wholesalesuiteplugin.com/' ) {

        $utm_content = get_option( 'igfw_installed_by', false );
        $url         = trailingslashit( $site_url ) . $url_path;

        return add_query_arg(
            array(
                'utm_source'   => $utm_source,
                'utm_medium'   => $utm_medium,
                'utm_campaign' => $utm_campaign,
                'utm_content'  => $utm_content,
            ),
            trailingslashit( $url )
        );
    }

    /**
     * Check whether a Purchase Order Number is required at checkout.
     *
     * Single source of truth for the `igfw_require_purchase_order_number` option so
     * the classic gateway, the Blocks payment method, the Blocks server-side
     * validation, and the script localizers all agree.
     *
     * @since 1.1.6
     * @access public
     *
     * @return bool True when the Purchase Order Number is required.
     */
    public static function is_purchase_order_number_required() {
        return 'yes' === get_option( 'igfw_require_purchase_order_number', 'no' );
    }

    /**
     * Get the localized, filterable Purchase Order Number field title.
     *
     * The default passed to the `igfw_purchase_order_number_title` filter is dynamic:
     * "Purchase Order (required)" when the PO number is required, "Purchase Order (optional)"
     * otherwise. Filter consumers should expect either base value.
     *
     * @since 1.1.6
     * @access public
     *
     * @return string The Purchase Order Number field title.
     */
    public static function get_purchase_order_number_title() {

        $default_title = self::is_purchase_order_number_required()
            ? __( 'Purchase Order (required)', 'invoice-gateway-for-woocommerce' )
            : __( 'Purchase Order (optional)', 'invoice-gateway-for-woocommerce' );

        return apply_filters( 'igfw_purchase_order_number_title', $default_title );
    }

    /**
     * Get the error message shown when a required Purchase Order Number is missing.
     *
     * @since 1.1.6
     * @access public
     *
     * @return string The "PO number required" error message.
     */
    public static function get_purchase_order_number_required_error() {
        return apply_filters(
            'igfw_purchase_order_number_required_error',
            __( 'Please enter a Purchase Order Number to continue.', 'invoice-gateway-for-woocommerce' )
        );
    }

    /**
     * Check whether the payment terms feature is enabled.
     *
     * @since 1.1.6
     * @access public
     *
     * @return bool True when payment terms are enabled.
     */
    public static function is_payment_terms_enabled() {
        return 'yes' === get_option( 'igfw_enable_payment_terms', 'no' );
    }

    /**
     * Get the configured payment term in days (Net X).
     *
     * A missing or invalid stored value falls back to 30; a filtered value is
     * clamped to a minimum of 1.
     *
     * @since 1.1.6
     * @access public
     *
     * @return int Term length in days (minimum 1, default 30).
     */
    public static function get_payment_terms_days() {
        $days = absint( get_option( 'igfw_payment_terms_days', 30 ) );

        if ( $days < 1 ) {
            $days = 30;
        }

        return max( 1, (int) apply_filters( 'igfw_get_payment_terms_days', $days ) );
    }

    /**
     * Calculate the payment due date for an invoice-gateway order.
     *
     * Based on the order creation date plus the configured term. A filter that
     * returns a non-positive value is ignored in favour of the computed due
     * date, so a misbehaving callback cannot brand the order immediately
     * overdue; a positive (including intentionally back-dated) timestamp is
     * honoured as-is.
     *
     * @since 1.1.6
     * @access public
     *
     * @param \WC_Order $order Order object.
     * @return int Due date as a Unix timestamp.
     */
    public static function calculate_due_date( $order ) {
        $created = $order->get_date_created();
        $base    = $created instanceof \WC_DateTime ? $created->getTimestamp() : time();
        $due     = $base + ( self::get_payment_terms_days() * DAY_IN_SECONDS );

        $filtered = (int) apply_filters( 'igfw_invoice_due_date', $due, $order );

        return $filtered > 0 ? $filtered : $due;
    }

    /**
     * Get the recipient for the overdue invoice notification.
     *
     * Merchant-facing by design — customer-facing payment reminders are
     * Wholesale Payments territory.
     *
     * @since 1.1.6
     * @access public
     *
     * @return string Filterable notification recipient (defaults to the admin email).
     */
    public static function get_payment_overdue_recipient() {
        return apply_filters( 'igfw_payment_overdue_notification_recipient', get_option( 'admin_email' ) );
    }

    /**
     * Check whether the Pay Now button feature is enabled.
     *
     * @since 1.1.6
     * @access public
     *
     * @return bool True when the Pay Now button feature is enabled.
     */
    public static function is_pay_now_enabled() {
        return 'yes' === get_option( 'igfw_enable_pay_now_button', 'no' );
    }

    /**
     * Get the Pay Now button label.
     *
     * @since 1.1.6
     * @access public
     *
     * @return string Filterable button label.
     */
    public static function get_pay_now_button_label() {
        return apply_filters( 'igfw_pay_now_button_label', __( 'Pay Now', 'invoice-gateway-for-woocommerce' ) );
    }

    /**
     * Check whether at least one enabled gateway other than the invoice gateway exists.
     *
     * Checks the enabled flag rather than full availability so the result is
     * reliable in email/admin context, where gateway availability methods may
     * depend on a cart/session that does not exist.
     *
     * @since 1.1.6
     * @access public
     *
     * @return bool True when a non-invoice gateway is enabled.
     */
    public static function has_non_invoice_gateway_enabled() {
        if ( ! function_exists( 'WC' ) ) {
            return false;
        }

        $payment_gateways = WC()->payment_gateways()->payment_gateways();

        foreach ( $payment_gateways as $gateway ) {
            if ( 'igfw_invoice_gateway' !== $gateway->id && 'yes' === $gateway->enabled ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether an invoice-gateway order can be paid via the Pay Now flow.
     *
     * Single source of truth for the email Pay Now button. Calls
     * WC_Order::needs_payment(), so it must NOT be used inside the
     * `woocommerce_valid_order_statuses_for_payment` filter, which
     * needs_payment() itself applies (it would recurse).
     *
     * @since 1.1.6
     * @access public
     *
     * @param mixed $order Order object (non-orders return false).
     * @return bool True when the order is payable via Pay Now.
     */
    public static function is_invoice_order_payable( $order ) {
        return $order instanceof \WC_Order
            && 'igfw_invoice_gateway' === $order->get_payment_method()
            && self::is_pay_now_enabled()
            && self::has_non_invoice_gateway_enabled()
            && $order->needs_payment();
    }
}
