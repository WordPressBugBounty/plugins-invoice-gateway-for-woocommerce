<?php
namespace IGFW\Models;

use IGFW\Abstracts\Abstract_Main_Plugin_Class;

use IGFW\Interfaces\Model_Interface;

use IGFW\Helpers\Plugin_Constants;
use IGFW\Helpers\Helper_Functions;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic of loading plugin scripts.
 * Private Model.
 *
 * @since 1.0.0
 */
class Script_Loader implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Bootstrap.
     *
     * @since 1.0.0
     * @access private
     * @var Bootstrap
     */
    private static $_instance;

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
     * Class constructor.
     * 
     * @since 1.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {
        
        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;

        $main_plugin->add_to_all_plugin_models( $this );

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     * 
     * @since 1.0.0
     * @access public
     * 
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Bootstrap
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );
        
        return self::$_instance;

    }

    /**
     * Load backend js and css scripts.
     *
     * @since 1.0.0
     * @since 1.1.2 Added support for custom orders table.
     * @access public
     *
     * @param string $handle Unique identifier of the current backend page.
     */
    public function load_backend_scripts( $handle ) {

        global $typenow;

        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        if ( ( ( 'post-new.php' === $handle || 'post.php' === $handle ) && $typenow === $screen ) ||
            ( $handle === $screen && isset( $_GET['action'] ) && ( 'new' === $_GET['action'] || 'edit' === $_GET['action'] ) )
        ) {
            wp_enqueue_style( 'igfw_wc-order_css', $this->_constants->CSS_ROOT_URL() . 'order/wc-order.css', array(), Plugin_Constants::VERSION, 'all' );
        }
    }

    /**
     * Load frontend js and css scripts.
     *
     * @since 1.0.0
     * @access public
     */
    public function load_frontend_scripts() {

        global $post, $wp;

    }

    /**
     * Execute plugin script loader.
     * 
     * @since 1.0.0
     * @access public
     */
    public function run () {

        add_action( 'admin_enqueue_scripts' , array( $this , 'load_backend_scripts' ) , 10 , 1 );
        add_action( 'wp_enqueue_scripts' , array( $this , 'load_frontend_scripts' ) );

    }

}
