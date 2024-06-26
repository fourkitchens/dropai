<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dropai\Plugin\DropaiLoaderBase;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Smalot\PdfParser\Parser;


/**
 * Provides a DropAI Loader plugin that loads PDF documents.
 *
 * @DropaiLoader(
 *   id = "pdf",
 *   label = @Translation("PDF")
 * )
 */
class FilePdfLoader extends DropaiLoaderBase {

  /**
   * {@inheritdoc}
   */
  public static function applies(EntityInterface $entity) {
    // Do not include file loaders in list of plugins. Instead we are using the
    // DocumentLoader plugin as a factory.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function load(FileInterface $entity): string {
    $content = '';
    $parser = new Parser();

    try {
      $pdfDoc = $parser->parseFile($filePath);
      $content = $pdfDoc->getText();
    } catch (Exception $e) { }

    return $content;
  }

}
