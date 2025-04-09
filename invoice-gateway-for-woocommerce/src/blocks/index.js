/**
 * Invoice Gateway for WooCommerce - Checkout Block Integration
 */
import { __ } from "@wordpress/i18n";
import { decodeEntities } from "@wordpress/html-entities";
import { useEffect } from "@wordpress/element";

// Import the SCSS file instead of CSS
import "./style.css";

/**
 * Get payment method data from settings
 */
const settings = window.igfw_invoice_gateway || {
  title: "Invoice Payment",
  description: "Pay with an invoice processed through our accounting system.",
  supports: ["products", "shipping_address"],
  enableForMethods: [],
  enableForVirtual: false,
  enablePurchaseOrderNumber: false,
  purchaseOrderNumberTitle: "Purchase Order (optional)",
  purchaseOrderNumberPlaceholder: "PO Number",
  purchaseOrderNumberDesc:
    "We will generate and send you an invoice for your order, if you have a PO number, please enter it.",
};

/**
 * Invoice Gateway Component
 */
const InvoiceGatewayComponent = ({ eventRegistration, emitResponse }) => {
  const { onPaymentProcessing } = eventRegistration || {};

  // Register event handlers for payment processing
  useEffect(() => {
    if (!onPaymentProcessing) return;

    const unsubscribe = onPaymentProcessing(() => {
      let paymentData = {};

      // Capture purchase order number if enabled
      if (settings.enablePurchaseOrderNumber) {
        const poNumber =
          document.querySelector('input[name="igfw_purchase_order_number"]')
            ?.value || "";
        paymentData.igfw_purchase_order_number = poNumber;
      }

      return {
        type: "success",
        meta: {
          paymentMethodData: {
            igfw_purchase_order_number: paymentData.igfw_purchase_order_number,
          },
        },
      };
    });

    return () => unsubscribe();
  }, [onPaymentProcessing]);

  return (
    <>
      {!settings.enablePurchaseOrderNumber && (
        <div className="igfw-invoice-gateway-description">
          {decodeEntities(settings.description)}
        </div>
      )}

      {settings.enablePurchaseOrderNumber && (
        <div className="igfw-purchase-order-number-container">
          <label htmlFor="igfw_purchase_order_number">
            {decodeEntities(settings.purchaseOrderNumberTitle)}
          </label>
          <div className="wc-block-components-text-input is_active">
            <input
              type="text"
              id="igfw_purchase_order_number"
              name="igfw_purchase_order_number"
              aria-label={decodeEntities(settings.purchaseOrderNumberTitle)}
              placeholder={decodeEntities(
                settings.purchaseOrderNumberPlaceholder
              )}
            />
          </div>
          <p className="igfw-purchase-order-description">
            {decodeEntities(settings.purchaseOrderNumberDesc)}
          </p>
        </div>
      )}
    </>
  );
};

// Register the payment method if WooCommerce blocks registry is available
if (
  window.wc &&
  window.wc.wcBlocksRegistry &&
  window.wc.wcBlocksRegistry.registerPaymentMethod
) {
  const { registerPaymentMethod } = window.wc.wcBlocksRegistry;

  const InvoiceGateway = {
    name: "igfw_invoice_gateway",
    label: decodeEntities(settings.title),
    content: <InvoiceGatewayComponent />,
    edit: <InvoiceGatewayComponent />,
    canMakePayment: ({
      cartTotals,
      cartNeedsShipping,
      selectedShippingMethods,
    }) => {
      // Virtual order, with virtual disabled.
      if (!cartNeedsShipping && !settings.enableForVirtual) {
        console.warn(
          "Not showing invoice gateway because cart is virtual and virtual is disabled"
        );
        return false;
      }

      // If specific shipping methods are enabled, check if current method is allowed
      if (
        cartNeedsShipping &&
        settings.enableForMethods &&
        settings.enableForMethods.length > 0 &&
        selectedShippingMethods.length > 0
      ) {
        const selectedMethod = selectedShippingMethods[0];
        const isEnabled = settings.enableForMethods.some((method) =>
          selectedMethod.startsWith(method)
        );

        if (!isEnabled) {
          return false;
        }
      }

      return true;
    },
    placeOrderButtonLabel: "Place order",
    ariaLabel: decodeEntities(settings.title),
    paymentMethodId: "igfw_invoice_gateway",
    supports: {
      features: settings.supports || ["products", "shipping_address"],
    },
  };

  registerPaymentMethod(InvoiceGateway);
}
