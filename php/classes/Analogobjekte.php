<?php

namespace own;

use own\DbAccess;
use own\JsonResponse;

class Analogobjekte extends DbAccess {
  public function getTableName(): string {
    return "analogobjekte";
  }
  public function getByArchivId(int|string $archivId): JsonResponse {
    $archivId = (int) $archivId;
    return ArchivDb::preparedWebQuery(
      "SELECT 
      an.id as an_id,
      an.archiv_id as archiv_id,
      an.seiten as seiten,
      an.objekttyp_id as objekttyp_id,
      an.quellen_id as quellen_id,
      an.lagerort_id as lagerort_id,
      an.regal_id as regal_id,
      an.fach_id as fach_id,
      an.platz_id as platz_id,
      an.gesperrt as gesperrt,
      an.gesperrt_bis as gesperrt_bis,
      an.digitalisiert as digitalisiert,
      an.dokumentendatum as dokumentendatum,
      ao.id as id,
      ao.ort_id as ort_id,
      ao.zeitraum_start as zeitraum_start,
      ao.zeitraum_ende as zeitraum_ende,
      ao.titel as titel,
      ao.themen_id as themen_id,
      ao.beschreibung as beschreibung
       FROM analogobjekte an 
        LEFT JOIN archivobjekte ao ON an.archivobjekt_id=ao.id WHERE an.archiv_id=? LIMIT 1",
      [$archivId]
    );
  }
}
