# -*- coding: utf-8 -*-
import sys, os
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer

sys.path.append(os.path.abspath('../../../../../_exts'))

extensions = ['sphinxcontrib.phpdomain', 'configurationblock']
master_doc = 'index'
highlight_language = 'php'

project = u'Silex'
copyright = u'2010-11 Fabien Potencier :: Traducido por Nacho Pacheco'

version = '0'
release = '0.0.0'
html_title = u'Manual de Silex en Español'
html_short_title = u'Silex en Español'
language = 'es'
exclude_patterns = ['_build']
pygments_style = 'native'
html_theme = 'tnp'
templates_path = ['_templates']
html_theme_path = ['../../../../../_themes']
html_static_path = ['../../../../../tnp/_static']
html_favicon = 'icotnp.ico'
html_show_sourcelink = False


lexers['php'] = PhpLexer(startinline=True)
