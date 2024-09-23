<?php
namespace IGFW\Models;

use IGFW\Helpers\Helper_Functions;
use IGFW\Helpers\Plugin_Constants;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

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
     * IGFW_Settings constructor.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Plugin_Constants $constants        Plugin constants object.
     * @param Helper_Functions $helper_functions Helper functions object.
     */
    public function __construct(Plugin_Constants $constants, Helper_Functions $helper_functions) {

        $this->_constants = $constants;
        $this->_helper_functions = $helper_functions;

        $this->id = 'igfw_settings';
        $this->label = __('Invoice Gateway', 'invoice-gateway-for-woocommerce');

        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 30); // 30 so it is after the API tab
        add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
        add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
        add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections'));

        // Custom settings fields
        add_action('woocommerce_admin_field_igfw_help_resources_field', array($this, 'render_igfw_help_resources_field'));
        add_action('woocommerce_admin_field_igfw_invoice_gateway_settings_link_field', array($this, 'render_igfw_invoice_gateway_settings_link_field'));
        add_action('woocommerce_admin_field_igfw_wws_banner_controls', array($this, 'render_igfw_wws_banner_controls'));

        do_action('igfw_settings_construct');

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
            '' => __('General', 'invoice-gateway-for-woocommerce'),
            'igfw_setting_help_section' => __('Help', 'invoice-gateway-for-woocommerce'),
        );

        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);

    }

    /**
     * Output the settings.
     *
     * @since 1.0.0
     * @access public
     */
    public function output() {

        global $current_section;

        $settings = $this->get_settings($current_section);
        \WC_Admin_Settings::output_fields($settings);

    }

    /**
     * Save settings.
     *
     * @since 1.0.0
     * @access public
     */
    public function save() {

        global $current_section;

        $settings = $this->get_settings($current_section);

        do_action('igfw_before_save_settings', $settings);

        \WC_Admin_Settings::save_fields($settings);

        do_action('igfw_after_save_settings', $settings);

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
    public function get_settings($current_section = '') {

        if ($current_section == 'igfw_setting_help_section') {

            // Help Section Options
            $settings = apply_filters('igfw_setting_help_section_options', $this->_get_help_section_options());

        } else {

            // General Section Options
            $settings = apply_filters('igfw_setting_general_section_options', $this->_get_general_section_options());

        }

        return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);

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
    private function _get_general_section_options() {

        return array(

            array(
                'title' => __('General Options', 'invoice-gateway-for-woocommerce'),
                'type' => 'title',
                'desc' => '',
                'id' => 'igfw_general_main_title',
            ),

            array(
                'name' => '',
                'type' => 'igfw_wws_banner_controls',
                'desc' => '',
                'id' => 'igfw_wws_banner',
            ),

            array(
                'name' => '',
                'type' => 'igfw_invoice_gateway_settings_link_field',
                'desc' => '',
                'id' => 'igfw_invoice_gateway_settings_link',
            ),

            array(
                'name' => __('Enable Purchase Order Number', 'invoice-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'desc' => __('Allow adding "Purchase Order Number" in the checkout page and option to add it in the edit order page.', 'invoice-gateway-for-woocommerce'),
                'id' => 'igfw_enable_purchase_order_number',
            ),

            array(
                'name'    => __( 'Default Order Status', 'invoice-gateway-for-woocommerce' ),
                'type'    => 'select',
                'desc'    => __( 'Select the default order status for invoice gateway.', 'invoice-gateway-for-woocommerce' ),
                'id'      => 'igfw_default_order_status',
                'options' => array(
                    'on-hold'    => __( 'On Hold', 'invoice-gateway-for-woocommerce' ),
                    'processing' => __( 'Processing', 'invoice-gateway-for-woocommerce' ),
                    'pending'    => __( 'Pending Payment', 'invoice-gateway-for-woocommerce' ),
                    'completed'  => __( 'Completed', 'invoice-gateway-for-woocommerce' ),
                ),
            ),

            array(
                'type' => 'sectionend',
                'id' => 'igfw_general_sectionend',
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
    private function _get_help_section_options() {

        return array(

            array(
                'title' => __('Help Options', 'invoice-gateway-for-woocommerce'),
                'type' => 'title',
                'desc' => '',
                'id' => 'igfw_help_main_title',
            ),

            array(
                'name' => '',
                'type' => 'igfw_help_resources_field',
                'desc' => '',
                'id' => 'igfw_help_resources',
            ),

            array(
                'title' => __('Clean up plugin options on un-installation', 'invoice-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'desc' => __('If checked, removes all plugin options when this plugin is uninstalled. <b>Warning:</b> This process is irreversible.', 'invoice-gateway-for-woocommerce'),
                'id' => Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS,
            ),

            array(
                'type' => 'sectionend',
                'id' => 'igfw_help_sectionend',
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
     * @param $value
     */
    public function render_igfw_help_resources_field($value) {
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for=""><?php _e('Knowledge Base', 'invoice-gateway-for-woocommerce');?></label>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title($value['type']); ?>">
                <?php echo sprintf(__('Looking for documentation? Please see our growing <a href="%1$s" target="_blank">Knowledge Base</a>', 'invoice-gateway-for-woocommerce'), "https://wordpress.org/plugins/invoice-gateway-for-woocommerce/faq/"); ?>
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
     * @param $value
     */
    public function render_igfw_invoice_gateway_settings_link_field($value) {?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for=""><?php _e('Invoice Gateway Settings', 'invoice-gateway-for-woocommerce');?></label>
            </th>
            </tr>
        <tr valign="top">
            <td>
                <?php echo sprintf(__('Click <a href="%1$s">here</a> to configure the invoice payment gateway.', 'invoice-gateway-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout&section=igfw_invoice_gateway')); ?>
            </td>
        </tr>

    <?php }

    /**
     * Render WWS promo banner.
     *
     * @since 1.0.0
     * @access public
     *
     * @param $value
     */
    public function render_igfw_wws_banner_controls($value) {
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc" colspan="4">
                <a style="outline: none; display: inline-block;" target="_blank" href="https://wholesalesuiteplugin.com/?utm_source=IGFW&utm_medium=Settings">
                    <img style="outline: none; border: 0;" src="<?php echo $this->_constants->IMAGES_ROOT_URL() . 'WWS_Banner.jpg'; ?>" alt="<?php _e('Wholesale Suite Plugin', 'invoice-gateway-for-woocommerce');?>"/>
                </a>
            </th>
        </tr>

        <?php
}

}