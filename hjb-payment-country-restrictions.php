<?php
/**
 * Plugin Name: HJB Payment Country Restrictions
 * Plugin URI: https://hjaltebyran.se
 * Description: Begränsa WooCommerce-betalningsmetoder per land och region med whitelist/blacklist-stöd.
 * Version: 1.2.0
 * Author: Hjältebyrån AB
 * Author URI: https://hjaltebyran.se
 * Text Domain: hjb-pgcr
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.5
 *
 * @package HJB_Payment_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

define( 'HJB_PGCR_VERSION', '1.2.0' );
define( 'HJB_PGCR_FILE', __FILE__ );
define( 'HJB_PGCR_PATH', plugin_dir_path( __FILE__ ) );
define( 'HJB_PGCR_URL', plugin_dir_url( __FILE__ ) );
define( 'HJB_PGCR_OPTION_KEY', 'hjb_pgcr_settings' );

/**
 * Declare HPOS compatibility.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
} );

/**
 * Check that WooCommerce is active before initializing.
 */
function hjb_pgcr_load_textdomain() {
    load_plugin_textdomain(
        'hjb-pgcr',
        false,
        dirname( plugin_basename( HJB_PGCR_FILE ) ) . '/languages'
    );
}
add_action( 'init', 'hjb_pgcr_load_textdomain' );

function hjb_pgcr_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'HJB Payment Country Restrictions kräver att WooCommerce är installerat och aktiverat.', 'hjb-pgcr' );
            echo '</p></div>';
        } );
        return;
    }

    require_once HJB_PGCR_PATH . 'includes/class-regions.php';
    require_once HJB_PGCR_PATH . 'includes/class-gateway-filter.php';
    require_once HJB_PGCR_PATH . 'includes/class-settings.php';
    require_once HJB_PGCR_PATH . 'includes/class-country-selector.php';

    HJB_PGCR\Regions::instance();
    HJB_PGCR\Gateway_Filter::instance();
    HJB_PGCR\Country_Selector::instance();

    if ( is_admin() ) {
        HJB_PGCR\Settings::instance();
    }
}
add_action( 'plugins_loaded', 'hjb_pgcr_init', 20 );
