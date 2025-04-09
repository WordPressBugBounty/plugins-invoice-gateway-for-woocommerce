<?php
/**
 * Activatable interface file.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Interfaces
 * @since 1.0.0
 * @since 1.1.4 - Applied PHPCS Rules. Compatibility for PHP 8.2+
 */

namespace IGFW\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Abstraction that provides contract relating to activation.
 * Any model that needs some sort of activation must implement this interface.
 *
 * @since 1.0.0
 */
interface Activatable_Interface {

    /**
     * Contract for activation.
     *
     * @since 1.0.0
     * @access public
     */
    public function activate();
}
