<?php
/**
 * Model interface file.
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
 * Abstraction that provides contract relating to plugin models.
 * All "regular models" should implement this interface.
 *
 * @since 1.0.0
 */
interface Model_Interface {

    /**
     * Contract for running the model.
     *
     * @since 1.0.0
     * @access public
     */
    public function run();
}
