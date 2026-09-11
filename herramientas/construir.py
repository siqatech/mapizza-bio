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
import tempfile
import urllib.parse
import urllib.request
from pathlib import Path

from PIL import Image, ImageEnhance, ImageFilter, ImageOps

RAIZ = Path(__file__).resolve().parent.parent
RECURSOS = RAIZ / "herramientas" / "recursos"
PLANTILLA = RAIZ / "herramientas" / "plantilla.html"

# --- tratamiento de la foto de fondo -------------------------------------
# Oscurecer y desenfocar aquí, y no en CSS, es lo que la baja de 77 kB a 28.
# Como la capa se ve al 50 % y casi toda enmascarada, la nitidez no se echa
# en falta. La saturación SUBE: desaturar sobre negro da gris plomo.
ANCHO_FOTO = 820
DESENFOQUE = 0.6      # antes 1.7, que ahorraba bytes pero dejaba la pizza
                      # en una mancha de color sin nada reconocible
CALIDAD = 44
BRILLO = 0.78        # antes 0.44: entre esto, la opacidad y la máscara,
                     # de la pizza no quedaba nada que mirar
COLOR = 1.20

# --- el retrato de la familia ---
# Va en la segunda vista, tras el fundido de la foto. Se vira a cálido
# porque el original es en blanco y negro y un gris puro, en una página
# de negros y dorados, se sale de la paleta.
ANCHO_RETRATO = 780
CALIDAD_RETRATO = 72
VIRADO_SOMBRAS = (26, 20, 14)
VIRADO_LUCES = (250, 242, 226)

# --- compresión del vídeo -------------------------------------------------
ANCHO_VIDEO = 960          # se recorta con object-fit: cover, no hace falta más
DESDE = 1                  # el primer segundo suele venir con el encuadre aún fijo
SEGUNDOS = 7               # se duplica al montar la ida y vuelta: salen 14 de bucle
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


def traer(ruta: str) -> Path:
    """Acepta una ruta local o una URL. Los originales del cliente viven en
    su propio servidor, así que pedirlos por http ahorra el paso de
    descargarlos a mano."""
    if not ruta.startswith(("http://", "https://")):
        destino = Path(ruta)
        if not destino.is_file():
            sys.exit(f"No encuentro el archivo: {ruta}")
        return destino

    sufijo = Path(urllib.parse.urlparse(ruta).path).suffix or ".bin"
    temporal = Path(tempfile.mkdtemp()) / f"descarga{sufijo}"
    print(f"  descargando {ruta}")
    with urllib.request.urlopen(ruta, timeout=120) as respuesta, \
            temporal.open("wb") as f:
        shutil.copyfileobj(respuesta, f)
    print(f"  descargado: {temporal.stat().st_size // 1024} kB")
    return temporal


def tratar_retrato(origen: Path) -> bytes:
    """Vira el retrato a cálido y lo deja a un tamaño razonable."""
    im = Image.open(origen).convert("RGB")
    im = ImageOps.exif_transpose(im)
    if im.width > ANCHO_RETRATO:
        im = im.resize((ANCHO_RETRATO, round(im.height * ANCHO_RETRATO / im.width)),
                       Image.LANCZOS)
    im = ImageOps.colorize(ImageOps.grayscale(im), VIRADO_SOMBRAS, VIRADO_LUCES)
    im = ImageEnhance.Brightness(im).enhance(0.92)
    buf = io.BytesIO()
    im.save(buf, "JPEG", quality=CALIDAD_RETRATO, optimize=True, progressive=True)
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
    """Deja un mp4 mudo, corto y ligero, que enlaza consigo mismo.

    El trozo se monta con su propio reverso detrás. Un corte seco vuelve al
    primer fotograma de golpe y ese salto se ve cada pocos segundos; yendo y
    volviendo, el final ya ES el principio y el bucle no tiene costura. En un
    fondo al 50 % y difuminado, que el movimiento se deshaga no se lee como
    marcha atrás, sino como un vaivén.
    """
    # Mismo tratamiento que la foto, y en el mismo orden: desenfocar,
    # multiplicar el brillo y subir la saturación. Si el vídeo no coincidiera
    # con su póster, al arrancar se vería un salto de luz. lutrgb multiplica,
    # que es lo que hace Pillow; eq=brightness sumaría, que es otra cosa.
    tratado = (
        f"scale={ANCHO_VIDEO}:-2:flags=lanczos,"
        f"gblur=sigma={DESENFOQUE},"
        "format=rgb24,"
        f"lutrgb=r='val*{BRILLO}':g='val*{BRILLO}':b='val*{BRILLO}',"
        "format=yuv420p,"
        f"eq=saturation={COLOR},"
        "setsar=1"
    )
    subprocess.run(
        ["ffmpeg", "-hide_banner", "-loglevel", "error", "-y",
         "-ss", str(DESDE), "-t", str(SEGUNDOS), "-i", str(origen),
         "-an",                                    # sin audio: no se oye nunca
         "-filter_complex",
         f"[0:v]{tratado},split[ida][vuelta];[vuelta]reverse[atras];[ida][atras]concat=n=2:v=1[v]",
         "-map", "[v]",
         "-c:v", "libx264", "-profile:v", "main", "-preset", "slow",
         "-crf", str(CRF), "-pix_fmt", "yuv420p",
         "-movflags", "+faststart",                # empieza a pintar sin el archivo entero
         str(destino)],
        check=True,
    )


def poster_del_video(origen: Path, destino: Path) -> None:
    """Saca el fotograma del vídeo ORIGINAL, no del comprimido.

    Del comprimido saldría ya oscurecido y tratar_foto lo oscurecería otra
    vez, así que el póster quedaba mucho más oscuro que el vídeo y al
    arrancar la reproducción la página pegaba un salto de brillo.
    """
    subprocess.run(
        ["ffmpeg", "-hide_banner", "-loglevel", "error", "-y",
         "-ss", str(DESDE), "-i", str(origen), "-frames:v", "1", str(destino)],
        check=True,
    )


ESTILO_VIDEO = """
/* El vídeo ya trae su propio movimiento: encima el acercamiento del CSS
   se notaría como un temblor. */
.horno { animation: none; transform: scale(1.06); }
"""

# En vertical, 'cover' se queda con la franja central del 16:9 y tira el
# resto. Si el plato no está centrado en el original, el móvil enseña
# justo lo que no interesa: en el vídeo del giro, la mesa vacía.
ESTILO_ENCUADRE = """
.horno {
  object-position: %s;
  background-position: %s;
}
"""


def envolver_estilo(*trozos: str) -> str:
    cuerpo = "".join(t for t in trozos if t)
    return f"\n<style>{cuerpo}</style>\n" if cuerpo.strip() else ""

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


SIN_RETRATO = """
/* Sin retrato, la historia se cuenta sobre el negro y ya. */
.retrato { display: none; }
"""


def construir(salida: Path, foto: Path | None, video: Path | None,
              encuadre: str | None, retrato: Path | None) -> None:
    html = PLANTILLA.read_text(encoding="utf8")

    if foto:
        datos = tratar_foto(foto)
        (RECURSOS / "foto-horno.jpg").write_bytes(datos)
        print(f"  foto tratada: {len(datos) // 1024} kB")

    if retrato:
        datos = tratar_retrato(retrato)
        (RECURSOS / "familia.jpg").write_bytes(datos)
        print(f"  retrato tratado: {len(datos) // 1024} kB")

    hay_retrato = (RECURSOS / "familia.jpg").is_file()

    piezas = {
        # Envuelto en url() aquí y no en la plantilla, porque sin retrato el
        # valor tiene que ser 'none' a secas: un data URI pelado hace que
        # background-image lo descarte y no se vea nada, sin dar ningún error.
        "{{RETRATO}}": (f'url("{uri_archivo(RECURSOS / "familia.jpg")}")'
                        if hay_retrato else "none"),
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
        poster_del_video(video, temp)
        piezas["{{FOTO_HORNO}}"] = uri(tratar_foto(temp), "image/jpeg")
        temp.unlink()

        piezas["{{CAPA_FONDO}}"] = (
            f'<video class="horno" data-src="{destino_video.name}" '
            'muted loop playsinline preload="none" '
            'disablepictureinpicture aria-hidden="true"></video>'
        )
        piezas["{{ESTILO_EXTRA}}"] = envolver_estilo(
            ESTILO_VIDEO,
            ESTILO_ENCUADRE % (encuadre, encuadre) if encuadre else "",
            "" if hay_retrato else SIN_RETRATO)
        piezas["{{GUION_EXTRA}}"] = GUION_VIDEO
    else:
        piezas["{{FOTO_HORNO}}"] = uri_archivo(RECURSOS / "foto-horno.jpg")
        piezas["{{CAPA_FONDO}}"] = '<div class="horno"></div>'
        piezas["{{ESTILO_EXTRA}}"] = envolver_estilo(
            ESTILO_ENCUADRE % (encuadre, encuadre) if encuadre else "",
            "" if hay_retrato else SIN_RETRATO)
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
    p.add_argument("--foto", help="foto nueva para el fondo; ruta o URL")
    p.add_argument("--video", help="vídeo para el fondo; ruta o URL")
    p.add_argument("--retrato",
                   help="foto de la familia para la historia; ruta o URL")
    p.add_argument("--encuadre",
                   help='qué parte del fondo se ve en vertical, en formato '
                        'object-position (por ejemplo "30%% 52%%"). El móvil '
                        'recorta la franja central del 16:9, así que si el '
                        'plato no está centrado hay que decírselo.')
    a = p.parse_args()

    construir(RAIZ / a.salida,
              traer(a.foto) if a.foto else None,
              traer(a.video) if a.video else None,
              a.encuadre,
              traer(a.retrato) if a.retrato else None)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
