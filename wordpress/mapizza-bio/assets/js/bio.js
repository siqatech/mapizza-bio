/**
 * MA PIZZA — Enlaces de bio.
 *
 * Todo cuelga de cada elemento .mapb: puede haber varios componentes en la
 * misma página y ninguno escucha nada fuera del suyo, salvo el scroll de la
 * ventana, que no es de nadie.
 */
( function () {
	'use strict';

	var quieto = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function arrancar( raiz ) {
		if ( raiz.dataset.mapbListo ) {
			return;
		}
		raiz.dataset.mapbListo = '1';

		/* Marca que hay JavaScript. Los estilos que ocultan la segunda vista
		   cuelgan de esta clase, así que sin guion el contenido se ve en vez
		   de quedarse invisible para siempre. */
		raiz.classList.add( 'mapb-js' );

		destelloAlTocar( raiz );
		asomarAlEntrar( raiz );
		senuelo( raiz );
		fondoDeVideo( raiz );
	}

	/* El destello tiene que nacer donde cae el dedo, y eso el CSS solo no lo
	   sabe: hay que pasarle las coordenadas del toque. */
	function destelloAlTocar( raiz ) {
		var tarjetas = raiz.querySelectorAll( 'a.mapb-tarjeta' );

		Array.prototype.forEach.call( tarjetas, function ( tarjeta ) {
			tarjeta.addEventListener( 'pointerdown', function ( e ) {
				if ( quieto ) {
					return;
				}

				var brillo = tarjeta.querySelector( '.mapb-destello' );
				if ( ! brillo ) {
					return;
				}

				var caja = tarjeta.getBoundingClientRect();
				brillo.style.setProperty( '--tx', ( e.clientX - caja.left ) + 'px' );
				brillo.style.setProperty( '--ty', ( e.clientY - caja.top ) + 'px' );

				/* Reiniciar la animación: sin el reflow, el segundo toque
				   seguido sobre la misma tarjeta no dispararía nada. */
				tarjeta.classList.remove( 'mapb-pulsa' );
				void tarjeta.offsetWidth;
				tarjeta.classList.add( 'mapb-pulsa' );

				/* Golpecito háptico en Android; iOS lo ignora sin quejarse. */
				if ( navigator.vibrate ) {
					navigator.vibrate( 10 );
				}
			}, { passive: true } );

			tarjeta.addEventListener( 'animationend', function ( e ) {
				if ( 'mapb-onda' === e.animationName ) {
					tarjeta.classList.remove( 'mapb-pulsa' );
				}
			} );
		} );
	}

	/* La segunda vista entra al asomar. Sin IntersectionObserver se muestra
	   sin más: nunca se queda escondida. */
	function asomarAlEntrar( raiz ) {
		var piezas = raiz.querySelectorAll( '.mapb-asoma' );
		var i;

		if ( ! ( 'IntersectionObserver' in window ) ) {
			for ( i = 0; i < piezas.length; i++ ) {
				piezas[ i ].classList.add( 'mapb-dentro' );
			}
			return;
		}

		/* Sin margen negativo abajo: con él, el navegador encoge la zona de
		   detección y un bloque pegado al final del documento —el pie— nunca
		   llega a entrar en ella. */
		var vigia = new IntersectionObserver( function ( entradas ) {
			for ( var k = 0; k < entradas.length; k++ ) {
				if ( entradas[ k ].isIntersecting ) {
					entradas[ k ].target.classList.add( 'mapb-dentro' );
					vigia.unobserve( entradas[ k ].target );
				}
			}
		}, { threshold: 0 } );

		for ( i = 0; i < piezas.length; i++ ) {
			vigia.observe( piezas[ i ] );
		}
	}

	function senuelo( raiz ) {
		var senal = raiz.querySelector( '.mapb-senuelo' );
		if ( ! senal ) {
			return;
		}

		/* El desplazamiento suave se hace aquí y no con scroll-behavior en
		   CSS: esa propiedad va en el documento y le cambiaría el
		   comportamiento a todos los enlaces del sitio. */
		senal.addEventListener( 'click', function ( e ) {
			var destino = raiz.querySelector( senal.getAttribute( 'href' ) );
			if ( ! destino ) {
				return;
			}
			e.preventDefault();
			destino.scrollIntoView( {
				behavior: quieto ? 'auto' : 'smooth',
				block: 'start'
			} );
		} );

		/* Una vez que has bajado, ya no hace falta que insista. */
		window.addEventListener( 'scroll', function alBajar() {
			if ( raiz.getBoundingClientRect().top < -40 ) {
				senal.classList.add( 'mapb-ido' );
				window.removeEventListener( 'scroll', alBajar );
			}
		}, { passive: true } );
	}

	/* El vídeo se engancha después y solo si toca. Hasta entonces —y para
	   siempre, si el navegador dice que no— se queda el póster, que es la
	   misma imagen de fondo de la versión sin vídeo. */
	function fondoDeVideo( raiz ) {
		var horno = raiz.querySelector( 'video.mapb-horno' );
		if ( ! horno || ! horno.getAttribute( 'data-src' ) ) {
			return;
		}

		var red = navigator.connection || {};
		var lenta = true === red.saveData || /(^|-)2g$/.test( red.effectiveType || '' );

		/* Nadie abre una página de bio para gastarse los datos en un fondo. */
		if ( quieto || lenta ) {
			return;
		}

		horno.src = horno.getAttribute( 'data-src' );

		var intento = horno.play();
		if ( intento && intento.catch ) {
			intento.catch( function () {} );
		}
	}

	function todos() {
		Array.prototype.forEach.call( document.querySelectorAll( '.mapb' ), arrancar );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', todos );
	} else {
		todos();
	}
} )();
