<?php
/**
 * Currency switcher helpers.
 *
 * @package MSMC_MultiCurrency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Utility for switching currencies.
 */
class MSMC_Multi_Currency_Switcher {

    const COOKIE_NAME = 'msmc_currency';
    const COOKIE_TTL  = 30; // Days.

    /**
     * Plugin instance.
     *
     * @var MSMC_Multi_Currency_Plugin
     */
    protected $plugin;

    /**
     * Constructor.
     *
     * @param MSMC_Multi_Currency_Plugin $plugin Plugin instance.
     */
    public function __construct( MSMC_Multi_Currency_Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Get cookie name.
     *
     * @return string
     */
    public static function get_cookie_name() {
        return apply_filters( 'msmc_mc_cookie_name', self::COOKIE_NAME );
    }

    /**
     * Persist selected currency.
     *
     * @param string $currency Currency code.
     */
    public static function set_current_currency( $currency ) {
        $currency = strtoupper( sanitize_text_field( $currency ) );
        $ttl      = apply_filters( 'msmc_mc_cookie_ttl', DAY_IN_SECONDS * self::COOKIE_TTL );
        $name     = self::get_cookie_name();

        setcookie( $name, $currency, time() + $ttl, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
        if ( COOKIEPATH !== SITECOOKIEPATH ) {
            setcookie( $name, $currency, time() + $ttl, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }

        $_COOKIE[ $name ] = $currency;
    }

    /**
     * Retrieve current currency from cookie.
     *
     * @return string
     */
    public static function get_current_currency() {
        $name = self::get_cookie_name();
        if ( isset( $_COOKIE[ $name ] ) ) {
            return strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) );
        }

        return '';
    }

    /**
     * Render currency switcher.
     *
     * @param array $atts Attributes.
     *
     * @return string
     */
    public function render_switcher( $atts = array() ) {
        $currencies = $this->plugin->get_supported_currency_definitions();
        $current    = $this->plugin->get_current_currency();
        $display    = isset( $atts['display'] ) ? $atts['display'] : 'dropdown';
        $class      = isset( $atts['class'] ) ? $atts['class'] : '';

        $output  = '<div class="msmc-currency-switcher ' . esc_attr( $class ) . '" data-current="' . esc_attr( $current ) . '">';
        $output .= $this->maybe_render_notice();

        if ( 'list' === $display ) {
            $output .= '<ul class="msmc-currency-switcher__list">';
            foreach ( $currencies as $code => $data ) {
                $url = esc_url( add_query_arg( 'msmc_currency', $code ) );
                $output .= sprintf(
                    '<li class="msmc-currency-switcher__item %4$s"><a href="%1$s" data-currency="%2$s">%3$s %2$s</a></li>',
                    $url,
                    esc_html( $code ),
                    esc_html( $data['flag'] ),
                    $code === $current ? 'is-active' : ''
                );
            }
            $output .= '</ul>';
        } else {
            $output .= '<form class="msmc-currency-switcher__form" method="get">';
            $output .= '<select name="msmc_currency" class="msmc-currency-switcher__select">';
            foreach ( $currencies as $code => $data ) {
                $output .= sprintf(
                    '<option value="%1$s" %3$s>%4$s %1$s - %2$s</option>',
                    esc_attr( $code ),
                    esc_html( $data['name'] ),
                    selected( $current, $code, false ),
                    esc_html( $data['flag'] )
                );
            }
            $output .= '</select>';

            foreach ( $this->get_preserved_query_args() as $key => $value ) {
                $output .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
            }

            $output .= '<noscript><button type="submit" class="button">' . esc_html__( 'Switch', 'msmc-multicurrency' ) . '</button></noscript>';
            $output .= '</form>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Generate note about rates if outdated.
     *
     * @return string
     */
    protected function maybe_render_notice() {
        $settings = $this->plugin->get_settings()->get_settings();
        if ( empty( $settings['last_update'] ) ) {
            return '';
        }

        $diff = time() - (int) $settings['last_update'];

        if ( $diff > DAY_IN_SECONDS * 2 ) {
            return '<p class="msmc-currency-switcher__notice">' . esc_html__( 'Exchange rates may be outdated.', 'msmc-multicurrency' ) . '</p>';
        }

        return '';
    }

    /**
     * Preserve query args when submitting form.
     *
     * @return array
     */
    protected function get_preserved_query_args() {
        $preserve = apply_filters( 'msmc_mc_preserved_query_args', array() );
        $query    = array();

        foreach ( $preserve as $key ) {
            if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $query[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
        }

        return $query;
    }
}
