<?php
/**
 * Plugin constants class file.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Helpers
 * @since 1.0.0
 * @since 1.1.4 - Applied PHPCS Rules. Compatibility for PHP 8.2+
 */

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

    /**
     * |--------------------------------------------------------------------------
     * | Class Properties
     * |--------------------------------------------------------------------------
     */

    /**
     * The Instance of the Plugin_Constants class.
     *
     * @var Plugin_Constants
     */
    private static $instance;

    // Plugin configuration constants.
    const TOKEN               = 'igfw';
    const INSTALLED_VERSION   = 'igfw_installed_version';
    const VERSION             = '1.1.6';
    const TEXT_DOMAIN         = 'invoice-gateway-for-woocommerce';
    const THEME_TEMPLATE_PATH = 'invoice-gateway-for-woocommerce';

    // Order Post Meta.
    const INVOICE_NUMBER = '_igfw_invoice_number';

    const PURCHASE_ORDER_NUMBER = '_igfw_purchase_order_number';

    const PAYMENT_DUE_DATE = '_igfw_payment_due_date';

    const PAYMENT_REMINDER_SENT = '_igfw_payment_reminder_sent';

    // Settings Constants.

    // Help Section.
    const CLEAN_UP_PLUGIN_OPTIONS = 'igfw_clean_up_plugin_options';

    // Add property declarations to fix deprecation warnings.
    /**
     * The main plugin file path.
     *
     * @var string
     */
    private $main_plugin_file_path;

    /**
     * The plugin directory path.
     *
     * @var string
     */
    private $plugin_dir_path;

    /**
     * The plugin directory URL.
     *
     * @var string
     */
    private $plugin_dir_url;

    /**
     * The plugin basename.
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * The plugin main file.
     *
     * @var string
     */
    private $plugin_main_file;

    /**
     * The CSS root URL.
     *
     * @var string
     */
    private $css_root_url;

    /**
     * The images root URL.
     *
     * @var string
     */
    private $images_root_url;

    /**
     * The JS root URL.
     *
     * @var string
     */
    private $js_root_url;

    /**
     * The views root path.
     *
     * @var string
     */
    private $views_root_path;

    /**
     * The templates root path.
     *
     * @var string
     */
    private $templates_root_path;


    /**
     * The logs root path.
     *
     * @var string
     */
    private $logs_root_path;

    /**
     * The build directory path.
     *
     * @var string
     */
    private $build_dir_path;

    /**
     * The build directory URL.
     *
     * @var string
     */
    private $build_dir_url;

    /**
     * |--------------------------------------------------------------------------
     * | Class Methods
     * |--------------------------------------------------------------------------
     */

    /**
     * Construct.
     */
    public function __construct() {

        // Path constants.
        $this->main_plugin_file_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'invoice-gateway-for-woocommerce' . DIRECTORY_SEPARATOR . 'invoice-gateway-for-woocommerce.php';
        $this->plugin_dir_path       = plugin_dir_path( $this->main_plugin_file_path );
        $this->plugin_dir_url        = plugin_dir_url( $this->main_plugin_file_path );
        $this->plugin_basename       = plugin_basename( dirname( $this->main_plugin_file_path ) );
        $this->plugin_main_file      = IGFW_PLUGIN_FILE;

        $this->css_root_url    = $this->plugin_dir_url . 'css/';
        $this->images_root_url = $this->plugin_dir_url . 'images/';
        $this->build_dir_url   = $this->plugin_dir_url . 'build/';
        $this->js_root_url     = $this->plugin_dir_url . 'js/';

        $this->views_root_path     = $this->plugin_dir_path . 'views/';
        $this->templates_root_path = $this->plugin_dir_path . 'templates/';
        $this->logs_root_path      = $this->plugin_dir_path . 'logs/';
        $this->build_dir_path      = $this->plugin_dir_path . 'build/';
    }

    /**
     * Get the instance of the Plugin_Constants class.
     *
     * @return Plugin_Constants
     */
    public static function get_instance() {

        if ( ! self::$instance instanceof self ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the main plugin file path.
     *
     * @return string
     */
    public function main_plugin_file_path() {
        return $this->main_plugin_file_path;
    }

    /**
     * Get the plugin directory path.
     *
     * @return string
     */
    public function plugin_dir_path() {
        return $this->plugin_dir_path;
    }

    /**
     * Get the plugin directory URL.
     *
     * @return string
     */
    public function plugin_dir_url() {
        return $this->plugin_dir_url;
    }

    /**
     * Get the plugin basename.
     *
     * @return string
     */
    public function plugin_basename() {
        return $this->plugin_basename;
    }

    /**
     * Get the plugin main file.
     *
     * @return string
     */
    public function plugin_main_file() {
        return $this->plugin_main_file;
    }

    /**
     * Get the CSS root URL.
     *
     * @return string
     */
    public function css_root_url() {
        return $this->css_root_url;
    }

    /**
     * Get the images root URL.
     *
     * @return string
     */
    public function images_root_url() {
        return $this->images_root_url;
    }

    /**
     * Get the JS root URL.
     *
     * @return string
     */
    public function js_root_url() {
        return $this->js_root_url;
    }

    /**
     * Get the views root path.
     *
     * @return string
     */
    public function views_root_path() {
        return $this->views_root_path;
    }

    /**
     * Get the templates root path.
     *
     * @return string
     */
    public function templates_root_path() {
        return $this->templates_root_path;
    }

    /**
     * Get the logs root path.
     *
     * @return string
     */
    public function logs_root_path() {
        return $this->logs_root_path;
    }

    /**
     * Get the build directory path.
     *
     * @return string
     */
    public function build_dir_path() {
        return $this->build_dir_path;
    }

    /**
     * Get the build directory URL.
     *
     * @return string
     */
    public function build_dir_url() {
        return $this->build_dir_url;
    }
}
