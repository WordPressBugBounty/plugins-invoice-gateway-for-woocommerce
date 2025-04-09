<?php
/**
 * Plugin Installer Helper.
 *
 * @package IGFW
 * @since 1.1.4
 */

namespace IGFW\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Model that houses the logic of the Plugin_Installer module.
 *
 * @since 1.1.4
 */
class Plugin_Installer {

    /**
     * The single instance of the class.
     *
     * @since 1.1.4
     * @var Plugin_Installer
     */
    private static $instance = null;

    /**
     * Get the single instance of the class.
     *
     * @since 1.1.4
     * @return Plugin_Installer
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Allowed plugins for installation.
     *
     * @since 1.1.4
     * @var array
     */
    private $allowed_plugins = array(
        'woocommerce-wholesale-prices' => 'woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php',
    );

    /**
     * Constructor.
     *
     * @since 1.1.4
     */
    public function __construct() {
        add_action( 'wp_ajax_igfw_install_activate_plugin', array( $this, 'ajax_install_activate_plugin' ) );
    }

    /**
     * Download and activate a given plugin.
     *
     * @since 1.1.4
     * @access public
     *
     * @param string $plugin_slug Plugin slug.
     * @param bool   $silently download plugin silently.
     * @return bool|\WP_Error True if successful, WP_Error otherwise.
     */
    public function download_and_activate_plugin( $plugin_slug, $silently = false ) {

        // Check if the current user has the required permissions.
        if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
            return new \WP_Error( 'permission_denied', __( 'You do not have sufficient permissions to install and activate plugins.', 'invoice-gateway-for-woocommerce' ) );
        }

        // Check if the plugin is valid.
        if ( ! $this->is_plugin_allowed_for_install( $plugin_slug ) ) {
            return new \WP_Error( 'igfw_plugin_not_allowed', __( 'The plugin is not valid.', 'invoice-gateway-for-woocommerce' ) );
        }

        // Get required files since we're calling this outside of context.
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        // Get the plugin info from WordPress.org's plugin repository.
        $api = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug ) );
        if ( is_wp_error( $api ) ) {
            return $api;
        }

        $plugin_basename = $this->get_plugin_basename_by_slug( $plugin_slug );

        // Check if the plugin is already active.
        if ( is_plugin_active( $plugin_basename ) ) {
            return new \WP_Error( 'igfw_plugin_already_active', __( 'The plugin is already installed.', 'invoice-gateway-for-woocommerce' ) );
        }

        // Check if the plugin is already installed but inactive, just activate it and return true.
        if ( $this->is_plugin_installed( $plugin_basename ) ) {
            return $this->activate_plugin( $plugin_basename, $plugin_slug );
        }

        // Download the plugin.
        $skin     = $silently ? new \WP_Ajax_Upgrader_Skin() : new \Plugin_Installer_Skin(
            array(
                'type'  => 'web',
                'title' => sprintf( 'Installing Plugin: %s', $api->name ),
            )
        );
        $upgrader = new \Plugin_Upgrader( $skin );

        $result = $upgrader->install( $api->download_link );

        // Check if the plugin was installed successfully.
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Activate the plugin.
        return $this->activate_plugin( $plugin_basename, $plugin_slug );
    }

    /**
     * Activate a plugin.
     *
     * @since 1.1.4
     * @access private
     *
     * @param string $plugin_basename Plugin basename.
     * @param string $plugin_slug     Plugin slug.
     * @return bool|\WP_Error True if successful, WP_Error otherwise.
     */
    private function activate_plugin( $plugin_basename, $plugin_slug ) {
        $result = activate_plugin( $plugin_basename );

        // Update WooCommerce Wholesale Prices source option when WooCommerce Wholesale Prices is installed.
        if ( 'woocommerce-wholesale-prices' === $plugin_slug ) {
            update_option( 'wwp_installed_by', 'igfw' );
        }

        return is_wp_error( $result ) ? $result : true;
    }

    /**
     * Get the list of allowed plugins for install.
     *
     * @since 1.1.4
     * @access public
     *
     * @return array List of allowed plugins.
     */
    public function get_allowed_plugins() {
        // Allow other plugins to be installed but not let them overwrite the ones listed above.
        $extra_allowed_plugins = apply_filters( 'igfw_allowed_install_plugins', array() );

        return array_merge( $this->allowed_plugins, $extra_allowed_plugins );
    }

    /**
     * Validate if the given plugin is allowed for install.
     *
     * @since 1.1.4
     * @access private
     *
     * @param string $plugin_slug Plugin slug.
     * @return bool True if valid, false otherwise.
     */
    private function is_plugin_allowed_for_install( $plugin_slug ) {
        return in_array( $plugin_slug, array_keys( $this->get_allowed_plugins() ), true );
    }

    /**
     * Check if the plugin is installed.
     *
     * @since 1.1.4
     * @access private
     *
     * @param string $plugin_name The plugin name.
     * @return bool
     */
    private function is_plugin_installed( $plugin_name ) {
        return file_exists( WP_PLUGIN_DIR . '/' . $plugin_name );
    }

    /**
     * Get the plugin basename by slug.
     *
     * @since 1.1.4
     * @access public
     *
     * @param string $plugin_slug Plugin slug.
     * @return string Plugin basename.
     */
    public function get_plugin_basename_by_slug( $plugin_slug ) {
        $allowed_plugins = $this->get_allowed_plugins();

        return $allowed_plugins[ $plugin_slug ] ?? '';
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX Functions
    |--------------------------------------------------------------------------
     */

    /**
     * AJAX install and activate a plugin.
     *
     * @since 1.1.4
     * @access public
     */
    public function ajax_install_activate_plugin() {

        // Check nonce.
        check_ajax_referer( 'igfw_install_plugin', 'nonce' );

        // Retrieve the plugin slug from the front-end.
        $plugin_slug = isset( $_REQUEST['plugin_slug'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin_slug'] ) ) : '';

        $silent = isset( $_REQUEST['silent'] ) ?? false;
        $result = $this->download_and_activate_plugin( $plugin_slug, $silent );

        do_action( 'igfw_after_install_activate_plugin', $plugin_slug, $result );

        if ( isset( $_REQUEST['redirect'] ) ) {
            wp_safe_redirect( admin_url( 'plugins.php' ) );
        }

        // Check if the result is a WP_Error.
        if ( is_wp_error( $result ) ) {
            // If it is, return a JSON response indicating failure.
            wp_send_json_error(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                )
            );
        } else {
            // If not, return a JSON response indicating success.
            wp_send_json_success(
                array(
                    'success' => true,
                    'message' => __( 'Plugin installed and activated successfully!', 'invoice-gateway-for-woocommerce' ),
                )
            );
        }
    }
}
