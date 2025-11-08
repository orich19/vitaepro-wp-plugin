<?php
if ( ! function_exists( 'readonly' ) ) {
    /**
     * Outputs the readonly attribute when the values match.
     *
     * @param mixed $is_readonly Value to compare.
     * @param mixed $current     Expected value to match.
     * @param bool  $echo        Whether to echo or just return the string.
     *
     * @return string
     */
    function readonly( $is_readonly, $current = true, $echo = true ) {
        $result = (string) $is_readonly === (string) $current ? ' readonly="readonly"' : '';

        if ( $echo ) {
            echo $result;
        }

        return $result;
    }
}

if ( ! function_exists( 'disabled' ) ) {
    /**
     * Outputs the disabled attribute when the values match.
     *
     * @param mixed $disabled Value to compare.
     * @param mixed $current  Expected value to match.
     * @param bool  $echo     Whether to echo or just return the string.
     *
     * @return string
     */
    function disabled( $disabled, $current = true, $echo = true ) {
        $result = (string) $disabled === (string) $current ? ' disabled="disabled"' : '';

        if ( $echo ) {
            echo $result;
        }

        return $result;
    }
}
