# DropAI Python

This module allows for Python interaction from Drupal to pre-process, tokenize, and split node HTML. It also extracts text from PDF's (including images), Microsoft Word documents, and PowerPoint documents.

## Installation

Since this module requires Python to be installed alongside Drupal, changes are required to your local Lando configuration.

1. Open `python/add-to-lando.yml` and copy the required key/values into your root `.lando.yml` or `.lando.local.yml`. Save the file.
2. Run `lando rebuild` to get the new containers and configuration.
3. Once finished, from the root of the project run `lando python web/modules/contrib/dropai/modules/dropai_python/python/app.py`
4. This will start the local Python Flask app. You may open another terminal session to perform other terminal operations while the Flask app runs in its own terminal.
5. Enable the `dropai_python` module in Drupal as normal via the UI or `lando drush en dropai_python`
6. Visit the settings page at `/admin/config/dropai/python` and verify that the Python server is set to the same one in your Lando configuration under `proxy`.

## Usage - Node HTML Processing

1. Visit a node view page and click on the AI Inspect tab (or visit `/node/[nid]/inspect`).
2. Change the Preprocessor to "Python Plain Text". The code window to the right of the form changes with plain text processed from Python.
3. There is also a Python-based tokenizer and splitter on the same page.

## Usage - Media Document Content

1. For documents, visit a document edit page (`/media/[mid]/edit`) and click on "Content (via Python)".
2. The text below the title will change to the content from the document.
3. Currently this module only supports PDF, Word, and PowerPoint documents.
