<?php
/**
 * Settings manager for the plugin.
 *
 * @package MSMC_MultiCurrency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles admin settings.
 */
class MSMC_Multi_Currency_Settings {

    /**
     * Parent plugin.
     *
     * @var MSMC_Multi_Currency_Plugin
     */
    protected $plugin;

    /**
     * Option key.
     *
     * @var string
     */
    protected $option_key;

    /**
     * Constructor.
     *
     * @param MSMC_Multi_Currency_Plugin $plugin Plugin instance.
     */
    public function __construct( MSMC_Multi_Currency_Plugin $plugin ) {
        $this->plugin     = $plugin;
        $this->option_key = $plugin->get_settings_option_key();

        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_msmc_mc_refresh_rates', array( $this, 'handle_manual_refresh' ) );
    }

    /**
     * Register admin menu.
     */
    public function register_menu() {
        add_menu_page(
            __( 'Multi-Currency', 'msmc-multicurrency' ),
            __( 'Multi-Currency', 'msmc-multicurrency' ),
            'manage_options',
            'msmc-multicurrency',
            array( $this, 'render_settings_page' ),
            'dashicons-money-alt',
            58
        );
    }

    /**
     * Register settings fields.
     */
    public function register_settings() {
        register_setting( 'msmc_mc_settings_group', $this->option_key, array( $this, 'sanitize_settings' ) );

        add_settings_section(
            'msmc_mc_general_section',
            __( 'General Settings', 'msmc-multicurrency' ),
            '__return_false',
            'msmc-multicurrency'
        );

        add_settings_field(
            'base_currency',
            __( 'Base Currency', 'msmc-multicurrency' ),
            array( $this, 'render_base_currency_field' ),
            'msmc-multicurrency',
            'msmc_mc_general_section'
        );

        add_settings_field(
            'default_currency',
            __( 'Default Display Currency', 'msmc-multicurrency' ),
            array( $this, 'render_default_currency_field' ),
            'msmc-multicurrency',
            'msmc_mc_general_section'
        );

        add_settings_field(
            'decimals',
            __( 'Decimal Places', 'msmc-multicurrency' ),
            array( $this, 'render_decimals_field' ),
            'msmc-multicurrency',
            'msmc_mc_general_section'
        );

        add_settings_field(
            'symbol_position',
            __( 'Currency Symbol Position', 'msmc-multicurrency' ),
            array( $this, 'render_symbol_position_field' ),
            'msmc-multicurrency',
            'msmc_mc_general_section'
        );

        add_settings_field(
            'show_header_switcher',
            __( 'Header Currency Switcher', 'msmc-multicurrency' ),
            array( $this, 'render_header_switcher_field' ),
            'msmc-multicurrency',
            'msmc_mc_general_section'
        );

        add_settings_section(
            'msmc_mc_rates_section',
            __( 'Exchange Rates', 'msmc-multicurrency' ),
            '__return_false',
            'msmc-multicurrency'
        );

        add_settings_field(
            'exchange_rates',
            __( 'Manual Exchange Rates', 'msmc-multicurrency' ),
            array( $this, 'render_exchange_rates_field' ),
            'msmc-multicurrency',
            'msmc_mc_rates_section'
        );

        add_settings_section(
            'msmc_mc_api_section',
            __( 'API Updates', 'msmc-multicurrency' ),
            '__return_false',
            'msmc-multicurrency'
        );

        add_settings_field(
            'api_endpoint',
            __( 'API Endpoint', 'msmc-multicurrency' ),
            array( $this, 'render_api_endpoint_field' ),
            'msmc-multicurrency',
            'msmc_mc_api_section'
        );

        add_settings_field(
            'api_key',
            __( 'API Key', 'msmc-multicurrency' ),
            array( $this, 'render_api_key_field' ),
            'msmc-multicurrency',
            'msmc_mc_api_section'
        );

        add_settings_field(
            'auto_update_rates',
            __( 'Automatic Updates', 'msmc-multicurrency' ),
            array( $this, 'render_auto_update_field' ),
            'msmc-multicurrency',
            'msmc_mc_api_section'
        );
    }

    /**
     * Sanitize options.
     *
     * @param array $input Input data.
     *
     * @return array
     */
    public function sanitize_settings( $input ) {
        $output   = $this->get_settings();
        $input    = is_array( $input ) ? $input : array();
        $currencies = $this->plugin->get_supported_currencies();

        if ( isset( $input['base_currency'] ) && in_array( $input['base_currency'], $currencies, true ) ) {
            $output['base_currency'] = $input['base_currency'];
        }

        if ( isset( $input['default_currency'] ) && in_array( $input['default_currency'], $currencies, true ) ) {
            $output['default_currency'] = $input['default_currency'];
        }

        if ( isset( $input['decimals'] ) ) {
            $output['decimals'] = max( 0, min( 4, absint( $input['decimals'] ) ) );
        }

        if ( isset( $input['symbol_position'] ) && in_array( $input['symbol_position'], array( 'before', 'after' ), true ) ) {
            $output['symbol_position'] = $input['symbol_position'];
        }

        $output['show_header_switcher'] = ! empty( $input['show_header_switcher'] );

        if ( isset( $input['exchange_rates'] ) && is_array( $input['exchange_rates'] ) ) {
            foreach ( $input['exchange_rates'] as $code => $rate ) {
                if ( in_array( $code, $currencies, true ) ) {
                    $output['exchange_rates'][ $code ] = floatval( $rate );
                }
            }
        }

        if ( isset( $input['api_endpoint'] ) ) {
            $endpoint = sanitize_text_field( $input['api_endpoint'] );
            if ( ! empty( $endpoint ) ) {
                $output['api_endpoint'] = $endpoint;
            }
        }

        if ( isset( $input['api_key'] ) ) {
            $output['api_key'] = sanitize_text_field( $input['api_key'] );
        }

        $output['auto_update_rates'] = ! empty( $input['auto_update_rates'] );

        return $output;
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = $this->get_settings();
        $message  = isset( $_GET['msmcmsg'] ) ? sanitize_text_field( wp_unslash( $_GET['msmcmsg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status   = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap msmc-settings">
            <h1><?php esc_html_e( 'MasterStudy Multi-Currency Toolkit', 'msmc-multicurrency' ); ?></h1>
            <?php if ( $message ) : ?>
                <div class="notice <?php echo esc_attr( 'error' === $status ? 'notice-error' : 'notice-success' ); ?> is-dismissible">
                    <p><?php echo esc_html( $message ); ?></p>
                </div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'msmc_mc_settings_group' );
                do_settings_sections( 'msmc-multicurrency' );
                submit_button();
                ?>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="msmc-refresh-form">
                <?php wp_nonce_field( 'msmc_mc_refresh_rates', 'msmc_mc_refresh_rates_nonce' ); ?>
                <input type="hidden" name="action" value="msmc_mc_refresh_rates" />
                <p>
                    <button type="submit" class="button button-secondary">
                        <?php esc_html_e( 'Refresh Rates from API', 'msmc-multicurrency' ); ?>
                    </button>
                    <?php if ( ! empty( $settings['last_update'] ) ) : ?>
                        <span class="description">
                            <?php
                            printf(
                                esc_html__( 'Last update: %s', 'msmc-multicurrency' ),
                                esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $settings['last_update'] ), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) )
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle manual refresh request.
     */
    public function handle_manual_refresh() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'msmc-multicurrency' ) );
        }

        check_admin_referer( 'msmc_mc_refresh_rates', 'msmc_mc_refresh_rates_nonce' );

        $result = $this->plugin->update_exchange_rates_from_api();

        if ( is_wp_error( $result ) ) {
            $message = $result->get_error_message();
            $status  = 'error';
        } else {
            $message = __( 'Exchange rates updated successfully.', 'msmc-multicurrency' );
            $status  = 'updated';
        }

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'    => 'msmc-multicurrency',
                    'msmcmsg' => rawurlencode( $message ),
                    'status'  => $status,
                ),
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Render base currency field.
     */
    public function render_base_currency_field() {
        $settings   = $this->get_settings();
        $currencies = $this->plugin->get_supported_currency_definitions();
        ?>
        <select name="<?php echo esc_attr( $this->option_key ); ?>[base_currency]">
            <?php foreach ( $currencies as $code => $data ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['base_currency'], $code ); ?>>
                    <?php echo esc_html( sprintf( '%1$s - %2$s', $code, $data['name'] ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'All exchange rates are relative to this currency.', 'msmc-multicurrency' ); ?></p>
        <?php
    }

    /**
     * Render default currency field.
     */
    public function render_default_currency_field() {
        $settings   = $this->get_settings();
        $currencies = $this->plugin->get_supported_currency_definitions();
        ?>
        <select name="<?php echo esc_attr( $this->option_key ); ?>[default_currency]">
            <?php foreach ( $currencies as $code => $data ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['default_currency'], $code ); ?>>
                    <?php echo esc_html( sprintf( '%1$s - %2$s', $code, $data['name'] ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Currency visitors see on first load.', 'msmc-multicurrency' ); ?></p>
        <?php
    }

    /**
     * Render decimals field.
     */
    public function render_decimals_field() {
        $settings = $this->get_settings();
        ?>
        <input type="number" min="0" max="4" name="<?php echo esc_attr( $this->option_key ); ?>[decimals]" value="<?php echo esc_attr( $settings['decimals'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Number of decimal points shown in prices.', 'msmc-multicurrency' ); ?></p>
        <?php
    }

    /**
     * Render symbol position field.
     */
    public function render_symbol_position_field() {
        $settings = $this->get_settings();
        ?>
        <select name="<?php echo esc_attr( $this->option_key ); ?>[symbol_position]">
            <option value="before" <?php selected( 'before', $settings['symbol_position'] ); ?>><?php esc_html_e( 'Before amount', 'msmc-multicurrency' ); ?></option>
            <option value="after" <?php selected( 'after', $settings['symbol_position'] ); ?>><?php esc_html_e( 'After amount', 'msmc-multicurrency' ); ?></option>
        </select>
        <?php
    }

    /**
     * Render header switcher option.
     */
    public function render_header_switcher_field() {
        $settings = $this->get_settings();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( $this->option_key ); ?>[show_header_switcher]" value="1" <?php checked( ! empty( $settings['show_header_switcher'] ) ); ?> />
            <?php esc_html_e( 'Display a currency switcher automatically in the site header.', 'msmc-multicurrency' ); ?>
        </label>
        <?php
    }

    /**
     * Render manual exchange rates field.
     */
    public function render_exchange_rates_field() {
        $settings   = $this->get_settings();
        $currencies = $this->plugin->get_supported_currency_definitions();
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Currency', 'msmc-multicurrency' ); ?></th>
                    <th><?php esc_html_e( 'Rate vs Base', 'msmc-multicurrency' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $currencies as $code => $data ) :
                    if ( $settings['base_currency'] === $code ) {
                        continue;
                    }
                    $rate = isset( $settings['exchange_rates'][ $code ] ) ? $settings['exchange_rates'][ $code ] : '';
                    ?>
                    <tr>
                        <td><?php echo esc_html( sprintf( '%s %s', $data['flag'], $data['name'] ) ); ?></td>
                        <td>
                            <input type="number" step="0.0001" min="0" name="<?php echo esc_attr( $this->option_key ); ?>[exchange_rates][<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( $rate ); ?>" />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description"><?php esc_html_e( 'Enter how much of the selected currency equals one unit of the base currency.', 'msmc-multicurrency' ); ?></p>
        <?php
    }

    /**
     * Render API endpoint field.
     */
    public function render_api_endpoint_field() {
        $settings = $this->get_settings();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( $this->option_key ); ?>[api_endpoint]" value="<?php echo esc_attr( $settings['api_endpoint'] ); ?>" />
        <p class="description"><?php printf( esc_html__( 'Use %1$s as placeholder for base currency. Example: https://open.er-api.com/v6/latest/%2$s', 'msmc-multicurrency' ), '%s', '%s' ); ?></p>
        <?php
    }

    /**
     * Render API key field.
     */
    public function render_api_key_field() {
        $settings = $this->get_settings();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( $this->option_key ); ?>[api_key]" value="<?php echo esc_attr( $settings['api_key'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Optional authentication token used when contacting the remote API.', 'msmc-multicurrency' ); ?></p>
        <?php
    }

    /**
     * Render automatic update option.
     */
    public function render_auto_update_field() {
        $settings = $this->get_settings();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( $this->option_key ); ?>[auto_update_rates]" value="1" <?php checked( ! empty( $settings['auto_update_rates'] ) ); ?> />
            <?php esc_html_e( 'Automatically update exchange rates twice daily.', 'msmc-multicurrency' ); ?>
        </label>
        <?php
    }

    /**
     * Retrieve all plugin settings.
     *
     * @return array
     */
    public function get_settings() {
        $defaults = array(
            'base_currency'        => 'SAR',
            'default_currency'     => 'SAR',
            'decimals'             => 2,
            'symbol_position'      => 'before',
            'show_header_switcher' => true,
            'exchange_rates'       => array(),
            'api_endpoint'         => 'https://open.er-api.com/v6/latest/%s',
            'api_key'              => '',
            'auto_update_rates'    => true,
            'last_update'          => 0,
        );

        $settings = get_option( $this->option_key, array() );

        return wp_parse_args( $settings, $defaults );
    }

    /**
     * Helper for retrieving single option value.
     *
     * @param string $key     Key.
     * @param mixed  $default Default value.
     *
     * @return mixed
     */
    public function get_option( $key, $default = null ) {
        $settings = $this->get_settings();

        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }
}
