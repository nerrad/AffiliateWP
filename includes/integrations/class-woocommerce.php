<?php

class Affiliate_WP_WooCommerce extends Affiliate_WP_Base {

	/**
	 * The order object
	 *
	 * @access  private
	 * @since   1.1
	*/
	private $order;

	/**
	 * Setup actions and filters
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->context = 'woocommerce';

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_pending_referral' ), 10, 2 );

		// There should be an option to choose which of these is used
		add_action( 'woocommerce_order_status_completed', array( $this, 'mark_referral_complete' ), 10 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'mark_referral_complete' ), 10 );
		add_action( 'woocommerce_order_status_completed_to_refunded', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_processing_to_refunded', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_completed_to_cancelled', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_pending_to_cancelled', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_pending_to_failed', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'wc-on-hold_to_trash', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'wc-processing_to_trash', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'wc-completed_to_trash', array( $this, 'revoke_referral_on_refund' ), 10 );

		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

		add_action( 'woocommerce_coupon_options', array( $this, 'coupon_option' ) );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'store_discount_affiliate' ) );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'store_allow_affiliate_coupons' ) );

		// Per product referral rates
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_settings' ), 100 );
		add_action( 'save_post', array( $this, 'save_meta' ) );

		// Dashboard tab for custom Coupons
		add_action( 'affwp_affiliate_dashboard_tabs', array( $this, 'custom_coupons' ), 10, 2 );
		add_action( 'wp_ajax_affwp_custom_coupons_add', array( $this, 'custom_coupons_add' ) );
		add_action( 'wp_ajax_affwp_custom_coupons_delete', array( $this, 'custom_coupons_delete' ) );

	}

	/**
	 * Store a pending referral when a new order is created
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function add_pending_referral( $order_id = 0, $posted ) {

		$this->order = apply_filters( 'affwp_get_woocommerce_order', new WC_Order( $order_id ) );

		// Check if an affiliate coupon was used
		$affiliate_id = $this->get_coupon_affiliate_id();

		if( $this->was_referred() || $affiliate_id ) {

			if( false !== $affiliate_id ) {
				$this->affiliate_id = $affiliate_id;
			}

			if ( $this->is_affiliate_email( $this->order->billing_email ) ) {
				return false; // Customers cannot refer themselves
			}

			// Check for an existing referral
			$existing = affiliate_wp()->referrals->get_by( 'reference', $order_id, $this->context );

			// If an existing referral exists and it is not pending, exit. If it is pending, we update it below
			if( $existing && 'pending' != $existing->status ) {

				return false; // Referral already created for this reference

			}

			$cart_shipping = $this->order->get_total_shipping();

			$items = $this->order->get_items();

			// Calculate the referral amount based on product prices
			$amount = 0.00;
			foreach( $items as $product ) {

				if( get_post_meta( $product['product_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
					continue; // Referrals are disabled on this product
				}

				// The order discount has to be divided across the items

				$product_total = $product['line_total'];
				$shipping      = 0;

				if( $cart_shipping > 0 && ! affiliate_wp()->settings->get( 'exclude_shipping' ) ) {

					$shipping       = $cart_shipping / count( $items );
					$product_total += $shipping;

				}

				if( ! affiliate_wp()->settings->get( 'exclude_tax' ) ) {

					$product_total += $product['line_tax'];

				}

				if( $product_total <= 0 ) {
					continue;
				}

				$amount += $this->calculate_referral_amount( $product_total, $order_id, $product['product_id'] );

			}

			if( 0 == $amount && affiliate_wp()->settings->get( 'ignore_zero_referrals' ) ) {
				return false; // Ignore a zero amount referral
			}

			$description = $this->get_referral_description();
			$visit_id    = affiliate_wp()->tracking->get_visit_id();

			if( $existing ) {

				// Update the previously created referral
				affiliate_wp()->referrals->update_referral( $existing->referral_id, array(
					'amount'       => $amount,
					'reference'    => $order_id,
					'description'  => $description,
					'affiliate_id' => $this->affiliate_id,
					'visit_id'     => $visit_id,
					'products'     => $this->get_products(),
					'context'      => $this->context
				) );

			} else {

				// Create a new referral
				$referral_id = affiliate_wp()->referrals->add( apply_filters( 'affwp_insert_pending_referral', array(
					'amount'       => $amount,
					'reference'    => $order_id,
					'description'  => $description,
					'affiliate_id' => $this->affiliate_id,
					'visit_id'     => $visit_id,
					'products'     => $this->get_products(),
					'context'      => $this->context
				), $amount, $order_id, $description, $this->affiliate_id, $visit_id, array(), $this->context ) );

				if( $referral_id ) {

					$amount = affwp_currency_filter( affwp_format_amount( $amount ) );
					$name   = affiliate_wp()->affiliates->get_affiliate_name( $this->affiliate_id );

					$this->order->add_order_note( sprintf( __( 'Referral #%d for %s recorded for %s', 'affiliate-wp' ), $referral_id, $amount, $name ) );

				}
			}


		}

	}

	/**
	 * Retrieves the product details array for the referral
	 *
	 * @access  public
	 * @since   1.6
	 * @return  array
	*/
	public function get_products( $order_id = 0 ) {

		$products  = array();
		$items     = $this->order->get_items();
		foreach( $items as $key => $product ) {

			if( get_post_meta( $product['product_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
				continue; // Referrals are disabled on this product
			}

			if( affiliate_wp()->settings->get( 'exclude_tax' ) ) {
				$amount = $product['line_total'] - $product['line_tax'];
			} else {
				$amount = $product['line_total'];
			}

			$products[] = array(
				'name'            => $product['name'],
				'id'              => $product['product_id'],
				'price'           => $amount,
				'referral_amount' => $this->calculate_referral_amount( $amount, $order_id, $product['product_id'] )
			);

		}

		return $products;

	}

	/**
	 * Mark referral as complete when payment is completed
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function mark_referral_complete( $order_id = 0 ) {

		$this->complete_referral( $order_id );

	}

	/**
	 * Revoke the referral when the order is refunded
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function revoke_referral_on_refund( $order_id = 0 ) {

		if ( is_a( $order_id, 'WP_Post' ) ) {
			$order_id = $order_id->ID;
		}

		if( ! affiliate_wp()->settings->get( 'revoke_on_refund' ) ) {
			return;
		}

		if( 'shop_order' != get_post_type( $order_id ) ) {
			return;
		}

		$this->reject_referral( $order_id );

	}

	/**
	 * Setup the reference link
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function reference_link( $reference = 0, $referral ) {

		if( empty( $referral->context ) || 'woocommerce' != $referral->context ) {

			return $reference;

		}

		$url = get_edit_post_link( $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

	/**
	 * Shows the affiliate drop down on the discount edit / add screens
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function coupon_option() {

		global $post;

		add_filter( 'affwp_is_admin_page', '__return_true' );
		affwp_admin_scripts();

		$affiliate_id      = get_post_meta( $post->ID, 'affwp_discount_affiliate', true );
		$user_id           = affwp_get_affiliate_user_id( $affiliate_id );
		$user              = get_userdata( $user_id );
		$user_name         = $user ? $user->user_login : '';
		$affiliate_coupons = get_post_meta( $post->ID, 'affwp_allow_affiliate_coupons', true );
?>
		<p class="form-field affwp-woo-coupon-field">
			<label for="user_name"><?php _e( 'Affiliate Discount?', 'affiliate-wp' ); ?></label>
			<span class="affwp-ajax-search-wrap">
				<span class="affwp-woo-coupon-input-wrap">
					<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
					<input type="text" name="user_name" id="user_name" value="<?php echo esc_attr( $user_name ); ?>" class="affwp-user-search" autocomplete="off" />
					<img class="affwp-ajax waiting" src="<?php echo admin_url('images/wpspin_light.gif'); ?>" style="display: none;"/>
				</span>
				<span id="affwp_user_search_results"></span>
				<img class="help_tip" data-tip='<?php _e( 'If you would like to connect this discount to an affiliate, enter the name of the affiliate it belongs to.', 'affiliate-wp' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
			</span>
		</p>

		<p class="form-field affwp_allow_affiliate_coupons_field ">
			<label for="affwp_allow_affiliate_coupons"><?php _e( 'Affiliate Coupon?', 'affiliate-wp' ); ?></label>
			<input type="checkbox" class="checkbox" style="" name="affwp_allow_affiliate_coupons" id="affwp_allow_affiliate_coupons" value="yes" <?php checked( $affiliate_coupons ); ?>>
			<span class="description"><?php _e( 'Check this box if you want to allow affiliates to create their own variations of this coupon to promote to their audience.', 'affiliate-wp' ); ?></span>
		</p>
<?php
	}

	/**
	 * Stores the affiliate ID in the discounts meta if it is an affiliate's discount
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function store_discount_affiliate( $coupon_id = 0 ) {

		if( empty( $_POST['user_name'] ) ) {

			delete_post_meta( $coupon_id, 'affwp_discount_affiliate' );
			return;

		}

		if( empty( $_POST['user_id'] ) && empty( $_POST['user_name'] ) ) {
			return;
		}

		if( empty( $_POST['user_id'] ) ) {
			$user = get_user_by( 'login', $_POST['user_name'] );
			if( $user ) {
				$user_id = $user->ID;
			}
		} else {
			$user_id = absint( $_POST['user_id'] );
		}

		$affiliate_id = affwp_get_affiliate_id( $user_id );

		update_post_meta( $coupon_id, 'affwp_discount_affiliate', $affiliate_id );
	}

	/**
	 * Stores the option to allow affiliates to customize this coupon
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function store_allow_affiliate_coupons( $coupon_id = 0 ) {

		if ( empty( $_POST['affwp_allow_affiliate_coupons'] ) ) {

			delete_post_meta( $coupon_id, 'affwp_allow_affiliate_coupons' );

			return;

		}

		update_post_meta( $coupon_id, 'affwp_allow_affiliate_coupons', 1 );

	}

	/**
	 * Retrieve the affiliate ID for the coupon used, if any
	 *
	 * @access  public
	 * @since   1.1
	*/
	private function get_coupon_affiliate_id() {

		$coupons = $this->order->get_used_coupons();

		if( empty( $coupons ) ) {
			return false;
		}

		foreach( $coupons as $code ) {

			$coupon       = new WC_Coupon( $code );
			$affiliate_id = get_post_meta( $coupon->id, 'affwp_discount_affiliate', true );

			if( $affiliate_id ) {

				if( ! affiliate_wp()->tracking->is_valid_affiliate( $affiliate_id ) ) {
					continue;
				}

				return $affiliate_id;

			}

		}

		return false;
	}

	/**
	 * Retrieves the referral description
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function get_referral_description() {

		$items       = $this->order->get_items();
		$description = array();

		foreach ( $items as $key => $item ) {

			$description[] = $item['name'];

			if ( get_post_meta( $item['product_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
				continue; // Referrals are disabled on this product
			}

		}

		$description = implode( ', ', $description );

		return $description;

	}

	/**
	 * Adds per-product referral rate settings input fields
	 *
	 * @access  public
	 * @since   1.2
	*/
	public function product_settings() {

		global $post;

		woocommerce_wp_text_input( array(
			'id'          => '_affwp_woocommerce_product_rate',
			'label'       => __( 'Affiliate Rate', 'affiliate-wp' ),
			'desc_tip'    => true,
			'description' => __( 'These settings will be used to calculate affiliate earnings per-sale. Leave blank to use default affiliate rates.', 'affiliate-wp' )
		) );
		woocommerce_wp_checkbox( array(
			'id'          => '_affwp_woocommerce_referrals_disabled',
			'label'       => __( 'Disable referrals', 'affiliate-wp' ),
			'description' => __( 'This will prevent orders of this product from generating referral commissions for affiliates.', 'affiliate-wp' ),
			'cbvalue'     => 1
		) );

	}

	/**
	 * Saves per-product referral rate settings input fields
	 *
	 * @access  public
	 * @since   1.2
	*/
	public function save_meta( $post_id = 0 ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		$post = get_post( $post_id );

		if( ! $post ) {
			return $post_id;
		}

		// Check post type is product
		if ( 'product' != $post->post_type ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if( ! empty( $_POST['_affwp_' . $this->context . '_product_rate'] ) ) {

			$rate = sanitize_text_field( $_POST['_affwp_' . $this->context . '_product_rate'] );
			update_post_meta( $post_id, '_affwp_' . $this->context . '_product_rate', $rate );

		} else {

			delete_post_meta( $post_id, '_affwp_' . $this->context . '_product_rate' );

		}

		if( isset( $_POST['_affwp_' . $this->context . '_referrals_disabled'] ) ) {

			update_post_meta( $post_id, '_affwp_' . $this->context . '_referrals_disabled', 1 );

		} else {

			delete_post_meta( $post_id, '_affwp_' . $this->context . '_referrals_disabled' );

		}

	}

	public function custom_coupons_enabled() {

		$option = affiliate_wp()->settings->get( 'custom_coupons_enabled' );

		return true; // Temp

		return ! empty( $option );

	}

	public function custom_coupons( $affiliate_id, $active_tab ) {

		if ( ! $this->custom_coupons_enabled() ) {
			return;
		}

		?>
		<li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'wc-coupons' ? ' active' : ''; ?>">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'wc-coupons' ) ); ?>"><?php _e( 'Coupons', 'affiliate-wp' ); ?></a>
		</li>
		<?php

	}

	public function custom_coupons_nonce_check() {

		if ( ! $this->custom_coupons_enabled() ) {
			return false;
		}

		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : null;

		if ( wp_verify_nonce( $nonce, 'affwp_dashboard_tab_coupons_' . affwp_get_affiliate_id() ) ) {
			return true;
		}

		return false;

	}

	public function custom_coupons_add() {

		if ( ! $this->custom_coupons_nonce_check() ) {
			wp_send_json_error( __( "Cheatin&#8217; huh?", 'affiliate-wp' ) );
		}

		$id     = ! empty( $_POST['id'] ) ? absint( $_POST['id'] )                : null;
		$code   = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : null;
		$desc   = isset( $_POST['desc'] ) ? sanitize_text_field( $_POST['desc'] ) : null;

		if ( empty( $id ) || empty( $code ) ) {
			wp_send_json_error( __( 'Sorry, that is not a valid entry.', 'affiliate-wp' ) );
		}

		$allowed = get_post_meta( $id, 'affwp_allow_affiliate_coupons', true );

		if ( ! $allowed ) {
			wp_send_json_error( __( 'Sorry, that is not a valid coupon template.', 'affiliate-wp' ) );
		}

		$exists = get_page_by_title( $code, 'OBJECT', 'shop_coupon' );

		if ( $exists ) {
			wp_send_json_error( __( 'Sorry, that coupon code already exists.', 'affiliate-wp' ) );
		}

		$inserted = $this->custom_coupons_clone_template( $id, $code, $desc );

		if ( false === $inserted ) {
			wp_send_json_error( __( 'Coupon could not be created.', 'affiliate-wp' ) );
		}

		wp_send_json_success();

	}

	public function custom_coupons_clone_template( $template_id, $code, $desc = null ) {

		if ( ! $this->custom_coupons_enabled() ) {
			return false;
		}

		$template = get_post( absint( $template_id ) );

		if ( ! $template ) {
			return false;
		}

		$desc = ! empty( $desc ) ? $desc : $template->post_excerpt;

		$args = array(
			'post_type'    => 'shop_coupon',
			'post_status'  => 'publish',
			'post_title'   => $code,
			'post_author'  => get_current_user_id(),
			'post_excerpt' => $desc,
		);

		$post_id = wp_insert_post( $args );

		if ( empty( $post_id ) ) {
			return false;
		}

		$meta = get_post_meta( $template_id );

		foreach ( $meta as $key => $value ) {

			if ( '_' === substr( $key, 0, 1 ) || 'affwp_' === substr( $key, 0, 6 ) ) {
				continue;
			}

			$value = ! empty( $value[0] ) ? maybe_unserialize( $value[0] ) : null;

			update_post_meta( $post_id, $key, $value );

		}

		$affiliate_id = affwp_get_affiliate_id();

		update_post_meta( $post_id, 'affwp_discount_affiliate', $affiliate_id );

		do_action( 'affwp_wc_custom_coupon_inserted', $template_id, $post_id, $affiliate_id );

		return true;

	}

	public function custom_coupons_delete() {

		if ( ! $this->custom_coupons_nonce_check() ) {
			wp_send_json_error( __( "Cheatin&#8217; huh?", 'affiliate-wp' ) );
		}

		$coupons = isset( $_POST['coupons'] ) ? $_POST['coupons'] : null;

		if ( empty( $coupons ) || ! is_array( $coupons ) ) {
			wp_send_json_error( __( 'No coupons were marked for deletion!', 'affiliate-wp' ) );
		}

		$coupons = array_map( 'absint', $coupons );

		$i = 0;

		foreach ( $coupons as $coupon_id ) {

			$coupon = get_post( $coupon_id );

			if ( empty( $coupon->post_type ) || 'shop_coupon' !== $coupon->post_type ) {
				continue;
			}

			$affiliate_id = get_post_meta( $coupon_id, 'affwp_discount_affiliate', true );

			if ( $affiliate_id !== affwp_get_affiliate_id() ) {
				continue;
			}

			$post = wp_delete_post( $coupon_id, true );

			if ( $post ) {
				$i++;
			}

		}

		if ( $i > 0 ) {
			wp_send_json_success();
		}

		wp_send_json_error( __( 'An unknown error occured and no coupons could be deleted.', 'affiliate-wp' ) );

	}

}

new Affiliate_WP_WooCommerce;
