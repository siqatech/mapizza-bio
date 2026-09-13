<?php
/**
 * La pantalla de edición del perfil. Todo con piezas de WordPress: cajas de
 * meta, biblioteca de medios y jquery-ui-sortable para el orden.
 */

defined( 'ABSPATH' ) || exit;

class MAPB_Admin {

	const NONCE = 'mapb_guardar_perfil';

	public static function init() {
		add_action( 'add_meta_boxes_' . MAPB_Perfil::TIPO, array( __CLASS__, 'cajas' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'nonce' ) );
		add_action( 'save_post_' . MAPB_Perfil::TIPO, array( __CLASS__, 'guardar' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'recursos' ) );
		add_filter( 'manage_' . MAPB_Perfil::TIPO . '_posts_columns', array( __CLASS__, 'columnas' ) );
		add_action( 'manage_' . MAPB_Perfil::TIPO . '_posts_custom_column', array( __CLASS__, 'columna' ), 10, 2 );
	}

	/** El shortcode, a mano en el listado: es lo que más se va a copiar. */
	public static function columnas( $columnas ) {
		$nuevas = array();
		foreach ( $columnas as $clave => $titulo ) {
			$nuevas[ $clave ] = $titulo;
			if ( 'title' === $clave ) {
				$nuevas['mapb_shortcode'] = __( 'Shortcode', 'mapizza-bio' );
				$nuevas['mapb_botones']   = __( 'Botones', 'mapizza-bio' );
			}
		}
		return $nuevas;
	}

	public static function columna( $columna, $post_id ) {
		if ( 'mapb_shortcode' === $columna ) {
			printf(
				'<code class="mapb-copiar" tabindex="0">[mapizza_bio id="%d"]</code>',
				(int) $post_id
			);
			return;
		}

		if ( 'mapb_botones' === $columna ) {
			$botones = get_post_meta( $post_id, '_mapb_botones', true );
			$botones = is_array( $botones ) ? $botones : array();
			$activos = count( array_filter( $botones, function ( $b ) {
				return ! empty( $b['activo'] );
			} ) );

			printf(
				/* translators: 1: botones activos, 2: total */
				esc_html__( '%1$d de %2$d activos', 'mapizza-bio' ),
				(int) $activos,
				count( $botones )
			);
		}
	}

	public static function recursos() {
		$pantalla = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Sin pantalla identificada no se carga nada: antes, si get_current_screen
		// devolvía null, la condición salía falsa y los recursos del plugin
		// acababan en todas las páginas del escritorio.
		if ( ! $pantalla || MAPB_Perfil::TIPO !== $pantalla->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'mapizza-bio-admin', MAPB_URL . 'assets/css/admin.css', array(), MAPB_VERSION );
		wp_enqueue_script(
			'mapizza-bio-admin',
			MAPB_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			MAPB_VERSION,
			true
		);
		wp_localize_script(
			'mapizza-bio-admin',
			'mapbTextos',
			array(
				'elegir'   => __( 'Elegir imagen', 'mapizza-bio' ),
				'usar'     => __( 'Usar esta imagen', 'mapizza-bio' ),
				'elegirV'  => __( 'Elegir vídeo', 'mapizza-bio' ),
				'usarV'    => __( 'Usar este vídeo', 'mapizza-bio' ),
				'borrar'   => __( '¿Quitar este botón?', 'mapizza-bio' ),
				'copiado'  => __( 'Copiado', 'mapizza-bio' ),
			)
		);
	}

	public static function cajas() {
		$cajas = array(
			'mapb-uso'      => array( __( 'Cómo se muestra', 'mapizza-bio' ), 'caja_uso', 'side' ),
			'mapb-marca'    => array( __( 'Marca', 'mapizza-bio' ), 'caja_marca', 'normal' ),
			'mapb-botones'  => array( __( 'Botones', 'mapizza-bio' ), 'caja_botones', 'normal' ),
			'mapb-horario'  => array( __( 'Horario', 'mapizza-bio' ), 'caja_horario', 'normal' ),
			'mapb-fondo'    => array( __( 'Fondo', 'mapizza-bio' ), 'caja_fondo', 'normal' ),
			'mapb-historia' => array( __( 'Historia', 'mapizza-bio' ), 'caja_historia', 'normal' ),
			'mapb-pie'      => array( __( 'Pie', 'mapizza-bio' ), 'caja_pie', 'normal' ),
		);

		foreach ( $cajas as $id => $caja ) {
			add_meta_box( $id, $caja[0], array( __CLASS__, $caja[1] ), MAPB_Perfil::TIPO, $caja[2] );
		}
	}

	// ------------------------------------------------------------------ cajas

	/**
	 * El nonce va aquí y no dentro de una caja de meta. Las cajas se pueden
	 * ocultar desde "Opciones de pantalla", y con la caja oculta el nonce no
	 * se imprimía: al guardar, la comprobación fallaba y los cambios se
	 * perdían en silencio.
	 */
	public static function nonce( $post ) {
		if ( MAPB_Perfil::TIPO === $post->post_type ) {
			wp_nonce_field( self::NONCE, 'mapb_nonce' );
		}
	}

	public static function caja_uso( $post ) {
		?>
		<p><?php esc_html_e( 'Pega este shortcode en cualquier página o entrada:', 'mapizza-bio' ); ?></p>
		<p><code class="mapb-copiar" tabindex="0">[mapizza_bio id="<?php echo (int) $post->ID; ?>"]</code></p>
		<p class="description">
			<?php esc_html_e( 'Sin el atributo id, [mapizza_bio] muestra el primer perfil publicado. El perfil no tiene página propia: solo se ve donde pongas el shortcode.', 'mapizza-bio' ); ?>
		</p>
		<hr />
		<?php
		self::casilla( $post, '_mapb_ajuste_scroll', __( 'Ajustar el desplazamiento entre las dos vistas', 'mapizza-bio' ) );
		?>
		<p class="description">
			<?php esc_html_e( 'Hace que el scroll encaje en cada vista. Afecta al desplazamiento de toda la página, así que actívalo solo en una página dedicada a la bio.', 'mapizza-bio' ); ?>
		</p>
		<?php
	}

	public static function caja_marca( $post ) {
		self::medio( $post, '_mapb_logo', __( 'Logotipo', 'mapizza-bio' ), __( 'Se muestra arriba del todo. Un PNG con fondo transparente funciona mejor.', 'mapizza-bio' ) );
		self::texto( $post, '_mapb_lema', __( 'Lema', 'mapizza-bio' ), 'Cocinar con amor, alimenta el corazón' );
	}

	public static function caja_horario( $post ) {
		self::texto( $post, '_mapb_horario_rango', __( 'Horario', 'mapizza-bio' ), '12:00 – 23:00' );
		self::texto( $post, '_mapb_horario_dias', __( 'Días', 'mapizza-bio' ), 'Lunes a domingo' );
		self::texto( $post, '_mapb_horario_datos', __( 'Detalle del producto', 'mapizza-bio' ), '33 cm · 6 slices · masa delgada' );
		self::texto( $post, '_mapb_senuelo', __( 'Texto de la flecha', 'mapizza-bio' ), 'Conoce su historia', __( 'Invita a bajar a la segunda vista. Déjalo vacío para ocultarlo.', 'mapizza-bio' ) );
	}

	public static function caja_fondo( $post ) {
		self::medio( $post, '_mapb_fondo_imagen', __( 'Imagen de fondo', 'mapizza-bio' ), __( 'Se ve velada en la franja inferior. Funciona mejor una foto horizontal y oscura.', 'mapizza-bio' ) );
		self::medio( $post, '_mapb_fondo_video', __( 'Vídeo de fondo', 'mapizza-bio' ), __( 'Opcional. Si lo pones, sustituye a la imagen, que se queda como primer fotograma. Súbelo ya comprimido: por encima de 1 MB se nota en datos móviles.', 'mapizza-bio' ), 'video' );
		self::texto( $post, '_mapb_fondo_encuadre', __( 'Encuadre', 'mapizza-bio' ), '30% 52%', __( 'Qué parte del fondo se ve en vertical. El móvil recorta la franja central, así que si el plato no está centrado, muévelo aquí (por ejemplo 30% 52%).', 'mapizza-bio' ) );
	}

	public static function caja_historia( $post ) {
		self::medio( $post, '_mapb_retrato', __( 'Retrato', 'mapizza-bio' ), __( 'Ocupa el fondo de la segunda vista. Una foto cuadrada u horizontal va mejor: en vertical se recorta mucho de ancho.', 'mapizza-bio' ) );
		echo '<p class="description mapb-nota">' . esc_html__( 'En los tres textos de abajo, lo que envuelvas en <em> sale en dorado y <br> parte la línea.', 'mapizza-bio' ) . '</p>';
		self::area( $post, '_mapb_historia_frase', __( 'Frase principal', 'mapizza-bio' ) );
		self::area( $post, '_mapb_historia_coro', __( 'Estrofa', 'mapizza-bio' ) );
		self::area( $post, '_mapb_historia_apunte', __( 'Apunte', 'mapizza-bio' ) );
		self::texto( $post, '_mapb_historia_firma', __( 'Firma', 'mapizza-bio' ), '— Miguel Alberto Blanco' );
	}

	public static function caja_pie( $post ) {
		self::texto( $post, '_mapb_cierre', __( 'Frase de cierre', 'mapizza-bio' ), '' );
		self::texto( $post, '_mapb_reclamos_texto', __( 'Texto del enlace legal', 'mapizza-bio' ), 'Libro de reclamaciones' );
		self::texto( $post, '_mapb_reclamos_url', __( 'Enlace del libro de reclamaciones', 'mapizza-bio' ), 'https://', __( 'Sin enlace, no se muestra.', 'mapizza-bio' ) );
		self::texto( $post, '_mapb_proximamente', __( 'Aviso de botón apagado', 'mapizza-bio' ), 'Próximamente' );
	}

	public static function caja_botones( $post ) {
		$botones = get_post_meta( $post->ID, '_mapb_botones', true );
		$botones = is_array( $botones ) ? array_values( $botones ) : array();
		?>
		<p class="description">
			<?php esc_html_e( 'Arrastra por el asa para cambiar el orden. Un botón apagado se ve en gris, no se puede pulsar y muestra el aviso de "Próximamente".', 'mapizza-bio' ); ?>
		</p>

		<div class="mapb-lista" id="mapb-lista">
			<?php
			foreach ( $botones as $i => $boton ) {
				self::fila_boton( $i, $boton );
			}
			?>
		</div>

		<p>
			<button type="button" class="button button-secondary" id="mapb-anadir">
				<?php esc_html_e( '+ Añadir botón', 'mapizza-bio' ); ?>
			</button>
		</p>

		<script type="text/html" id="mapb-plantilla-fila">
			<?php self::fila_boton( '__i__', array() ); ?>
		</script>
		<?php
	}

	private static function fila_boton( $i, $boton ) {
		$boton = wp_parse_args(
			$boton,
			array( 'titulo' => '', 'url' => '', 'icono' => '', 'imagen' => 0, 'activo' => 1 )
		);

		$base      = 'mapb_botones[' . $i . ']';
		$imagen_id = (int) $boton['imagen'];
		$vista     = $imagen_id ? wp_get_attachment_image_url( $imagen_id, 'thumbnail' ) : '';
		?>
		<div class="mapb-fila<?php echo empty( $boton['activo'] ) ? ' mapb-fila--apagada' : ''; ?>">
			<span class="mapb-asa dashicons dashicons-menu" aria-hidden="true"></span>

			<div class="mapb-campos">
				<label>
					<span><?php esc_html_e( 'Nombre', 'mapizza-bio' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $base ); ?>[titulo]"
						value="<?php echo esc_attr( $boton['titulo'] ); ?>" />
				</label>

				<label>
					<span><?php esc_html_e( 'Enlace', 'mapizza-bio' ); ?></span>
					<input type="url" name="<?php echo esc_attr( $base ); ?>[url]"
						value="<?php echo esc_attr( $boton['url'] ); ?>" placeholder="https://" />
				</label>

				<label>
					<span><?php esc_html_e( 'Icono', 'mapizza-bio' ); ?></span>
					<select name="<?php echo esc_attr( $base ); ?>[icono]">
						<?php foreach ( MAPB_Iconos::catalogo() as $clave => $nombre ) : ?>
							<option value="<?php echo esc_attr( $clave ); ?>"
								<?php selected( $boton['icono'], $clave ); ?>>
								<?php echo esc_html( $nombre ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>

				<div class="mapb-medio mapb-medio--mini" data-tipo="image">
					<span><?php esc_html_e( 'O imagen propia', 'mapizza-bio' ); ?></span>
					<input type="hidden" name="<?php echo esc_attr( $base ); ?>[imagen]"
						value="<?php echo (int) $imagen_id; ?>" class="mapb-medio-id" />
					<div class="mapb-medio-vista">
						<?php if ( $vista ) : ?>
							<img src="<?php echo esc_url( $vista ); ?>" alt="" />
						<?php endif; ?>
					</div>
					<button type="button" class="button button-small mapb-medio-elegir">
						<?php esc_html_e( 'Elegir', 'mapizza-bio' ); ?>
					</button>
					<button type="button" class="button-link mapb-medio-quitar"
						<?php echo $vista ? '' : 'hidden'; ?>>
						<?php esc_html_e( 'Quitar', 'mapizza-bio' ); ?>
					</button>
				</div>
			</div>

			<div class="mapb-acciones">
				<label class="mapb-interruptor">
					<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[activo]" value="1"
						<?php checked( ! empty( $boton['activo'] ) ); ?> />
					<span><?php esc_html_e( 'Activo', 'mapizza-bio' ); ?></span>
				</label>
				<button type="button" class="button-link delete mapb-quitar">
					<?php esc_html_e( 'Quitar', 'mapizza-bio' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	// ------------------------------------------------------------- campos sueltos

	private static function texto( $post, $clave, $etiqueta, $marcador = '', $ayuda = '' ) {
		$valor = get_post_meta( $post->ID, $clave, true );
		printf(
			'<p class="mapb-campo"><label for="%1$s"><strong>%2$s</strong></label>'
				. '<input type="text" id="%1$s" name="mapb[%1$s]" value="%3$s" placeholder="%4$s" class="widefat" />%5$s</p>',
			esc_attr( $clave ),
			esc_html( $etiqueta ),
			esc_attr( $valor ),
			esc_attr( $marcador ),
			$ayuda ? '<span class="description">' . esc_html( $ayuda ) . '</span>' : ''
		);
	}

	private static function area( $post, $clave, $etiqueta ) {
		$valor = get_post_meta( $post->ID, $clave, true );
		printf(
			'<p class="mapb-campo"><label for="%1$s"><strong>%2$s</strong></label>'
				. '<textarea id="%1$s" name="mapb[%1$s]" rows="3" class="widefat">%3$s</textarea></p>',
			esc_attr( $clave ),
			esc_html( $etiqueta ),
			esc_textarea( $valor )
		);
	}

	private static function casilla( $post, $clave, $etiqueta ) {
		$valor = get_post_meta( $post->ID, $clave, true );
		printf(
			'<label class="mapb-interruptor"><input type="checkbox" name="mapb[%1$s]" value="1" %2$s /> <span>%3$s</span></label>',
			esc_attr( $clave ),
			checked( $valor, '1', false ),
			esc_html( $etiqueta )
		);
	}

	private static function medio( $post, $clave, $etiqueta, $ayuda = '', $tipo = 'image' ) {
		$id     = (int) get_post_meta( $post->ID, $clave, true );
		$es_img = 'image' === $tipo;
		$vista  = '';

		if ( $id ) {
			$vista = $es_img
				? wp_get_attachment_image( $id, 'medium', false, array( 'alt' => '' ) )
				: '<span class="mapb-medio-archivo">' . esc_html( basename( (string) wp_get_attachment_url( $id ) ) ) . '</span>';
		}
		?>
		<div class="mapb-campo mapb-medio" data-tipo="<?php echo esc_attr( $tipo ); ?>">
			<label><strong><?php echo esc_html( $etiqueta ); ?></strong></label>
			<input type="hidden" name="mapb[<?php echo esc_attr( $clave ); ?>]"
				value="<?php echo (int) $id; ?>" class="mapb-medio-id" />
			<div class="mapb-medio-vista"><?php echo wp_kses_post( $vista ); ?></div>
			<p>
				<button type="button" class="button mapb-medio-elegir">
					<?php echo $es_img ? esc_html__( 'Elegir imagen', 'mapizza-bio' ) : esc_html__( 'Elegir vídeo', 'mapizza-bio' ); ?>
				</button>
				<button type="button" class="button-link mapb-medio-quitar" <?php echo $id ? '' : 'hidden'; ?>>
					<?php esc_html_e( 'Quitar', 'mapizza-bio' ); ?>
				</button>
			</p>
			<?php if ( $ayuda ) : ?>
				<span class="description"><?php echo esc_html( $ayuda ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}

	// ------------------------------------------------------------------ guardar

	public static function guardar( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['mapb_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mapb_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$entrada = isset( $_POST['mapb'] ) && is_array( $_POST['mapb'] )
			? wp_unslash( $_POST['mapb'] )
			: array();

		foreach ( MAPB_Perfil::campos() as $clave => $tipo ) {
			$bruto = isset( $entrada[ $clave ] ) ? $entrada[ $clave ] : '';

			switch ( $tipo ) {
				case 'medio':
					$valor = (int) $bruto;
					break;
				case 'url':
					$valor = esc_url_raw( trim( (string) $bruto ) );
					break;
				case 'rico':
					$valor = wp_kses( (string) $bruto, MAPB_Render::TAGS_RICOS );
					break;
				case 'bool':
					$valor = $bruto ? '1' : '';
					break;
				default:
					$valor = sanitize_text_field( (string) $bruto );
			}

			update_post_meta( $post_id, $clave, $valor );
		}

		update_post_meta( $post_id, '_mapb_botones', self::limpiar_botones() );
	}

	private static function limpiar_botones() {
		if ( ! isset( $_POST['mapb_botones'] ) || ! is_array( $_POST['mapb_botones'] ) ) {
			return array();
		}

		$brutos = wp_unslash( $_POST['mapb_botones'] );

		// El orden lo da el índice, que el guion renumera al arrastrar. Si el
		// guion no llegara a correr, ksort deja el orden que ya había en vez
		// de barajarlos.
		ksort( $brutos, SORT_NUMERIC );

		$limpios = array();

		foreach ( $brutos as $bruto ) {
			if ( ! is_array( $bruto ) ) {
				continue;
			}

			$titulo = sanitize_text_field( isset( $bruto['titulo'] ) ? $bruto['titulo'] : '' );
			$url    = esc_url_raw( trim( isset( $bruto['url'] ) ? $bruto['url'] : '' ) );
			$imagen = (int) ( isset( $bruto['imagen'] ) ? $bruto['imagen'] : 0 );

			// Una fila del todo vacía es una fila que el editor añadió y no usó.
			if ( '' === $titulo && '' === $url && ! $imagen ) {
				continue;
			}

			$icono = sanitize_key( isset( $bruto['icono'] ) ? $bruto['icono'] : '' );

			$limpios[] = array(
				'titulo' => $titulo,
				'url'    => $url,
				'icono'  => array_key_exists( $icono, MAPB_Iconos::catalogo() ) ? $icono : '',
				'imagen' => $imagen,
				'activo' => empty( $bruto['activo'] ) ? 0 : 1,
			);
		}

		return $limpios;
	}
}
