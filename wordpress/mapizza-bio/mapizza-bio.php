<?php
/**
 * Plugin Name:       MA PIZZA — Enlaces de bio
 * Plugin URI:        https://mapizza.pe
 * Description:       Página de enlaces para la bio de Instagram, administrable desde WordPress e insertable en cualquier página con el shortcode [mapizza_bio].
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Siqa Tecnología
 * License:           GPL-2.0-or-later
 * Text Domain:       mapizza-bio
 *
 * El plugin no toca nada fuera de lo suyo: no registra reglas de reescritura,
 * no captura URLs, no engancha the_content ni template_redirect. Se muestra
 * solo donde se escribe su shortcode.
 */

defined( 'ABSPATH' ) || exit;

define( 'MAPB_VERSION', '1.0.0' );
define( 'MAPB_ARCHIVO', __FILE__ );
define( 'MAPB_RUTA', plugin_dir_path( __FILE__ ) );
define( 'MAPB_URL', plugin_dir_url( __FILE__ ) );

require_once MAPB_RUTA . 'includes/class-mapb-perfil.php';
require_once MAPB_RUTA . 'includes/class-mapb-iconos.php';
require_once MAPB_RUTA . 'includes/class-mapb-admin.php';
require_once MAPB_RUTA . 'includes/class-mapb-render.php';
require_once MAPB_RUTA . 'includes/class-mapb-shortcode.php';

add_action( 'plugins_loaded', 'mapb_arrancar' );

function mapb_arrancar() {
	MAPB_Perfil::init();
	MAPB_Admin::init();
	MAPB_Shortcode::init();
}

/**
 * Al activar, deja creado un perfil de ejemplo si no hay ninguno, para que
 * el shortcode muestre algo desde el primer momento en vez de un hueco.
 */
register_activation_hook( __FILE__, 'mapb_al_activar' );

function mapb_al_activar() {
	require_once MAPB_RUTA . 'includes/class-mapb-perfil.php';
	MAPB_Perfil::registrar_tipo();

	$existentes = get_posts(
		array(
			'post_type'      => MAPB_Perfil::TIPO,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $existentes ) ) {
		return;
	}

	$id = wp_insert_post(
		array(
			'post_type'   => MAPB_Perfil::TIPO,
			'post_status' => 'publish',
			'post_title'  => __( 'Bio principal', 'mapizza-bio' ),
		)
	);

	if ( $id && ! is_wp_error( $id ) ) {
		foreach ( MAPB_Perfil::predeterminados() as $clave => $valor ) {
			update_post_meta( $id, $clave, $valor );
		}
	}
}
