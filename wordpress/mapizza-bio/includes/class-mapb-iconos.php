<?php
/**
 * Iconos de marca que trae el plugin, para no obligar a subir a la biblioteca
 * el logotipo de cada plataforma. Si un botón lleva imagen propia, esa manda.
 */

defined( 'ABSPATH' ) || exit;

class MAPB_Iconos {

	/** Los que se ofrecen en el desplegable del botón. */
	public static function catalogo() {
		return array(
			''           => __( '— Sin icono (usa el texto) —', 'mapizza-bio' ),
			'rappi'      => 'Rappi',
			'pedidosya'  => 'PedidosYa',
			'whatsapp'   => 'WhatsApp',
			'instagram'  => 'Instagram',
			'telefono'   => __( 'Teléfono', 'mapizza-bio' ),
			'ubicacion'  => __( 'Ubicación', 'mapizza-bio' ),
			'carta'      => __( 'Carta', 'mapizza-bio' ),
			'enlace'     => __( 'Enlace', 'mapizza-bio' ),
		);
	}

	/** Los dos que son mapa de bits viven en assets/img. */
	private static function bitmaps() {
		return array(
			'rappi'     => array( 'archivo' => 'rappi.png', 'clase' => 'mapb-logo-rappi' ),
			'pedidosya' => array( 'archivo' => 'pedidosya.png', 'clase' => 'mapb-logo-pedidosya' ),
		);
	}

	/**
	 * Devuelve el marcado del icono. `$apagado` pinta en gris los que llevan
	 * color de marca: un botón desactivado no puede seguir gritando en rojo.
	 */
	public static function pintar( $clave, $etiqueta = '', $apagado = false ) {
		$clave   = sanitize_key( $clave );
		$bitmaps = self::bitmaps();

		if ( isset( $bitmaps[ $clave ] ) ) {
			return sprintf(
				'<i class="mapb-bmp %s" role="img" aria-label="%s" style="background-image:url(%s)"></i>',
				esc_attr( $bitmaps[ $clave ]['clase'] ),
				esc_attr( $etiqueta ),
				esc_url( MAPB_URL . 'assets/img/' . $bitmaps[ $clave ]['archivo'] )
			);
		}

		$svg = self::svg( $clave, $apagado );

		if ( ! $svg ) {
			return '';
		}

		return sprintf(
			'<svg class="mapb-logo-icono" viewBox="%s" role="img" aria-label="%s" focusable="false">%s</svg>',
			esc_attr( 'instagram' === $clave ? '0 0 32 32' : '0 0 24 24' ),
			esc_attr( $etiqueta ),
			$svg
		);
	}

	private static function svg( $clave, $apagado ) {
		$gris = '#8d8d8d';

		switch ( $clave ) {
			case 'whatsapp':
				$burbuja = $apagado ? $gris : '#25D366';
				return '<path fill="' . esc_attr( $burbuja ) . '" d="M12.04 2C6.6 2 2.17 6.43 2.17 11.87c0 1.74.46 3.44 1.32 4.94L2.09 22l5.32-1.38a9.85 9.85 0 0 0 4.63 1.18h.01c5.44 0 9.87-4.43 9.87-9.87a9.8 9.8 0 0 0-2.89-6.98A9.8 9.8 0 0 0 12.04 2Z"/>'
					. '<path fill="#fff" d="M17.4 14.36c-.29-.15-1.72-.85-1.99-.94-.27-.1-.46-.15-.66.15-.19.29-.75.94-.92 1.13-.17.2-.34.22-.63.08-.29-.15-1.23-.46-2.35-1.45-.87-.77-1.45-1.73-1.62-2.02-.17-.29-.02-.45.13-.6.13-.13.29-.34.44-.51.15-.17.19-.29.29-.49.1-.19.05-.36-.02-.51-.07-.15-.66-1.58-.9-2.17-.24-.57-.48-.49-.66-.5h-.56c-.19 0-.51.07-.77.36-.27.29-1.01 1-1.01 2.43s1.04 2.82 1.18 3.01c.15.2 2.04 3.12 4.95 4.37.69.3 1.23.48 1.65.61.69.22 1.33.19 1.83.12.56-.09 1.72-.7 1.96-1.38.24-.68.24-1.27.17-1.39-.07-.12-.26-.19-.55-.34Z"/>';

			case 'instagram':
				// El degradado lleva un id único por si hay varios componentes
				// en la misma página: dos <defs> con el mismo id se pisan.
				$id    = 'mapb-ig-' . wp_unique_id();
				$relleno = $apagado
					? '<rect x="1" y="1" width="30" height="30" rx="9" fill="' . esc_attr( $gris ) . '"/>'
					: '<defs><radialGradient id="' . esc_attr( $id ) . '" cx="0.28" cy="1.06" r="1.22">'
						. '<stop offset="0" stop-color="#fdf497"/><stop offset="0.10" stop-color="#fdd85d"/>'
						. '<stop offset="0.34" stop-color="#fa7e1e"/><stop offset="0.53" stop-color="#d62976"/>'
						. '<stop offset="0.76" stop-color="#962fbf"/><stop offset="1" stop-color="#4f5bd5"/>'
						. '</radialGradient></defs>'
						. '<rect x="1" y="1" width="30" height="30" rx="9" fill="url(#' . esc_attr( $id ) . ')"/>';
				return $relleno
					. '<rect x="7.4" y="7.4" width="17.2" height="17.2" rx="5.4" fill="none" stroke="#fff" stroke-width="2.1"/>'
					. '<circle cx="16" cy="16" r="4.5" fill="none" stroke="#fff" stroke-width="2.1"/>'
					. '<circle cx="22.1" cy="9.9" r="1.35" fill="#fff"/>';

			case 'telefono':
				return '<path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M6.5 3h3l1.5 4-2 1.5a12 12 0 0 0 6.5 6.5L17 13l4 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4 5.2 2 2 0 0 1 6 3Z"/>';

			case 'ubicacion':
				return '<path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6" fill="none" stroke="currentColor" stroke-width="1.6"/>';

			case 'carta':
				return '<path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M5 3h11l3 3v15H5z"/><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M8.5 9h7M8.5 12.5h7M8.5 16h4"/>';

			case 'enlace':
				return '<path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" d="M10.5 13.5a4 4 0 0 0 5.7 0l2.3-2.3a4 4 0 0 0-5.7-5.7l-1.3 1.3"/><path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" d="M13.5 10.5a4 4 0 0 0-5.7 0l-2.3 2.3a4 4 0 0 0 5.7 5.7l1.3-1.3"/>';
		}

		return '';
	}
}
