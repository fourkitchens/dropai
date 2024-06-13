# DropAI Module

## Overview

The DropAI suite comprises modules that empower Drupal websites with AI capabilities, facilitating the transformation of Drupal's data model into a Language Model (LLM). It facilitates the creation of embeddings from Drupal content and metadata, enabling efficient content management and transformation into a vector database.

## PDF Reader

This module provides integration with PDF files, its implementation allows managing different libraries for converting a PDF to plain text, some of them:

### PDF Paser:

The smalot/pdfparser is a standalone PHP package that provides various tools to extract data from PDF files.

This library is under active maintenance. There is no active development by the author of this library (at the moment), but we welcome any pull request adding/extending functionality!

**Installation:**

Add the library to composer.

`lando composer require smalot/pdfparser`

Autoload the library to the project, you need to add these lines to the composer.json file:

```
  "autoload": {
    "psr-4": {
      "Smalot\\PdfParser\\": "vendor/smalot/pdfparser/src"
    }
  }
```

**Documentation:**

For more information click [here](https://github.com/smalot/pdfparser).
