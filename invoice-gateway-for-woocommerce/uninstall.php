<?php
/**
 * Uninstall
 *
 * @package Invoice Gateway for WooCommerce
 */

// Exit if uninstall is not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

require_once 'Helpers/Plugin_Constants.php';

/**
 * Function that houses the code that cleans up the plugin on un-installation.
 *
 * @since 1.0.0
 * @since 1.1.0 Added igfw_activation_date option.
 * @since 1.1.6 Unschedule the payment terms recurring action and sweep every plugin option (general/PO, payment terms, additional New Order recipients, gateway restrictions, gateway settings, and plugin state).
 */
function igfw_plugin_cleanup() {

    // Remove the recurring overdue check regardless of the cleanup toggle — it
    // is runtime state, not user data. No-op when WooCommerce (and with it
    // Action Scheduler) is not loaded at uninstall time.
    if ( function_exists( 'as_unschedule_all_actions' ) ) {
        as_unschedule_all_actions( 'igfw_check_overdue_invoices', array(), 'igfw' );
    }

    if ( get_option( \IGFW\Helpers\Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS, false ) === 'yes' ) {

        // Help settings section options.
        delete_option( \IGFW\Helpers\Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS );
        delete_option( 'igfw_activation_date' );

        // General settings section options.
        delete_option( 'igfw_enable_purchase_order_number' );
        delete_option( 'igfw_require_purchase_order_number' );
        delete_option( 'igfw_default_order_status' );
        delete_option( 'igfw_additional_new_order_recipients' );
        delete_option( 'igfw_enable_pay_now_button' );

        // Payment terms options.
        delete_option( 'igfw_enable_payment_terms' );
        delete_option( 'igfw_payment_terms_days' );

        // Restrictions section options.
        delete_option( 'igfw_enable_gateway_restrictions' );
        delete_option( 'igfw_restrict_existing_unpaid' );
        delete_option( 'igfw_max_unpaid_invoices' );
        delete_option( 'igfw_min_order_amount' );
        delete_option( 'igfw_max_order_amount' );
        delete_option( 'igfw_allowed_roles' );
        delete_option( 'igfw_restriction_message' );

        // Payment gateway settings + plugin state.
        delete_option( 'woocommerce_igfw_invoice_gateway_settings' );
        delete_option( \IGFW\Helpers\Plugin_Constants::INSTALLED_VERSION );
        delete_option( 'igfw_installed_by' );
    }
}

if ( function_exists( 'is_multisite' ) && is_multisite() ) {

    global $wpdb;

    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

    foreach ( $blog_ids as $the_blog_id ) {

        switch_to_blog( $the_blog_id );
        igfw_plugin_cleanup();
    }

    restore_current_blog();

    return;

} else {
    igfw_plugin_cleanup();
}
