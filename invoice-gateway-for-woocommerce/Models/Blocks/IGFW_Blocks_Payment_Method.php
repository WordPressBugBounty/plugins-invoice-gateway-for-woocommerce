<?php
/**
 * WooCommerce Blocks integration payment method class
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Models/Blocks
 * @since 1.1.4
 */

namespace IGFW\Models\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use IGFW\Helpers\Helper_Functions;

/**
 * Invoice Gateway Blocks payment method
 */
class IGFW_Blocks_Payment_Method extends AbstractPaymentMethodType {

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'igfw_invoice_gateway';

    /**
     * Settings for this payment method
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_igfw_invoice_gateway_settings', array() );
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $asset_path   = WP_PLUGIN_DIR . '/invoice-gateway-for-woocommerce/build/index.asset.php';
        $version      = \IGFW\Helpers\Plugin_Constants::VERSION;
        $dependencies = array();

        if ( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = is_array( $asset ) && isset( $asset['version'] ) ? $asset['version'] : $version;
            $dependencies = is_array( $asset ) && isset( $asset['dependencies'] ) ? $asset['dependencies'] : $dependencies;
        }

        wp_register_script(
            'igfw-blocks-integration',
            plugins_url( '/build/index.js', WP_PLUGIN_DIR . '/invoice-gateway-for-woocommerce/invoice-gateway-for-woocommerce.php' ),
            $dependencies,
            $version,
            true
        );

        return array( 'igfw-blocks-integration' );
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'                            => isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Invoice Payment', 'invoice-gateway-for-woocommerce' ),
            'description'                      => isset( $this->settings['description'] ) ? $this->settings['description'] : __( 'Pay with an invoice processed through our accounting system.', 'invoice-gateway-for-woocommerce' ),
            'supports'                         => $this->get_supported_features(),
            'enableForMethods'                 => isset( $this->settings['enable_for_methods'] ) ? $this->settings['enable_for_methods'] : array(),
            'enableForVirtual'                 => isset( $this->settings['enable_for_virtual'] ) && 'yes' === $this->settings['enable_for_virtual'],
            'enablePurchaseOrderNumber'        => 'yes' === get_option( 'igfw_enable_purchase_order_number', 'no' ),
            'requirePurchaseOrderNumber'       => Helper_Functions::is_purchase_order_number_required(),
            'purchaseOrderNumberTitle'         => Helper_Functions::get_purchase_order_number_title(),
            'purchaseOrderNumberPlaceholder'   => apply_filters( 'igfw_purchase_order_number_placeholder', __( 'PO Number', 'invoice-gateway-for-woocommerce' ) ),
            'purchaseOrderNumberDesc'          => apply_filters( 'igfw_purchase_order_number_desc', __( 'We will generate and send you an invoice for your order, if you have a PO number, please enter it.', 'invoice-gateway-for-woocommerce' ) ),
            'purchaseOrderNumberRequiredError' => Helper_Functions::get_purchase_order_number_required_error(),
        );
    }

    /**
     * Get supported features
     *
     * @return array
     */
    public function get_supported_features() {
        return array(
            'products',
            'shipping_address',
        );
    }
}
