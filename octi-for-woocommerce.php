<?php
/**
 * Plugin Name: OCTI.io for seller
 * Plugin URI: http://www.octi.io
 * Description: Integration plugin for theme sellers at octi.io.
 * Version: 1.0.1
 * Author: OCTI.io
 * Author URI: http://www.octi.io
 * Requires at least: 4.3
 * Tested up to: 4.3
 *
 * Text Domain: octi-for-seller
 * Domain Path: /i18n/languages/
 *
 * @package OCTI_FOR_SELLER
 * @category Core
 * @author OCTI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!defined('OCTI_BASE_URL')) {
    define('OCTI_BASE_URL', 'https://www.octi.io/shop');
}


/**
 * Main class
 *
 * @class OCTI_FOR_SELLER
 * @version 1.0
 */
final class OCTI_FOR_SELLER {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('parse_request', array($this, 'octi_install') );
    }

    /**
     * Static function to build the internal link
     * use like OCTI_FOR_SELLER::link('hola')
     * @version 1.0
     * @return string http link
     */
    public static function link($name) {
        return self::get_internal_link($name);
    }

    /**
     * Static function to build the button with internal link
     * use like OCTI_FOR_SELLER::button('hola')
     * @version 1.0
     * @return string html link
     */
    public static function button($name, $args) {
        return self::get_octi_button($args, self::get_internal_link($name));
    }

    /**
     * Detect on request parsing the octi button and redirect to octi.io
     * @version 1.0
     */
    public function octi_install() {
        global $wp;

        if (!isset($_GET['octi']) || !isset($_GET['name'])) return;
        if (!preg_match('/[a-z0-9\-]+/i', $_GET['name'])) return;
        if (!wp_verify_nonce($_GET['nonce'], 'octi-install-'.$_GET['name'] )) return;

        if (!isset($this->settings)) {
            $this->settings = (array) get_option( 'octi-for-seller' );
        }

        header('Location: '.OCTI_OTP::generate( $this->settings['key'], $_GET['name'] ));
        exit();
    }

    /**
     * General functions
     * @version 1.0
     */
    public function init() {
        add_shortcode('octi-button', array($this, 'shortcode_octi_button') );
        add_shortcode('octi-link', array($this, 'shortcode_octi_link') );
        add_shortcode('octi_button', array($this, 'shortcode_octi_button') );
        add_shortcode('octi_link', array($this, 'shortcode_octi_link') );
        add_filter('woocommerce_available_download_link', array($this, 'woocommerce_available_download_link'), 1, 2);
        add_action('woocommerce_order_item_meta_end', array($this, 'woocommerce_order_item_meta_end'), 5, 3 );
    }

    /**
     * WooCommerce Basic integration
     * Show botton in the "available downloads" list. Next to each download link
     * @version 1.0
     */
    public function woocommerce_available_download_link($html, $download) {

        $name = get_post_meta($download['product_id'],'octi_name', true);

        // This product don't has the octi_name meta
        if (empty($name)) return $html;

        $octi_html = $this->get_octi_button(false, $this->get_internal_link($name));

        return $html.' '.apply_filters('octi_download_html', $octi_html);
    }

    /**
     * WooCommerce Basic integration
     * Show botton under each download item on the review/detail order
     * https://github.com/woothemes/woocommerce/blob/5893875b0c03dda7b2d448d1a904ccfad3cdae3f/templates/order/order-details-item.php#L39
     * @version 1.0.1
     */
    public function woocommerce_order_item_meta_end($item_id, $item, $order) {

        $name = get_post_meta((int) $item['product_id'],'octi_name', true);

        // Do nothing if not octi_name meta defined
        if (empty($name)) return;

        $octi_html = $this->get_octi_button(false, $this->get_internal_link($name));
        echo apply_filters('octi_download_html', '<br>'.$octi_html, $item_id, $item, $order);
    }


    /**
     * OCTI HTML link to internal link
     * @version 1.0
     */
    public function get_octi_button($args=false, $link) {

        $basic = '<a href="%s" target="_blank" title="%s"><img alt="%s" src="//www.octi.io/buttons/octi-st-m.png"></a>';

        $html = sprintf($basic,
                    $link,
                    __('One click to install','octi-for-seller'),
                    __('One click to install','octi-for-seller')
                );

        return apply_filters('octi_button', $html);
    }

    /**
     * OCTI build link
     * @version 1.0
     */
    public function get_internal_link($name) {
        return add_query_arg( array(
            'octi' => true,
            'name' => $name,
            'nonce' => wp_create_nonce('octi-install-'.$name),
            'force' => wp_rand() // Just to "jump" a cache plugins/browser/servers...
        ), get_bloginfo('url') );
    }

    /**
     * Shortcode to build the html link (button)
     * @version 1.0
     */
    public function shortcode_octi_button($args) {

        if (!empty($args['name']))
            $name = $args['name'];

        echo $this->get_octi_button($args, $this->get_internal_link($name) );
    }

    /**
     * Shortcode to link
     * @version 1.0
     */
    public function shortcode_octi_link($args) {

        if (!empty($args['name']))
            $name = $args['name'];

        echo $this->get_internal_link($name);
    }


    /**
     * Admin functions
     * @version 1.0
     */
    public function admin_init() {
        // Field in general settings to save the password
        register_setting( 'general', 'octi-for-seller' );
        add_settings_section( 'octi-for-seller', 'OCTI.io', array($this,'section_one_callback'), 'general' );
        add_settings_field( 'field-one', 'Seller KEY', array($this,'field_one_callback'), 'general', 'octi-for-seller' );

        // Extra meta box to save the name of the product in the WC Product
        add_action( 'add_meta_boxes', array($this, 'octi_name_in_post') );
        add_action( 'save_post', array($this, 'octi_name_in_post_save') );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    public function section_one_callback() {
        echo sprintf('<em>%s</em>',__('Save the access key to <a href="https://www.octi.io" target="_blank">octi.io</a>','octi-for-seller'));
    }

    public function field_one_callback() {
        $settings = (array) get_option( 'octi-for-seller' );
        $key = esc_attr( $settings['key'] );
        echo '<input name="octi-for-seller[key]" type="text" value="'.$key.'" class="regular-text code">';
    }

    public function octi_name_in_post() {

        $screens = apply_filters('octi_field_post_type', array( 'product' ) );

        foreach ( $screens as $screen )
            add_meta_box(
                'octi_name_in_post',
                __( 'Write the name of the app in octi.io', 'octi-for-seller' ),
                array($this, 'octi_name_in_post_callback'),
                $screen
            );
    }

    public function octi_name_in_post_callback($post) {

        // Add a nonce field so we can check for it later.
        wp_nonce_field( 'octi_name_in_post_save', 'octi_name_in_post_nonce' );

        /*
         * Use get_post_meta() to retrieve an existing value
         * from the database and use the value for the form.
         */
        $value = get_post_meta( $post->ID, 'octi_name', true );

        echo '<label for="octi_name">';
            _e( 'SLUG / Name of APP', 'octi-for-seller' );
        echo '</label> ';
        echo '<input type="text" id="octi_name" name="octi_name" value="' . esc_attr( $value ) . '" size="25" />';
    }


    /**
     * When the post is saved, saves our custom data.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function octi_name_in_post_save($post_id) {

        /*
         * We need to verify this came from our screen and with proper authorization,
         * because the save_post action can be triggered at other times.
         */

        // Check if our nonce is set.
        if ( ! isset( $_POST['octi_name_in_post_nonce'] ) ) return;

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['octi_name_in_post_nonce'], 'octi_name_in_post_save' ) ) return;


        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;


        // Check the user's permissions.
        if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] )
            if ( ! current_user_can( 'edit_page', $post_id ) ) return;
        else
            if ( ! current_user_can( 'edit_post', $post_id ) ) return;


        if ( ! isset( $_POST['octi_name'] ) ) return;

        // Sanitize user input.
        update_post_meta( $post_id, 'octi_name', sanitize_text_field( trim($_POST['octi_name']) ) );


        if (!isset($this->settings)) {
            $this->settings = (array) get_option( 'octi-for-seller' );
        }

        $response = wp_remote_head(OCTI_OTP::generate( $this->settings['key'], $_POST['octi_name'] ));
        if ($response['response']['code'] != 302) {
            add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
        }

    }

    public function add_notice_query_var($location) {
        remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
        return add_query_arg( array( 'octi_error' => 'invalid' ), $location );
    }

    public function admin_notices() {
        if ( ! isset( $_GET['octi_error'] ) ) return;
        ?>
        <div class="error">
            <p><?php esc_html_e( "OCTI.io: The APP name don't exists or you don't have access", 'octi-for-seller' ); ?></p>
        </div>
        <?php
    }
}
// Init OCTI
new OCTI_FOR_SELLER();

/**
 * OTP Hash generation class
 *
 * @class OCTI_OTP
 * @version 1.0
 */
class OCTI_OTP {

    private $interval = 30;
    private $key = false;
    private $name = false;
    private $timestamp = false;

    public function __construct($key, $name) {
        $this->key = $key;
        $this->name = $name;
        $this->timestamp = time();
    }

    protected function _generate() {
        return OCTI_BASE_URL.'/'.$this->name.'/buy/'.hash_hmac('sha256', $this->name, $this->timecode().$this->key ).'/';
    }

    static public function generate($key, $name) {
        $s = new self($key, $name);
        return $s->_generate();
    }

    private function timecode() {
        return (int)( (((int)$this->timestamp * 1000) / ($this->interval * 1000)));
    }

}
