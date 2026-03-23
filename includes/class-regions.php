<?php
/**
 * Region definitions and country code expansion.
 *
 * @package HJB_Payment_Country_Restrictions
 */

namespace HJB_PGCR;

defined( 'ABSPATH' ) || exit;

class Regions {

    private static $instance = null;

    /**
     * All available region definitions.
     *
     * @var array
     */
    private $regions = [];

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->build_regions();
    }

    /**
     * Build all region definitions.
     */
    private function build_regions() {
        $eu_countries = $this->get_eu_countries();

        $nordic = [ 'SE', 'NO', 'DK', 'FI', 'IS' ];

        $eea = array_unique( array_merge( $eu_countries, [ 'NO', 'IS', 'LI' ] ) );

        // Broader Europe including non-EU/EEA countries.
        $europe_extra = [
            'GB', 'CH', 'UA', 'RS', 'BA', 'ME', 'MK', 'AL', 'MD', 'BY',
            'GE', 'AM', 'AZ', 'TR', 'RU', 'XK', 'AD', 'MC', 'SM', 'VA',
        ];
        $europe_all = array_unique( array_merge( $eea, $europe_extra ) );

        $north_america = [ 'US', 'CA', 'MX' ];

        $south_america = [
            'AR', 'BO', 'BR', 'CL', 'CO', 'EC', 'GY', 'PY', 'PE', 'SR', 'UY', 'VE',
        ];

        $asia_pacific = [
            'CN', 'JP', 'KR', 'IN', 'ID', 'TH', 'VN', 'PH', 'MY', 'SG',
            'AU', 'NZ', 'TW', 'HK', 'MO', 'BD', 'PK', 'LK', 'NP', 'MM',
            'KH', 'LA', 'BN', 'MN', 'KZ', 'UZ', 'KG', 'TJ', 'TM',
        ];

        $middle_east = [
            'AE', 'SA', 'QA', 'KW', 'BH', 'OM', 'IL', 'JO', 'LB', 'IQ',
            'IR', 'SY', 'YE', 'PS',
        ];

        $africa = [
            'ZA', 'NG', 'KE', 'EG', 'MA', 'GH', 'TZ', 'ET', 'UG', 'DZ',
            'TN', 'SN', 'CI', 'CM', 'MZ', 'AO', 'ZW', 'BW', 'NA', 'MU',
            'RW', 'LY', 'SD', 'CD', 'MG',
        ];

        $this->regions = [
            'nordic' => [
                'label'     => __( 'Norden', 'hjb-pgcr' ),
                'countries' => $nordic,
            ],
            'eu' => [
                'label'     => __( 'EU', 'hjb-pgcr' ),
                'countries' => $eu_countries,
            ],
            'eea' => [
                'label'     => __( 'EES (EU + NO, IS, LI)', 'hjb-pgcr' ),
                'countries' => $eea,
            ],
            'europe' => [
                'label'     => __( 'Europa (alla)', 'hjb-pgcr' ),
                'countries' => $europe_all,
            ],
            'north_america' => [
                'label'     => __( 'Nordamerika', 'hjb-pgcr' ),
                'countries' => $north_america,
            ],
            'south_america' => [
                'label'     => __( 'Sydamerika', 'hjb-pgcr' ),
                'countries' => $south_america,
            ],
            'asia_pacific' => [
                'label'     => __( 'Asien & Oceanien', 'hjb-pgcr' ),
                'countries' => $asia_pacific,
            ],
            'middle_east' => [
                'label'     => __( 'Mellanöstern', 'hjb-pgcr' ),
                'countries' => $middle_east,
            ],
            'africa' => [
                'label'     => __( 'Afrika', 'hjb-pgcr' ),
                'countries' => $africa,
            ],
        ];

        /**
         * Filter to add or modify available regions.
         *
         * @param array $regions All region definitions.
         */
        $this->regions = apply_filters( 'hjb_pgcr_regions', $this->regions );
    }

    /**
     * Get EU countries from WooCommerce or fallback.
     *
     * @return array
     */
    private function get_eu_countries() {
        if ( function_exists( 'WC' ) && WC()->countries ) {
            $eu = WC()->countries->get_european_union_countries();
            if ( ! empty( $eu ) ) {
                return $eu;
            }
        }

        // Fallback list.
        return [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        ];
    }

    /**
     * Get all regions with labels.
     *
     * @return array
     */
    public function get_all() {
        return $this->regions;
    }

    /**
     * Get region labels for dropdown/checkboxes.
     *
     * @return array [ 'slug' => 'Label', ... ]
     */
    public function get_labels() {
        $labels = [];
        foreach ( $this->regions as $slug => $region ) {
            $labels[ $slug ] = $region['label'];
        }
        return $labels;
    }

    /**
     * Expand an array of region slugs to a flat array of unique country codes.
     *
     * @param array $region_slugs Array of region slugs.
     * @return array Flat array of country codes.
     */
    public function expand( array $region_slugs ) {
        $countries = [];
        foreach ( $region_slugs as $slug ) {
            if ( isset( $this->regions[ $slug ] ) ) {
                $countries = array_merge( $countries, $this->regions[ $slug ]['countries'] );
            }
        }
        return array_unique( $countries );
    }

    /**
     * Get a list of all WooCommerce selling countries.
     *
     * @return array [ 'SE' => 'Sweden', ... ]
     */
    public function get_all_countries() {
        if ( function_exists( 'WC' ) && WC()->countries ) {
            return WC()->countries->get_allowed_countries();
        }
        return [];
    }
}
