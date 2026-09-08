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

## Logotipos

Los mapas de bits van incrustados en base64 y declarados **una sola vez** como
variables CSS (`--ma-marca`, `--ma-firma`, `--rappi`, `--pedidosya`); repetirlos
en cada `<img>` costaba 45 kB de más. Rappi y PedidosYa van en su color de
marca; el resto en blanco, como en la referencia del cliente.
