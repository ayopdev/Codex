<?php
/**
 * Plugin Name: MasterStudy Multi-Currency Toolkit
 * Plugin URI: https://stylemixthemes.com/
 * Description: Adds multi-currency support to MasterStudy LMS and WooCommerce with manual rates, automatic updates, and flexible switchers.
 * Version: 1.0.0
 * Author: OpenAI Codex
 * Author URI: https://openai.com/
 * Text Domain: msmc-multicurrency
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MSMC_MC_VERSION', '1.0.0' );
define( 'MSMC_MC_PLUGIN_FILE', __FILE__ );
define( 'MSMC_MC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MSMC_MC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MSMC_MC_PLUGIN_DIR . 'includes/class-msmc-plugin.php';

register_activation_hook( MSMC_MC_PLUGIN_FILE, array( 'MSMC_Multi_Currency_Plugin', 'activate' ) );
register_deactivation_hook( MSMC_MC_PLUGIN_FILE, array( 'MSMC_Multi_Currency_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'MSMC_Multi_Currency_Plugin', 'instance' ) );
