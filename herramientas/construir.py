#!/usr/bin/env python3
"""Construye las páginas de MA PIZZA a partir de herramientas/plantilla.html.

    # versión 1: fondo de foto (el index.html que se sube)
    python3 herramientas/construir.py

    # cambiar la foto del fondo por otra
    python3 herramientas/construir.py --foto ~/fotos/pizza.jpg

    # versión 2 y 3: fondo de vídeo
    python3 herramientas/construir.py --video herramientas/pizzazoom.mov --salida index2.html
    python3 herramientas/construir.py --video herramientas/pizagira.mp4  --salida index3.html

Los logotipos y la foto van incrustados en base64, así que el HTML que sale
se sube solo. El vídeo NO: se comprime a un archivo aparte junto al HTML,
porque en base64 el navegador tendría que descargarlo entero antes de pintar
nada. La página arranca mostrando el póster —el mismo aspecto que la versión
de foto— y solo entonces engancha el vídeo.

Necesita:  pip install pillow      (siempre)
           ffmpeg                  (solo para --video)
"""
import argparse
import base64
import io
import mimetypes
import shutil
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageEnhance, ImageFilter

RAIZ = Path(__file__).resolve().parent.parent
RECURSOS = RAIZ / "herramientas" / "recursos"
PLANTILLA = RAIZ / "herramientas" / "plantilla.html"

# --- tratamiento de la foto de fondo -------------------------------------
# Oscurecer y desenfocar aquí, y no en CSS, es lo que la baja de 77 kB a 28.
# Como la capa se ve al 50 % y casi toda enmascarada, la nitidez no se echa
# en falta. La saturación SUBE: desaturar sobre negro da gris plomo.
ANCHO_FOTO = 820
DESENFOQUE = 1.7
CALIDAD = 44
BRILLO = 0.44
COLOR = 1.15

# --- compresión del vídeo -------------------------------------------------
ANCHO_VIDEO = 960          # se recorta con object-fit: cover, no hace falta más
SEGUNDOS = 12              # un bucle corto pesa menos y se nota menos que se repite
CRF = 30                   # va al 50 % de opacidad y difuminado: no pide calidad


def tratar_foto(origen: Path) -> bytes:
    im = Image.open(origen).convert("RGB")
    an, al = im.size
    im = im.crop((0, int(al * 0.10), an, int(al * 0.92)))
    im = im.resize((ANCHO_FOTO, round(im.height * ANCHO_FOTO / im.width)), Image.LANCZOS)
    im = im.filter(ImageFilter.GaussianBlur(DESENFOQUE))
    im = ImageEnhance.Brightness(im).enhance(BRILLO)
    im = ImageEnhance.Color(im).enhance(COLOR)
    buf = io.BytesIO()
    im.save(buf, "JPEG", quality=CALIDAD, optimize=True, progressive=True)
    return buf.getvalue()


def uri(datos: bytes, tipo: str) -> str:
    return f"data:{tipo};base64," + base64.b64encode(datos).decode()


def uri_archivo(ruta: Path) -> str:
    tipo = mimetypes.guess_type(ruta.name)[0] or "application/octet-stream"
    return uri(ruta.read_bytes(), tipo)


def exigir_ffmpeg() -> None:
    if not shutil.which("ffmpeg"):
        sys.exit("Hace falta ffmpeg para --video.  sudo apt install ffmpeg")


def comprimir_video(origen: Path, destino: Path) -> None:
    """Deja un mp4 mudo, corto y ligero, apto para reproducir en bucle."""
    subprocess.run(
        ["ffmpeg", "-hide_banner", "-loglevel", "error", "-y",
         "-i", str(origen),
         "-t", str(SEGUNDOS),
         "-an",                                    # sin audio: no se oye nunca
         "-vf", f"scale={ANCHO_VIDEO}:-2:flags=lanczos,eq=brightness=-0.16:saturation=1.1",
         "-c:v", "libx264", "-profile:v", "main", "-preset", "slow",
         "-crf", str(CRF), "-pix_fmt", "yuv420p",
         "-movflags", "+faststart",                # empieza a pintar sin el archivo entero
         str(destino)],
        check=True,
    )


def poster_del_video(origen: Path, destino: Path) -> None:
    subprocess.run(
        ["ffmpeg", "-hide_banner", "-loglevel", "error", "-y",
         "-i", str(origen), "-ss", "1", "-frames:v", "1", str(destino)],
        check=True,
    )


ESTILO_VIDEO = """
<style>
/* El vídeo ya trae su propio movimiento: encima el acercamiento del CSS
   se notaría como un temblor. */
.horno { animation: none; transform: scale(1.06); }
</style>
"""

GUION_VIDEO = """
/* El vídeo se engancha después y solo si toca. Hasta entonces —y para
   siempre, si el navegador dice que no— se queda el póster, que es la
   misma imagen que usa la versión de foto. */
(function () {
  var horno = document.querySelector('video.horno');
  if (!horno) return;

  var quieto = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var red = navigator.connection || {};
  var lenta = red.saveData === true || /(^|-)2g$/.test(red.effectiveType || '');

  /* Nadie abre una página de bio para gastarse los datos en un fondo. */
  if (quieto || lenta) return;

  horno.src = horno.getAttribute('data-src');
  var intento = horno.play();
  if (intento && intento.catch) { intento.catch(function () {}); }
})();
"""


def construir(salida: Path, foto: Path | None, video: Path | None) -> None:
    html = PLANTILLA.read_text(encoding="utf8")

    if foto:
        datos = tratar_foto(foto)
        (RECURSOS / "foto-horno.jpg").write_bytes(datos)
        print(f"  foto tratada: {len(datos) // 1024} kB")

    piezas = {
        "{{MA_MARK}}": uri_archivo(RECURSOS / "ma-marca.png"),
        "{{MA_LOCKUP}}": uri_archivo(RECURSOS / "ma-firma.png"),
        "{{RAPPI}}": uri_archivo(RECURSOS / "rappi.png"),
        "{{PEDIDOSYA}}": uri_archivo(RECURSOS / "pedidosya.png"),
    }

    if video:
        exigir_ffmpeg()
        nombre = salida.stem.replace("index", "fondo") or "fondo"
        destino_video = RAIZ / f"{nombre}.mp4"
        print(f"  comprimiendo {video.name} -> {destino_video.name}")
        comprimir_video(video, destino_video)
        print(f"  vídeo: {destino_video.stat().st_size // 1024} kB")

        temp = RAIZ / "herramientas" / "_poster.jpg"
        poster_del_video(destino_video, temp)
        piezas["{{FOTO_HORNO}}"] = uri(tratar_foto(temp), "image/jpeg")
        temp.unlink()

        piezas["{{CAPA_FONDO}}"] = (
            f'<video class="horno" data-src="{destino_video.name}" '
            'muted loop playsinline preload="none" '
            'disablepictureinpicture aria-hidden="true"></video>'
        )
        piezas["{{ESTILO_EXTRA}}"] = ESTILO_VIDEO
        piezas["{{GUION_EXTRA}}"] = GUION_VIDEO
    else:
        piezas["{{FOTO_HORNO}}"] = uri_archivo(RECURSOS / "foto-horno.jpg")
        piezas["{{CAPA_FONDO}}"] = '<div class="horno"></div>'
        piezas["{{ESTILO_EXTRA}}"] = ""
        piezas["{{GUION_EXTRA}}"] = ""

    for hueco, valor in piezas.items():
        html = html.replace(hueco, valor)

    sobrantes = [h for h in ("{{MA_MARK}}", "{{CAPA_FONDO}}") if h in html]
    if sobrantes:
        sys.exit(f"Quedaron huecos sin rellenar: {sobrantes}")

    salida.write_text(html, encoding="utf8")
    print(f"  {salida.name}: {len(html.encode()) // 1024} kB")


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--salida", default="index.html", help="archivo HTML a generar")
    p.add_argument("--foto", help="foto nueva para el fondo (se trata y se guarda)")
    p.add_argument("--video", help="vídeo para el fondo (se comprime aparte)")
    a = p.parse_args()

    for etiqueta, valor in (("--foto", a.foto), ("--video", a.video)):
        if valor and not Path(valor).is_file():
            sys.exit(f"No encuentro el archivo de {etiqueta}: {valor}")

    construir(RAIZ / a.salida,
              Path(a.foto) if a.foto else None,
              Path(a.video) if a.video else None)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
