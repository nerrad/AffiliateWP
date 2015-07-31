/* globals affwp_coupons_i18n */

jQuery( function( $ ) {

	var $table     = $( '#affwp-coupons table' ),
	    $thead     = $table.find( 'thead' ),
	    $tbody     = $table.find( 'tbody' ),
	    $deleteBtn = $( '#affwp-coupons-delete' ),
	    $addBtn    = $( '#affwp-coupons-add' ),
	    $template  = $( '#affwp-add-coupon-template' ),
	    $code      = $( '#affwp-add-coupon-code' ),
	    $desc      = $( '#affwp-add-coupon-description' );


	function calc_rows() {
		var found       = $tbody.find( 'tr:not( .hidden )' ).length,
		    selected    = $tbody.find( 'tr:not( .hidden ) input:checkbox:checked' ).length,
		    allSelected = ( found > 0 && found === selected );

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

		if ( ! window.confirm( affwp_coupons_i18n.delete_coupons ) ) {
			return false;
		}

		var $selectedRows = $tbody.find( 'tr:not( .hidden ) input:checkbox:checked' ).closest( 'tr' );

		$selectedRows.remove();

		$table.find( 'input:checkbox' ).prop( 'checked', false );

		calc_rows();
	});

	// Change template selection
	$template.on( 'change', function() {
		var id    = $( this ).val(),
		    code  = $( this ).find( ':selected' ).text(),
		    desc  = $( this ).find( ':selected' ).data( 'coupon-description' );

		$code.val( id > 0 ? code : '' );
		$desc.text( id > 0 ? desc : '' );

		$code.prop( 'disabled', id > 0 ? false : true );
		$desc.prop( 'disabled', id > 0 ? false : true );
	});

	// Add new
	$addBtn.on( 'click', function( e ) {
		if ( $template.val() < 0 ) {
			window.alert( affwp_coupons_i18n.template_required );

			return false;
		}

		if ( $code.val().length ) {
			e.preventDefault();
		}

		calc_rows();
	});

});
