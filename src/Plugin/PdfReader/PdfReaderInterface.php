<?php

namespace Drupal\dropai\Plugin\PdfReader;

interface PdfReaderInterface {
  public function getText($filePath);
}
