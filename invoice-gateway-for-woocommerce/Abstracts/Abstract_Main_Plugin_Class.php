<?php
namespace IGFW\Abstracts;

use IGFW\Interfaces\Model_Interface;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Abstract class that the main plugin class needs to extend.
 *
 * @since 1.0.0
 */
abstract class Abstract_Main_Plugin_Class {

    /**
     * Property that houses an array of all the "regular models" of the plugin.
     *
     * @since 1.0.0
     * @access protected
     * @var array
     */
    protected $__all_models = array();

    /**
     * Property that houses an array of all "public regular models" of the plugin.
     * Public models can be accessed and utilized by external entities via the main plugin class.
     *
     * @since 1.0.0
     * @access public
     * @var array
     */
    public $models = array();

    /**
     * Add a "regular model" to the main plugin class "all models" array.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Model_Interface $model Regular model.
     */
    public function add_to_all_plugin_models( Model_Interface $model ) {

        $class_name = get_class( $model );
        if ( !array_key_exists( $class_name , $this->__all_models ) )
            $this->__all_models[ $class_name ] = $model;
        
    }

    /**
     * Add a "regular model" to the main plugin class "public models" array.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Model_Interface $model Regular model.
     */
    public function add_to_public_models( Model_Interface $model ) {
        
        $class_name = get_class( $model );
        if ( !array_key_exists( $class_name , $this->models ) )
            $this->models[ $class_name ] = $model;
        
    }

}