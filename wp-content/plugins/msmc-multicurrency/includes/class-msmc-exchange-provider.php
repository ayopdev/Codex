<?php
/**
 * Exchange provider for retrieving live rates.
 *
 * @package MSMC_MultiCurrency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles communication with third-party exchange API.
 */
class MSMC_Multi_Currency_Exchange_Provider {

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
     * Fetch rates from remote API and persist them.
     *
     * @return array|WP_Error
     */
    public function fetch_rates() {
        $settings   = $this->plugin->get_settings()->get_settings();
        $base       = $settings['base_currency'];
        $endpoint   = $settings['api_endpoint'];
        $currencies = $this->plugin->get_supported_currencies();

        if ( false !== strpos( $endpoint, '%s' ) ) {
            $url = sprintf( $endpoint, rawurlencode( $base ) );
        } else {
            $url = add_query_arg( array( 'base' => $base ), $endpoint );
        }

        if ( ! empty( $settings['api_key'] ) ) {
            $url = add_query_arg( array( 'apiKey' => $settings['api_key'] ), $url );
        }

        $url  = apply_filters( 'msmc_mc_api_endpoint', $url, $settings );
        $args = apply_filters(
            'msmc_mc_api_request_args',
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept'        => 'application/json',
                    'Authorization' => ! empty( $settings['api_key'] ) ? 'Bearer ' . $settings['api_key'] : '',
                ),
            ),
            $settings
        );

        if ( empty( $args['headers']['Authorization'] ) ) {
            unset( $args['headers']['Authorization'] );
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error( 'msmc_mc_bad_status', __( 'Unexpected response from exchange API.', 'msmc-multicurrency' ), $code );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body ) || ! is_array( $body ) ) {
            return new WP_Error( 'msmc_mc_invalid_body', __( 'Exchange API returned invalid data.', 'msmc-multicurrency' ) );
        }

        $rates_data = array();
        if ( isset( $body['rates'] ) && is_array( $body['rates'] ) ) {
            $rates_data = $body['rates'];
        } elseif ( isset( $body['result'] ) && is_array( $body['result'] ) ) {
            $rates_data = $body['result'];
        }

        if ( empty( $rates_data ) ) {
            return new WP_Error( 'msmc_mc_missing_rates', __( 'No exchange rates found in API response.', 'msmc-multicurrency' ) );
        }

        $parsed = array();
        foreach ( $currencies as $currency ) {
            if ( $currency === $base ) {
                $parsed[ $currency ] = 1.0;
                continue;
            }

            if ( isset( $rates_data[ $currency ] ) && is_numeric( $rates_data[ $currency ] ) ) {
                $parsed[ $currency ] = (float) $rates_data[ $currency ];
            }
        }

        if ( empty( $parsed ) ) {
            return new WP_Error( 'msmc_mc_empty_rates', __( 'The selected API did not return any of the requested currencies.', 'msmc-multicurrency' ) );
        }

        $settings['exchange_rates'] = array_merge( $settings['exchange_rates'], $parsed );
        update_option( $this->plugin->get_settings_option_key(), $settings );
        do_action( 'msmc_mc_after_update_rates', $parsed, $settings );

        return $parsed;
    }
}
