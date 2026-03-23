<?php
/**
 * Admin settings page for Payment Country Restrictions.
 *
 * @package HJB_Payment_Country_Restrictions
 */

namespace HJB_PGCR;

defined( 'ABSPATH' ) || exit;

class Settings {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_hjb_pgcr_save', [ $this, 'ajax_save' ] );
    }

    /**
     * Add admin menu page under WooCommerce.
     */
    public function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Betalningsrestriktioner', 'hjb-pgcr' ),
            __( 'Betalningsrestriktioner', 'hjb-pgcr' ),
            'manage_woocommerce',
            'hjb-pgcr',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        if ( 'woocommerce_page_hjb-pgcr' !== $hook ) {
            return;
        }

        // WooCommerce already loads Select2 in admin, but let's make sure.
        wp_enqueue_style( 'woocommerce_admin_styles' );
        wp_enqueue_script( 'wc-enhanced-select' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );

        wp_enqueue_style(
            'hjb-pgcr-admin',
            HJB_PGCR_URL . 'assets/admin.css',
            [],
            HJB_PGCR_VERSION
        );

        wp_enqueue_script(
            'hjb-pgcr-admin',
            HJB_PGCR_URL . 'assets/admin.js',
            [ 'jquery', 'select2' ],
            HJB_PGCR_VERSION,
            true
        );

        // Pass data to JS.
        $regions = Regions::instance();
        wp_localize_script( 'hjb-pgcr-admin', 'hjbPgcr', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'hjb_pgcr_save' ),
            'regions'   => $regions->get_all(),
            'i18n'      => [
                'saved'           => __( 'Inställningar sparade!', 'hjb-pgcr' ),
                'error'           => __( 'Något gick fel vid sparning.', 'hjb-pgcr' ),
                'resultCountries' => __( 'Resulterande länder', 'hjb-pgcr' ),
                'noRestrictions'  => __( 'Inga begränsningar – visas för alla länder', 'hjb-pgcr' ),
                'showFor'         => __( 'Visas BARA för', 'hjb-pgcr' ),
                'hideFor'         => __( 'Döljs för', 'hjb-pgcr' ),
                'countries'       => __( 'länder', 'hjb-pgcr' ),
                'selectCountries' => __( 'Välj länder...', 'hjb-pgcr' ),
            ],
        ] );
    }

    /**
     * Render the settings page.
     */
    public function render_page() {
        $gateways = $this->get_all_gateways();
        $settings = get_option( HJB_PGCR_OPTION_KEY, [] );
        $regions  = Regions::instance();
        $all_countries = $regions->get_all_countries();
        $region_labels = $regions->get_labels();

        ?>
        <div class="wrap hjb-pgcr-wrap">
            <h1><?php esc_html_e( 'Betalningsrestriktioner per land', 'hjb-pgcr' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Konfigurera vilka betalningsmetoder som ska vara tillgängliga baserat på kundens land. Välj whitelist (visa bara för valda länder) eller blacklist (dölj för valda länder).', 'hjb-pgcr' ); ?>
            </p>

            <div id="hjb-pgcr-notices"></div>

            <?php if ( empty( $gateways ) ) : ?>
                <div class="notice notice-warning inline">
                    <p><?php esc_html_e( 'Inga aktiva betalningsmetoder hittades i WooCommerce.', 'hjb-pgcr' ); ?></p>
                </div>
            <?php else : ?>
                <form id="hjb-pgcr-form">
                    <div class="hjb-pgcr-gateways">
                        <?php foreach ( $gateways as $gateway_id => $gateway ) :
                            $rule = $settings[ $gateway_id ] ?? [
                                'mode'      => 'none',
                                'regions'   => [],
                                'countries' => [],
                            ];
                            $mode            = $rule['mode'] ?? 'none';
                            $saved_regions   = $rule['regions'] ?? [];
                            $saved_countries = $rule['countries'] ?? [];
                            $is_enabled      = $gateway->enabled === 'yes';
                            $has_rules       = $mode !== 'none';
                        ?>
                        <div class="hjb-pgcr-gateway <?php echo $has_rules ? 'has-rules' : ''; ?> <?php echo $is_enabled ? '' : 'gateway-disabled'; ?>"
                             data-gateway-id="<?php echo esc_attr( $gateway_id ); ?>">

                            <div class="hjb-pgcr-gateway-header">
                                <span class="hjb-pgcr-gateway-icon">
                                    <?php if ( $has_rules ) : ?>
                                        <span class="dashicons dashicons-shield" title="<?php esc_attr_e( 'Har aktiva restriktioner', 'hjb-pgcr' ); ?>"></span>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-shield-alt" title="<?php esc_attr_e( 'Inga restriktioner', 'hjb-pgcr' ); ?>"></span>
                                    <?php endif; ?>
                                </span>
                                <h3 class="hjb-pgcr-gateway-title">
                                    <?php echo esc_html( $gateway->get_title() ); ?>
                                    <code class="hjb-pgcr-gateway-id"><?php echo esc_html( $gateway_id ); ?></code>
                                </h3>
                                <span class="hjb-pgcr-gateway-status">
                                    <?php if ( $is_enabled ) : ?>
                                        <span class="status-dot status-active"></span>
                                        <?php esc_html_e( 'Aktiverad', 'hjb-pgcr' ); ?>
                                    <?php else : ?>
                                        <span class="status-dot status-inactive"></span>
                                        <?php esc_html_e( 'Inaktiverad', 'hjb-pgcr' ); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="hjb-pgcr-toggle dashicons dashicons-arrow-down-alt2"></span>
                            </div>

                            <div class="hjb-pgcr-gateway-body" style="display: none;">
                                <!-- Mode selection -->
                                <div class="hjb-pgcr-field hjb-pgcr-mode-field">
                                    <label class="hjb-pgcr-field-label"><?php esc_html_e( 'Restriktionsläge', 'hjb-pgcr' ); ?></label>
                                    <div class="hjb-pgcr-radio-group">
                                        <label class="hjb-pgcr-radio">
                                            <input type="radio"
                                                   name="hjb_pgcr[<?php echo esc_attr( $gateway_id ); ?>][mode]"
                                                   value="none"
                                                   <?php checked( $mode, 'none' ); ?>>
                                            <span class="hjb-pgcr-radio-label">
                                                <?php esc_html_e( 'Inga begränsningar', 'hjb-pgcr' ); ?>
                                                <small><?php esc_html_e( 'Visas för alla länder', 'hjb-pgcr' ); ?></small>
                                            </span>
                                        </label>
                                        <label class="hjb-pgcr-radio">
                                            <input type="radio"
                                                   name="hjb_pgcr[<?php echo esc_attr( $gateway_id ); ?>][mode]"
                                                   value="whitelist"
                                                   <?php checked( $mode, 'whitelist' ); ?>>
                                            <span class="hjb-pgcr-radio-label">
                                                <?php esc_html_e( 'Whitelist', 'hjb-pgcr' ); ?>
                                                <small><?php esc_html_e( 'Visa BARA för valda länder/regioner', 'hjb-pgcr' ); ?></small>
                                            </span>
                                        </label>
                                        <label class="hjb-pgcr-radio">
                                            <input type="radio"
                                                   name="hjb_pgcr[<?php echo esc_attr( $gateway_id ); ?>][mode]"
                                                   value="blacklist"
                                                   <?php checked( $mode, 'blacklist' ); ?>>
                                            <span class="hjb-pgcr-radio-label">
                                                <?php esc_html_e( 'Blacklist', 'hjb-pgcr' ); ?>
                                                <small><?php esc_html_e( 'Dölj för valda länder/regioner', 'hjb-pgcr' ); ?></small>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Country/Region selection (shown when not 'none') -->
                                <div class="hjb-pgcr-restrictions-config" style="<?php echo $mode === 'none' ? 'display:none;' : ''; ?>">
                                    <!-- Regions -->
                                    <div class="hjb-pgcr-field">
                                        <label class="hjb-pgcr-field-label"><?php esc_html_e( 'Regioner', 'hjb-pgcr' ); ?></label>
                                        <div class="hjb-pgcr-checkbox-group">
                                            <?php foreach ( $region_labels as $slug => $label ) : ?>
                                                <label class="hjb-pgcr-checkbox">
                                                    <input type="checkbox"
                                                           name="hjb_pgcr[<?php echo esc_attr( $gateway_id ); ?>][regions][]"
                                                           value="<?php echo esc_attr( $slug ); ?>"
                                                           <?php checked( in_array( $slug, $saved_regions, true ) ); ?>>
                                                    <?php echo esc_html( $label ); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Individual countries -->
                                    <div class="hjb-pgcr-field">
                                        <label class="hjb-pgcr-field-label"><?php esc_html_e( 'Enskilda länder', 'hjb-pgcr' ); ?></label>
                                        <select class="hjb-pgcr-country-select"
                                                name="hjb_pgcr[<?php echo esc_attr( $gateway_id ); ?>][countries][]"
                                                multiple="multiple"
                                                data-placeholder="<?php esc_attr_e( 'Välj länder...', 'hjb-pgcr' ); ?>">
                                            <?php foreach ( $all_countries as $code => $name ) : ?>
                                                <option value="<?php echo esc_attr( $code ); ?>"
                                                        <?php selected( in_array( $code, $saved_countries, true ) ); ?>>
                                                    <?php echo esc_html( $name ); ?> (<?php echo esc_html( $code ); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Live preview -->
                                    <div class="hjb-pgcr-preview">
                                        <div class="hjb-pgcr-preview-label"></div>
                                        <div class="hjb-pgcr-preview-countries"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="hjb-pgcr-submit-wrap">
                        <button type="submit" class="button button-primary button-hero">
                            <?php esc_html_e( 'Spara inställningar', 'hjb-pgcr' ); ?>
                        </button>
                        <span class="hjb-pgcr-saving-spinner spinner" style="float: none;"></span>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX handler for saving settings.
     */
    public function ajax_save() {
        check_ajax_referer( 'hjb_pgcr_save', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Behörighet saknas.', 'hjb-pgcr' ) ] );
        }

        $raw_data = isset( $_POST['settings'] ) ? $_POST['settings'] : [];
        $settings = $this->sanitize_settings( $raw_data );

        update_option( HJB_PGCR_OPTION_KEY, $settings, false );

        wp_send_json_success( [ 'message' => __( 'Inställningar sparade!', 'hjb-pgcr' ) ] );
    }

    /**
     * Sanitize incoming settings data.
     *
     * @param array $raw Raw settings data.
     * @return array Sanitized settings.
     */
    private function sanitize_settings( $raw ) {
        if ( ! is_array( $raw ) ) {
            return [];
        }

        $sanitized = [];
        $valid_modes = [ 'none', 'whitelist', 'blacklist' ];
        $region_slugs = array_keys( Regions::instance()->get_labels() );

        foreach ( $raw as $gateway_id => $rule ) {
            $gateway_id = sanitize_text_field( $gateway_id );

            $mode = isset( $rule['mode'] ) ? sanitize_text_field( $rule['mode'] ) : 'none';
            if ( ! in_array( $mode, $valid_modes, true ) ) {
                $mode = 'none';
            }

            $regions = [];
            if ( ! empty( $rule['regions'] ) && is_array( $rule['regions'] ) ) {
                foreach ( $rule['regions'] as $slug ) {
                    $slug = sanitize_text_field( $slug );
                    if ( in_array( $slug, $region_slugs, true ) ) {
                        $regions[] = $slug;
                    }
                }
            }

            $countries = [];
            if ( ! empty( $rule['countries'] ) && is_array( $rule['countries'] ) ) {
                foreach ( $rule['countries'] as $code ) {
                    $code = strtoupper( sanitize_text_field( $code ) );
                    if ( preg_match( '/^[A-Z]{2}$/', $code ) ) {
                        $countries[] = $code;
                    }
                }
            }

            $sanitized[ $gateway_id ] = [
                'mode'      => $mode,
                'regions'   => $regions,
                'countries' => $countries,
            ];
        }

        return $sanitized;
    }

    /**
     * Get all registered payment gateways (both enabled and disabled).
     *
     * @return array
     */
    private function get_all_gateways() {
        if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
            return [];
        }

        return WC()->payment_gateways()->payment_gateways();
    }
}
