/**
 * Pantalla de edición del perfil: biblioteca de medios y orden de los botones.
 * Solo se carga en la pantalla del tipo de contenido del plugin.
 */
( function ( $ ) {
	'use strict';

	var textos = window.mapbTextos || {};

	/* ---------------------------------------------------- biblioteca de medios */

	$( document ).on( 'click', '.mapb-medio-elegir', function ( e ) {
		e.preventDefault();

		var caja  = $( this ).closest( '.mapb-medio' );
		var video = 'video' === caja.data( 'tipo' );

		var marco = wp.media( {
			title: video ? textos.elegirV : textos.elegir,
			library: { type: video ? 'video' : 'image' },
			button: { text: video ? textos.usarV : textos.usar },
			multiple: false
		} );

		marco.on( 'select', function () {
			var medio = marco.state().get( 'selection' ).first().toJSON();
			caja.find( '.mapb-medio-id' ).val( medio.id );

			var vista = caja.find( '.mapb-medio-vista' );
			if ( video ) {
				vista.html( $( '<span class="mapb-medio-archivo"></span>' ).text( medio.filename ) );
			} else {
				var url = ( medio.sizes && medio.sizes.medium ) ? medio.sizes.medium.url : medio.url;
				vista.html( $( '<img alt="" />' ).attr( 'src', url ) );
			}

			caja.find( '.mapb-medio-quitar' ).prop( 'hidden', false );
		} );

		marco.open();
	} );

	$( document ).on( 'click', '.mapb-medio-quitar', function ( e ) {
		e.preventDefault();
		var caja = $( this ).closest( '.mapb-medio' );
		caja.find( '.mapb-medio-id' ).val( 0 );
		caja.find( '.mapb-medio-vista' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	/* ------------------------------------------------------------- los botones */

	var lista = $( '#mapb-lista' );

	/* El orden lo lleva el índice del campo, no la posición en la página: al
	   arrastrar hay que renumerar o PHP recompone el array por clave y se
	   pierde el orden nuevo. */
	function renumerar() {
		lista.find( '.mapb-fila' ).each( function ( i ) {
			$( this ).find( '[name^="mapb_botones["]' ).each( function () {
				this.name = this.name.replace( /mapb_botones\[[^\]]*\]/, 'mapb_botones[' + i + ']' );
			} );
		} );
	}

	if ( lista.length && $.fn.sortable ) {
		lista.sortable( {
			handle: '.mapb-asa',
			axis: 'y',
			placeholder: 'mapb-hueco',
			forcePlaceholderSize: true,
			update: renumerar
		} );
	}

	$( '#mapb-anadir' ).on( 'click', function ( e ) {
		e.preventDefault();
		var plantilla = $( '#mapb-plantilla-fila' ).html();
		lista.append( plantilla.replace( /__i__/g, lista.find( '.mapb-fila' ).length ) );
		renumerar();
	} );

	$( document ).on( 'click', '.mapb-quitar', function ( e ) {
		e.preventDefault();
		if ( window.confirm( textos.borrar ) ) {
			$( this ).closest( '.mapb-fila' ).remove();
			renumerar();
		}
	} );

	/* Que se vea apagada en cuanto se desmarca, sin tener que guardar. */
	$( document ).on( 'change', '.mapb-fila .mapb-interruptor input', function () {
		$( this ).closest( '.mapb-fila' ).toggleClass( 'mapb-fila--apagada', ! this.checked );
	} );

	/* ------------------------------------------------ copiar el shortcode */

	$( document ).on( 'click keydown', '.mapb-copiar', function ( e ) {
		if ( 'keydown' === e.type && 'Enter' !== e.key && ' ' !== e.key ) {
			return;
		}
		e.preventDefault();

		var nodo = this;
		var texto = nodo.textContent;

		var avisar = function () {
			nodo.classList.add( 'mapb-copiado' );
			nodo.setAttribute( 'data-aviso', textos.copiado );
			window.setTimeout( function () {
				nodo.classList.remove( 'mapb-copiado' );
			}, 1400 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( texto ).then( avisar, function () {} );
			return;
		}

		/* Sin API de portapapeles —o sin https— al menos lo deja seleccionado
		   para copiarlo a mano. */
		var rango = document.createRange();
		rango.selectNodeContents( nodo );
		window.getSelection().removeAllRanges();
		window.getSelection().addRange( rango );
	} );
} )( jQuery );
