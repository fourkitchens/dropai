<?php

namespace Drupal\dropai\Plugin\PdfReader;

interface DocReaderInterface {
  public function getText($filePath);
}
