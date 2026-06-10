<?php
/**
 * WooCommerce Blocks integration for Invoice Gateway
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Models/Blocks
 * @since 1.1.4
 */

namespace IGFW\Models\Blocks;

use IGFW\Helpers\Plugin_Constants;
use IGFW\Helpers\Helper_Functions;
use IGFW\Interfaces\Model_Interface;
use IGFW\Abstracts\Abstract_Main_Plugin_Class;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use IGFW\Models\Blocks\IGFW_Blocks_Payment_Method;

/**
 * Invoice Gateway Blocks integration
 *
 * @since 1.1.4
 */
class IGFW_Gateway_Blocks_Support implements Model_Interface {

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'igfw_invoice_gateway';

    /**
     * Model that houses all the plugin constants.
     *
     * @var Plugin_Constants
     */
    private $constants;

    /**
     * Singleton instance
     *
     * @var IGFW_Gateway_Blocks_Support
     */
    private static $instance = null;

    /**
     * Constructor
     *
     * @param Abstract_Main_Plugin_Class $main_plugin Main plugin object.
     * @param Plugin_Constants           $constants   Plugin constants object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants ) {
        $this->constants = $constants;

        $main_plugin->add_to_all_plugin_models( $this );
    }

    /**
     * Ensure singleton instance
     *
     * @param Abstract_Main_Plugin_Class $main_plugin Main plugin object.
     * @param Plugin_Constants           $constants   Plugin constants object.
     *
     * @return IGFW_Gateway_Blocks_Support Instance
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants ) {
        if ( null === self::$instance ) {
            self::$instance = new self( $main_plugin, $constants );
        }
        return self::$instance;
    }

    /**
     * Run this model
     */
    public function run() {
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            array( $this, 'initialize_blocks_integration' )
        );

        // Process the purchase order number from REST API.
        add_action(
            'woocommerce_rest_checkout_process_payment_with_context',
            array( $this, 'process_purchase_order_number' ),
            10,
            4
        );
    }

    /**
     * Initialize blocks integration
     *
     * @param PaymentMethodRegistry $payment_method_registry Payment method registry object.
     */
    public function initialize_blocks_integration( PaymentMethodRegistry $payment_method_registry ) {
        require_once $this->constants->plugin_dir_path() . 'Models/Blocks/IGFW_Blocks_Payment_Method.php';

        $payment_method_registry->register( new IGFW_Blocks_Payment_Method() );
    }

    /**
     * Process the purchase order number from REST API.
     *
     * @param PaymentContext $payment_context Payment context.
     * @param PaymentResult  $payment_result  Payment result.
     *
     * @since 1.1.4
     * @since 1.1.6 Enforce the required Purchase Order Number setting.
     *
     * @throws \Exception When a Purchase Order Number is required but missing.
     *
     * @return PaymentResult The payment result; returned for hook-signature symmetry (the action's return value is not consumed).
     */
    public function process_purchase_order_number( PaymentContext $payment_context, PaymentResult &$payment_result ) {

        $payment_method = $payment_context->payment_method;
        $order          = $payment_context->order;

        $save_po_number = wc_string_to_bool( get_option( 'igfw_enable_purchase_order_number', 'no' ) );

        if ( 'igfw_invoice_gateway' !== $payment_method || ! $save_po_number ) {
            return $payment_result;
        }

        $request = $payment_context->payment_data;

        $po_number = isset( $request['igfw_purchase_order_number'] ) ? sanitize_text_field( $request['igfw_purchase_order_number'] ) : '';

        if ( Helper_Functions::is_purchase_order_number_required() && '' === $po_number ) {
            throw new \Exception( esc_html( Helper_Functions::get_purchase_order_number_required_error() ) );
        }

        if ( ! empty( $po_number ) ) {
            $order->update_meta_data( \IGFW\Helpers\Plugin_Constants::PURCHASE_ORDER_NUMBER, $po_number );
            $order->save();
        }

        return $payment_result;
    }
}
