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

        // Validate the additional New Order recipients at save time.
        add_filter( 'woocommerce_admin_settings_sanitize_option_igfw_additional_new_order_recipients', array( $this, 'sanitize_additional_new_order_recipients' ), 10, 3 );

        do_action( 'igfw_settings_construct' );
    }

    /**
     * Validate the "Additional New Order Email Recipient(s)" option when settings are saved.
     *
     * Splits the comma-separated value, drops any token that isn't a valid email,
     * and surfaces a settings error listing the removed addresses so the admin gets
     * feedback instead of the silent send-time drop. Returns the cleaned list.
     *
     * @since 1.1.6
     * @access public
     *
     * @param string $value     The sanitized option value (post `wc_clean`).
     * @param array  $option    The option definition (unused).
     * @param string $raw_value The raw posted value (unused).
     * @return string Comma-separated list of valid email addresses.
     */
    public function sanitize_additional_new_order_recipients( $value, $option = array(), $raw_value = '' ) {

        $emails  = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
        $valid   = array();
        $invalid = array();

        foreach ( $emails as $email ) {
            if ( is_email( $email ) ) {
                $valid[] = $email;
            } else {
                $invalid[] = $email;
            }
        }

        if ( ! empty( $invalid ) ) {
            \WC_Admin_Settings::add_error(
                sprintf(
                    /* translators: %s: comma-separated list of invalid email addresses that were removed. */
                    __( 'Invoice Gateway: the following Additional New Order Email Recipient(s) are not valid email addresses and were removed: %s', 'invoice-gateway-for-woocommerce' ),
                    implode( ', ', array_unique( $invalid ) )
                )
            );
        }

        return implode( ', ', array_unique( $valid ) );
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
            ''                                  => __( 'General', 'invoice-gateway-for-woocommerce' ),
            'igfw_setting_restrictions_section' => __( 'Restrictions', 'invoice-gateway-for-woocommerce' ),
            'igfw_setting_help_section'         => __( 'Help', 'invoice-gateway-for-woocommerce' ),
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

        } elseif ( 'igfw_setting_restrictions_section' === $current_section ) {

            // Restrictions Section Options.
            $settings = apply_filters( 'igfw_setting_restrictions_section_options', $this->get_restrictions_section_options() );

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
                'name' => __( 'Require Purchase Order Number', 'invoice-gateway-for-woocommerce' ),
                'type' => 'checkbox',
                'desc' => __( 'Require customers to enter a Purchase Order Number before they can place the order. Only applies when "Enable Purchase Order Number" is on.', 'invoice-gateway-for-woocommerce' ),
                'id'   => 'igfw_require_purchase_order_number',
            ),

            array(
                'name'    => __( 'Default Order Status', 'invoice-gateway-for-woocommerce' ),
                'type'    => 'select',
                'desc'    => __( 'Select the default order status for invoice gateway.', 'invoice-gateway-for-woocommerce' ),
                'id'      => 'igfw_default_order_status',
                'options' => $this->get_wc_order_statuses(),
            ),

            array(
                'name' => __( 'Enable Payment Terms', 'invoice-gateway-for-woocommerce' ),
                'type' => 'checkbox',
                'desc' => __( 'Stamp a payment due date (Net X days) on invoice gateway orders and email the store admin when an order becomes overdue.', 'invoice-gateway-for-woocommerce' ),
                'id'   => 'igfw_enable_payment_terms',
            ),

            array(
                'name'              => __( 'Payment Terms (days)', 'invoice-gateway-for-woocommerce' ),
                'type'              => 'number',
                'desc'              => __( 'Number of days after the order is placed before payment is due (e.g. 30 for Net 30).', 'invoice-gateway-for-woocommerce' ),
                'id'                => 'igfw_payment_terms_days',
                'default'           => '30',
                'css'               => 'width: 80px;',
                'custom_attributes' => array(
                    'min'  => 1,
                    'step' => 1,
                ),
            ),

            array(
                'name'        => __( 'Additional New Order Email Recipient(s)', 'invoice-gateway-for-woocommerce' ),
                'type'        => 'text',
                'desc'        => __( 'Also send the New Order email to these address(es) when an order is paid via the invoice gateway. Separate multiple addresses with commas.', 'invoice-gateway-for-woocommerce' ),
                'id'          => 'igfw_additional_new_order_recipients',
                'css'         => 'min-width: 350px;',
                'placeholder' => 'finance@example.com, manager@example.com',
                'desc_tip'    => true,
            ),

            array(
                'name' => __( 'Enable Pay Now Button', 'invoice-gateway-for-woocommerce' ),
                'type' => 'checkbox',
                'desc' => __( 'Add a Pay Now button to the customer On-hold and Invoice emails so customers can pay outstanding invoice orders online via your other enabled payment gateway(s). The Invoice Payment method itself is hidden on the pay page.', 'invoice-gateway-for-woocommerce' ),
                'id'   => 'igfw_enable_pay_now_button',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'igfw_general_sectionend',
            ),

        );
    }

    /**
     * Get restrictions section options.
     *
     * Simple, non-dollar gating only — dollar credit limits and overdue-aging
     * restrictions live in Wholesale Payments.
     *
     * @since 1.1.6
     * @access private
     *
     * @return array
     */
    private function get_restrictions_section_options() {

        return array(

            array(
                'title' => __( 'Gateway Restrictions', 'invoice-gateway-for-woocommerce' ),
                'type'  => 'title',
                'desc'  => sprintf(
                    // Translators: %1$s is the opening anchor tag, %2$s is the closing anchor tag.
                    __( 'Control who can check out with the invoice gateway. Looking for dollar credit limits or overdue-based restrictions? Those are available in %1$sWholesale Payments%2$s.', 'invoice-gateway-for-woocommerce' ),
                    '<a href="' . esc_url( Helper_Functions::get_utm_url( '', 'igfw', 'settings', 'restrictions' ) ) . '" target="_blank">',
                    '</a>'
                ),
                'id'    => 'igfw_restrictions_section',
            ),

            array(
                'name' => __( 'Enable Restrictions', 'invoice-gateway-for-woocommerce' ),
                'type' => 'checkbox',
                'desc' => __( 'Master toggle — none of the rules below apply unless this is on.', 'invoice-gateway-for-woocommerce' ),
                'id'   => 'igfw_enable_gateway_restrictions',
            ),

            array(
                'name' => __( 'Block When An Unpaid Invoice Exists', 'invoice-gateway-for-woocommerce' ),
                'type' => 'checkbox',
                'desc' => __( 'Hide the invoice gateway from logged-in customers who already have an unpaid invoice order.', 'invoice-gateway-for-woocommerce' ),
                'id'   => 'igfw_restrict_existing_unpaid',
            ),

            array(
                'name'              => __( 'Maximum Unpaid Invoices', 'invoice-gateway-for-woocommerce' ),
                'type'              => 'number',
                'desc'              => __( 'Hide the invoice gateway once a logged-in customer has this many unpaid invoice orders. Leave empty or 0 for no limit.', 'invoice-gateway-for-woocommerce' ),
                'id'                => 'igfw_max_unpaid_invoices',
                'css'               => 'width: 80px;',
                'custom_attributes' => array(
                    'min'  => 0,
                    'step' => 1,
                ),
            ),

            array(
                'name'              => __( 'Minimum Order Amount', 'invoice-gateway-for-woocommerce' ),
                'type'              => 'number',
                'desc'              => __( 'Hide the invoice gateway when the cart total is below this amount. Leave empty for no minimum.', 'invoice-gateway-for-woocommerce' ),
                'id'                => 'igfw_min_order_amount',
                'css'               => 'width: 100px;',
                'custom_attributes' => array(
                    'min'  => 0,
                    'step' => 'any',
                ),
            ),

            array(
                'name'              => __( 'Maximum Order Amount', 'invoice-gateway-for-woocommerce' ),
                'type'              => 'number',
                'desc'              => __( 'Hide the invoice gateway when the cart total is above this amount. Leave empty for no maximum.', 'invoice-gateway-for-woocommerce' ),
                'id'                => 'igfw_max_order_amount',
                'css'               => 'width: 100px;',
                'custom_attributes' => array(
                    'min'  => 0,
                    'step' => 'any',
                ),
            ),

            array(
                'name'    => __( 'Allowed Roles', 'invoice-gateway-for-woocommerce' ),
                'type'    => 'multiselect',
                'class'   => 'wc-enhanced-select',
                'css'     => 'min-width: 350px;',
                'desc'    => __( 'Only these roles can use the invoice gateway. Guests are blocked when roles are selected. Leave empty to allow everyone.', 'invoice-gateway-for-woocommerce' ),
                'id'      => 'igfw_allowed_roles',
                'options' => $this->helper_functions->get_all_user_roles(),
            ),

            array(
                'name' => __( 'Restriction Message', 'invoice-gateway-for-woocommerce' ),
                'type' => 'textarea',
                'desc' => __( 'Shown on the classic checkout when the invoice gateway is hidden by a rule above. Leave empty for the default message.', 'invoice-gateway-for-woocommerce' ),
                'id'   => 'igfw_restriction_message',
                'css'  => 'min-width: 350px; min-height: 60px;',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'igfw_restrictions_sectionend',
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
