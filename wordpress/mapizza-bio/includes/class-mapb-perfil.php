<?php
/**
 * El perfil de bio: un tipo de contenido propio, uno por página de enlaces.
 *
 * Va como tipo de contenido y no como página de opciones justamente para que
 * mañana pueda haber varios perfiles sin tocar código: uno para mapizza.pe,
 * otro para /bio, otro para una campaña. Se registra SIN URL pública, así que
 * no crea rutas ni intercepta nada del sitio.
 */

defined( 'ABSPATH' ) || exit;

class MAPB_Perfil {

	const TIPO = 'mapb_perfil';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'registrar_tipo' ) );
	}

	public static function registrar_tipo() {
		register_post_type(
			self::TIPO,
			array(
				'labels'              => array(
					'name'               => __( 'Enlaces de bio', 'mapizza-bio' ),
					'singular_name'      => __( 'Perfil de bio', 'mapizza-bio' ),
					'add_new'            => __( 'Añadir perfil', 'mapizza-bio' ),
					'add_new_item'       => __( 'Añadir perfil de bio', 'mapizza-bio' ),
					'edit_item'          => __( 'Editar perfil de bio', 'mapizza-bio' ),
					'all_items'          => __( 'Perfiles', 'mapizza-bio' ),
					'menu_name'          => __( 'Enlaces de bio', 'mapizza-bio' ),
					'search_items'       => __( 'Buscar perfiles', 'mapizza-bio' ),
					'not_found'          => __( 'Todavía no hay perfiles.', 'mapizza-bio' ),
				),
				// Sin URL propia y sin reescrituras: el perfil es contenido para
				// el shortcode, no una página del sitio. Esto es lo que evita
				// que el plugin capture rutas o compita con las páginas reales.
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-smiley',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
			)
		);
	}

	/**
	 * Campos de texto e imagen. La clave es el meta; el valor, cómo se limpia.
	 *
	 *   texto  → una línea
	 *   rico   → admite <em> y <br>, que es lo que pinta el dorado y parte los versos
	 *   url    → enlace
	 *   medio  → id de la biblioteca de medios
	 *   bool   → interruptor
	 */
	public static function campos() {
		return array(
			'_mapb_logo'            => 'medio',
			'_mapb_lema'            => 'texto',
			'_mapb_horario_rango'   => 'texto',
			'_mapb_horario_dias'    => 'texto',
			'_mapb_horario_datos'   => 'texto',
			'_mapb_senuelo'         => 'texto',
			'_mapb_fondo_imagen'    => 'medio',
			'_mapb_fondo_video'     => 'medio',
			'_mapb_fondo_encuadre'  => 'texto',
			'_mapb_retrato'         => 'medio',
			'_mapb_historia_frase'  => 'rico',
			'_mapb_historia_coro'   => 'rico',
			'_mapb_historia_apunte' => 'rico',
			'_mapb_historia_firma'  => 'texto',
			'_mapb_cierre'          => 'texto',
			'_mapb_reclamos_url'    => 'url',
			'_mapb_reclamos_texto'  => 'texto',
			'_mapb_proximamente'    => 'texto',
			'_mapb_ajuste_scroll'   => 'bool',
		);
	}

	/** Lo que trae un perfil recién creado: la bio de MA PIZZA tal cual está hoy. */
	public static function predeterminados() {
		return array(
			'_mapb_lema'            => 'Cocinar con amor, alimenta el corazón',
			'_mapb_horario_rango'   => '12:00 – 23:00',
			'_mapb_horario_dias'    => 'Lunes a domingo',
			'_mapb_horario_datos'   => '33 cm · 6 slices · masa delgada',
			'_mapb_senuelo'         => 'Conoce su historia',
			'_mapb_fondo_encuadre'  => '30% 52%',
			'_mapb_historia_frase'  => 'Me fui de casa persiguiendo un sueño, <em>sabiendo que lo más valioso ya lo tenía allí.</em>',
			'_mapb_historia_coro'   => 'Por ellos me fui.<br />Por ellos sigo.<br /><em>Y a ellos voy a volver.</em>',
			'_mapb_historia_apunte' => 'Cada paso que doy con MA PIZZA lleva el mismo ingrediente con el que me educaron: <em>cariño, esfuerzo y propósito.</em>',
			'_mapb_historia_firma'  => '— Miguel Alberto Blanco',
			'_mapb_cierre'          => 'Masa ultra delgada, crocante y suave a la vez. No vas a encontrar otra igual.',
			'_mapb_reclamos_texto'  => 'Libro de reclamaciones',
			'_mapb_proximamente'    => 'Próximamente',
			'_mapb_botones'         => array(
				array(
					'titulo' => 'Rappi',
					'url'    => 'https://www.rappi.com.pe/restaurantes/108052-ma-pizza',
					'icono'  => 'rappi',
					'imagen' => 0,
					'activo' => 1,
				),
				array(
					'titulo' => 'PedidosYa',
					'url'    => 'https://www.pedidosya.com.pe/restaurantes/lima/ma-pizza-surquillo-85785ef5-3ee0-4264-8570-6dda9b44dc6e-menu',
					'icono'  => 'pedidosya',
					'imagen' => 0,
					'activo' => 1,
				),
				array(
					'titulo' => 'WhatsApp',
					'url'    => 'https://wa.me/51982000004',
					'icono'  => 'whatsapp',
					'imagen' => 0,
					'activo' => 1,
				),
				array(
					'titulo' => 'Instagram',
					'url'    => 'https://www.instagram.com/mapizza.pe/',
					'icono'  => 'instagram',
					'imagen' => 0,
					'activo' => 1,
				),
			),
		);
	}

	/** Lee un perfil entero, ya normalizado, listo para pintar. */
	public static function leer( $id ) {
		$datos = array( 'id' => (int) $id );

		foreach ( array_keys( self::campos() ) as $clave ) {
			$corto           = substr( $clave, 6 );           // quita "_mapb_"
			$datos[ $corto ] = get_post_meta( $id, $clave, true );
		}

		$botones = get_post_meta( $id, '_mapb_botones', true );
		$datos['botones'] = is_array( $botones ) ? array_values( $botones ) : array();

		return $datos;
	}

	/**
	 * El perfil que usa el shortcode cuando no se le dice cuál: el más
	 * reciente publicado. Con un solo perfil —el caso de hoy— el shortcode
	 * funciona sin argumentos.
	 */
	public static function predeterminado() {
		$ids = get_posts(
			array(
				'post_type'        => self::TIPO,
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/** Resuelve el atributo del shortcode: id numérico, slug o título. */
	public static function resolver( $referencia ) {
		$referencia = trim( (string) $referencia );

		if ( '' === $referencia ) {
			return self::predeterminado();
		}

		if ( ctype_digit( $referencia ) ) {
			$post = get_post( (int) $referencia );
			return ( $post && self::TIPO === $post->post_type ) ? $post->ID : 0;
		}

		$post = get_page_by_path( sanitize_title( $referencia ), OBJECT, self::TIPO );

		return $post ? $post->ID : 0;
	}
}
