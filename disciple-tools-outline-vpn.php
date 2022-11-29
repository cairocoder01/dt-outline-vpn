<?php
/**
 * Plugin Name: Disciple.Tools - Outline VPN
 * Plugin URI: https://github.com/cairocoder01/disciple-tools-outline-vpn
 * Description: Disciple.Tools - Outline VPN is intended to integrate login security using Outline VPN.
 * Text Domain: disciple-tools-outline-vpn
 * Domain Path: /languages
 * Version:  0.1
 * Author URI: https://github.com/cairocoder01
 * GitHub Plugin URI: https://github.com/cairocoder01/disciple-tools-outline-vpn
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 6.1.1
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gets the instance of the `Disciple_Tools_Outline_VPN` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function disciple_tools_outline_vpn() {
    $disciple_tools_outline_vpn_required_dt_theme_version = '1.19';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( 'Disciple_Tools' );
    if ( $is_theme_dt && version_compare( $version, $disciple_tools_outline_vpn_required_dt_theme_version, '<' ) ) {
        add_action( 'admin_notices', 'disciple_tools_outline_vpn_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return Disciple_Tools_Outline_VPN::instance();

}
add_action( 'after_setup_theme', 'disciple_tools_outline_vpn', 20 );

//register the D.T Plugin
add_filter( 'dt_plugins', function ( $plugins ){
    $plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version', 'Plugin Name' => 'Plugin Name' ], false );
    $plugins['disciple-tools-outline-vpn'] = [
        'plugin_url' => trailingslashit( plugin_dir_url( __FILE__ ) ),
        'version' => $plugin_data['Version'] ?? null,
        'name' => $plugin_data['Plugin Name'] ?? null,
    ];
    return $plugins;
});

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class Disciple_Tools_Outline_VPN {

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        if ( is_admin() ) {
            require_once( 'admin/admin-menu-and-tabs.php' ); // adds starter admin page and section for plugin
        }

        $this->i18n();

        if ( is_admin() ) { // adds links to the plugin description area in the plugin admin list.

            add_filter( 'user_row_actions', array( $this, 'filter_user_row_actions' ), 10, 2 );
            add_action( 'init', array( $this, 'action_init' ) );
        }

    }

    /**
     * Adds a 'Switch To' link to each list of user actions on the Users screen.
     *
     * @param array<string,string> $actions Array of actions to display for this user row.
     * @param WP_User              $user    The user object displayed in this row.
     * @return array<string,string> Array of actions to display for this user row.
     */
    public function filter_user_row_actions( array $actions, WP_User $user ) {
        // $link = self::maybe_switch_url( $user );
        $link = '?action=trigger_outline_vpn_webhook&email=';

        if ( ! $link || !current_user_can( 'manage_options' ) ) {
            return $actions;
        }


        $actions['resend_vpn_email'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($link . $user->user_email),
            esc_html__('Send VPN key', 'dt-outline-vpn')
        );

        return $actions;
    }

    public function action_init()
    {
        if (!isset($_REQUEST['action'])) {
            return;
        }

        if ( !current_user_can( 'manage_options' ) ) {
            wp_die(esc_html__('Could not send VPN key.', 'disciple-tools-outline-vpn'), 403);
            exit;
        }

        $current_user = (is_user_logged_in()) ? wp_get_current_user() : null;

        switch ($_REQUEST['action']) {

            // We're attempting to switch to another user:
            case 'trigger_outline_vpn_webhook':
                $link = get_option( "dt_outline_vpn_webhook_url" );
                if ( isset( $_REQUEST['email'] ) && isset( $link ) ) {
                    $email = $_REQUEST['email'];
                    // error_log( 'triggered: ' . $email);

                    $args = array(
                        'method' => 'POST',
                        'body' => json_encode(array(
                            'data__user_email' => $email
                        )),
                    );

                    // POST the data to the endpoint
                    $result = wp_remote_post( $link, $args );

                    if (is_wp_error( $result )) {
                        echo '<div id="message" class="error notice is-dismissible"><p>'
                            . __('Error sending request to webhook.', 'disciple-tools-outline-vpn')
                            . '</p></div>';
                    } else {
                        echo '<div id="message" class="updated notice is-dismissible"><p>'
                            . __('Sent VPN key to user: ', 'disciple-tools-outline-vpn')
                            . $email
                            . '</p></div>';
                    }
                } else if ( !isset( $_REQUEST['email'] ) ) {
                    echo '<div id="message" class="error notice is-dismissible"><p>'
                        . __('Could not send VPN key. Email address is missing.', 'disciple-tools-outline-vpn')
                        . '</p></div>';
                } else if ( !isset( $link ) ) {
                    echo '<div id="message" class="error notice is-dismissible"><p>'
                        . __('Could not send VPN key. Webhook URL is not configured.', 'disciple-tools-outline-vpn')
                        . '</p></div>';
                } else {
                    echo '<div id="message" class="error notice is-dismissible"><p>'
                        . __('Could not send VPN key.', 'disciple-tools-outline-vpn')
                        . '</p></div>';
                }

                break;
        }
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {
        // add elements here that need to fire on activation
    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        // add functions here that need to happen on deactivation
        delete_option( 'dismissed-disciple-tools-outline-vpn' );
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        $domain = 'disciple-tools-outline-vpn';
        load_plugin_textdomain( $domain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'disciple-tools-outline-vpn';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( 'disciple_tools_outline_vpn::' . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}


// Register activation hook.
register_activation_hook( __FILE__, [ 'Disciple_Tools_Outline_VPN', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'Disciple_Tools_Outline_VPN', 'deactivation' ] );


if ( ! function_exists( 'disciple_tools_outline_vpn_hook_admin_notice' ) ) {
    function disciple_tools_outline_vpn_hook_admin_notice() {
        global $disciple_tools_outline_vpn_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = "'Disciple.Tools - Outline VPN' plugin requires 'Disciple.Tools' theme to work. Please activate 'Disciple.Tools' theme or make sure it is latest version.";
        if ( $wp_theme->get_template() === 'disciple-tools-theme' ){
            $message .= ' ' . sprintf( esc_html( 'Current Disciple.Tools version: %1$s, required version: %2$s' ), esc_html( $current_version ), esc_html( $disciple_tools_outline_vpn_required_dt_theme_version ) );
        }
        // Check if it's been dismissed...
        if ( ! get_option( 'dismissed-disciple-tools-outline-vpn', false ) ) { ?>
            <div class="notice notice-error notice-disciple-tools-outline-vpn is-dismissible" data-notice="disciple-tools-outline-vpn">
                <p><?php echo esc_html( $message );?></p>
            </div>
            <script>
                jQuery(function($) {
                    $( document ).on( 'click', '.notice-disciple-tools-outline-vpn .notice-dismiss', function () {
                        $.ajax( ajaxurl, {
                            type: 'POST',
                            data: {
                                action: 'dismissed_notice_handler',
                                type: 'disciple-tools-outline-vpn',
                                security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                            }
                        })
                    });
                });
            </script>
        <?php }
    }
}

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( !function_exists( 'dt_hook_ajax_notice_handler' ) ){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST['type'] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}

/**
 * Check for plugin updates even when the active theme is not Disciple.Tools
 *
 * Below is the publicly hosted .json file that carries the version information. This file can be hosted
 * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
 * a template.
 * Also, see the instructions for version updating to understand the steps involved.
 * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
 */
add_action( 'plugins_loaded', function (){
   if ( is_admin() && !( is_multisite() && class_exists( "DT_Multisite" ) ) || wp_doing_cron() ){
       // Check for plugin updates
       if ( ! class_exists( 'Puc_v4_Factory' ) ) {
           if ( file_exists( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' )){
               require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
           }
       }
       if ( class_exists( 'Puc_v4_Factory' ) ){
           Puc_v4_Factory::buildUpdateChecker(
               'https://raw.githubusercontent.com/cairocoder01/disciple-tools-outline-vpn/master/version-control.json',
               __FILE__,
               'disciple-tools-outline-vpn'
           );

       }
   }
} );
