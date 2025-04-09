<?php
/**
 * WPAY Pointer functionality for admin interface.
 *
 * @package Invoice_Gateway_For_WooCommerce
 * @subpackage Models
 * @since 1.1.4
 */

namespace IGFW\Models;

use IGFW\Abstracts\Abstract_Main_Plugin_Class;

use IGFW\Interfaces\Model_Interface;

use IGFW\Helpers\Plugin_Constants;
use IGFW\Helpers\Helper_Functions;

/**
 * Class WPAY_Pointer
 *
 * @since 1.1.4
 * @package IGFW\Models
 */
class WPAY_Pointer implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the WPAY plugin name.
     *
     * @since 1.1.4
     * @access private
     * @var string
     */
    const WPAY_PLUGIN_NAME = 'woocommerce-wholesale-payments/woocommerce-wholesale-payments.php';

    /**
     * Property that holds the single main instance of WPAY_Pointer.
     *
     * @since 1.1.4
     * @access private
     * @var WPAY_Pointer
     */
    private static $instance;

    /**
     * Property that holds the main plugin instance.
     *
     * @since 1.1.4
     * @access private
     * @var Abstract_Main_Plugin_Class
     */
    private $main_plugin;

    /**
     * Property that holds the constants instance.
     *
     * @since 1.1.4
     * @access private
     * @var Plugin_Constants
     */
    private $constants;

    /**
     * Property that holds the helper functions instance.
     *
     * @since 1.1.4
     * @access private
     * @var Helper_Functions
     */
    private $helper_functions;

    /**
     * Constructor
     *
     * @param Abstract_Main_Plugin_Class $main_plugin Main plugin instance.
     * @param Plugin_Constants           $constants   The plugin constants instance.
     * @param Helper_Functions           $helper_functions The helper functions instance.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {
        $this->main_plugin      = $main_plugin;
        $this->constants        = $constants;
        $this->helper_functions = $helper_functions;
        $main_plugin->add_to_all_plugin_models( $this );
    }

    /**
     * Get instance
     *
     * @param Abstract_Main_Plugin_Class $main_plugin Main plugin instance.
     * @param Plugin_Constants           $constants   The plugin constants instance.
     * @param Helper_Functions           $helper_functions The helper functions instance.
     * @return WPAY_Pointer
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self( $main_plugin, $constants, $helper_functions );
        }
        return self::$instance;
    }

    /**
     * Run
     *
     * @return void
     */
    public function run() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_pointer' ), 10 );
        add_action( 'admin_print_footer_scripts', array( $this, 'load_pointer' ), 10 );
        add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 99 );
        add_action( 'wp_ajax_igfw_dismiss_pointer', array( $this, 'dismiss_pointer' ) );
        register_activation_hook( IGFW_PLUGIN_FILE, array( $this, 'activate' ) );
    }

    /**
     * Admin bar menu
     *
     * @return void
     */
    public function admin_bar_menu() {
        global $wp_admin_bar;

        if ( ! is_admin() || ! $this->should_load_pointer() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $wp_admin_bar->add_node(
            array(
                'id'    => 'igfw_toolbar',
                'title' => $this->get_admin_bar_title(),
                'href'  => admin_url( 'admin.php?page=wc-settings&tab=igfw_settings' ),
            )
        );
    }

    /**
     * Get the admin bar title
     *
     * @since 1.1.4
     * @access public
     *
     * @return string
     */
    public function get_admin_bar_title() {
        ob_start();
        ?>
        <span id="igfw_toolbar-container">
            <img src="<?php echo esc_url( $this->constants->images_root_url() ); ?>wholesale-suite.svg" alt="<?php esc_attr_e( 'Wholesale Payments', 'invoice-gateway-for-woocommerce' ); ?>" style="width: 20px; height: 20px; margin-left: 10px;" />
            <?php esc_html_e( 'Wholesale Payments', 'invoice-gateway-for-woocommerce' ); ?>
        </span>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue pointer
     *
     * @return void
     */
    public function enqueue_pointer() {
        if ( ! $this->should_load_pointer() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_enqueue_style( 'wp-pointer' );
        wp_enqueue_script( 'wp-pointer' );
        wp_enqueue_style(
            'igfw-admin-pointer',
            $this->constants->build_dir_url() . 'pointer.css',
            array(),
            $this->constants::VERSION
        );
    }

    /**
     * Get pointer content
     *
     * @since 1.1.4
     * @access public
     *
     * @return string
     */
    public function get_pointer_content() {
        $content  = '<h3>' . esc_html__( 'Setup Wholesale Payments (Recommended)', 'invoice-gateway-for-woocommerce' ) . '</h3>';
        $content .= '<p>' . esc_html__( "We hope you're enjoying Invoice Gateways plugin. Did you know there's a more powerful way to invoice your customers? It's called Wholesale Payments plugin.", 'invoice-gateway-for-woocommerce' ) . '</p>';
        $content .= '<ul>';
        $content .= '<li>' . esc_html__( 'Create NET 30, 60, or any other invoice payment plan', 'invoice-gateway-for-woocommerce' ) . '</li>';
        $content .= '<li>' . esc_html__( 'Works exclusively with wholesale customers', 'invoice-gateway-for-woocommerce' ) . '</li>';
        $content .= '<li>' . esc_html__( 'Restrict which customers can use payment plans', 'invoice-gateway-for-woocommerce' ) . '</li>';
        $content .= '<li>' . esc_html__( "Backed by Stripe's Invoice API", 'invoice-gateway-for-woocommerce' ) . '</li>';
        $content .= '</ul>';

        $utm_url = esc_url( Helper_Functions::get_utm_url( 'woocommerce-wholesale-payments', 'igfw', 'upsell', 'upsellpointer' ) );

        $wpay_installed = $this->helper_functions->is_plugin_installed( self::WPAY_PLUGIN_NAME );

        if ( ! $wpay_installed ) {
            $content .= '<p><a href=\'' . $utm_url . '\' target=\'_blank\' class=\'button button-primary\'>' . esc_html__( 'Get Wholesale Payments', 'invoice-gateway-for-woocommerce' ) . '</a></p>';
        } elseif ( $wpay_installed && ! is_plugin_active( self::WPAY_PLUGIN_NAME ) ) {
            $activate_url = wp_nonce_url( 'plugins.php?action=activate&plugin=' . self::WPAY_PLUGIN_NAME, 'activate-plugin_' . self::WPAY_PLUGIN_NAME );
            $content     .= '<p><a href=\'' . $activate_url . '\' class=\'button button-primary\'>' . esc_html__( 'Activate Wholesale Payments', 'invoice-gateway-for-woocommerce' ) . '</a></p>';
        }

        return $content;
    }

    /**
     * Maybe load pointer
     *
     * @since 1.1.4
     * @access public
     *
     * @return bool
     */
    public function should_load_pointer() {
        if ( is_plugin_active( self::WPAY_PLUGIN_NAME ) ) {
            return true;
        }

        if ( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) !== 'wc-settings' ) {
            return false;
        }

        $activation_date     = get_option( 'igfw_activation_date' );
        $activation_datetime = $activation_date ? strtotime( $activation_date ) : false;
        $now                 = time();

        $date_diff = $activation_datetime ? ( $now - $activation_datetime ) / DAY_IN_SECONDS : false;

        if ( $date_diff < 21 || $this->is_user_pointer_dismissed() ) {
            return false;
        }

        return true;
    }

    /**
     * Load pointer
     *
     * @since 1.1.4
     * @access public
     *
     * @return void
     */
    public function load_pointer() {
        if ( ! $this->should_load_pointer() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#wp-admin-bar-igfw_toolbar").pointer({
                    "content": "<?php echo wp_kses_post( trim( $this->get_pointer_content() ) ); ?>",
                    "buttons": function (event, t) {
                        var redirectUrl = '<?php echo admin_url( 'admin-ajax.php?action=igfw_dismiss_pointer&key=igfw_dismiss_pointer&nonce=' . wp_create_nonce( 'igfw_dismiss_pointer' ) . '&redirect=' . basename( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore ?>';
                        var button = $('<a class="close" href="' + redirectUrl + '"></a>').text(wp.i18n.__('Dismiss Forever'));

                        return button.on('click.pointer', function (e) {
                            e.preventDefault();
                            jQuery('#wp-admin-bar-igfw_toolbar').remove();
                            window.location.href = redirectUrl;
                            t.element.pointer('close');
                        });
                    },
                    "position": {"edge": "top", "align": "center"},
                    "pointerClass": "igfw-bar-tooltip",
                    "pointerWidth": 370,
                }).pointer('open');
            });
        </script>
        <?php
    }

    /**
     * Dismiss admin notice
     *
     * @since 1.1.4
     * @access public
     */
    public function dismiss_pointer() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $notice_key   = isset( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : ''; // phpcs:ignore

        if ( $notice_key && 'igfw_dismiss_pointer' === $notice_key && isset( $_REQUEST['nonce'] ) && false !== check_ajax_referer( 'igfw_dismiss_pointer', 'nonce', false ) ) {
            update_user_meta( get_current_user_id(), '_igfw_dismiss_pointer', 'yes' );
        }

        $redirect_url = isset( $_REQUEST['redirect'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect'] ) ) : admin_url( 'admin.php?page=wc-settings&tab=igfw_settings' ); // phpcs:ignore
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Activate
     *
     * @since 1.1.4
     * @access public
     */
    public function activate() {
        update_option( 'igfw_activation_date', current_time( 'mysql' ) );
    }

    /**
     * Check if the user has dismissed the pointer
     *
     * @since 1.1.4
     * @access public
     *
     * @param int $user_id User ID.
     * @return bool
     */
    public function is_user_pointer_dismissed( $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        $dismissed = get_user_meta( $user_id, '_igfw_dismiss_pointer', true );

        if ( 'yes' === $dismissed ) {
            return true;
        }

        return false;
    }
}
