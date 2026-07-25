<?php

namespace own;

use own\DbAccess;
//use own\JsonResponse;
use own\ArchivDb;

class Archivobjekte extends DbAccess {
  public function getTableName(): string {
    return "archivobjekte";
  }
  public function saveDataset(array $data): array {
    return ArchivDb::saveDataset($this->getTableName(), $data);
  }
}
