/* globals affwp_coupons_vars */

jQuery( function( $ ) {

	var $table     = $( '#affwp-coupons table' ),
	    $thead     = $table.find( 'thead' ),
	    $tbody     = $table.find( 'tbody' ),
	    $deleteBtn = $( '#affwp-coupons-delete' ),
	    $addBtn    = $( '#affwp-coupons-add' ),
	    $template  = $( '#affwp-add-coupon-template' ),
	    $code      = $( '#affwp-add-coupon-code' ),
	    $desc      = $( '#affwp-add-coupon-description' ),
	    login      = $( '#affwp-affiliate-login' ).val(),
	    nonce      = $( '#coupon_nonce' ).val();

	function calc_rows() {
		var $noRows     = $tbody.find( 'tr.affwp-no-results' ),
		    found       = $tbody.find( 'tr:not( .affwp-hidden )' ).length,
		    selected    = $tbody.find( 'tr:not( .affwp-hidden ) input:checkbox:checked' ).length,
		    allSelected = ( found > 0 && found === selected );

		( found > 0 ) ? $noRows.hide() : $noRows.show();

		$deleteBtn.prop( 'disabled', selected ? false : true );

		$thead.find( 'input:checkbox' ).prop( 'checked', allSelected ? true : false );
	}

	// On load
	$( document ).ready( function() {
		calc_rows();
	});

	// Select all
	$thead.on( 'click', 'input:checkbox', function() {
		var select = $( this ).is( ':checked' );

		$tbody.find( 'tr input:checkbox' ).prop( 'checked', select );

		calc_rows();
	});

	// Select one
	$tbody.on( 'click', 'input:checkbox', function() {
		calc_rows();
	});

	// Delete
	$deleteBtn.on( 'click', function( e ) {
		e.preventDefault();

		if ( ! window.confirm( affwp_coupons_vars.i18n.delete_coupons ) ) {
			return false;
		}

		var $selectedRows = $tbody.find( 'tr:not( .hidden ) input:checkbox:checked' ).closest( 'tr' );

		ajax_delete( $selectedRows );
	});

	function ajax_delete( $selectedRows ) {
		var ids = [];

		$.each( $selectedRows, function() {
			ids.push( $( this ).data( 'coupon-id' ) );
		});

		var data = {
			'action': 'affwp_custom_coupons_delete',
			'nonce': nonce,
			'coupons': ids
		};

		$.ajax({
			type: 'POST',
			url: affwp_coupons_vars.ajaxurl,
			data: data,
			dataType: 'json',
			success: function( response ) {
				if ( response.success ) {
					$selectedRows.remove();
					$table.find( 'input:checkbox' ).prop( 'checked', false );

					calc_rows();

					return false;
				}

				window.alert( response.data );
			}
		});
	}

	// Change template selection
	$template.on( 'change', function() {
		var id    = $( this ).val(),
		    code  = $( this ).find( ':selected' ).text() + '_' + login,
		    desc  = $( this ).find( ':selected' ).data( 'coupon-description' );

		$code.val( id > 0 ? code : '' );
		$desc.text( id > 0 ? desc : '' );

		$code.prop( 'disabled', id > 0 ? false : true );
		$desc.prop( 'disabled', id > 0 ? false : true );
	});

	// Add new coupon
	$addBtn.on( 'click', function( e ) {
		if ( $template.val() < 0 ) {
			window.alert( affwp_coupons_vars.i18n.template_required );

			return false;
		}

		if ( $code.val().length ) {
			e.preventDefault();
		}

		ajax_add();
	});

	function ajax_add() {
		var data = {
			'action': 'affwp_custom_coupons_add',
			'nonce': nonce,
			'id': $template.val(),
			'code': $code.val(),
			'description': $desc.val(),
		};

		$.ajax({
			type: 'POST',
			url: affwp_coupons_vars.ajaxurl,
			data: data,
			dataType: 'json',
			success: function( response ) {
				if ( response.success ) {
					$template.val( '-1' );
					$code.val( '' );
					$desc.text( '' );

					calc_rows();

					return false;
				}

				window.alert( response.data );
			}
		});
	}

});
