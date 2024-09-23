<?php
namespace IGFW\Helpers;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses all the helper functions of the plugin.
 *
 * 1.0.0
 */
class Helper_Functions {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Helper_Functions.
     *
     * @since 1.0.0
     * @access private
     * @var Helper_Functions
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
     * @param Plugin_Constants $constants Plugin constants object.
     */
    public function __construct( Plugin_Constants $constants ) {

        $this->_constants = $constants;

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     * 
     * @since 1.0.0
     * @access public
     * 
     * @param Plugin_Constants $constants Plugin constants object.
     * @return Helper_Functions
     */
    public static function get_instance( Plugin_Constants $constants ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $constants );
        
        return self::$_instance;

    }
    



    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
    */

    /**
     * Write data to plugin log file.
     *
     * @since 1.0.0
     * @access public
     *
     * @param mixed Data to log.
     */
    public function write_debug_log( $log )  {

        error_log( "\n[" . current_time( 'mysql' ) . "]\n" . $log . "\n--------------------------------------------------\n" , 3 , $this->_constants->LOGS_ROOT_PATH() . 'debug.log' );

    }

    /**
     * Check if current user is authorized to manage the plugin on the backend.
     * 
     * @since 1.0.0
     * @access public
     *
     * @param WP_User $user WP_User object.
     * @return boolean True if authorized, False otherwise.
     */
    public function current_user_authorized( $user = null ) {

        // Array of roles allowed to access/utilize the plugin
        $admin_roles = apply_filters( 'igfw_admin_roles' , array( 'administrator' ) );

        if ( is_null( $user ) )
            $user = wp_get_current_user();

        if ( $user->ID )
            return count( array_intersect( ( array ) $user->roles , $admin_roles ) ) ? true : false;
        else
            return false;
        
    }

    /**
     * Returns the timezone string for a site, even if it's set to a UTC offset
     * 
     * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
     * 
     * Reference:
     * http://www.skyverge.com/blog/down-the-rabbit-hole-wordpress-and-timezones/
     * 
     * @since 1.0.0
     * @access public
     * 
     * @return string Valid PHP timezone string
     */
    public function get_site_current_timezone() {

        // if site timezone string exists, return it
        if ( $timezone = get_option( 'timezone_string' ) )
            return $timezone;

        // get UTC offset, if it isn't set then return UTC
        if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
            return 'UTC';

        return convert_utc_offset_to_timezone( $utc_offset );
        
    }

    /**
     * Conver UTC offset to timezone.
     *
     * @since 1.0.0
     * @access public
     *
     * @param float/int/string $utc_offset UTC offset.
     * @return string valid PHP timezone string
     */
    public function convert_utc_offset_to_timezone( $utc_offset ) {

        // adjust UTC offset from hours to seconds
        $utc_offset *= 3600;

        // attempt to guess the timezone string from the UTC offset
        if ( $timezone = timezone_name_from_abbr( '' , $utc_offset , 0 ) )
            return $timezone;
        
        // last try, guess timezone string manually
        $is_dst = date( 'I' );

        foreach ( timezone_abbreviations_list() as $abbr )
            foreach ( $abbr as $city )
                if ( $city[ 'dst' ] == $is_dst && $city[ 'offset' ] == $utc_offset )
                    return $city[ 'timezone_id' ];
        
        // fallback to UTC
        return 'UTC';

    }

    /**
     * Get all user roles.
     *
     * @since 1.0.0
     * @access public
     *
     * @global WP_Roles $wp_roles Core class used to implement a user roles API.
     *
     * @return array Array of all site registered user roles. User role key as the key and value is user role text.
     */
    public function get_all_user_roles() {

        global $wp_roles;
        return $wp_roles->get_names();

    }


}
