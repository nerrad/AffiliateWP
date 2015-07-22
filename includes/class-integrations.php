<?php

class Affiliate_WP_Integrations {

	/**
	 * An array of all registered integrations
	 *
	 * @var array
	 */
	private $registered = array();

	/**
	 * An array of the enabled integrations
	 *
	 * @var array
	 */
	private $enabled = array();

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->registered = (array) apply_filters( 'affwp_integrations',
			array(
				'edd'            => 'Easy Digital Downloads',
				'formidablepro'  => 'Formidable Pro',
				'gravityforms'   => 'Gravity Forms',
				'exchange'       => 'iThemes Exchange',
				'jigoshop'       => 'Jigoshop',
				'marketpress'    => 'MarketPress',
				'membermouse'    => 'MemberMouse',
				'memberpress'    => 'MemberPress',
				'ninja-forms'    => 'Ninja Forms',
				'pmp'            => 'Paid Memberships Pro',
				'rcp'            => 'Restrict Content Pro',
				'shopp'	         => 'Shopp',
				'sproutinvoices' => 'Sprout Invoices',
				'woocommerce'    => 'WooCommerce',
				'wpec'           => 'WP e-Commerce',
			)
		);

		$this->enabled = (array) apply_filters( 'affwp_enabled_integrations', affiliate_wp()->settings->get( 'integrations', array() ) );

		$this->load();

	}

	/**
	 * Load each enabled integration
	 */
	private function load() {

		if ( ! $this->enabled ) {
			return;
		}

		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/integrations/class-base.php';

		$loaded = array();

		do_action( 'affwp_integrations_load' );

		foreach ( $this->enabled as $filename => $label ) {

			if ( is_readable( AFFILIATEWP_PLUGIN_DIR . 'includes/integrations/class-' . $filename . '.php' ) ) {

				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/integrations/class-' . $filename . '.php';

				$loaded[ $filename ] = $label;

			}

		}

		do_action( 'affwp_integrations_loaded', $loaded );

	}

	/**
	 * Return an array of all registered integrations
	 *
	 * @return array
	 */
	public function get_registered() {

		return (array) $this->registered;

	}

	/**
	 * Return an array of all enabled integrations
	 *
	 * @return array
	 */
	public function get_enabled() {

		return (array) $this->enabled;

	}

	/**
	 * Check to see if a particlar integration has been registered
	 *
	 * @param  string $integration
	 * @return bool
	 */
	public function is_registered( $integration ) {

		return array_key_exists( $integration, $this->get_registered() );

	}

	/**
	 * Check to see if a particlar integration is enabled
	 *
	 * @param  string $integration
	 * @return bool
	 */
	public function is_enabled( $integration ) {

		return array_key_exists( $integration, $this->get_enabled() );

	}

}
