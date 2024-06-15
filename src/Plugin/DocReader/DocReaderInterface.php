<?php

namespace Drupal\dropai\Plugin\DocReader;

interface DocReaderInterface {
  public function getText($filePath);
}
