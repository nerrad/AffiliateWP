<?php

$coupons = new WP_Query(
	array(
		'post_type'   => 'shop_coupon',
		'post_status' => 'publish',
		'meta_key'    => 'affwp_discount_affiliate',
		'meta_value'  => affwp_get_affiliate_id(),
	)
);

$predefined_coupons = new WP_Query(
	array(
		'post_type'   => 'shop_coupon',
		'post_status' => 'publish',
		'meta_key'    => 'affwp_allow_affiliate_coupons',
		'meta_value'  => 1,
	)
);

?>

<div id="affwp-affiliate-dashboard-coupons" class="affwp-tab-content">

	<h4><?php _e( 'My Coupons', 'affiliate-wp' ); ?></h4>

	<form method="post" id="affwp-coupons" class="affwp-form">

		<table class="affwp-table affwp-coupons-table">
			<thead>
				<tr>
					<th><input type="checkbox"<?php disabled( ! $coupons->have_posts() ); ?> /></th>
					<th>Coupon Code</th>
					<th>Description</th>
					<th>Amount</th>
					<th>Uses</th>
				</tr>
			</thead>

			<tbody>

			<?php if ( $coupons->have_posts() ) : ?>

				<?php while ( $coupons->have_posts() ) : $coupons->the_post(); ?>

					<?php
					$discount_type = get_post_meta( $post->ID, 'discount_type', true );
					$coupon_amount = get_post_meta( $post->ID, 'coupon_amount', true );
					$coupon_amount = ( 'percent' === $discount_type ) ? affwp_format_amount( $coupon_amount ) . '%' : affwp_currency_filter( affwp_format_amount( $coupon_amount ) );
					?>

					<tr>
						<td><input type="checkbox" /></td>
						<td><code><?php echo esc_html( $post->post_title ); ?></code></td>
						<td><?php echo nl2br( esc_html( $post->post_excerpt ) ); ?></td>
						<td><?php echo esc_html( $coupon_amount ); ?></td>
						<td><?php echo absint( get_post_meta( $post->ID, 'usage_count', true ) ); ?></td>
					</tr>

				<?php endwhile; ?>

			<?php else : ?>

				<tr>
					<td colspan="5"><?php _e( 'Sorry, no coupons found.', 'affiliate-wp' ); ?></td>
				</tr>

			<?php endif; ?>

			</tbody>

		</table>

		<div class="affwp-coupons-submit-wrap">
			<input type="submit" class="button" value="<?php esc_attr_e( 'Delete Selected', 'affiliate-wp' ); ?>" />
		</div>

	</form>

	<h4><?php _e( 'Add New', 'affiliate-wp' ); ?></h4>

	<p><?php _e( 'Create an exclusive coupon code for your audience, and receive credit for every referral!', 'affiliate-wp' ); ?></p>

	<form method="post" id="affwp-add-coupon" class="affwp-form">

		<select name="coupon_template" id="affwp-add-coupon-template">
			<?php if ( $predefined_coupons->have_posts() ) : ?>
				<?php while ( $predefined_coupons->have_posts() ) : $predefined_coupons->the_post(); ?>
					<option value="<?php echo absint( $post->ID ) ?>"><?php echo esc_html( $post->post_title ); ?></option>
				<?php endwhile; ?>
			<?php endif; ?>
			<option value="-1"><?php _e( 'Custom', 'affiliate-wp' ); ?></option>
		</select>

		<hr>

		<div class="affwp-wrap affwp-add-coupon-code-wrap">
			<label for="affwp-add-coupon-code"><?php _e( 'Custom Coupon Code', 'affiliate-wp' ); ?></label>
			<input type="text" name="coupon_code" id="affwp-add-coupon-code" placeholder="e.g. takeoff10" />
		</div>

		<div class="affwp-wrap affwp-add-coupon-description-wrap">
			<label for="affwp-add-coupon-description"><?php _e( 'Custom Description', 'affiliate-wp' ); ?></label>
			<textarea name="coupon_description" id="affwp-add-coupon-description" placeholder="e.g. Take 10% off any order!"></textarea>
		</div>

		<div class="affwp-wrap affwp-add-coupon-type-wrap">
			<label for="affwp-add-coupon-type"><?php _e( 'Type', 'affiliate-wp' ); ?></label>
			<select name="coupon_type" id="affwp-add-coupon-type">
				<option value="fixed_cart"><?php _e( 'Cart Discount', 'affiliate-wp' ); ?></option>
				<option value="percent"><?php _e( 'Cart % Discount', 'affiliate-wp' ); ?></option>
			</select>
		</div>

		<div class="affwp-wrap affwp-add-coupon-amount-wrap">
			<label for="affwp-add-coupon-amount"><?php _e( 'Amount', 'affiliate-wp' ); ?></label>
			<input type="number" name="coupon_amount" id="affwp-add-coupon-amount" step="0.01" min="0.01" max="100" /> %
		</div>

		<div class="affwp-add-coupon-submit-wrap">
			<?php wp_nonce_field( 'affwp_dashboard_tab_coupons_' . affwp_get_affiliate_id(), 'coupon_nonce' ); ?>
			<input type="hidden" class="affwp-affiliate-uid" value="<?php echo esc_attr( affwp_get_affiliate_uid() ); ?>" />
			<input type="submit" class="button" value="<?php esc_attr_e( 'Add Coupon', 'affiliate-wp' ); ?>" />
		</div>

	</form>

</div>
