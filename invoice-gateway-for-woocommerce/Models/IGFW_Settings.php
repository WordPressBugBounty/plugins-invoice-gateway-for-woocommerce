<?php
/**
 * IGFW Settings class for WooCommerce settings.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Models
 * @since 1.0.0
 */

namespace IGFW\Models;

use IGFW\Helpers\Helper_Functions;
use IGFW\Helpers\Plugin_Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * WooCommerce Settings Page class for Invoice Gateway.
 *
 * @since 1.0.0
 */
class IGFW_Settings extends \WC_Settings_Page {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

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
     * IGFW_Settings constructor.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Plugin_Constants $constants        Plugin constants object.
     * @param Helper_Functions $helper_functions Helper functions object.
     */
    public function __construct( Plugin_Constants $constants, Helper_Functions $helper_functions ) {

        $this->constants        = $constants;
        $this->helper_functions = $helper_functions;

        $this->id    = 'igfw_settings';
        $this->label = __( 'Invoice Gateway', 'invoice-gateway-for-woocommerce' );

        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 30 ); // 30 so it is after the API tab.
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
        add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

        // Custom settings fields.
        add_action( 'woocommerce_admin_field_igfw_help_resources_field', array( $this, 'render_igfw_help_resources_field' ) );
        add_action( 'woocommerce_admin_field_igfw_invoice_gateway_settings_link_field', array( $this, 'render_igfw_invoice_gateway_settings_link_field' ) );
        add_action( 'woocommerce_admin_field_igfw_plugin_installer_field', array( $this, 'render_igfw_plugin_installer_field' ) );
        do_action( 'igfw_settings_construct' );
    }

    /**
     * Get sections.
     *
     * @since 1.0.0
     * @access public
     *
     * @return array
     */
    public function get_sections() {

        $sections = array(
            ''                          => __( 'General', 'invoice-gateway-for-woocommerce' ),
            'igfw_setting_help_section' => __( 'Help', 'invoice-gateway-for-woocommerce' ),
        );

        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }

    /**
     * Output the settings.
     *
     * @since 1.0.0
     * @access public
     */
    public function output() {

        global $current_section;

        $settings = $this->get_settings( $current_section );
        \WC_Admin_Settings::output_fields( $settings );
    }

    /**
     * Save settings.
     *
     * @since 1.0.0
     * @access public
     */
    public function save() {

        global $current_section;

        $settings = $this->get_settings( $current_section );

        do_action( 'igfw_before_save_settings', $settings );

        \WC_Admin_Settings::save_fields( $settings );

        do_action( 'igfw_after_save_settings', $settings );
    }

    /**
     * Get settings array.
     *
     * @since 1.0.0
     * @access public
     *
     * @param  string $current_section Current settings section.
     * @return array  Array of options for the current setting section.
     */
    public function get_settings( $current_section = '' ) {

        if ( 'igfw_setting_help_section' === $current_section ) {

            // Help Section Options.
            $settings = apply_filters( 'igfw_setting_help_section_options', $this->get_help_section_options() );

        } else {

            // General Section Options.
            $settings = apply_filters( 'igfw_setting_general_section_options', $this->get_general_section_options() );

        }

        return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
    }

    /*
    |--------------------------------------------------------------------------------------------------------------
    | Section Settings
    |--------------------------------------------------------------------------------------------------------------
     */

    /**
     * Get general section options.
     *
     * @since 1.0.0
     * @access private
     *
     * @return array
     */
    private function get_general_section_options() {

        return array(

            array(
                'title' => __( 'General Options', 'invoice-gateway-for-woocommerce' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'igfw_general_section',
            ),

            array(
                'name' => '',
                'type' => 'igfw_plugin_installer_field',
                'desc' => '',
                'id'   => 'igfw_plugin_installer',
            ),

            array(
                'name' => '',
                'type' => 'igfw_invoice_gateway_settings_link_field',
                'desc' => '',
                'id'   => 'igfw_invoice_gateway_settings_link',
            ),

            array(
                'name' => __( 'Enable Purchase Order Number', 'invoice-gateway-for-woocommerce' ),
                'type' => 'checkbox',
                'desc' => __( 'Allow adding "Purchase Order Number" in the checkout page and option to add it in the edit order page.', 'invoice-gateway-for-woocommerce' ),
                'id'   => 'igfw_enable_purchase_order_number',
            ),

            array(
                'name'    => __( 'Default Order Status', 'invoice-gateway-for-woocommerce' ),
                'type'    => 'select',
                'desc'    => __( 'Select the default order status for invoice gateway.', 'invoice-gateway-for-woocommerce' ),
                'id'      => 'igfw_default_order_status',
                'options' => $this->get_wc_order_statuses(),
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'igfw_general_sectionend',
            ),

        );
    }

    /**
     * Get help section options
     *
     * @since 1.0.0
     * @access private
     *
     * @return array
     */
    private function get_help_section_options() {

        return array(

            array(
                'title' => __( 'Help Options', 'invoice-gateway-for-woocommerce' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'igfw_help_main_title',
            ),

            array(
                'name' => '',
                'type' => 'igfw_help_resources_field',
                'desc' => '',
                'id'   => 'igfw_help_resources',
            ),

            array(
                'title' => __( 'Clean up plugin options on un-installation', 'invoice-gateway-for-woocommerce' ),
                'type'  => 'checkbox',
                'desc'  => __( 'If checked, removes all plugin options when this plugin is uninstalled. <b>Warning:</b> This process is irreversible.', 'invoice-gateway-for-woocommerce' ),
                'id'    => Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS,
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'igfw_help_sectionend',
            ),

        );
    }

    /*
    |--------------------------------------------------------------------------------------------------------------
    | Custom Settings Fields
    |--------------------------------------------------------------------------------------------------------------
     */

    /**
     * Render help resources controls.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $value Field value.
     */
    public function render_igfw_help_resources_field( $value ) {
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for=""><?php esc_html_e( 'Knowledge Base', 'invoice-gateway-for-woocommerce' ); ?></label>
            </th>
            <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                <?php
                // Translators: %1$s is the URL to the knowledge base.
                echo wp_kses_post( sprintf( __( 'Looking for documentation? Please see our growing <a href="%1$s" target="_blank">Knowledge Base</a>', 'invoice-gateway-for-woocommerce' ), 'https://wordpress.org/plugins/invoice-gateway-for-woocommerce/faq/' ) );
                ?>
            </td>
        </tr>

        <?php
    }

    /**
     * Render invoice gateway settings link field.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $value Field value.
     */
    public function render_igfw_invoice_gateway_settings_link_field( $value ) {
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for=""><?php esc_html_e( 'Invoice Gateway Settings', 'invoice-gateway-for-woocommerce' ); ?></label>
            </th>
        </tr>
        <tr valign="top">
            <td>
                <?php
                // Translators: %1$s is the URL to the invoice gateway settings.
                echo wp_kses_post( sprintf( __( 'Click <a href="%1$s">here</a> to configure the invoice payment gateway.', 'invoice-gateway-for-woocommerce' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=igfw_invoice_gateway' ) ) ) );
                ?>
            </td>
        </tr>

        <?php
    }

    /**
     * Render plugin installer field.
     *
     * @since 1.1.4
     * @access public
     *
     * @param array $value Field value.
     */
    public function render_igfw_plugin_installer_field( $value ) {
        $plugin_name         = 'woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php';
        $is_wwp_installed    = $this->helper_functions->is_plugin_installed( $plugin_name );
        $is_wwp_active       = is_plugin_active( $plugin_name );
        $go_to_settings_text = __( 'Go to Settings', 'invoice-gateway-for-woocommerce' );

        $button_text = ! $is_wwp_installed
            ? __( 'Install & Activate (FREE)', 'invoice-gateway-for-woocommerce' )
            : ( ! $is_wwp_active
                ? __( 'Activate Plugin', 'invoice-gateway-for-woocommerce' )
                : $go_to_settings_text );

        ?>

        <tr valign="top">
            <th scope="row" class="titledesc" colspan="4">
                <div id="igfw-plugin-installer">
                    <h2>
                        <?php esc_html_e( 'Enjoying Invoice Gateway for WooCommerce? Check out our other top rated plugins:', 'invoice-gateway-for-woocommerce' ); ?>
                    </h2>

                    <div id="igfw-plugin-upsells">
                        <div class="igfw-plugin-upsell" >
                            <a class="image-link" id="wholesale-suite" href="https://wholesalesuiteplugin.com/?utm_source=IGFW&utm_medium=Settings" target="_blank">
                                <img
                                    src="<?php echo esc_url( $this->constants->images_root_url() . 'wholesale-suite.svg' ); ?>"
                                    alt="<?php esc_attr_e( 'Wholesale Prices Icon', 'invoice-gateway-for-woocommerce' ); ?>"
                                />
                            </a>
                            <div class="igfw-plugin-upsell-content">
                                <h3 class="upsell-title"><?php esc_html_e( 'Wholesale Prices (Free Plugin)', 'invoice-gateway-for-woocommerce' ); ?></h3>
                                <p class="upsell-content">
                                    <?php esc_html_e( 'Easily add wholesale pricing to your WooCommerce products. #1 wholesale plugin.', 'invoice-gateway-for-woocommerce' ); ?>
                                </p>
                                <button
                                    class="button upsell-button plugin-installer <?php echo $is_wwp_installed && $is_wwp_active ? 'hidden' : ''; ?>"
                                    data-plugin-slug="woocommerce-wholesale-prices" <?php echo $is_wwp_installed && $is_wwp_active ? 'disabled' : ''; ?>
                                    >
                                    <?php echo esc_html( $button_text ); ?>
                                </button>
                                <a
                                    href="<?php echo esc_url( admin_url( 'admin.php?page=wholesale-settings' ) ); ?>"
                                    class="button upsell-button go-to-settings <?php echo ( ! $is_wwp_active ) ? 'hidden' : ''; ?>">
                                    <?php echo esc_html( $go_to_settings_text ); ?>
                                </a>
                            </div>
                        </div>
                        <div class="igfw-plugin-upsell">
                            <a class="image-link" href="https://wholesalesuiteplugin.com/?utm_source=IGFW&utm_medium=Settings" target="_blank">
                                <img
                                    src="<?php echo esc_url( $this->constants->images_root_url() . 'wholesale-payments.svg' ); ?>"
                                    alt="<?php esc_attr_e( 'Wholesale Prices Icon', 'invoice-gateway-for-woocommerce' ); ?>"
                                />
                            </a>
                            <div class="igfw-plugin-upsell-content">
                                <h3 class="upsell-title"><?php esc_html_e( 'Wholesale Payments by Wholesale Suite', 'invoice-gateway-for-woocommerce' ); ?></h3>
                                <p class="upsell-content">
                                    <?php esc_html_e( 'Add NET 30/45/60 invoices for wholesale customers. Create your own invoice payment plans easily.', 'invoice-gateway-for-woocommerce' ); ?>
                                </p>
                                <a href="https://wholesalesuiteplugin.com/?utm_source=IGFW&utm_medium=Settings" class="button upsell-button" data-plugin-slug="wholesale-payments">
                                    <?php esc_html_e( 'Get Plugin', 'invoice-gateway-for-woocommerce' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </th>
        </tr>
        <?php
    }

    /**
     * Get all available WooCommerce order statuses
     * excluding those that aren't relevant for new orders.
     *
     * @since 1.1.5
     * @access private
     *
     * @return array
     */
    private function get_wc_order_statuses() {
        $order_statuses = wc_get_order_statuses();
        $statuses       = array();

        // Statuses to exclude (not relevant for new orders).
        $excluded_statuses = array( 'wc-cancelled', 'wc-failed', 'wc-refunded', 'wc-trash', 'wc-checkout-draft' );

        // Convert statuses from wc-status format to status format (strip the wc- prefix).
        foreach ( $order_statuses as $status_key => $status_label ) {
            // Skip excluded statuses.
            if ( in_array( $status_key, $excluded_statuses, true ) ) {
                continue;
            }

            $key              = str_replace( 'wc-', '', $status_key );
            $statuses[ $key ] = $status_label;
        }

        return $statuses;
    }
}
