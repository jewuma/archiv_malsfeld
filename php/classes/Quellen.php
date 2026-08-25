<?php

namespace own;

use own\DbAccess;
use own\ArchivDb;

class Quellen extends DbAccess {
  public function getTableName(): string {
    return "quellen";
  }
  public function getSelector(): JsonResponse {
    return ArchivDb::webQuery("SELECT id, CONCAT_WS(', ', `name`, `vorname`) AS display
      FROM quellen ORDER BY `name`, `vorname`");
  }
}
