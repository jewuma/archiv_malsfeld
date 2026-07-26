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
  public function createOrUpdate(string $parameter): JsonResponse {
    $schema = [
      "archivobjekt" => "object",
      "archivobjekt.id" => "integer,optional",
      "archivobjekt.ort_id" => "integer",
      "archivobjekt.zeitraum_start" => "string,emptyOK",
      "archivobjekt.zeitraum_ende" => "string,emptyOK",
      "archivobjekt.start_ergaenzung" => "string,emptyOK",
      "archivobjekt.ende_ergaenzung" => "string,emptyOK",
      "archivobjekt.themen_id" => "integer",
      "archivobjekt.titel" => "string",
      "archivobjekt.beschreibung" => "string,emptyOK",
      "archivobjekt.status" => "integer",
      "archivobjekt.archivdatum" => "date,optional",
      "analogobjekte" => "object,optional",
      "analogobjekte.*.id" => "integer,optional,nullOK",
      "analogobjekte.*.archiv_id" => "string",
      "analogobjekte.*.seiten" => "integer,nullOK",
      "analogobjekte.*.objekttyp_id" => "integer",
      "analogobjekte.*.gesperrt" => "boolean",
      "analogobjekte.*.gesperrt_bis" => "string,optional,nullOK",
      "analogobjekte.*.lagerort_id" => "integer",
      "analogobjekte.*.regal_id" => "integer,nullOK",
      "analogobjekte.*.fach_id" => "integer,nullOK",
      "analogobjekte.*.platz_id" => "integer,nullOK",
      "analogobjekte.*.digitalisiert" => "integer",
      "analogobjekte.*.dokumentendatum" => "string,optional,emptyOK",
      "dateien" => "object,optional",
      "dateien.*.id" => "integer,optional",
      "dateien.*.pfad" => "string",
      "dateien.*.dateiname" => "filename",
      "dateien.*.objekttyp_id" => "integer",
      "dateien.*.gesperrt" => "boolean",
      "dateien.*.gesperrt_bis" => "string,optional,nullOK",
      "dateien.*.dateidatum" => "string,emptyOK",
      "dateien.*.archivdatum" => "date,optional"
    ];

    $p = Validator::validateJsonAgainstSchema($parameter, $schema);
    $username = AppContext::getUsername();
    $isArchivobjektUpdate = isset($p["archivobjekt"]["id"]) && $p["archivobjekt"]["id"] > 0;
    if ($isArchivobjektUpdate) {
      $p["archivobjekt"]["aenderungsdatum"] = date("Y-m-d H:i:s");
      $p["archivobjekt"]["geaendert_durch"] = $username;
    } else {
      $p["archivobjekt"]["archivdatum"] = date("Y-m-d H:i:s");
      $p["archivobjekt"]["archiviert_durch"] = $username;
    }
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
          $isAnalogobjektUpdate = isset($analogobjekt["id"]) && $analogobjekt["id"] > 0;
          if ($isAnalogobjektUpdate) {
            $analogobjekt["aenderungsdatum"] = date("Y-m-d H:i:s");
            $analogobjekt["geaendert_durch"] = $username;
          } else {
            unset($analogobjekt["id"]);
            $analogobjekt["archivdatum"] = date("Y-m-d H:i:s");
            $analogobjekt["archiviert_durch"] = $username;
          }
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
          $isDateiUpdate = isset($datei["id"]) && $datei["id"] > 0;
          if ($isDateiUpdate) {
            $datei["aenderungsdatum"] = date("Y-m-d H:i:s");
            $datei["geaendert_durch"] = $username;
          } else {
            unset($datei["id"]);
            $datei["archivdatum"] = date("Y-m-d H:i:s");
            $datei["archiviert_durch"] = $username;
          }
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
