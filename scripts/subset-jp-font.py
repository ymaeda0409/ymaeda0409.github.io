"""Builds the bundled Japanese font subsets (Flutter web cannot fall back to system
fonts offline). Covers kana, JIS level-1 kanji (~3,000 common characters), full-width
forms and every character used by the app's own strings, so names typed in the admin
render too. Usage: python3 scripts/subset-jp-font.py <NotoSansJP variable .ttf> <app dir>...
"""
import glob
import json
import os
import sys

from fontTools import subset
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer

source, apps = sys.argv[1], sys.argv[2:]
chars = set()
for lo, hi in [(0x3000, 0x303F), (0x3040, 0x309F), (0x30A0, 0x30FF), (0xFF01, 0xFF9F)]:
    chars.update(map(chr, range(lo, hi + 1)))
for row in range(16, 48):  # JIS X 0208 level-1 kanji
    for cell in range(1, 95):
        try:
            chars.add(bytes([0xA0 + row, 0xA0 + cell]).decode('euc_jp'))
        except UnicodeDecodeError:
            pass


def walk(o):
    if isinstance(o, str):
        chars.update(o)
    elif isinstance(o, dict):
        for v in o.values():
            walk(v)
    elif isinstance(o, list):
        for v in o:
            walk(v)


for app in apps:
    for f in glob.glob(f'{app}/lib/l10n/*.arb') + glob.glob(f'{app}/assets/demo/*.json'):
        walk(json.load(open(f, encoding='utf-8')))
text = ''.join(sorted(c for c in chars if ord(c) > 0x2E7F))
print('characters', len(text))

for weight, name in [(400, 'Regular'), (700, 'Bold')]:
    font = instancer.instantiateVariableFont(TTFont(source), {'wght': weight})
    options = subset.Options()
    options.layout_features = ['*']
    options.name_IDs = ['*']
    options.notdef_outline = True
    subsetter = subset.Subsetter(options)
    subsetter.populate(text=text)
    subsetter.subset(font)
    for app in apps:
        out = f'{app}/assets/fonts/NotoSansJP-{name}.ttf'
        os.makedirs(os.path.dirname(out), exist_ok=True)
        font.save(out)
        print(out, os.path.getsize(out))
