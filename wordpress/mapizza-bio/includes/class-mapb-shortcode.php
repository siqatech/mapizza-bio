<?php
/**
 * El shortcode [mapizza_bio].
 *
 *   [mapizza_bio]              el primer perfil publicado
 *   [mapizza_bio id="12"]      un perfil concreto por id
 *   [mapizza_bio id="bio-2"]   o por slug
 *
 * Los estilos y el guion se encolan SOLO donde el shortcode se usa. En el
 * resto del sitio el plugin no añade ni un byte.
 */

defined( 'ABSPATH' ) || exit;

class MAPB_Shortcode {

	const ETIQUETA = 'mapizza_bio';

	public static function init() {
		add_shortcode( self::ETIQUETA, array( __CLASS__, 'pintar' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'registrar' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'encolar_si_toca' ), 20 );
	}

	public static function registrar() {
		wp_register_style(
			'mapizza-bio',
			MAPB_URL . 'assets/css/bio.css',
			array(),
			MAPB_VERSION
		);

		wp_register_script(
			'mapizza-bio',
			MAPB_URL . 'assets/js/bio.js',
			array(),
			MAPB_VERSION,
			true
		);
	}

	/**
	 * Encola por adelantado si el contenido de la entrada trae el shortcode.
	 * Así el CSS entra en la cabecera y no hay un parpadeo sin estilos.
	 *
	 * No es la única vía: pintar() vuelve a encolar por si el shortcode llega
	 * desde un constructor de páginas, una plantilla o un widget, donde este
	 * vistazo al contenido no lo vería. Encolar dos veces no hace nada.
	 */
	public static function encolar_si_toca() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();

		if ( $post && has_shortcode( $post->post_content, self::ETIQUETA ) ) {
			self::encolar();
		}
	}

	private static function encolar() {
		wp_enqueue_style( 'mapizza-bio' );
		wp_enqueue_script( 'mapizza-bio' );
	}

	public static function pintar( $atributos ) {
		$atributos = shortcode_atts(
			array(
				'id' => '',
			),
			$atributos,
			self::ETIQUETA
		);

		$id = MAPB_Perfil::resolver( $atributos['id'] );

		if ( ! $id || 'publish' !== get_post_status( $id ) ) {
			// Nada que enseñar. Al visitante no se le cuenta el problema; a
			// quien puede arreglarlo, sí.
			if ( current_user_can( 'edit_posts' ) ) {
				return '<p class="mapb-aviso">'
					. esc_html__( 'MA PIZZA — Enlaces de bio: no encuentro un perfil publicado para este shortcode.', 'mapizza-bio' )
					. '</p>';
			}
			return '';
		}

		self::encolar();

		$datos = MAPB_Perfil::leer( $id );
		$html  = MAPB_Render::pintar( $datos );

		if ( ! empty( $datos['ajuste_scroll'] ) ) {
			$html = self::estilo_ajuste() . $html;
		}

		return $html;
	}

	/**
	 * El ajuste de desplazamiento entre vistas va en el documento, no en el
	 * componente: scroll-snap-type tiene que estar en el elemento que
	 * desplaza. Por eso es opcional y sale aparte, bien a la vista, en lugar
	 * de ir escondido en la hoja de estilos afectando a cualquier página.
	 */
	private static function estilo_ajuste() {
		return '<style id="mapb-ajuste">html{scroll-snap-type:y proximity}</style>';
	}
}
