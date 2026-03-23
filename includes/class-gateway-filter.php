<?php
/**
 * Gateway filter – restricts payment gateways based on customer country.
 *
 * @package HJB_Payment_Country_Restrictions
 */

namespace HJB_PGCR;

defined( 'ABSPATH' ) || exit;

class Gateway_Filter {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_gateways' ], 100 );
    }

    /**
     * Filter payment gateways based on saved country restrictions.
     *
     * @param array $gateways Available payment gateways.
     * @return array Filtered payment gateways.
     */
    public function filter_gateways( $gateways ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $gateways;
        }

        $country = $this->get_customer_country();
        if ( empty( $country ) ) {
            return $gateways;
        }

        $settings = get_option( HJB_PGCR_OPTION_KEY, [] );
        if ( empty( $settings ) || ! is_array( $settings ) ) {
            return $gateways;
        }

        $regions = Regions::instance();

        foreach ( $gateways as $gateway_id => $gateway ) {
            if ( ! isset( $settings[ $gateway_id ] ) ) {
                continue;
            }

            $rule = $settings[ $gateway_id ];
            $mode = $rule['mode'] ?? 'none';

            if ( 'none' === $mode ) {
                continue;
            }

            // Build the complete list of countries for this rule.
            $rule_countries = $this->build_country_list( $rule, $regions );

            if ( empty( $rule_countries ) ) {
                // No countries configured – skip filtering for this gateway.
                continue;
            }

            $country_in_list = in_array( $country, $rule_countries, true );

            if ( 'whitelist' === $mode && ! $country_in_list ) {
                unset( $gateways[ $gateway_id ] );
            } elseif ( 'blacklist' === $mode && $country_in_list ) {
                unset( $gateways[ $gateway_id ] );
            }
        }

        /**
         * Filter the gateways after country restriction has been applied.
         *
         * @param array  $gateways Filtered gateways.
         * @param string $country  Customer billing country.
         * @param array  $settings Current restriction settings.
         */
        return apply_filters( 'hjb_pgcr_filtered_gateways', $gateways, $country, $settings );
    }

    /**
     * Build a flat array of country codes from regions + individual countries.
     *
     * @param array   $rule    The rule config for a single gateway.
     * @param Regions $regions Regions instance.
     * @return array
     */
    private function build_country_list( $rule, $regions ) {
        $countries = [];

        // Expand regions.
        $rule_regions = $rule['regions'] ?? [];
        if ( ! empty( $rule_regions ) && is_array( $rule_regions ) ) {
            $countries = $regions->expand( $rule_regions );
        }

        // Merge individual countries.
        $rule_countries = $rule['countries'] ?? [];
        if ( ! empty( $rule_countries ) && is_array( $rule_countries ) ) {
            $countries = array_merge( $countries, $rule_countries );
        }

        return array_unique( $countries );
    }

    /**
     * Get the customer billing country.
     *
     * @return string Two-letter country code or empty string.
     */
    private function get_customer_country() {
        if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
            return '';
        }

        $country = WC()->customer->get_billing_country();

        // Fallback to shipping country if billing is empty.
        if ( empty( $country ) ) {
            $country = WC()->customer->get_shipping_country();
        }

        // Fallback to store base country.
        if ( empty( $country ) ) {
            $country = WC()->countries->get_base_country();
        }

        return strtoupper( $country );
    }
}
