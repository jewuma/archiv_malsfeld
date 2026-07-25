<?php

namespace own;

use own\ArchivDb;
use own\JsonResponse;
use own\Validator;
use own\Archivobjekte;
use own\Analogobjekte;
use own\Dateien;

class Archiveingang {
  private \PDO $db;
  public function __construct() {
    $this->db = ArchivDb::getDbInstance();
  }
  public function createOrUpdate(string $parameter): JsonResponse {
    $schema = [
      "archivobjekt" => "object",
      "archivobjekt.id" => "integer,optional",
      "archivobjekt.ort_id" => "integer",
      "archivobjekt.zeitraum_start" => "string",
      "archivobjekt.zeitraum_ende" => "string",
      "archivobjekt.start_ergaenzung" => "string,optional",
      "archivobjekt.ende_ergaenzung" => "string,optional",
      "archivobjekt.themen_id" => "integer",
      "archivobjekt.titel" => "string",
      "archivobjekt.beschreibung" => "string,emptyOK",
      "archivobjekt.status" => "integer",
      "archivobjekt.archivdatum" => "date,optional",
      "analogobjekte" => "object,optional",
      "analogobjekte.*.id" => "integer,optional",
      "analogobjekte.*.archiv_id" => "string",
      "analogobjekte.*.seiten" => "integer",
      "analogobjekte.*.objekttyp_id" => "integer",
      "analogobjekte.*.lagerort_id" => "integer",
      "analogobjekte.*.regal_id" => "integer",
      "analogobjekte.*.fach_id" => "integer",
      "analogobjekte.*.platz_id" => "integer",
      "analogobjekte.*.digitalisiert" => "integer",
      "analogobjekte.*.dokumentendatum" => "string,emptyOK",
      "dateien" => "object,optional",
      "dateien.*.id" => "integer,optional",
      "dateien.*.pfad" => "string",
      "dateien.*.dateiname" => "filename",
      "dateien.*.objekttyp_id" => "integer",
      "dateien.*.gesperrt" => "boolean",
      "dateien.*.gesperrt_bis" => "string,optional",
      "dateien.*.dateidatum" => "string,emptyOK",
      "dateien.*.archivdatum" => "date,optional"
    ];
    $p = Validator::validateJsonAgainstSchema($parameter, $schema);
    $this->db->beginTransaction();
    try {
      $archivObjektId = null;
      if (isset($p["archivobjekt"]["id"])) {
        $archivObjektId = $p["archivobjekt"]["id"];
        if ($archivObjektId > 0) {
          $this->updateArchivobjekt($archivObjektId, $p["archivobjekt"]);
        } else {
          throw new \Exception("Ungültige Archivobjekt-ID");
        }
      } else {
        $archivObjektId = $this->createArchivobjekt($p["archivobjekt"]);
      }
      if (isset($p["analogobjekte"])) {
        foreach ($p["analogobjekte"] as &$analogobjekt) {
          $analogobjekt["archivobjekt_id"] = $archivObjektId;
          if (isset($analogobjekt["id"]) && $analogobjekt["id"] > 0) {
            // Update existing analogobjekt
            $this->updateAnalogobjekt($analogobjekt["id"], $analogobjekt);
          } else {
            // Create new analogobjekt
            $this->createAnalogobjekt($analogobjekt);
          }
        }
      }
      if (isset($p["dateien"])) {
        foreach ($p["dateien"] as &$datei) {
          $datei["archivobjekt_id"] = $archivObjektId;
          if (isset($datei["id"]) && $datei["id"] > 0) {
            // Update existing datei
            $this->updateDatei($datei["id"], $datei);
          } else {
            // Create new datei
            $this->createDatei($datei);
          }
        }
      }
    } catch (\Exception $e) {
      $this->db->rollBack();
      throw $e;
    }
    $this->db->commit();
    return JsonResponse::success($p);
  }
  private function createArchivobjekt(array $archivObjektData): int {
    $ao = new Archivobjekte();
    $result = $ao->saveDataset($archivObjektData);
    $archivObjektId = $result["id"];
    return $archivObjektId;
  }
  private function updateArchivobjekt(int $archivObjektId, array $archivObjektData): void {
    $archivObjektData["id"] = $archivObjektId;
    $ao = new Archivobjekte();
    $ao->save($archivObjektData, true);
  }
  private function createAnalogobjekt(array $analogobjektData): int {
    $an = new Analogobjekte();
    $result = $an->save($analogobjektData);
    return $result->data["id"];
  }
  private function updateAnalogobjekt(int $analogobjektId, array $analogobjektData): void {
    $analogobjektData["id"] = $analogobjektId;
    $an = new Analogobjekte();
    $an->save($analogobjektData, true);
  }
  private function createDatei(array $dateiData): int {
    $d = new Dateien();
    $result = $d->save($dateiData);
    return $result->data["id"];
  }
  private function updateDatei(int $dateiId, array $dateiData): void {
    $dateiData["id"] = $dateiId;
    $d = new Dateien();
    $d->save($dateiData, true);
  }
}
