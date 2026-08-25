<?php

namespace own;

use own\ArchivDb;
use own\JsonResponse;
use own\Validator;
use own\Archivobjekte;
use own\Analogobjekte;
use own\Dateien;
use own\AppContext;

class Archiveingang {
  private \PDO $db;
  public function __construct() {
    $this->db = ArchivDb::getDbInstance();
  }
}
