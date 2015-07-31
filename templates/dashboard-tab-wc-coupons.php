<?php

$my_coupons = new WP_Query(
	array(
		'post_type'   => 'shop_coupon',
		'post_status' => 'publish',
		'meta_key'    => 'affwp_discount_affiliate',
		'meta_value'  => affwp_get_affiliate_id(),
		'orderby'     => 'title',
		'order'       => 'ASC',
	)
);

$coupon_templates = new WP_Query(
	array(
		'post_type'   => 'shop_coupon',
		'post_status' => 'publish',
		'meta_key'    => 'affwp_allow_affiliate_variations',
		'meta_value'  => 1,
		'orderby'     => 'title',
		'order'       => 'ASC',
	)
);

?>

<div id="affwp-affiliate-dashboard-coupons" class="affwp-tab-content">

	<h4><?php _e( 'My Coupons', 'affiliate-wp' ); ?></h4>

	<form method="post" id="affwp-coupons" class="affwp-form">

		<table class="affwp-table affwp-coupons-table">
			<thead>
				<tr>
					<th><input type="checkbox"<?php disabled( ! $my_coupons->have_posts() ); ?> /></th>
					<th><?php _e( 'Coupon Code', 'affiliate-wp' ); ?></th>
					<th><?php _e( 'Description', 'affiliate-wp' ); ?></th>
					<th><?php _e( 'Amount', 'affiliate-wp' ); ?></th>
					<th><?php _e( 'Uses', 'affiliate-wp' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<tr class="affwp-hidden affwp-no-results" style="display:none;">
					<td colspan="5"><?php _e( 'Sorry, no coupons found.', 'affiliate-wp' ); ?></td>
				</tr>

				<?php if ( $my_coupons->have_posts() ) : ?>

					<?php while ( $my_coupons->have_posts() ) : $my_coupons->the_post(); ?>

						<?php
						$discount_type = get_post_meta( $post->ID, 'discount_type', true );
						$coupon_amount = get_post_meta( $post->ID, 'coupon_amount', true );
						$coupon_amount = ( 'percent' === $discount_type ) ? affwp_format_amount( $coupon_amount ) . '%' : affwp_currency_filter( affwp_format_amount( $coupon_amount ) );
						?>

						<tr data-coupon-id="<?php echo absint( $post->ID ); ?>">
							<td class="affwp-coupon-cb"><input type="checkbox" /></td>
							<td class="affwp-coupon-title"><code><?php echo esc_html( $post->post_title ); ?></code></td>
							<td class="affwp-coupon-description"><?php echo esc_html( $post->post_excerpt ); ?></td>
							<td class="affwp-coupon-amount"><?php echo esc_html( $coupon_amount ); ?></td>
							<td class="affwp-coupon-uses"><?php echo absint( get_post_meta( $post->ID, 'usage_count', true ) ); ?></td>
						</tr>

					<?php endwhile; ?>

				<?php endif; ?>

				<tr class="affwp-hidden affwp-empty-row" style="display:none;">
					<td class="affwp-coupon-cb"><input type="checkbox" /></td>
					<td class="affwp-coupon-title"><code></code></td>
					<td class="affwp-coupon-description"></td>
					<td class="affwp-coupon-amount"></td>
					<td class="affwp-coupon-uses"></td>
				</tr>

			</tbody>

		</table>

		<div class="affwp-coupons-submit-wrap">
			<?php wp_nonce_field( 'affwp_dashboard_tab_coupons_' . affwp_get_affiliate_id(), 'coupon_nonce' ); ?>
			<input type="submit" id="affwp-coupons-delete" class="button" value="<?php esc_attr_e( 'Delete Selected', 'affiliate-wp' ); ?>" />
		</div>

	</form>

	<h4><?php _e( 'Add New', 'affiliate-wp' ); ?></h4>

	<ol>
		<li><?php _e( 'Pick from the list of our available coupon codes below.', 'affiliate-wp' ); ?></li>
		<li><?php _e( "Customize the coupon's code and description for your audience.", 'affiliate-wp' ); ?></li>
		<li><?php _e( 'Receive credit for every referral who uses it!', 'affiliate-wp' ); ?></li>
	</ol>

	<form method="post" id="affwp-add-coupon" class="affwp-form">

		<div class="affwp-wrap affwp-add-coupon-template-wrap">
			<label for="affwp-add-coupon-template"><?php _e( 'Coupon', 'affiliate-wp' ); ?></label>
			<select name="coupon_template" id="affwp-add-coupon-template" required>
				<option value="-1"><?php _e( '- Select One -', 'affiliate-wp' ); ?></option>
				<?php if ( $coupon_templates->have_posts() ) : ?>
					<?php while ( $coupon_templates->have_posts() ) : $coupon_templates->the_post(); ?>
						<option value="<?php echo absint( $post->ID ) ?>" data-coupon-description="<?php echo esc_attr( $post->post_excerpt ); ?>"><?php echo esc_html( $post->post_title ); ?></option>
					<?php endwhile; ?>
				<?php endif; ?>
			</select>
		</div>

		<div class="affwp-wrap affwp-add-coupon-code-wrap">
			<label for="affwp-add-coupon-code"><?php _e( 'Code', 'affiliate-wp' ); ?></label>
			<input type="text" name="coupon_code" id="affwp-add-coupon-code" required disabled />
		</div>

		<div class="affwp-wrap affwp-add-coupon-description-wrap">
			<label for="affwp-add-coupon-description"><?php _e( 'Description', 'affiliate-wp' ); ?></label>
			<textarea name="coupon_description" id="affwp-add-coupon-description" disabled></textarea>
		</div>

		<div class="affwp-add-coupon-submit-wrap">
			<?php wp_nonce_field( 'affwp_dashboard_tab_coupons_' . affwp_get_affiliate_id(), 'coupon_nonce' ); ?>
			<input type="hidden" id="affwp-affiliate-login" value="<?php echo sanitize_key( affwp_get_affiliate_login( affwp_get_affiliate_id() ) ); ?>" />
			<input type="submit" id="affwp-coupons-add" class="button" value="<?php esc_attr_e( 'Add Coupon', 'affiliate-wp' ); ?>" />
		</div>

	</form>

</div>
