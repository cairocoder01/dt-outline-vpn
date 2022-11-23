<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_Outline_VPN_Menu
 */
class Disciple_Tools_Outline_VPN_Menu {

    public $token = 'disciple_tools_outline_vpn';
    public $page_title = 'Outline VPN';

    private static $_instance = null;

    /**
     * Disciple_Tools_Outline_VPN_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_Outline_VPN_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return Disciple_Tools_Outline_VPN_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        $this->page_title = __( 'Outline VPN', 'disciple-tools-outline-vpn' );
    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        $this->page_title = __( 'Outline VPN', 'disciple-tools-outline-vpn' );

        add_submenu_page( 'dt_extensions', $this->page_title, $this->page_title, 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple.Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        if ( isset( $_GET['tab'] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php echo esc_html( $this->page_title ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'general' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">General</a>
            </h2>

            <?php
            switch ( $tab ) {
                case 'general':
                    $object = new Disciple_Tools_Outline_VPN_Tab_General();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }
}
Disciple_Tools_Outline_VPN_Menu::instance();

/**
 * Class Disciple_Tools_Outline_VPN_Tab_General
 */
class Disciple_Tools_Outline_VPN_Tab_General {
    public function content() {

        $this->save_settings();
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        $webhook_url = get_option( "dt_outline_vpn_webhook_url" );
        ?>
        <form method="POST" action="">
        <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>

            <!-- Box -->
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Settings</th>
                    </tr>
                </thead>
                <tbody>
                <tr>
                    <th><label for="webhook_url">Webhook URL</label></th>
                        <td>
                            <input type="text"
                                   name="webhook_url"
                                   id="webhook_url"
                                   value="<?php echo esc_attr( $webhook_url ) ?>"
                                   style="width:100%;"
                            />
                            <div class="muted">Webhook to be sent user data when a user is created or needs their auth token re-sent.</div>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <button type="submit" class="button">Update</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <!-- End Box -->
        </form>
        <?php
    }

    public function right_column() {
        ?>
        <?php
    }

    public function save_settings() {
        if ( !empty( $_POST ) ){
            if ( isset( $_POST['security_headers_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
                if ( isset( $_POST['webhook_url'] ) ) {
                    update_option( "dt_outline_vpn_webhook_url", sanitize_text_field( wp_unslash( $_POST['webhook_url'] ) ) );
                }
            }
        }
    }
}


