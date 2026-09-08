# MA PIZZA — página de enlaces (bio de Instagram)

`index.html` es un **archivo único**: no carga fuentes, imágenes ni scripts
externos, así que se sube tal cual a cualquier hosting y funciona. Pesa 71 kB.

## Qué se puede editar

Todo está señalizado con comentarios dentro del archivo:

| Buscar en el archivo | Qué cambia |
| --- | --- |
| `ENLACES` | Las URLs de Rappi, PedidosYa, WhatsApp e Instagram |
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

## La foto del fondo

La foto cubre toda la vista, pero una máscara de degradado solo la deja asomar
en el tercio inferior: negro hasta el 70 %, un ascenso corto, y otra vez a la
baja al llegar al pie. La pizza se intuye de fondo sin que la página deje de ser
negra, que es donde las tarjetas recortan. Lleva un acercamiento de 58 s, corto
a propósito para que el movimiento no se note como una animación.

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
para ver el efecto. Para cambiarla por la buena:

```bash
pip install pillow
python3 herramientas/incrustar-foto.py ruta/de/la/foto.jpg
```

El script recorta una banda ancha, la desenfoca un poco, la oscurece, la
comprime y la reincrusta en la variable CSS `--foto-horno` de `index.html`.
El desenfoque no es capricho: es lo que baja el archivo de 77 kB a 25 kB, y
como la capa se ve velada y difuminada, la pérdida de nitidez no se aprecia.

Funciona mejor una foto **horizontal y oscura**, con la pizza más o menos
centrada. Tiene que ser fotografía propia del cliente: las previsualizaciones
de bancos de imágenes llevan marca de agua y no están licenciadas, y una pizza
de stock en la bio de una pizzería se nota. Después de cambiarla conviene mirar que el pie siga legible: los
cuatro bloques de texto van hoy por encima de 4,5:1 de contraste.

## Logotipos

Los mapas de bits van incrustados en base64 y declarados **una sola vez** como
variables CSS (`--ma-marca`, `--ma-firma`, `--rappi`, `--pedidosya`); repetirlos
en cada `<img>` costaba 45 kB de más. Rappi y PedidosYa van en su color de
marca; el resto en blanco, como en la referencia del cliente.
