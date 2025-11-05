<?php
/**
 * Currency switcher widget.
 *
 * @package MSMC_MultiCurrency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Widget for displaying the currency switcher.
 */
class MSMC_Multi_Currency_Widget extends WP_Widget {

    /**
     * MSMC_Multi_Currency_Widget constructor.
     */
    public function __construct() {
        parent::__construct(
            'msmc_currency_widget',
            __( 'Currency Switcher (MasterStudy)', 'msmc-multicurrency' ),
            array(
                'classname'   => 'msmc-currency-widget',
                'description' => __( 'Allow visitors to switch currencies for MasterStudy LMS and WooCommerce.', 'msmc-multicurrency' ),
            )
        );
    }

    /**
     * Render widget front-end.
     *
     * @param array $args Widget args.
     * @param array $instance Widget instance.
     */
    public function widget( $args, $instance ) {
        $plugin   = MSMC_Multi_Currency_Plugin::instance();
        $switcher = new MSMC_Multi_Currency_Switcher( $plugin );

        $title   = isset( $instance['title'] ) ? $instance['title'] : '';
        $display = isset( $instance['display'] ) ? $instance['display'] : 'dropdown';

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
        }

        echo $switcher->render_switcher( array( 'display' => $display ) );

        echo $args['after_widget'];
    }

    /**
     * Render admin form.
     *
     * @param array $instance Instance values.
     */
    public function form( $instance ) {
        $title   = isset( $instance['title'] ) ? $instance['title'] : ''; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        $display = isset( $instance['display'] ) ? $instance['display'] : 'dropdown';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'msmc-multicurrency' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'display' ) ); ?>"><?php esc_html_e( 'Display Style:', 'msmc-multicurrency' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display' ) ); ?>">
                <option value="dropdown" <?php selected( $display, 'dropdown' ); ?>><?php esc_html_e( 'Dropdown', 'msmc-multicurrency' ); ?></option>
                <option value="list" <?php selected( $display, 'list' ); ?>><?php esc_html_e( 'List', 'msmc-multicurrency' ); ?></option>
            </select>
        </p>
        <?php
    }

    /**
     * Sanitize widget update.
     *
     * @param array $new_instance New instance.
     * @param array $old_instance Old instance.
     *
     * @return array
     */
    public function update( $new_instance, $old_instance ) {
        $instance            = $old_instance;
        $instance['title']   = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $display             = isset( $new_instance['display'] ) ? $new_instance['display'] : 'dropdown';
        $instance['display'] = in_array( $display, array( 'dropdown', 'list' ), true ) ? $display : 'dropdown';

        return $instance;
    }
}
