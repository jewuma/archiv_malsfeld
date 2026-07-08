<?php

namespace own;

class FileResponse {
  public function __construct(
    public string $fileName,
    public string $mimeType,
    public ?string $fileContent = null,
  ) {
  }
}
