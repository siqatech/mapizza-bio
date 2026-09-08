#!/usr/bin/env python3
"""Cambia la foto del fondo de index.html.

    python3 herramientas/incrustar-foto.py foto.jpg

Recorta una banda ancha, la desenfoca un poco, la oscurece, la comprime y
la reincrusta en la variable CSS --foto-horno. El desenfoque no es solo
estético: es lo que permite bajar de 77 kB a 25 kB, y como la capa se ve
al 50 % de opacidad y casi toda enmascarada, nadie nota la pérdida de nitidez.

Necesita Pillow:  pip install pillow
"""
import base64, io, re, sys
from pathlib import Path

from PIL import Image, ImageEnhance, ImageFilter

ANCHO = 820          # de sobra: la capa nunca se ve nítida
DESENFOQUE = 1.7
CALIDAD = 44
BRILLO = 0.50        # oscurecer aquí comprime mejor que hacerlo en CSS
COLOR = 0.74

RAIZ = Path(__file__).resolve().parent.parent
DESTINO = RAIZ / "index.html"


def preparar(ruta: Path) -> bytes:
    im = Image.open(ruta).convert("RGB")
    an, al = im.size
    # Solo interesa la franja central: arriba y abajo se pierden bajo la
    # máscara de degradado.
    im = im.crop((0, int(al * 0.10), an, int(al * 0.92)))
    im = im.resize((ANCHO, round(im.height * ANCHO / im.width)), Image.LANCZOS)
    im = im.filter(ImageFilter.GaussianBlur(DESENFOQUE))
    im = ImageEnhance.Brightness(im).enhance(BRILLO)
    im = ImageEnhance.Color(im).enhance(COLOR)
    buf = io.BytesIO()
    im.save(buf, "JPEG", quality=CALIDAD, optimize=True, progressive=True)
    return buf.getvalue()


def main() -> int:
    if len(sys.argv) != 2:
        print(__doc__)
        return 2

    origen = Path(sys.argv[1])
    if not origen.is_file():
        print(f"No encuentro la foto: {origen}")
        return 1

    datos = preparar(origen)
    uri = "data:image/jpeg;base64," + base64.b64encode(datos).decode()

    html = DESTINO.read_text(encoding="utf8")
    nuevo, n = re.subn(
        r'(--foto-horno: url\(")[^"]*("\);)',
        lambda m: m.group(1) + uri + m.group(2),
        html,
        count=1,
    )
    if n != 1:
        print("No encontré la variable --foto-horno en index.html.")
        return 1

    DESTINO.write_text(nuevo, encoding="utf8")
    print(f"Listo: {len(datos) // 1024} kB incrustados. "
          f"index.html pesa ahora {len(nuevo.encode()) // 1024} kB.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
