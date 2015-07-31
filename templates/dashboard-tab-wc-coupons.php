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
			<input type="submit" id="affwp-coupons-delete" class="button" value="<?php esc_attr_e( 'Delete Selected', 'affiliate-wp' ); ?>" />
		</div>

	</form>

	<h4><?php _e( 'Add New', 'affiliate-wp' ); ?></h4>

	<p><?php _e( 'Create an exclusive coupon code for your audience, and receive credit for every referral!', 'affiliate-wp' ); ?></p>

	<form method="post" id="affwp-add-coupon" class="affwp-form">

		<div class="affwp-wrap affwp-add-coupon-template-wrap">
			<label for="affwp-add-coupon-template"><?php _e( 'Coupon', 'affiliate-wp' ); ?></label>
			<select name="coupon_template" id="affwp-add-coupon-template" required>
				<option value="-1"><?php _e( '- Select One -', 'affiliate-wp' ); ?></option>
				<?php if ( $predefined_coupons->have_posts() ) : ?>
					<?php while ( $predefined_coupons->have_posts() ) : $predefined_coupons->the_post(); ?>
						<option value="<?php echo absint( $post->ID ) ?>" data-coupon-description="<?php echo esc_attr( $post->post_excerpt ); ?>"><?php echo esc_html( $post->post_title ); ?></option>
					<?php endwhile; ?>
				<?php endif; ?>
			</select>
		</div>

		<div class="affwp-wrap affwp-add-coupon-code-wrap">
			<label for="affwp-add-coupon-code"><?php _e( 'Code', 'affiliate-wp' ); ?></label>
			<input type="text" name="coupon_code" id="affwp-add-coupon-code" />
		</div>

		<div class="affwp-wrap affwp-add-coupon-description-wrap">
			<label for="affwp-add-coupon-description"><?php _e( 'Description', 'affiliate-wp' ); ?></label>
			<textarea name="coupon_description" id="affwp-add-coupon-description"></textarea>
		</div>

		<div class="affwp-add-coupon-submit-wrap">
			<?php wp_nonce_field( 'affwp_dashboard_tab_coupons_' . affwp_get_affiliate_id(), 'coupon_nonce' ); ?>
			<input type="hidden" class="affwp-affiliate-uid" value="<?php echo esc_attr( affwp_get_affiliate_uid() ); ?>" />
			<input type="submit" id="affwp-coupons-add" class="button" value="<?php esc_attr_e( 'Add Coupon', 'affiliate-wp' ); ?>" />
		</div>

	</form>

</div>
