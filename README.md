# MA PIZZA — página de enlaces (bio de Instagram)

`index.html` es un **archivo único**: no carga fuentes, imágenes ni scripts
externos, así que se sube tal cual a cualquier hosting y funciona. Pesa 71 kB.

## Qué se puede editar

Todo está señalizado con comentarios dentro del archivo:

| Buscar en el archivo | Qué cambia |
| --- | --- |
| `ENLACES` | Las URLs de Rappi, PedidosYa, WhatsApp e Instagram |
| `RELATO` | El texto de marca y la firma de Miguel Blanco |
| `HORARIO` | El horario y la línea "33 cm · 6 slices · masa delgada" |
| `RECLAMACIONES` | El enlace al libro de reclamaciones |

**Pendiente:** el libro de reclamaciones sigue con `href="#"` porque no
teníamos la URL. Es el único enlace sin destino real.

WhatsApp usa el formato `https://wa.me/51XXXXXXXXX` (número sin `+` ni espacios).

## Cómo está hecho

- **Fondo**: generado por CSS y un `feTurbulence` en línea (piedra, luz cálida
  desde la esquina superior derecha, rescoldo al pie y brasas que suben). Sin
  fotos: pesa unos bytes y no hay que recortarlo para cada pantalla.
  Las capas van en `z-index: 0` y el contenido en `1`; **nunca en negativo**,
  porque una capa de z-index negativo se pinta antes que el fondo de los
  elementos en flujo y el negro de `body` la taparía entera.
- **Borde dorado**: un `conic-gradient` recortado al `border-box` cuyo ángulo
  gira con `@property`, con una fase distinta por tarjeta. Donde el navegador
  no soporte `@property` el borde se queda quieto, que se ve bien igual.
- **Toque**: el destello nace en el punto exacto donde cae el dedo (JS pasa las
  coordenadas del `pointerdown` al CSS), la tarjeta se hunde, el halo sube y en
  Android hay una vibración de 10 ms.
- **Responsive**: una sola columna en móvil, tablet y escritorio, con el
  cintillo partido en dos líneas por debajo de 360 px y una variante compacta
  para móvil apaisado. Objetivos táctiles de 44 px o más.
- **`prefers-reduced-motion`**: apaga brasas, halos y entradas.

## Cómo se construye

`index.html` no se edita a mano: sale de `herramientas/plantilla.html` más los
recursos de `herramientas/recursos/`.

```bash
pip install pillow                      # siempre
sudo apt install ffmpeg                 # solo para las versiones con vídeo

python3 herramientas/construir.py                       # -> index.html
python3 herramientas/construir.py --foto pizza.jpg      # cambia la foto del fondo
```

Los logotipos y la foto van incrustados en base64, así que el HTML que sale se
sube solo, sin carpeta de assets.

## Las dos vistas

La página son dos pantallas, no una columna larga: primero dónde pedir, y al
bajar quién la hace. Un señuelo ("Desliza" con una flecha) avisa de que hay
segunda, y `scroll-snap-type: y proximity` ayuda a caer en ella —`proximity` y
no `mandatory`, que secuestra el scroll y en un móvil se siente como si la
página peleara. En apaisado no hay altura para dos pantallas, así que ahí el
snap se apaga y vuelve a ser una columna.

**La segunda vista tiene que medir una pantalla entera.** Si mide menos, el
navegador no puede desplazarse lo suficiente para dejarla arriba del todo y al
llegar al final acabas viendo la cola vacía de la primera: eso es lo que se leía
como "abajo está todo negro". Y como mide una pantalla, su contenido la reparte
—el hueco entre la voz de Miguel y la firma de la casa— en vez de amontonarse
arriba y dejar un vacío.

La segunda vista no entra al cargar sino cuando asoma, con un
`IntersectionObserver`. Los estilos que la ocultan cuelgan de una clase `.js`
que pone el propio guion, así que sin JavaScript el contenido se ve igual en
lugar de quedarse invisible para siempre.

## El fondo de foto

La foto cubre toda la vista, pero una máscara de degradado solo la deja asomar
en el tercio inferior: negro hasta el 70 %, un ascenso corto, y otra vez a la
baja al llegar al pie. La pizza se intuye de fondo sin que la página deje de ser
negra, que es donde las tarjetas recortan. Lleva un acercamiento de 58 s, corto
a propósito para que no se lea como una animación.

Medido sobre el fondo real, el fondo lejos de las tarjetas está en `rgb(5,4,4)`
y el interior de una tarjeta en `rgb(14,12,10)`: la tarjeta es **más clara** que
el fondo, que es como debe leerse un panel. Cuando fue al revés, la página se
veía plomiza.

Tres cosas que hay que respetar al retocar el fondo, porque cada una produjo
gris en su momento:

1. **El grano va en `mix-blend-mode: soft-light`.** `feTurbulence` promedia un
   gris medio, así que pintado sin mezcla al 9 % subía el negro de toda la
   página a `rgb(11,11,11)`. Con soft-light el negro se queda negro y la
   textura solo asoma donde ya hay luz.
2. **Nada de realces en el centro.** Un `radial-gradient` amplio en mitad de la
   pantalla cae justo detrás de la columna de tarjetas y las rodea de gris. Los
   cálidos van pegados a las esquinas.
3. **La foto se satura, no se desatura.** Bajar la saturación sobre negro da
   barro gris; lo que se busca es oscura y cálida (brillo 0.44, color 1.15).

**La foto actual es provisional**: es una del catálogo de Rappi, puesta solo
para ver el efecto. Funciona mejor una foto **horizontal y oscura**, con la
pizza más o menos centrada, y tiene que ser fotografía propia: las
previsualizaciones de bancos de imágenes llevan marca de agua y no están
licenciadas, y una pizza de stock en la bio de una pizzería se nota. Después de
cambiarla conviene mirar que el pie siga legible.

## Las versiones con vídeo

Ya están generadas: **`index2.html` + `fondo2.mp4`** (el zoom sobre la masa) e
**`index3.html` + `fondo3.mp4`** (la pizza girando). Cada pareja se sube junta:
el HTML busca el vídeo por nombre, en la misma carpeta.

Para rehacerlas desde otros originales:

```bash
python3 herramientas/construir.py --video ruta/pizzazoom.mov --salida index2.html
python3 herramientas/construir.py --video ruta/pizagira.mp4  --salida index3.html
```

**El vídeo no va incrustado**: en base64 el navegador tendría que descargarlo
entero antes de pintar nada. Comprimido queda en 350 kB y 173 kB —14 s, mudo,
960 px, `faststart`— y cada HTML en 91 kB.

El bucle se monta con el trozo y su propio reverso detrás. Un corte seco vuelve
al primer fotograma de golpe y ese salto se ve cada pocos segundos; yendo y
volviendo, el final ya *es* el principio. Comprobado: la diferencia media entre
el primer y el último fotograma es de 3,2 y 1,3 sobre 255.

El vídeo lleva **el mismo tratamiento que la foto y en el mismo orden**
—desenfoque, multiplicación del brillo, saturación—, porque si no coincidiera
con su póster se vería un salto de luz al arrancar. `lutrgb` multiplica, que es
lo que hace Pillow; `eq=brightness` sumaría, que es otra cosa. Comprobado: el
RGB medio del póster y el del primer fotograma no se separan más de 6 sobre
255.

La página arranca mostrando el póster, que es un fotograma del propio vídeo
tratado igual que la foto: el primer pintado es idéntico al de la versión 1.
Solo después el guion engancha el vídeo, y **no lo engancha nunca** si el
navegador pide movimiento reducido o si la conexión declara ahorro de datos o
2G. Si la reproducción falla, se queda el póster. Nadie abre una página de bio
para gastarse los datos en un fondo.

En la versión con vídeo se desactiva el acercamiento del CSS: el vídeo ya trae
su propio movimiento y encima se notaría como un temblor.

## Logotipos## Logotipos

Los mapas de bits van incrustados en base64 y declarados **una sola vez** como
variables CSS (`--ma-marca`, `--ma-firma`, `--rappi`, `--pedidosya`); repetirlos
en cada `<img>` costaba 45 kB de más. Rappi y PedidosYa van en su color de
marca; el resto en blanco, como en la referencia del cliente.
