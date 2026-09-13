#!/usr/bin/env python3
"""Pasa el CSS de la página suelta a CSS de plugin: nombres propios y todo
colgando de .mapb, sin una sola regla que pueda tocar el resto del sitio.

    python3 herramientas/a-plugin.py entrada.css salida.css

Se ejecuta cuando cambia el diseño de la plantilla suelta, para volcar ese
cambio al plugin sin reescribir 800 líneas a mano.
"""
import re, sys
from pathlib import Path

RAIZ = ".mapb"
PREFIJO = "mapb-"

CLASES = ['apunte','asoma','bmp','brasa','brasas','cabecera','cara','cierre','datos',
          'destello','dias','enlaces','entra','envoltura','filete','firma','firma-alta',
          'fondo','historia','horario','lema','logo-icono','logo-pedidosya','logo-rappi',
          'marco-retrato','pie','rango','reclamos','retrato','senuelo','tarjeta','vista',
          'vista--historia','vista--pedidos','voz','voz--coro','dentro','pulsa','ido',
          'js','lienzo','proximamente','etiqueta','logo-imagen',
          # .horno no aparecía en ningún class="..." de la plantilla porque su
          # elemento se inyecta desde el build. Se quedó sin renombrar y su
          # regla dejó de casar con el marcado: el fondo salía con altura 0 y
          # sin imagen, sin dar ningún error. De ahí la comprobación de abajo.
          'horno', 'marco', 'rotulo-boton', 'tarjeta--apagada', 'horno--video']

# Variables que @property registra a lo bruto en todo el documento: esas sí
# necesitan apellido, o chocan con las del tema.
VARS_GLOBALES = {'--giro': '--mapb-giro', '--luz': '--mapb-luz'}


def renombrar_clases(txt: str) -> str:
    for c in sorted(CLASES, key=len, reverse=True):
        txt = re.sub(r'\.' + re.escape(c) + r'(?![\w-])', '.' + PREFIJO + c, txt)
    return txt


def renombrar_animaciones(css: str) -> tuple[str, list[str]]:
    nombres = re.findall(r'@keyframes\s+([\w-]+)', css)
    for n in nombres:
        css = re.sub(r'(@keyframes\s+)' + re.escape(n) + r'\b', r'\1' + PREFIJO + n, css)
        css = re.sub(r'(animation(?:-name)?\s*:[^;]*?\b)' + re.escape(n) + r'\b',
                     r'\1' + PREFIJO + n, css)
    return css, nombres


def renombrar_vars(txt: str) -> str:
    for viejo, nuevo in VARS_GLOBALES.items():
        txt = re.sub(re.escape(viejo) + r'(?![\w-])', nuevo, txt)
    return txt


def partir_bloques(css: str):
    """Devuelve (prologo, selector_o_regla, cuerpo) recorriendo el nivel actual."""
    piezas, i, n, inicio = [], 0, len(css), 0
    while i < n:
        if css[i] == '{':
            cabeza = css[inicio:i]
            prof, j = 1, i + 1
            while j < n and prof:
                if css[j] == '{': prof += 1
                elif css[j] == '}': prof -= 1
                j += 1
            piezas.append((cabeza, css[i + 1:j - 1]))
            i = inicio = j
        else:
            i += 1
    cola = css[inicio:]
    return piezas, cola


def prefijar_selector(sel: str) -> str | None:
    sel = sel.strip()
    partes = []
    for s in sel.split(','):
        s = s.strip()
        if not s:
            continue
        if s in ('html', 'body'):
            return None                       # se tratan aparte
        if s == ':root':
            partes.append(RAIZ)
        elif s == '*':
            partes += [f'{RAIZ} *', f'{RAIZ} *::before', f'{RAIZ} *::after']
        elif s.startswith(RAIZ + '-') or s == RAIZ:
            partes.append(f'{RAIZ} {s}' if s != RAIZ else RAIZ)
        else:
            partes.append(f'{RAIZ} {s}')
    return ',\n'.join(partes) if partes else None


def procesar(css: str, sangria: str = '') -> str:
    piezas, cola = partir_bloques(css)
    salida = []
    for cabeza, cuerpo in piezas:
        comentarios = ''
        m = re.match(r'^((?:\s*/\*.*?\*/\s*)*)(.*)$', cabeza, re.S)
        if m:
            comentarios, cabeza = m.group(1), m.group(2)
        cabeza = cabeza.strip()

        if cabeza.startswith('@keyframes') or cabeza.startswith('@property'):
            salida.append(f'{comentarios}{cabeza} {{{cuerpo}}}')
        elif cabeza.startswith('@media') or cabeza.startswith('@supports'):
            salida.append(f'{comentarios}{cabeza} {{\n{procesar(cuerpo)}\n}}')
        else:
            sel = prefijar_selector(cabeza)
            if sel is None:
                continue                      # html/body: fuera
            salida.append(f'{comentarios}{sel} {{{cuerpo}}}')
    if cola.strip():
        salida.append(cola.strip())
    return '\n'.join(salida)


def main() -> int:
    css = Path(sys.argv[1]).read_text(encoding='utf8')
    css = renombrar_clases(css)
    css = renombrar_vars(css)
    css, anims = renombrar_animaciones(css)
    css = procesar(css)
    Path(sys.argv[2]).write_text(css, encoding='utf8')
    print(f'animaciones renombradas: {anims}')
    print(f'salida: {len(css.splitlines())} líneas')
    # Dos redes de seguridad, y las dos han hecho falta.
    fuera = [l for l in css.splitlines()
             if re.match(r'^[^\s@/].*\{', l) and not l.startswith(('.mapb', ','))]
    print('reglas fuera de .mapb:', fuera if fuera else 'ninguna')

    # Una clase sin renombrar no da error en ningún sitio: simplemente deja de
    # casar con el marcado y ese trozo del diseño desaparece en silencio.
    limpio = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    sueltas = sorted({
        c for c in re.findall(r'\.([A-Za-z_][\w-]*)', limpio)
        if not c.startswith('mapb') and c not in ('org', 'w3')
    })
    print('clases sin prefijo:', sueltas if sueltas else 'ninguna')

    if fuera or sueltas:
        print('\nEl CSS no está listo para el plugin.')
        return 1

    return 0


if __name__ == '__main__':
    raise SystemExit(main())
