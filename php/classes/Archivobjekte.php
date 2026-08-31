<?php

namespace own;

use own\DbAccess;
use own\JsonResponse;
use own\ArchivDb;
use own\Validator;
use own\ArchivFiles;

class Archivobjekte extends DbAccess {
  public function getTableName(): string {
    return "archivobjekte";
  }
  public static function deleteIfOrphaned(int $id): JsonResponse {
    $db = ArchivDb::getDbInstance();
    $analogQuery = "SELECT COUNT(*) as child_count FROM analogobjekte WHERE archivobjekt_id = :id";
    $stmt = $db->prepare($analogQuery);
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    $digitalQuery = "SELECT COUNT(*) as child_count FROM digitalobjekte WHERE archivobjekt_id = :id";
    $stmt = $db->prepare($digitalQuery);
    $stmt->execute(['id' => $id]);
    $digitalResult = $stmt->fetch();
    $totalChildCount = ($result['child_count'] ?? 0) + ($digitalResult['child_count'] ?? 0);
    if ($totalChildCount === 0) {
      $stmt = $db->prepare("DELETE FROM archivobjekte WHERE id = :id");
      $stmt->execute(['id' => $id]);
    }
    return JsonResponse::success($totalChildCount === 0);
  }
  public function saveDataset(array $data): array {
    return ArchivDb::saveDataSet($this->getTableName(), $data);
  }
  public function saveOrUpdate(string $parameter): JsonResponse {
    $schema = [
      "archivObjekt" => "object",
      "archivObjekt.id" => "integer,optional",
      "archivObjekt.ort_id" => "integer",
      "archivObjekt.zeitraum_start" => "string,emptyOK",
      "archivObjekt.zeitraum_ende" => "string,emptyOK",
      "archivObjekt.start_ergaenzung" => "string,emptyOK",
      "archivObjekt.ende_ergaenzung" => "string,emptyOK",
      "archivObjekt.schlagworte" => "array,optional",
      "archivObjekt.schlagworte.*" => "integer,optional",
      "archivObjekt.titel" => "string",
      "archivObjekt.beschreibung" => "string,emptyOK",
      "archivObjekt.dateiPfad" => "string,optional,emptyOK",
      "archivObjekt.status" => "integer",
      "archivObjekt.archivdatum" => "date,optional",
      "archivObjekt.archivOption" => "string,emptyOK",
      "archivObjekt.sourceFiles" => "array,optional",
      "archivObjekt.analogObjekte" => "array,optional",
      "archivObjekt.analogObjekte.*.id" => "integer,optional,nullOK",
      "archivObjekt.analogObjekte.*.archiv_id" => "string",
      "archivObjekt.analogObjekte.*.seiten" => "integer,nullOK",
      "archivObjekt.analogObjekte.*.objekttyp_id" => "integer",
      "archivObjekt.analogObjekte.*.gesperrt" => "boolean",
      "archivObjekt.analogObjekte.*.gesperrt_bis" => "string,optional,nullOK",
      "archivObjekt.analogObjekte.*.lagerort_id" => "integer,nullOK",
      "archivObjekt.analogObjekte.*.regal_id" => "integer,nullOK",
      "archivObjekt.analogObjekte.*.fach_id" => "integer,nullOK",
      "archivObjekt.analogObjekte.*.platz_id" => "integer,nullOK",
      "archivObjekt.analogObjekte.*.digitalisiert" => "integer",
      "archivObjekt.analogObjekte.*.dokumentendatum" => "string,optional,nullOK",
      "archivObjekt.digitalObjekte" => "array,optional",
      "archivObjekt.digitalObjekte.*.id" => "integer,optional",
      "archivObjekt.digitalObjekte.*.gesperrt" => "boolean",
      "archivObjekt.digitalObjekte.*.gesperrt_bis" => "string,optional,nullOK",
      "archivObjekt.digitalObjekte.*.dateidatum" => "date,nullOK",
      "archivObjekt.digitalObjekte.*.quellen_id" => "integer,nullOK",
      "archivObjekt.digitalObjekte.*.sourcefilepath" => "string,emptyOK",
    ];

    $p = Validator::validateJsonAgainstSchema($parameter, $schema);
    $archivObjekt = $p["archivObjekt"];
    $isArchivobjektUpdate = isset($archivObjekt["id"]) && $archivObjekt["id"] > 0;
    $this->updateDate($archivObjekt, $isArchivobjektUpdate);
    if (!$isArchivobjektUpdate) {
      unset($archivObjekt["id"]);
    }
    $archivOption = $archivObjekt["archivOption"];
    if ($archivOption === "CombineFiles") {
      $tempFile = ArchivFiles::combineFiles($archivObjekt["sourceFiles"]);
      $archivObjekt["digitalObjekte"][0]["sourcefilepath"] = $tempFile;
    }
    $this->db->beginTransaction();
    try {
      $archivObjektId = null;
      if ($isArchivobjektUpdate) {
        $archivObjektId = $archivObjekt["id"];
        if ($archivObjektId > 0) {
          $this->updateArchivobjekt($archivObjektId, $archivObjekt);
        } else {
          throw new \Exception("Ungültige Archivobjekt-ID");
        }
      } else {
        $archivObjektId = $this->createArchivobjekt($archivObjekt);
      }
      if (isset($archivObjekt["analogObjekte"])) {
        foreach ($archivObjekt["analogObjekte"] as &$analogobjekt) {
          $isAnalogobjektUpdate = isset($analogobjekt["id"]) && $analogobjekt["id"] > 0;
          $this->updateDate($analogobjekt, $isAnalogobjektUpdate);
          if (!$isAnalogobjektUpdate) {
            unset($analogobjekt["id"]);
          }
          $analogobjekt["archivobjekt_id"] = $archivObjektId;
          if (isset($analogobjekt["id"]) && $analogobjekt["id"] > 0) {
            // Update existing analogobjekt
            $this->updateAnalogobjekt($analogobjekt["id"], $analogobjekt);
          } else {
            // Create new analogobjekt
            $analogobjekt["id"] = $this->createAnalogobjekt($analogobjekt);
          }
        }
      }
      if (isset($archivObjekt["digitalObjekte"])) {
        foreach ($archivObjekt["digitalObjekte"] as &$digitalobjekt) {
          $isDigitalobjektUpdate = isset($digitalobjekt["id"]) && $digitalobjekt["id"] > 0;
          $this->updateDate($digitalobjekt, $isDigitalobjektUpdate);
          $digitalobjekt["archivobjekt_id"] = $archivObjektId;
          $digitalobjekt["dateiendung"] = strtolower(pathinfo($digitalobjekt["sourcefilepath"], PATHINFO_EXTENSION));
          if (isset($digitalobjekt["id"]) && $digitalobjekt["id"] > 0) {
            // Update existing digitalobjekt
            $this->updateDigitalobjekt($digitalobjekt["id"], $digitalobjekt);
          } else {
            // Create new digitalobjekt
            $digitalobjekt["id"] = $this->createDigitalobjekt($digitalobjekt);
          }
        }
      }
      if (isset($archivObjekt["schlagworte"])) {
        $this->saveSchlagworte($archivObjektId, $archivObjekt["schlagworte"]);
      }
      if ($archivOption === "CombineFiles") {
        ArchivFiles::deleteInboxFiles($archivObjekt["sourceFiles"]);
      }
    } catch (\Exception $e) {
      $this->db->rollBack();
      throw $e;
    }
    $this->db->commit();
    // Erzeugte IDs an den Aufrufer zurückgeben, damit Folgeaufrufe als Update erkannt werden
    $archivObjekt["id"] = $archivObjektId;
    $p["archivObjekt"] = $archivObjekt;
    return JsonResponse::success($p);
  }
  private function createArchivobjekt(array $archivObjektData): int {
    $result = $this->saveDataset($archivObjektData);
    $archivObjektId = $result["id"];
    return $archivObjektId;
  }
  private function updateArchivobjekt(int $archivObjektId, array $archivObjektData): void {
    $archivObjektData["id"] = $archivObjektId;
    $this->saveDataset($archivObjektData);
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
  private function createDigitalobjekt(array $digitalobjektData): int {
    $d = new Digitalobjekte();
    $result = $d->save($digitalobjektData);
    return $result->data["id"];
  }
  private function updateDigitalobjekt(int $digitalobjektId, array $digitalobjektData): void {
    $digitalobjektData["id"] = $digitalobjektId;
    $d = new Digitalobjekte();
    $d->save($digitalobjektData, true);
  }
  private function saveSchlagworte(int $archivObjektId, array $schlagworte): void {
    $deleteQuery = "DELETE FROM archivobjekt_schlagwort WHERE archivobjekt_id = :archivobjekt_id";
    $stmt = $this->db->prepare($deleteQuery);
    $stmt->execute(["archivobjekt_id" => $archivObjektId]);
    $insertQuery = "INSERT INTO archivobjekt_schlagwort (archivobjekt_id, schlagwort_id) VALUES (:archivobjekt_id, :schlagwort_id)";
    $stmt = $this->db->prepare($insertQuery);
    foreach ($schlagworte as $schlagwortId) {
      $stmt->execute(["archivobjekt_id" => $archivObjektId, "schlagwort_id" => $schlagwortId]);
    }
  }
  private function updateDate(array &$objekt, bool $isUpdate): void {
    if ($isUpdate) {
      $objekt["aenderungsdatum"] = date("Y-m-d H:i:s");
      $objekt["geaendert_durch"] = AppContext::getUsername();
    } else {
      $objekt["archivdatum"] = date("Y-m-d H:i:s");
      $objekt["archiviert_durch"] = AppContext::getUsername();
    }
  }
}
