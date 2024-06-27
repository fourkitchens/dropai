<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\dropai\Plugin\DropaiLoader\FileLoader;
use Smalot\PdfParser\Parser;

/**
 * Provides a DropAI Loader plugin that loads PDF documents.
 *
 * @DropaiLoader(
 *   id = "pdf",
 *   label = @Translation("PDF"),
 *   mime_type = "application/pdf"
 * )
 */
class FilePdfLoader extends FileLoader {

  /**
   * {@inheritdoc}
   */
  public static function applies(ContentEntityInterface $entity) {
    return self::isFile($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(ContentEntityInterface $entity): string {
    $content = '';
    if (!self::isFile($entity)) {
      return '';
    }
    try {
      $parser = new Parser();
      $pdfDoc = $parser->parseFile($this->getFilePath($entity));
      $content = $pdfDoc->getText();
    }
    catch (\Exception $e) {
      // @todo: catch errors.
    }
    return $content;
  }

}
