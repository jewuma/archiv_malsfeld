<?php

namespace own;

use own\DbAccess;
use own\JsonResponse;

class Analogobjekte extends DbAccess
{
  public function getTableName(): string
  {
    return "analogobjekte";
  }
  public function getByArchivId(int|string $archivId): JsonResponse
  {
    $archivId = (int) $archivId;
    return ArchivDb::preparedWebQuery("SELECT * FROM analogobjekte WHERE archiv_id=? LIMIT 1", [$archivId]);
  }
}