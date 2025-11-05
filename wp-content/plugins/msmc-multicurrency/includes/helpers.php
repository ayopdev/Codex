<?php
/**
 * Helper functions for MasterStudy Multi-Currency Toolkit.
 *
 * @package MSMC_MultiCurrency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'msmc_round_money' ) ) {
    /**
     * Round a money value to appropriate decimals.
     *
     * @param float $value Value to round.
     *
     * @return float
     */
    function msmc_round_money( $value ) {
        return round( $value, 4 );
    }
}

if ( ! function_exists( 'msmc_get_array_value' ) ) {
    /**
     * Retrieve a value from an array with default.
     *
     * @param array  $array   Array.
     * @param string $key     Key.
     * @param mixed  $default Default value.
     *
     * @return mixed
     */
    function msmc_get_array_value( $array, $key, $default = null ) {
        if ( isset( $array[ $key ] ) ) {
            return $array[ $key ];
        }

        return $default;
    }
}
