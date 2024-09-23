<?php
namespace IGFW\Models;

use IGFW\Abstracts\Abstract_Main_Plugin_Class;

use IGFW\Interfaces\Model_Interface;
use IGFW\Interfaces\Activatable_Interface;
use IGFW\Interfaces\Initiable_Interface;

use IGFW\Helpers\Plugin_Constants;
use IGFW\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic of 'Bootstraping' the plugin.
 * Private Model.
 *
 * @since 1.0.0
 */
class Bootstrap implements Model_Interface {

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

    /**
     * Array of models implementing the IGFW\Interfaces\Activatable_Interface.
     * 
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_activatables;

    /**
     * Array of models implementing the IGFW\Interfaces\Initiable_Interface.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_initiables;




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
     * @param array                      $activatables     Array of models implementing IGFW\Interfaces\Activatable_Interface.
     * @param array                      $initiables       Array of models implementing IGFW\Interfaces\Initiable_Interface.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions , array $activatables = array() , array $initiables = array() ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;
        $this->_activatables     = $activatables;
        $this->_initiables       = $initiables;

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
     * @param array                      $activatables     Array of models implementing IGFW\Interfaces\Activatable_Interface.
     * @param array                      $initiables       Array of models implementing IGFW\Interfaces\Initiable_Interface.
     * @return Bootstrap
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions , array $activatables = array() , array $initiables = array() ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions , $activatables , $initiables );
        
        return self::$_instance;

    }

    /**
     * Load plugin text domain.
     *
     * @since 1.0.0
     * @access public
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain( Plugin_Constants::TEXT_DOMAIN , false , $this->_constants->PLUGIN_BASENAME() . '/languages' );

    }

    /**
     * Method that houses the logic relating to activating the plugin.
     *
     * @since 1.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     * 
     * @param boolean $network_wide Flag that determines whether the plugin has been activated network wid ( on multi site environment ) or not.
     */
    public function activate_plugin( $network_wide ) {

        global $wpdb;

        if ( is_multisite() ) {

            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    $this->_activate_plugin( $blog_id );

                }

                restore_current_blog();

            } else
                $this->_activate_plugin( $wpdb->blogid ); // activated on a single site, in a multi-site

        } else
            $this->_activate_plugin( $wpdb->blogid ); // activated on a single site

    }

    /**
     * Method to initialize a newly created site in a multi site set up.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int    $blogid  Blog ID of the created blog.
     * @param int    $user_id User ID of the user creating the blog.
     * @param string $domain  Domain used for the new blog.
     * @param string $path    Path to the new blog.
     * @param int    $site_id Site ID. Only relevant on multi-network installs.
     * @param array  $meta Meta data. Used to set initial site options.
     */
    public function new_mu_site_init( $blog_id , $user_id , $domain , $path , $site_id , $meta ) {

        if ( is_plugin_active_for_network( 'invoice-gateway-for-woocommerce/invoice-gateway-for-woocommerce.php' ) ) {

            switch_to_blog( $blog_id );
            $this->_activate_plugin( $blog_id );
            restore_current_blog();

        }

    }

    /**
     * Initialize plugin settings options.
     * This is a compromise to my idea of 'Modularity'. Ideally, bootstrap should not take care of plugin settings stuff.
     * However due to how WooCommerce do its thing, we need to do it this way. We can't separate settings on its own.
     *
     * @since 1.0.0
     * @access private
     */
    private function _initialize_plugin_settings_options() {

        // Help settings section options

        // Set initial value of 'no' for the option that sets the option that specify whether to delete the options on plugin uninstall. Optionception.
        if ( !get_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS , false ) )
            update_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS , 'no' );
        
    }
    
    /**
     * Actual function that houses the code to execute on plugin activation.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int $blogid Blog ID of the created blog.
     */
    private function _activate_plugin( $blogid ) {

        // Initialize settings options
        $this->_initialize_plugin_settings_options();

        // Execute 'activate' contract of models implementing IGFW\Interfaces\Activatable_Interface
        foreach ( $this->_activatables as $activatable )
            if ( $activatable instanceof Activatable_Interface )
                $activatable->activate();
        
        // Update current installed plugin version
        update_option( Plugin_Constants::INSTALLED_VERSION , Plugin_Constants::VERSION );

        flush_rewrite_rules();

    }

    /**
     * Method that houses the logic relating to deactivating the plugin.
     *
     * @since 1.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param boolean $network_wide Flag that determines whether the plugin has been activated network wid ( on multi site environment ) or not.
     */
    public function deactivate_plugin( $network_wide ) {

        global $wpdb;

        // check if it is a multisite network
        if ( is_multisite() ) {

            // check if the plugin has been activated on the network or on a single site
            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                
                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    $this->_deactivate_plugin( $wpdb->blogid );

                }

                restore_current_blog();

            } else
                $this->_deactivate_plugin( $wpdb->blogid ); // activated on a single site, in a multi-site
            
        } else
            $this->_deactivate_plugin( $wpdb->blogid ); // activated on a single site
        
    }

    /**
     * Actual method that houses the code to execute on plugin deactivation.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int $blogid Blog ID of the created blog.
     */
    private function _deactivate_plugin( $blogid ) {

        flush_rewrite_rules();

    }

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.0.0
     * @access public
     */
    public function initialize() {
        
        // Execute 'initialize' contract of models implementing IGFW\Interfaces\Initiable_Interface
        foreach ( $this->_initiables as $initiable )
            if ( $initiable instanceof Initiable_Interface )
                $initiable->initialize();
        
    }

    /**
     * Declare compatibility with WooCommerce HPOS.
     *
     * @since 1.1.2
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'invoice-gateway-for-woocommerce/invoice-gateway-for-woocommerce.php', true );
        }
    }

    /**
     * Add plugin listing custom action link ( settings )
     *
     * @since 1.1.3
     *
     * @param array  $links Array of plugin action links.
     * @param string $file  Plugin file path.
     * @return array
     */
    public function add_plugin_action_links( $links, $file ) {
        if ( $this->_constants->PLUGIN_MAIN_FILE() === $file ) {
            $settings_link = '<a href="admin.php?page=wc-settings&tab=igfw_settings">' . __( 'Settings', 'invoice-gateway-for-woocommerce' ) . '</a>';
            array_unshift( $links, $settings_link );
        }
        return $links;
    }

    /**
     * Execute plugin bootstrap code.
     * 
     * @since 1.0.0
     * @access public
     */
    public function run() {

        // Internationalization
        add_action( 'plugins_loaded' , array( $this , 'load_plugin_textdomain' ) );

        // Execute plugin activation/deactivation
        register_activation_hook( $this->_constants->MAIN_PLUGIN_FILE_PATH() , array( $this , 'activate_plugin' ) );
        register_deactivation_hook( $this->_constants->MAIN_PLUGIN_FILE_PATH() , array( $this , 'deactivate_plugin' ) );

        // Execute plugin initialization ( plugin activation ) on every newly created site in a multi site set up
        add_action( 'wpmu_new_blog' , array( $this , 'new_mu_site_init' ) , 10 , 6 );

        // Execute codes that need to run on 'init' hook
        add_action( 'init' , array( $this , 'initialize' ) );

        // HPOS compatibility.
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );

        // Register Invoice Payment Gateway
        // We half to do it this way due to how WooCommerce do its thing
        add_filter( 'woocommerce_payment_gateways' , function( $methods ) {

            $methods[] = 'IGFW\Models\Gateways\IGFW_Invoice_Gateway'; 
            return $methods;

        } , 10 , 1 );

        add_filter('plugin_action_links', array($this, 'add_plugin_action_links'), 10, 2);

        // Register Settings Page
        // We half to do it this way due to how WooCommerce do its thing
        add_filter( 'woocommerce_get_settings_pages' , function( $settings ) {

            $settings[] = new \IGFW\Models\IGFW_Settings( $this->_constants , $this->_helper_functions );
            return $settings;

        } , 10 , 1 );

    }

}
