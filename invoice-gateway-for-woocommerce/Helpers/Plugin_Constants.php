<?php
namespace IGFW\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Model that houses all the plugin constants.
 * Note as much as possible, we need to make this class succinct as the only purpose of this is to house all the constants that is utilized by the plugin.
 * Therefore we omit class member comments and minimize comments as much as possible.
 * In fact the only verbouse comment here is this comment you are reading right now.
 * And guess what, it just got worse coz now this comment takes 5 lines instead of 3.
 *
 * @since 1.0.0
 */
class Plugin_Constants {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    private static $_instance;

    // Plugin configuration constants
    const TOKEN               = 'igfw';
    const INSTALLED_VERSION   = 'igfw_installed_version';
    const VERSION             = '1.1.2';
    const TEXT_DOMAIN         = 'invoice-gateway-for-woocommerce';
    const THEME_TEMPLATE_PATH = 'invoice-gateway-for-woocommerce';

    // Order Post Meta
    const Invoice_Number        = 'igfw_invoice_number';
    const Purchase_Order_Number = 'igfw_purchase_order_number';

    // Settings Constants

    // Help Section
    const CLEAN_UP_PLUGIN_OPTIONS = 'igfw_clean_up_plugin_options';

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    public function __construct() {

        // Path constants
        $this->_MAIN_PLUGIN_FILE_PATH = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'invoice-gateway-for-woocommerce' . DIRECTORY_SEPARATOR . 'invoice-gateway-for-woocommerce.php';
        $this->_PLUGIN_DIR_PATH       = plugin_dir_path( $this->_MAIN_PLUGIN_FILE_PATH );
        $this->_PLUGIN_DIR_URL        = plugin_dir_url( $this->_MAIN_PLUGIN_FILE_PATH );
        $this->_PLUGIN_BASENAME       = plugin_basename( dirname( $this->_MAIN_PLUGIN_FILE_PATH ) );
        $this->_PLUGIN_MAIN_FILE      = IGFW_PLUGIN_FILE;

        $this->_CSS_ROOT_URL    = $this->_PLUGIN_DIR_URL . 'css/';
        $this->_IMAGES_ROOT_URL = $this->_PLUGIN_DIR_URL . 'images/';
        $this->_JS_ROOT_URL     = $this->_PLUGIN_DIR_URL . 'js/';

        $this->_VIEWS_ROOT_PATH     = $this->_PLUGIN_DIR_PATH . 'views/';
        $this->_TEMPLATES_ROOT_PATH = $this->_PLUGIN_DIR_PATH . 'templates/';
        $this->_LOGS_ROOT_PATH      = $this->_PLUGIN_DIR_PATH . 'logs/';
    }

    public static function get_instance() {

        if ( ! self::$_instance instanceof self ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function MAIN_PLUGIN_FILE_PATH() {
        return $this->_MAIN_PLUGIN_FILE_PATH;
    }

    public function PLUGIN_DIR_PATH() {
        return $this->_PLUGIN_DIR_PATH;
    }

    public function PLUGIN_DIR_URL() {
        return $this->_PLUGIN_DIR_URL;
    }

    public function PLUGIN_BASENAME() {
        return $this->_PLUGIN_BASENAME;
    }
    
    public function PLUGIN_MAIN_FILE() {
        return $this->_PLUGIN_MAIN_FILE;
    }

    public function CSS_ROOT_URL() {
        return $this->_CSS_ROOT_URL;
    }

    public function IMAGES_ROOT_URL() {
        return $this->_IMAGES_ROOT_URL;
    }

    public function JS_ROOT_URL() {
        return $this->_JS_ROOT_URL;
    }

    public function VIEWS_ROOT_PATH() {
        return $this->_VIEWS_ROOT_PATH;
    }

    public function TEMPLATES_ROOT_PATH() {
        return $this->_TEMPLATES_ROOT_PATH;
    }

    public function LOGS_ROOT_PATH() {
        return $this->_LOGS_ROOT_PATH;
    }

}
