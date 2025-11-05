<?php
/**
 * Main plugin bootstrap.
 *
 * @package MSMC_MultiCurrency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Primary plugin controller.
 */
class MSMC_Multi_Currency_Plugin {

    /**
     * Singleton instance.
     *
     * @var MSMC_Multi_Currency_Plugin|null
     */
    protected static $instance = null;

    /**
     * Plugin settings helper.
     *
     * @var MSMC_Multi_Currency_Settings
     */
    protected $settings;

    /**
     * Cached supported currencies definition.
     *
     * @var array
     */
    protected $supported_currencies = array();

    /**
     * Get plugin instance.
     *
     * @return MSMC_Multi_Currency_Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Activation hook.
     */
    public static function activate() {
        $self = self::instance();
        $self->maybe_set_default_options();
        $self->schedule_events();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( 'msmc_mc_refresh_rates' );
    }

    /**
     * MSMC_Multi_Currency_Plugin constructor.
     */
    protected function __construct() {
        $this->load_dependencies();
        $this->supported_currencies = $this->get_supported_currency_definitions();
        $this->settings             = new MSMC_Multi_Currency_Settings( $this );

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'maybe_set_currency_from_request' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'widgets_init', array( $this, 'register_widgets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        add_action( 'wp_body_open', array( $this, 'output_header_switcher' ) );
        add_action( 'wp_footer', array( $this, 'output_header_switcher_fallback' ), 5 );

        add_filter( 'woocommerce_currency', array( $this, 'filter_woocommerce_currency' ) );
        $this->hook_woocommerce_prices();
        $this->hook_masterstudy_prices();

        add_filter( 'msmc_mc_currency_options', array( $this, 'filter_currency_options' ) );
        add_action( 'msmc_mc_after_update_rates', array( $this, 'maybe_persist_rate_timestamp' ), 10, 2 );

        $this->maybe_schedule_events();
    }

    /**
     * Load required files.
     */
    protected function load_dependencies() {
        require_once MSMC_MC_PLUGIN_DIR . 'includes/helpers.php';
        require_once MSMC_MC_PLUGIN_DIR . 'includes/class-msmc-settings.php';
        require_once MSMC_MC_PLUGIN_DIR . 'includes/class-msmc-exchange-provider.php';
        require_once MSMC_MC_PLUGIN_DIR . 'includes/class-msmc-currency-switcher.php';
        require_once MSMC_MC_PLUGIN_DIR . 'includes/class-msmc-widget.php';
        require_once MSMC_MC_PLUGIN_DIR . 'includes/class-msmc-api-controller.php';
    }

    /**
     * Load plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'msmc-multicurrency', false, dirname( plugin_basename( MSMC_MC_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Register plugin shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode( 'msmc_currency_switcher', array( $this, 'render_switcher_shortcode' ) );
    }

    /**
     * Register widgets.
     */
    public function register_widgets() {
        register_widget( 'MSMC_Multi_Currency_Widget' );
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        $controller = new MSMC_Multi_Currency_API_Controller( $this );
        $controller->register_routes();
    }

    /**
     * Handle currency switch request.
     */
    public function maybe_set_currency_from_request() {
        if ( isset( $_GET['msmc_currency'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $currency = sanitize_text_field( wp_unslash( $_GET['msmc_currency'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $this->is_currency_supported( $currency ) ) {
                MSMC_Multi_Currency_Switcher::set_current_currency( $currency );
            }
        }
    }

    /**
     * Enqueue front-end assets.
     */
    public function enqueue_assets() {
        wp_enqueue_style( 'msmc-multicurrency', MSMC_MC_PLUGIN_URL . 'assets/css/multicurrency.css', array(), MSMC_MC_VERSION );
        wp_enqueue_script( 'msmc-multicurrency', MSMC_MC_PLUGIN_URL . 'assets/js/multicurrency.js', array( 'jquery' ), MSMC_MC_VERSION, true );

        wp_localize_script(
            'msmc-multicurrency',
            'MSMCMultiCurrency',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'restUrl'   => esc_url_raw( rest_url( 'msmc/v1' ) ),
                'nonce'     => wp_create_nonce( 'wp_rest' ),
                'currencies'=> $this->get_currency_options_for_frontend(),
                'current'   => $this->get_current_currency(),
            )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( false === strpos( $hook, 'msmc' ) ) {
            return;
        }

        wp_enqueue_style( 'msmc-multicurrency-admin', MSMC_MC_PLUGIN_URL . 'assets/css/multicurrency-admin.css', array(), MSMC_MC_VERSION );
        wp_enqueue_script( 'msmc-multicurrency-admin', MSMC_MC_PLUGIN_URL . 'assets/js/multicurrency-admin.js', array( 'jquery' ), MSMC_MC_VERSION, true );
        wp_localize_script(
            'msmc-multicurrency-admin',
            'MSMCMultiCurrencyAdmin',
            array(
                'restUrl'     => esc_url_raw( rest_url( 'msmc/v1/rates/update' ) ),
                'nonce'       => wp_create_nonce( 'wp_rest' ),
                'loadingText' => __( 'Updating rates…', 'msmc-multicurrency' ),
                'successText' => __( 'Exchange rates updated.', 'msmc-multicurrency' ),
                'errorText'   => __( 'Unable to update rates.', 'msmc-multicurrency' ),
            )
        );
    }

    /**
     * Render shortcode.
     *
     * @param array $atts Attributes.
     *
     * @return string
     */
    public function render_switcher_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'display' => 'dropdown',
                'class'   => '',
            ),
            $atts,
            'msmc_currency_switcher'
        );

        $switcher = new MSMC_Multi_Currency_Switcher( $this );

        return $switcher->render_switcher( $atts );
    }

    /**
     * Output currency switcher in header if enabled.
     */
    public function output_header_switcher() {
        if ( ! $this->settings->get_option( 'show_header_switcher', true ) ) {
            return;
        }

        echo '<div class="msmc-header-switcher">' . wp_kses_post( $this->render_switcher_shortcode( array( 'class' => 'msmc-header' ) ) ) . '</div>';
    }

    /**
     * Output fallback switcher in footer when wp_body_open is missing.
     */
    public function output_header_switcher_fallback() {
        if ( did_action( 'wp_body_open' ) || ! $this->settings->get_option( 'show_header_switcher', true ) ) {
            return;
        }

        echo '<div class="msmc-header-switcher msmc-header-switcher--footer">' . wp_kses_post( $this->render_switcher_shortcode( array( 'class' => 'msmc-header' ) ) ) . '</div>';
    }

    /**
     * Ensure WooCommerce prices are converted.
     */
    protected function hook_woocommerce_prices() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        add_filter( 'woocommerce_product_get_price', array( $this, 'convert_price_from_base' ), 99, 2 );
        add_filter( 'woocommerce_product_get_regular_price', array( $this, 'convert_price_from_base' ), 99, 2 );
        add_filter( 'woocommerce_product_get_sale_price', array( $this, 'convert_price_from_base' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_price', array( $this, 'convert_price_from_base' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'convert_price_from_base' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'convert_price_from_base' ), 99, 2 );
        add_filter( 'woocommerce_variation_prices_price', array( $this, 'convert_variation_price' ), 99, 3 );
        add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'convert_variation_price' ), 99, 3 );
        add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'convert_variation_price' ), 99, 3 );
        add_filter( 'woocommerce_shipping_rate_cost', array( $this, 'convert_shipping_cost' ), 99, 2 );
        add_filter( 'woocommerce_shipping_rate_taxes', array( $this, 'convert_shipping_taxes' ), 99, 2 );
        add_filter( 'woocommerce_currency_symbol', array( $this, 'filter_currency_symbol' ), 10, 2 );
    }

    /**
     * Add hooks for MasterStudy LMS price rendering.
     */
    protected function hook_masterstudy_prices() {
        $hooks = array(
            'stm_lms_currency'                => 10,
            'stm_lms_course_price'            => 10,
            'stm_lms_price'                   => 10,
            'stm_lms_price_format'            => 10,
            'stm_lms_course_price_html'       => 10,
            'stm_lms_course_price_output'     => 10,
            'stm_lms_course_lesson_price'     => 10,
            'stm_lms_bundle_price_html'       => 10,
            'masterstudy_lms_course_price'    => 10,
        );

        foreach ( $hooks as $hook => $priority ) {
            add_filter( $hook, array( $this, 'filter_masterstudy_price' ), $priority, 3 );
        }
    }

    /**
     * Filter MasterStudy price displays.
     *
     * @param mixed $value Value from filter.
     * @return mixed
     */
    public function filter_masterstudy_price( $value ) {
        if ( is_string( $value ) && preg_match( '/([0-9]+(?:[\.,][0-9]+)?)/', $value, $matches, PREG_OFFSET_CAPTURE ) ) {
            $match     = $matches[1][0];
            $offset    = $matches[1][1];
            $amount    = floatval( str_replace( ',', '.', $match ) );
            $converted = $this->convert_from_base( $amount );
            $formatted = $this->format_price( $converted, $this->get_current_currency() );

            if ( function_exists( 'mb_substr' ) ) {
                $before = mb_substr( $value, 0, $offset, 'UTF-8' );
                $after  = mb_substr( $value, $offset + strlen( $match ), null, 'UTF-8' );
            } else {
                $before = substr( $value, 0, $offset );
                $after  = substr( $value, $offset + strlen( $match ) );
            }

            $before = preg_replace( '/[\s\p{Sc}]+$/u', '', $before );

            return $before . $formatted . $after;
        }

        if ( is_numeric( $value ) ) {
            return $this->convert_from_base( (float) $value );
        }

        return $value;
    }

    /**
     * Filter WooCommerce currency.
     *
     * @param string $currency Currency code.
     *
     * @return string
     */
    public function filter_woocommerce_currency( $currency ) {
        $current = $this->get_current_currency();
        if ( $current ) {
            return $current;
        }

        return $currency;
    }

    /**
     * Convert WooCommerce base price to current currency.
     *
     * @param float          $price Price.
     * @param WC_Product|int $product Product.
     *
     * @return float
     */
    public function convert_price_from_base( $price, $product = null ) {
        unset( $product );
        if ( ! $price ) {
            return $price;
        }

        return $this->convert_from_base( (float) $price );
    }

    /**
     * Convert variation price arrays.
     *
     * @param float        $price Price.
     * @param WC_Product   $variation Variation object.
     * @param WC_Product   $product Parent product.
     *
     * @return float
     */
    public function convert_variation_price( $price, $variation = null, $product = null ) {
        unset( $variation, $product );
        if ( ! $price ) {
            return $price;
        }

        return $this->convert_from_base( (float) $price );
    }

    /**
     * Convert shipping cost to selected currency.
     *
     * @param float                 $cost Cost.
     * @param WC_Shipping_Rate|null $rate Rate.
     *
     * @return float
     */
    public function convert_shipping_cost( $cost, $rate = null ) {
        unset( $rate );
        if ( ! $cost ) {
            return $cost;
        }

        return $this->convert_from_base( (float) $cost );
    }

    /**
     * Convert shipping taxes array.
     *
     * @param array                $taxes Taxes.
     * @param WC_Shipping_Rate|int $rate  Rate.
     *
     * @return array
     */
    public function convert_shipping_taxes( $taxes, $rate = null ) {
        unset( $rate );
        if ( empty( $taxes ) || ! is_array( $taxes ) ) {
            return $taxes;
        }

        foreach ( $taxes as $key => $amount ) {
            $taxes[ $key ] = $this->convert_from_base( (float) $amount );
        }

        return $taxes;
    }

    /**
     * Filter WooCommerce currency symbol.
     *
     * @param string $symbol Symbol.
     * @param string $currency Currency.
     *
     * @return string
     */
    public function filter_currency_symbol( $symbol, $currency ) {
        $symbol_map = $this->get_supported_currency_definitions();

        if ( isset( $symbol_map[ $currency ]['symbol'] ) ) {
            return $symbol_map[ $currency ]['symbol'];
        }

        return $symbol;
    }

    /**
     * Convert amount from base currency to requested currency.
     *
     * @param float  $amount Amount in base currency.
     * @param string $currency Optional currency override.
     *
     * @return float
     */
    public function convert_from_base( $amount, $currency = null ) {
        $currency = $currency ? $currency : $this->get_current_currency();
        if ( ! $currency || $currency === $this->get_base_currency() ) {
            return $amount;
        }

        $rate = $this->get_exchange_rate( $currency );
        if ( ! $rate || $rate <= 0 ) {
            return $amount;
        }

        return (float) msmc_round_money( $amount * $rate );
    }

    /**
     * Format price for display.
     *
     * @param float  $amount Amount.
     * @param string $currency Currency code.
     *
     * @return string
     */
    public function format_price( $amount, $currency ) {
        $options = $this->settings->get_settings();
        $decimals = isset( $options['decimals'] ) ? absint( $options['decimals'] ) : 2;
        $symbol   = $this->get_currency_symbol( $currency );

        $formatted = number_format_i18n( $amount, $decimals );

        $position = isset( $options['symbol_position'] ) ? $options['symbol_position'] : 'before';

        if ( 'after' === $position ) {
            return sprintf( '%s %s', $formatted, $symbol );
        }

        return sprintf( '%s %s', $symbol, $formatted );
    }

    /**
     * Return supported currencies definition map.
     *
     * @return array
     */
    public function get_supported_currency_definitions() {
        $currencies = array(
            'SAR' => array(
                'name'   => __( 'Saudi Riyal', 'msmc-multicurrency' ),
                'symbol' => '﷼',
                'flag'   => '🇸🇦',
            ),
            'USD' => array(
                'name'   => __( 'US Dollar', 'msmc-multicurrency' ),
                'symbol' => '$',
                'flag'   => '🇺🇸',
            ),
            'EUR' => array(
                'name'   => __( 'Euro', 'msmc-multicurrency' ),
                'symbol' => '€',
                'flag'   => '🇪🇺',
            ),
            'GBP' => array(
                'name'   => __( 'British Pound', 'msmc-multicurrency' ),
                'symbol' => '£',
                'flag'   => '🇬🇧',
            ),
            'KWD' => array(
                'name'   => __( 'Kuwaiti Dinar', 'msmc-multicurrency' ),
                'symbol' => 'د.ك',
                'flag'   => '🇰🇼',
            ),
            'BHD' => array(
                'name'   => __( 'Bahraini Dinar', 'msmc-multicurrency' ),
                'symbol' => 'د.ب',
                'flag'   => '🇧🇭',
            ),
            'OMR' => array(
                'name'   => __( 'Omani Rial', 'msmc-multicurrency' ),
                'symbol' => '﷼',
                'flag'   => '🇴🇲',
            ),
            'QAR' => array(
                'name'   => __( 'Qatari Riyal', 'msmc-multicurrency' ),
                'symbol' => '﷼',
                'flag'   => '🇶🇦',
            ),
            'AED' => array(
                'name'   => __( 'UAE Dirham', 'msmc-multicurrency' ),
                'symbol' => 'د.إ',
                'flag'   => '🇦🇪',
            ),
        );

        return apply_filters( 'msmc_mc_currency_definitions', $currencies );
    }

    /**
     * Retrieve base currency.
     *
     * @return string
     */
    public function get_base_currency() {
        return $this->settings->get_option( 'base_currency', 'SAR' );
    }

    /**
     * Retrieve list of supported currency codes.
     *
     * @return array
     */
    public function get_supported_currencies() {
        return array_keys( $this->supported_currencies );
    }

    /**
     * Whether currency supported.
     *
     * @param string $currency Currency code.
     *
     * @return bool
     */
    public function is_currency_supported( $currency ) {
        return in_array( $currency, $this->get_supported_currencies(), true );
    }

    /**
     * Retrieve current user currency.
     *
     * @return string
     */
    public function get_current_currency() {
        $currency = MSMC_Multi_Currency_Switcher::get_current_currency();

        if ( ! $currency ) {
            $currency = $this->settings->get_option( 'default_currency', $this->get_base_currency() );
        }

        if ( ! $this->is_currency_supported( $currency ) ) {
            $currency = $this->get_base_currency();
        }

        return $currency;
    }

    /**
     * Retrieve symbol for currency.
     *
     * @param string $currency Currency.
     *
     * @return string
     */
    public function get_currency_symbol( $currency ) {
        if ( isset( $this->supported_currencies[ $currency ]['symbol'] ) ) {
            return $this->supported_currencies[ $currency ]['symbol'];
        }

        return $currency;
    }

    /**
     * Retrieve exchange rate for currency.
     *
     * @param string $currency Currency.
     *
     * @return float|null
     */
    public function get_exchange_rate( $currency ) {
        $rates = $this->settings->get_option( 'exchange_rates', array() );

        if ( $currency === $this->get_base_currency() ) {
            return 1.0;
        }

        if ( isset( $rates[ $currency ] ) ) {
            return (float) $rates[ $currency ];
        }

        return null;
    }

    /**
     * Retrieve formatted options for front-end use.
     *
     * @return array
     */
    protected function get_currency_options_for_frontend() {
        $options = array();

        foreach ( $this->supported_currencies as $code => $data ) {
            $options[] = array(
                'code' => $code,
                'name' => $data['name'],
                'flag' => $data['flag'],
            );
        }

        return $options;
    }

    /**
     * Filter currency options via hook.
     *
     * @param array $options Options.
     *
     * @return array
     */
    public function filter_currency_options( $options ) {
        foreach ( $this->supported_currencies as $code => $data ) {
            $options[ $code ] = $data['name'];
        }

        return $options;
    }

    /**
     * Provide plugin settings accessor.
     *
     * @return MSMC_Multi_Currency_Settings
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Ensure default options exist.
     */
    protected function maybe_set_default_options() {
        $defaults = array(
            'base_currency'         => 'SAR',
            'default_currency'      => 'SAR',
            'supported_currencies'  => $this->get_supported_currencies(),
            'exchange_rates'        => array(
                'USD' => 0.27,
                'EUR' => 0.25,
                'GBP' => 0.22,
                'KWD' => 0.083,
                'BHD' => 0.10,
                'OMR' => 0.10,
                'QAR' => 0.97,
                'AED' => 0.98,
                'SAR' => 1,
            ),
            'decimals'              => 2,
            'symbol_position'       => 'before',
            'show_header_switcher'  => true,
            'api_endpoint'          => 'https://open.er-api.com/v6/latest/%s',
            'api_key'               => '',
            'auto_update_rates'     => true,
            'last_update'           => 0,
        );

        $current = get_option( 'msmc_mc_settings', array() );
        $merged  = wp_parse_args( $current, $defaults );
        update_option( 'msmc_mc_settings', $merged );
    }

    /**
     * Convert selected currency switcher to settings.
     */
    public function update_exchange_rates_from_api() {
        $provider = new MSMC_Multi_Currency_Exchange_Provider( $this );
        $rates    = $provider->fetch_rates();

        return $rates;
    }

    /**
     * Persist timestamp when rates updated.
     *
     * @param array $rates Rates.
     * @param array $settings Settings.
     */
    public function maybe_persist_rate_timestamp( $rates, $settings ) {
        unset( $rates );
        $settings['last_update'] = time();
        update_option( 'msmc_mc_settings', $settings );
    }

    /**
     * Get plugin settings key.
     *
     * @return string
     */
    public function get_settings_option_key() {
        return 'msmc_mc_settings';
    }

    /**
     * Schedule cron events when required.
     */
    protected function schedule_events() {
        if ( ! wp_next_scheduled( 'msmc_mc_refresh_rates' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'twicedaily', 'msmc_mc_refresh_rates' );
        }
    }

    /**
     * Possibly schedule events if option toggled.
     */
    protected function maybe_schedule_events() {
        add_action( 'msmc_mc_refresh_rates', array( $this, 'maybe_refresh_rates' ) );

        if ( $this->settings->get_option( 'auto_update_rates', true ) ) {
            $this->schedule_events();
        } else {
            wp_clear_scheduled_hook( 'msmc_mc_refresh_rates' );
        }
    }

    /**
     * Refresh rates if API available.
     */
    public function maybe_refresh_rates() {
        if ( ! $this->settings->get_option( 'auto_update_rates', true ) ) {
            return;
        }

        $this->update_exchange_rates_from_api();
    }
}
