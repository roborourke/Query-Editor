( function( $ ) {

	$( document ).ready( function() {

		// on change use_condition
		$( '.queries' ).on( 'click', 'input[name^="qe_use_condition"]', function() {
			if ( $( this ).is( ':checked' ) && parseInt( $( this ).val(), 10 ) == 1 )
				$( this ).parents( '.query' ).find( '.conditions' ).slideDown( 'slow' );
			else
				$( this ).parents( '.query' ).find( '.conditions' ).slideUp( 'slow' );
		} );

		// show/hide
		$( '.queries' ).on( 'change', 'select.condition', function() {
			$( this ).siblings().not( '.operator,.remove' ).hide().parent().find( '.' + $( this ).val() ).show();
		} );

		// enhance usability of term excludes
		$( '.queries .exclude-terms .checklist' ).each( function() {
			var list = $( this );

			$( '<a class="term-list-toggle" href="#"><small>(Show/hide term list)</small></a>' )
				.appendTo( list.hide().prev() )
				.click( function() {
					list.toggle();
					return false;
				} );

			// de/select all button
			list.prepend( '<label class="toggle-all"><input type="checkbox" value="1" /> Check / Uncheck all</label>' );

		} );

		// de/select all button functionality
		$( '.queries' ).on( 'click', '.toggle-all input', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( this ).parent().next().find( 'input[type="checkbox"]' ).attr( 'checked', 'checked' );
			} else {
				$( this ).parent().next().find( 'input[type="checkbox"]' ).removeAttr( 'checked' );
			}
		} );

		// radio text colour change
		$( '.queries' ).on( 'click', 'input[type="radio"]', function() {
			$( '.queries input[name="' + $( this ).attr( 'name' ) + '"]' ).parents( 'label' ).removeClass( 'checked' );
			$( this ).parents( 'label' ).addClass( 'checked' );
		} );
		$( '.queries input[type="radio"][checked]' ).parents( 'label' ).addClass( 'checked' );

	} );

} )( jQuery );
