=== MA PIZZA — Enlaces de bio ===
Contributors: siqatech
Tags: link in bio, enlaces, shortcode, instagram
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Página de enlaces para la bio de Instagram, administrable desde WordPress y
que se inserta en cualquier página con un shortcode.

== Description ==

Convierte la página de enlaces de MA PIZZA en un componente de WordPress. Todo
el contenido —logotipo, textos, horario, botones, imágenes, historia— se edita
desde el escritorio, sin tocar código.

= Cómo se usa =

1. Menú **Enlaces de bio** → editar el perfil que se crea al activar.
2. Copiar el shortcode que aparece en la caja "Cómo se muestra".
3. Pegarlo en la página donde deba verse.

Hoy puede ir en la portada y mañana en `/bio` sin tocar nada más: el perfil no
tiene URL propia, solo se ve donde esté el shortcode.

= Varios perfiles =

Cada perfil es una entrada del tipo "Enlaces de bio", así que puede haber
tantos como haga falta —uno por marca, por campaña o por idioma— y cada página
llama al suyo con `[mapizza_bio id="12"]`.

= El lema =

Puede ir partido en dos con un sello en medio —"Cocinar con amor · MA ·
Alimenta el corazón"— rellenando las tres casillas de la caja **Marca**. Si se
deja el sello vacío, el lema sale como una sola línea seguida.

Cuando el ancho no da para una línea, el sello sube solo a la suya. La decisión
se toma midiendo el contenedor donde esté pegado el shortcode, no la pantalla:
una página con relleno lateral deja bastante menos sitio que una en blanco.

= Botones =

Se reordenan arrastrando. Cada uno puede apagarse: entonces sale en gris, sin
enlace y con el aviso de "Próximamente". Apagado es un `<span>` sin `href`, no
un enlace con el clic anulado, así que tampoco responde al teclado ni a un
lector de pantalla.

Cada botón admite un icono del catálogo (Rappi, PedidosYa, WhatsApp, Instagram,
teléfono, ubicación, carta, enlace) o una imagen propia de la biblioteca de
medios, que tiene prioridad sobre el icono.

== Lo que el plugin NO hace ==

Conviene decirlo, porque es lo que lo hace seguro de instalar en un sitio que
ya funciona:

* No registra reglas de reescritura ni captura URLs.
* No engancha `the_content`, `template_redirect` ni `template_include`.
* No necesita ser la portada ni depende de ninguna página concreta.
* No encola ni una línea de CSS o JS en las páginas donde no está el shortcode.
* Sus estilos cuelgan todos de `.mapb` y sus clases llevan prefijo `mapb-`, así
  que ni tocan al tema ni el tema los toca.

== Changelog ==

= 1.0.0 =
* Primera versión: perfil administrable, shortcode, botones reordenables y
  apagables, fondo de imagen o vídeo, retrato e historia.
