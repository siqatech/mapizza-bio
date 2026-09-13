<?php
/**
 * Pinta el componente a partir de un perfil. Todo lo que sale de la base de
 * datos pasa por esc_* o wp_kses antes de llegar al HTML.
 */

defined( 'ABSPATH' ) || exit;

class MAPB_Render {

	/** Etiquetas que se permiten en los textos de la historia. */
	const TAGS_RICOS = array(
		'em'     => array(),
		'strong' => array(),
		'br'     => array(),
		'span'   => array( 'class' => array() ),
	);

	public static function pintar( $datos ) {
		$uid = 'mapb-' . $datos['id'] . '-' . wp_unique_id();

		$clases = array( 'mapb' );
		if ( ! empty( $datos['ajuste_scroll'] ) ) {
			$clases[] = 'mapb--ajuste';
		}

		ob_start();
		?>
<div class="<?php echo esc_attr( implode( ' ', $clases ) ); ?>"
	 id="<?php echo esc_attr( $uid ); ?>"
	 style="<?php echo esc_attr( self::variables( $datos ) ); ?>">

	<div class="mapb-lienzo" aria-hidden="true">
		<div class="mapb-marco">
			<div class="mapb-fondo"><?php echo self::capa_fondo( $datos ); ?></div>
			<div class="mapb-brasas"><?php echo self::brasas(); ?></div>
		</div>
	</div>

	<div class="mapb-envoltura">

		<section class="mapb-vista mapb-vista--pedidos">
			<?php echo self::cabecera( $datos ); ?>
			<?php echo self::botones( $datos ); ?>
			<?php echo self::horario( $datos ); ?>
			<?php echo self::senuelo( $datos, $uid ); ?>
		</section>

		<section class="mapb-vista mapb-vista--historia" id="<?php echo esc_attr( $uid ); ?>-historia">
			<?php echo self::retrato( $datos ); ?>
			<?php echo self::historia( $datos ); ?>
			<?php echo self::pie( $datos ); ?>
		</section>

	</div>
</div>
		<?php
		return self::en_una_linea( ob_get_clean() );
	}

	/**
	 * Devuelve el marcado sin un solo salto de línea.
	 *
	 * No es cosmética: wpautop convierte los saltos dobles en párrafos, y
	 * cuando el shortcode se pinta ANTES de ese filtro —que es lo que pasa
	 * dentro del widget de texto de Elementor— acaba metiendo <p> y </p>
	 * sueltos dentro del componente. Esos párrafos vacíos heredan el margen
	 * del tema y aparecen como franjas en blanco arriba y abajo.
	 *
	 * Sin saltos de línea, wpautop no tiene dónde cortar.
	 */
	private static function en_una_linea( $html ) {
		return trim( preg_replace( '/\s*\R\s*/u', ' ', $html ) );
	}

	/**
	 * Las imágenes entran como variables CSS en el propio elemento, no en la
	 * hoja de estilos: así la hoja se cachea igual para todos los perfiles y
	 * cada uno pone las suyas.
	 */
	private static function variables( $datos ) {
		$vars = array();

		$firma = self::url_medio( $datos['logo'], 'medium' );
		if ( $firma ) {
			$vars[] = '--ma-firma:url(' . $firma . ')';
		}

		$retrato = self::url_medio( $datos['retrato'], 'large' );
		if ( $retrato ) {
			$vars[] = '--foto-familia:url(' . $retrato . ')';
		}

		$fondo = self::url_medio( $datos['fondo_imagen'], 'large' );
		if ( $fondo ) {
			$vars[] = '--foto-horno:url(' . $fondo . ')';
		}

		return implode( ';', $vars );
	}

	/**
	 * La URL tiene que salir absoluta, y wp_get_attachment_image_src la da
	 * así. No es un detalle: estas URLs viajan dentro de variables CSS, y una
	 * ruta relativa en una variable NO se resuelve contra el documento sino
	 * contra la hoja de estilos que la consume. Una ruta relativa acabaría
	 * buscando la imagen dentro de assets/css/ y desapareciendo sin error.
	 */
	private static function url_medio( $id, $tamano = 'full' ) {
		$src = self::medio( $id, $tamano );

		return $src ? $src[0] : '';
	}

	/** Devuelve array( url, ancho, alto ) o cadena vacía. */
	private static function medio( $id, $tamano = 'full' ) {
		$id = (int) $id;

		if ( ! $id ) {
			return '';
		}

		$src = wp_get_attachment_image_src( $id, $tamano );

		if ( ! $src ) {
			return '';
		}

		$src[0] = esc_url_raw( $src[0] );

		return $src;
	}

	/** Fondo: vídeo si lo hay, y si no la imagen, que ya va en la variable. */
	private static function capa_fondo( $datos ) {
		$encuadre = $datos['fondo_encuadre'] ? $datos['fondo_encuadre'] : '50% 64%';
		$estilo   = 'object-position:' . $encuadre . ';background-position:' . $encuadre;
		$video    = self::url_medio( $datos['fondo_video'] );

		if ( $video ) {
			return sprintf(
				'<video class="mapb-horno mapb-horno--video" data-src="%s" muted loop playsinline preload="none" disablepictureinpicture aria-hidden="true" style="%s"></video>',
				esc_url( $video ),
				esc_attr( $estilo )
			);
		}

		return '<div class="mapb-horno" style="' . esc_attr( $estilo ) . '"></div>';
	}

	private static function brasas() {
		$chispas = array(
			array( 8, 3, 19, 0, 22 ),   array( 19, 5, 24, 3.5, -18 ),
			array( 31, 2, 16, 7, 30 ),  array( 44, 4, 21, 1.5, -26 ),
			array( 57, 3, 27, 9, 16 ),  array( 68, 5, 18, 5, -32 ),
			array( 79, 2, 23, 12, 24 ), array( 90, 4, 20, 2.5, -14 ),
			array( 14, 2, 29, 14, -20 ), array( 63, 3, 25, 17, 28 ),
		);

		$html = '';
		foreach ( $chispas as $c ) {
			$html .= sprintf(
				'<span class="mapb-brasa" style="left:%d%%;--t:%dpx;--dur:%ds;--esp:%ss;--dx:%dpx"></span>',
				$c[0], $c[1], $c[2], $c[3], $c[4]
			);
		}

		return $html;
	}

	private static function cabecera( $datos ) {
		$html = '<header class="mapb-cabecera mapb-entra" style="--d:60ms">';

		if ( self::url_medio( $datos['logo'] ) ) {
			$html .= '<i class="mapb-bmp mapb-firma-alta" role="img" aria-label="'
				. esc_attr( get_the_title( $datos['id'] ) ) . '"></i>';
		}

		$html .= self::lema( $datos );

		return $html . '</header>';
	}

	/**
	 * El lema puede ir partido en dos con un sello en medio, como en la
	 * referencia de la marca. Sin sello se comporta como una línea normal,
	 * así que los perfiles que ya existían siguen viéndose igual.
	 */
	private static function lema( $datos ) {
		$izquierda = $datos['lema'];
		$derecha   = isset( $datos['lema_2'] ) ? $datos['lema_2'] : '';
		$sello     = self::medio( isset( $datos['lema_icono'] ) ? $datos['lema_icono'] : 0, 'medium' );

		if ( ! $izquierda && ! $derecha && ! $sello ) {
			return '';
		}

		$html = '<p class="mapb-lema">';

		if ( $izquierda ) {
			$html .= '<span>' . esc_html( $izquierda ) . '</span>';
		}

		if ( $sello ) {
			// La proporción se saca del propio archivo: así el sello reserva su
			// sitio antes de que cargue la imagen y la línea no pega un salto.
			$html .= sprintf(
				'<i class="mapb-lema-sello" aria-hidden="true" style="background-image:url(%s);aspect-ratio:%d/%d"></i>',
				esc_url( $sello[0] ),
				max( 1, (int) $sello[1] ),
				max( 1, (int) $sello[2] )
			);
		}

		if ( $derecha ) {
			$html .= '<span>' . esc_html( $derecha ) . '</span>';
		}

		return $html . '</p>';
	}

	private static function botones( $datos ) {
		if ( empty( $datos['botones'] ) ) {
			return '';
		}

		$proximamente = $datos['proximamente'] ? $datos['proximamente'] : __( 'Próximamente', 'mapizza-bio' );
		$retardo      = 200;
		$fase         = 0;
		$html         = '<ul class="mapb-enlaces">';

		foreach ( $datos['botones'] as $boton ) {
			$titulo = isset( $boton['titulo'] ) ? $boton['titulo'] : '';
			$activo = ! empty( $boton['activo'] ) && ! empty( $boton['url'] );
			$dentro = self::cara_boton( $boton, $titulo, $activo );

			$html .= '<li class="mapb-entra" style="--d:' . (int) $retardo . 'ms">';

			if ( $activo ) {
				// Botón vivo: un enlace de verdad, con su destino.
				$html .= sprintf(
					'<a class="mapb-tarjeta" style="--fase:%ss" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">'
						. '<span class="mapb-destello"></span><span class="mapb-cara">%s</span></a>',
					esc_attr( $fase ),
					esc_url( $boton['url'] ),
					esc_attr( $titulo ),
					$dentro
				);
			} else {
				// Apagado: un <span>, no un <a> con el clic anulado por JS. Sin
				// href no hay nada que pulsar, ni con el dedo, ni con el
				// teclado, ni con un lector de pantalla.
				$html .= sprintf(
					'<span class="mapb-tarjeta mapb-tarjeta--apagada" aria-disabled="true" aria-label="%s">'
						. '<span class="mapb-cara">%s</span>'
						. '<span class="mapb-etiqueta">%s</span></span>',
					esc_attr( trim( $titulo . ' — ' . $proximamente ) ),
					$dentro,
					esc_html( $proximamente )
				);
			}

			$html   .= '</li>';
			$retardo += 60;
			$fase    -= 2.2;
		}

		return $html . '</ul>';
	}

	private static function cara_boton( $boton, $titulo, $activo ) {
		$imagen = self::url_medio( isset( $boton['imagen'] ) ? $boton['imagen'] : 0, 'medium' );

		// La imagen propia manda sobre el icono del catálogo.
		if ( $imagen ) {
			return sprintf(
				'<i class="mapb-bmp mapb-logo-imagen" role="img" aria-label="%s" style="background-image:url(%s)"></i>',
				esc_attr( $titulo ),
				esc_url( $imagen )
			);
		}

		$icono = MAPB_Iconos::pintar(
			isset( $boton['icono'] ) ? $boton['icono'] : '',
			$titulo,
			! $activo
		);

		if ( $icono ) {
			return $icono;
		}

		return '<span class="mapb-rotulo-boton">' . esc_html( $titulo ) . '</span>';
	}

	private static function horario( $datos ) {
		if ( ! $datos['horario_rango'] && ! $datos['horario_dias'] && ! $datos['horario_datos'] ) {
			return '';
		}

		$html = '<div class="mapb-horario mapb-entra" style="--d:460ms">';

		if ( $datos['horario_rango'] ) {
			$html .= '<p class="mapb-rango">' . esc_html( $datos['horario_rango'] ) . '</p>';
		}
		if ( $datos['horario_dias'] ) {
			$html .= '<p class="mapb-dias">' . esc_html( $datos['horario_dias'] ) . '</p>';
		}
		if ( $datos['horario_datos'] ) {
			$html .= '<p class="mapb-datos">' . esc_html( $datos['horario_datos'] ) . '</p>';
		}

		return $html . '</div>';
	}

	private static function senuelo( $datos, $uid ) {
		if ( ! $datos['senuelo'] ) {
			return '';
		}

		return sprintf(
			'<a class="mapb-senuelo mapb-entra" href="#%s-historia" style="--d:540ms"><span>%s</span>'
				. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg></a>',
			esc_attr( $uid ),
			esc_html( $datos['senuelo'] )
		);
	}

	private static function retrato( $datos ) {
		if ( ! self::url_medio( $datos['retrato'] ) ) {
			return '';
		}

		return '<div class="mapb-marco-retrato mapb-asoma"><i class="mapb-retrato" role="img" aria-label="'
			. esc_attr( $datos['historia_firma'] ) . '"></i></div>';
	}

	private static function historia( $datos ) {
		$frase  = wp_kses( $datos['historia_frase'], self::TAGS_RICOS );
		$coro   = wp_kses( $datos['historia_coro'], self::TAGS_RICOS );
		$apunte = wp_kses( $datos['historia_apunte'], self::TAGS_RICOS );

		if ( ! $frase && ! $coro && ! $apunte ) {
			return '';
		}

		$html = '<div class="mapb-historia mapb-asoma" style="--d:120ms">';

		if ( $frase ) {
			$html .= '<p class="mapb-voz">' . $frase . '</p>';
		}
		if ( $frase && ( $coro || $apunte ) ) {
			$html .= '<hr class="mapb-filete" />';
		}
		if ( $coro ) {
			$html .= '<p class="mapb-voz mapb-voz--coro">' . $coro . '</p>';
		}
		if ( $apunte ) {
			$html .= '<p class="mapb-apunte">' . $apunte . '</p>';
		}
		if ( $datos['historia_firma'] ) {
			$html .= '<p class="mapb-firma">' . esc_html( $datos['historia_firma'] ) . '</p>';
		}

		return $html . '</div>';
	}

	private static function pie( $datos ) {
		if ( ! $datos['cierre'] && ! $datos['reclamos_url'] ) {
			return '';
		}

		$html = '<footer class="mapb-pie mapb-asoma" style="--d:240ms">';

		if ( $datos['cierre'] ) {
			$html .= '<p class="mapb-cierre">' . esc_html( $datos['cierre'] ) . '</p>';
		}

		if ( $datos['reclamos_url'] ) {
			$texto = $datos['reclamos_texto'] ? $datos['reclamos_texto'] : __( 'Libro de reclamaciones', 'mapizza-bio' );
			$html .= sprintf(
				'<a class="mapb-reclamos" href="%s" target="_blank" rel="noopener noreferrer">'
					. '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">'
					. '<path d="M4 5.5C6.2 4.7 8.5 4.8 11 6v13c-2.5-1.2-4.8-1.3-7-.5v-13Z" stroke="currentColor" stroke-width="1.5"/>'
					. '<path d="M20 5.5c-2.2-.8-4.5-.7-7 .5v13c2.5-1.2 4.8-1.3 7-.5v-13Z" stroke="currentColor" stroke-width="1.5"/>'
					. '</svg>%s</a>',
				esc_url( $datos['reclamos_url'] ),
				esc_html( $texto )
			);
		}

		return $html . '</footer>';
	}
}
