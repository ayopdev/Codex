<?php
/**
 * REST API controller for the plugin.
 *
 * @package MSMC_MultiCurrency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MSMC_Multi_Currency_API_Controller
 */
class MSMC_Multi_Currency_API_Controller extends WP_REST_Controller {

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
        $this->plugin    = $plugin;
        $this->namespace = 'msmc/v1';
        $this->rest_base = 'rates';
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'permission_callback' => array( $this, 'permission_read' ),
                    'callback'            => array( $this, 'get_rates' ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/update',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'permission_callback' => array( $this, 'permission_update' ),
                    'callback'            => array( $this, 'update_rates' ),
                ),
            )
        );
    }

    /**
     * Read permission callback.
     *
     * @return bool
     */
    public function permission_read() {
        return current_user_can( 'read' ) || ! is_user_logged_in();
    }

    /**
     * Update permission callback.
     *
     * @return bool
     */
    public function permission_update() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Retrieve exchange rates.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_REST_Response
     */
    public function get_rates( WP_REST_Request $request ) {
        unset( $request );

        $settings = $this->plugin->get_settings()->get_settings();

        $response = array(
            'base'        => $settings['base_currency'],
            'default'     => $settings['default_currency'],
            'currency'    => $this->plugin->get_current_currency(),
            'rates'       => $settings['exchange_rates'],
            'last_update' => $settings['last_update'],
        );

        return rest_ensure_response( $response );
    }

    /**
     * Update exchange rates from API.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function update_rates( WP_REST_Request $request ) {
        unset( $request );

        $result = $this->plugin->update_exchange_rates_from_api();

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $settings = $this->plugin->get_settings()->get_settings();

        $response = array(
            'base'        => $settings['base_currency'],
            'rates'       => $settings['exchange_rates'],
            'last_update' => $settings['last_update'],
        );

        return rest_ensure_response( $response );
    }
}
