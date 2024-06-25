# DropAI Module

## Overview

The DropAI suite comprises modules that empower Drupal websites with AI capabilities, facilitating the transformation of Drupal's data model into a Language Model (LLM). It facilitates the creation of embeddings from Drupal content and metadata, enabling efficient content management and transformation into a vector database.

## Document Reader

This module provides integration with the following documents:

- PDF
- DOCX
- DOC
- ODT
- PPTX
- PPT
- ODP

The implementation allows managing different libraries for converting a document to plain text, some of them are:

### PDF Paser

The smalot/pdfparser is a standalone PHP package that provides various tools to extract data from PDF files.

This library is under active maintenance. There is no active development by the author of this library (at the moment), but we welcome any pull request adding/extending functionality!

**Documentation:**

For more information click [here](https://github.com/smalot/pdfparser).

### PHP Word

PHPWord is a library written in pure PHP that provides a set of classes to write to and read from different document file formats. The current version of PHPWord supports Microsoft [Office Open XML](http://en.wikipedia.org/wiki/Office_Open_XML) (OOXML or OpenXML), OASIS [Open Document Format for Office Applications](http://en.wikipedia.org/wiki/OpenDocument) (OpenDocument or ODF), [Rich Text Format](http://en.wikipedia.org/wiki/Rich_Text_Format) (RTF), HTML, and PDF.

PHPWord is an open source project licensed under the terms of [LGPL version 3](COPYING.LESSER). PHPWord is aimed to be a high quality software product by incorporating [continuous integration](https://github.com/PHPOffice/PHPWord/actions) and unit testing. You can learn more about PHPWord by reading the [Developers' Documentation](https://phpoffice.github.io/PHPWord/).

**Documentation:**

For more information click [here](https://github.com/PHPOffice/PHPWord).

### PHP Presentation

PHPPresentation is a library written in pure PHP that provides a set of classes to write to different presentation file formats, i.e. Microsoft [Office Open XML](http://en.wikipedia.org/wiki/Office_Open_XML) (OOXML or OpenXML) or OASIS [Open Document Format for Office Applications](http://en.wikipedia.org/wiki/OpenDocument) (OpenDocument or ODF).

PHPPresentation is an open source project licensed under the terms of [LGPL version 3](https://github.com/PHPOffice/PHPPresentation/blob/develop/COPYING.LESSER). PHPPresentation is aimed to be a high quality software product by incorporating [continuous integration](https://github.com/PHPOffice/PHPPresentation/actions/workflows/php.yml) and [unit testing](https://coveralls.io/github/PHPOffice/PHPPresentation). You can learn more about PHPPresentation by reading the [Developers' Documentation](https://phpoffice.github.io/PHPPresentation) and the [API Documentation](https://phpoffice.github.io/PHPPresentation/docs/).

**Documentation:**

For more information click [here](https://github.com/PHPOffice/PHPPresentation).