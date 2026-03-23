<?php
/**
 * Country Selector + Svea Checkout neutralizer.
 *
 * @package HJB_Payment_Country_Restrictions
 */

namespace HJB_PGCR;

defined( 'ABSPATH' ) || exit;

class Country_Selector {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		/*
		 * AJAX hooks must be registered BEFORE the is_admin() guard.
		 * admin-ajax.php sets is_admin()=true for all AJAX requests (including
		 * frontend ones), so if we returned early the action would never fire.
		 */
		add_action( 'wp_ajax_hjb_pgcr_set_country',        [ $this, 'ajax_set_country' ] );
		add_action( 'wp_ajax_nopriv_hjb_pgcr_set_country', [ $this, 'ajax_set_country' ] );

		// URL-based reset: ?hjb_reset=1 clears confirmed flag immediately on page load.
		add_action( 'wp', [ $this, 'handle_url_reset' ] );

		// Remaining hooks are frontend page rendering only.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts',               [ $this, 'enqueue_assets' ] );
		add_action( 'woocommerce_before_checkout_form', [ $this, 'render_modal' ], 1 );
		add_action( 'woocommerce_before_checkout_form', [ $this, 'render_country_bar' ], 2 );
		add_action( 'wc_ajax_refresh_sco_snippet',      [ $this, 'maybe_intercept_svea' ], 1 );

		// Reset country (clears confirmed flag, shows modal again).
		add_action( 'wp_ajax_hjb_pgcr_reset_country',        [ $this, 'ajax_reset_country' ] );
		add_action( 'wp_ajax_nopriv_hjb_pgcr_reset_country', [ $this, 'ajax_reset_country' ] );
	}

	// -------------------------------------------------------------------------

	/**
	 * Check whether any Svea gateway is available for the current country,
	 * based on the plugin's own gateway filter settings (whitelist/blacklist).
	 * This replaces the old hardcoded $svea_countries list.
	 *
	 * @return bool
	 */
	private function is_svea_available() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return false;
		}

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		foreach ( array_keys( $gateways ) as $id ) {
			if ( strpos( strtolower( $id ), 'svea' ) !== false ) {
				return true;
			}
		}

		return false;
	}

	public function maybe_intercept_svea() {
		if ( $this->is_svea_available() ) {
			return; // Svea is allowed for this country — let it handle normally.
		}

		// Svea not available: return a valid empty snippet so Svea's JS doesn't reload the page.
		wp_send_json( [
			'result'    => 'success',
			'messages'  => '',
			'reload'    => false,
			'redirect'  => false,
			'fragments' => new \stdClass(),
		] );
	}

	public function enqueue_assets() {
		if ( ! is_checkout() ) {
			return;
		}

		// Dequeue all Svea scripts when country is confirmed and Svea is not available.
		if ( $this->is_confirmed() && ! $this->is_svea_available() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_svea' ], 999 );
		}

		wp_enqueue_style(
			'hjb-pgcr-selector',
			HJB_PGCR_URL . 'assets/country-selector.css',
			[],
			HJB_PGCR_VERSION
		);

		wp_enqueue_script(
			'hjb-pgcr-selector',
			HJB_PGCR_URL . 'assets/country-selector.js',
			[ 'jquery' ],
			HJB_PGCR_VERSION,
			true
		);

		wp_localize_script( 'hjb-pgcr-selector', 'hjbPgcrSelector', [
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'hjb_pgcr_set_country' ),
			'confirmed' => $this->is_confirmed() ? '1' : '0',
			'current'   => $this->get_current_country(),
			'i18n'      => [
				'heading'    => __( 'Select your country', 'hjb-pgcr' ),
				'subheading' => __( 'Select your country to see the correct shipping options and payment methods.', 'hjb-pgcr' ),
				'button'     => __( 'Continue to checkout', 'hjb-pgcr' ),
				'choose'     => __( '— Select your country —', 'hjb-pgcr' ),
				'error'      => __( 'Could not update country. Please try again.', 'hjb-pgcr' ),
			],
		] );
	}

	public function dequeue_svea() {
		global $wp_scripts;
		if ( empty( $wp_scripts->registered ) ) {
			return;
		}
		foreach ( $wp_scripts->registered as $handle => $script ) {
			if ( isset( $script->src ) && strpos( $script->src, 'svea-checkout-for-woocommerce' ) !== false ) {
				wp_dequeue_script( $handle );
			}
		}
	}

	public function render_modal() {
		if ( ! is_checkout() || $this->is_confirmed() ) {
			return;
		}

		$countries = WC()->countries->get_countries();
		$current   = $this->get_current_country();
		?>
		<div id="hjb-pgcr-modal-overlay" class="hjb-pgcr-modal-overlay" role="dialog" aria-modal="true">
			<div class="hjb-pgcr-modal">
				<h2><?php esc_html_e( 'Select your country', 'hjb-pgcr' ); ?></h2>
				<p><?php esc_html_e( 'Select your country to see the correct shipping options and payment methods.', 'hjb-pgcr' ); ?></p>
				<div class="hjb-pgcr-modal-field">
					<label for="hjb-pgcr-modal-country"><?php esc_html_e( 'Country', 'hjb-pgcr' ); ?></label>
					<select id="hjb-pgcr-modal-country">
						<option value=""><?php esc_html_e( '— Select your country —', 'hjb-pgcr' ); ?></option>
						<?php foreach ( $countries as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $current ); ?>>
								<?php echo esc_html( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<button id="hjb-pgcr-modal-confirm" class="hjb-pgcr-modal-btn" disabled>
					<?php esc_html_e( 'Continue to checkout', 'hjb-pgcr' ); ?>
					<span class="hjb-pgcr-btn-spinner" style="display:none;"></span>
				</button>
			</div>
		</div>
		<?php
	}

	public function render_country_bar() {
		if ( ! is_checkout() || ! $this->is_confirmed() ) {
			return;
		}
		$countries = WC()->countries->get_countries();
		$current   = $this->get_current_country();
		$label     = $countries[ $current ] ?? $current;
		?>
		<div class="hjb-pgcr-country-bar">
			<span class="hjb-pgcr-country-bar__label">
				<?php esc_html_e( 'Shipping country:', 'hjb-pgcr' ); ?>
			</span>
			<strong class="hjb-pgcr-country-bar__current">
				<?php echo esc_html( $label ); ?>
			</strong>
			<button type="button" id="hjb-pgcr-change-country" class="hjb-pgcr-country-bar__change">
				<?php esc_html_e( 'Change country', 'hjb-pgcr' ); ?>
			</button>
		</div>
		<?php
	}

	public function handle_url_reset() {
		if ( ! is_checkout() || empty( $_GET['hjb_reset'] ) ) {
			return;
		}

		if ( WC()->session ) {
			WC()->session->set( 'hjb_pgcr_country_confirmed', false );
			WC()->session->save_data();
		}

		// Redirect to clean checkout URL without the query param.
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	public function ajax_reset_country() {
		if ( function_exists( 'WC' ) && WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		$nonce_ok = check_ajax_referer( 'hjb_pgcr_reset_country', 'nonce', false );
		if ( ! $nonce_ok ) {
			wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
		}

		if ( WC()->session ) {
			WC()->session->set( 'hjb_pgcr_country_confirmed', false );
			// Force session save – without this the value may not persist before page reload.
			WC()->session->save_data();
		}

		wp_send_json_success();
	}

	public function ajax_set_country() {
		// Initialize WC session before anything else.
		if ( function_exists( 'WC' ) && WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		// Verify nonce – die with 403 on failure.
		$nonce_ok = check_ajax_referer( 'hjb_pgcr_set_country', 'nonce', false );
		if ( ! $nonce_ok ) {
			wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
		}

		$country = isset( $_POST['country'] )
			? strtoupper( sanitize_text_field( wp_unslash( $_POST['country'] ) ) )
			: '';

		$all = array_keys( WC()->countries->get_countries() );
		if ( ! preg_match( '/^[A-Z]{2}$/', $country ) || ! in_array( $country, $all, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid country.', 'hjb-pgcr' ) ] );
		}

		if ( WC()->customer ) {
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_shipping_country( $country );
			WC()->customer->save();
		}

		if ( WC()->session ) {
			$sd                      = (array) WC()->session->get( 'customer', [] );
			$sd['country']           = $country;
			$sd['shipping_country']  = $country;
			WC()->session->set( 'customer', $sd );
			WC()->session->set( 'hjb_pgcr_country_confirmed', true );
			// Force write to DB before the JS does window.location.reload().
			WC()->session->save_data();
		}

		wp_send_json_success( [
			'country'           => $country,
			'is_svea_supported' => $this->is_svea_available(),
		] );
	}

	private function is_confirmed() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return false;
		}
		return (bool) WC()->session->get( 'hjb_pgcr_country_confirmed', false );
	}

	private function get_current_country() {
		if ( function_exists( 'WC' ) ) {
			if ( WC()->customer ) {
				$c = WC()->customer->get_billing_country();
				if ( ! empty( $c ) ) return strtoupper( $c );
			}
			if ( WC()->countries ) {
				return strtoupper( WC()->countries->get_base_country() );
			}
		}
		return 'SE';
	}
}
