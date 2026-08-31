<?php

namespace own;

use own\DbAccess;
use own\JsonResponse;
use own\Validator;
use own\ArchivFiles;
use own\AppContext;

class Digitalobjekte extends DbAccess {
  public function __construct() {
    parent::__construct();
  }
  public function getTableName(): string {
    return "digitalobjekte";
  }
  public function delete(string|array $primaryKey): JsonResponse {
    $id = is_array($primaryKey) ? (int)($primaryKey['id'] ?? 0) : (int)$primaryKey;
    if (!$id) {
      throw new \Exception("Digitalobjekt-Id fehlt", 400);
    }

    $stmt = $this->db->prepare("SELECT dateiendung, archivobjekt_id FROM digitalobjekte WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $digitalobjekt = $stmt->fetch();
    if (!$digitalobjekt) {
      throw new \Exception("Datensatz in digitalobjekte nicht gefunden.", 404);
    }
    $archivobjektId = (int)$digitalobjekt['archivobjekt_id'];

    $path = ArchivFiles::$baseDir . substr((string)$id, 0, 4) . "/" . $id . "." . $digitalobjekt['dateiendung'];
    if (is_file($path) && !unlink($path)) {
      throw new \Exception("Die Archivdatei konnte nicht gelöscht werden", 500);
    }

    $response = parent::delete($primaryKey);
    $orphanedResponse = Archivobjekte::deleteIfOrphaned($archivobjektId);
    $response->data = is_array($response->data) ? $response->data : [];
    $response->data["archivobjektDeleted"] = $orphanedResponse->data;
    return $response;
  }
  public function deleteToInbox(string|array $primaryKey): JsonResponse {
    $id = is_array($primaryKey) ? (int)($primaryKey['id'] ?? 0) : (int)$primaryKey;
    if (!$id) {
      throw new \Exception("Digitalobjekt-Id fehlt", 400);
    }

    $stmt = $this->db->prepare("SELECT dateiendung,archivobjekt_id FROM digitalobjekte WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $digitalobjekt = $stmt->fetch();
    if (!$digitalobjekt) {
      throw new \Exception("Datensatz in digitalobjekte nicht gefunden.", 404);
    }

    $extension = strtolower($digitalobjekt['dateiendung']);
    $archivobjektId = (int)$digitalobjekt['archivobjekt_id'];
    $sourcePath = ArchivFiles::$baseDir . substr((string)$id, 0, 4) . "/" . $id . "." . $extension;
    if (!is_file($sourcePath)) {
      throw new \Exception("Die Archivdatei konnte nicht gefunden werden", 404);
    }
    $targetPath = ArchivFiles::$inboxDir . $id . "." . $extension;
    if (file_exists($targetPath)) {
      throw new \Exception("Eine Datei mit diesem Namen liegt bereits im Archiveingang", 409);
    }

    if (!rename($sourcePath, $targetPath)) {
      throw new \Exception("Die Datei konnte nicht in den Archiveingang verschoben werden", 500);
    }
    try {
      $response = parent::delete($primaryKey);
      $orphanedResponse = Archivobjekte::deleteIfOrphaned($archivobjektId);
      $response->data = is_array($response->data) ? $response->data : [];
      $response->data["archivobjektDeleted"] = $orphanedResponse->data;
      return $response;
    } catch (\Exception $e) {
      rename($targetPath, $sourcePath);
      throw $e;
    }
  }
  public function save(string|array $dataset, bool $isUpdate = false): JsonResponse {
    $useTransaction = true;
    if (is_string($dataset)) {
      $schema = [
        "id" => "integer,optional",
        "archivobjekt_id" => "integer",
        "titel" => "string",
        "quellen_id" => "integer,nullOK",
        "gesperrt" => "boolean",
        "gesperrt_bis" => "string,nullOK",
        "dateidatum" => "date,emptyOK",
        "sourcefilepath" => "string",
      ];
      if ($isUpdate) {
        $schema = [
          "id" => "integer,optional",
          "titel" => "string,optional",
          "quellen_id" => "integer,nullOK,optional",
          "gesperrt" => "boolean,optional",
          "gesperrt_bis" => "string,nullOK,optional",
          "dateidatum" => "date,emptyOK,optional",
          "sourcefilepath" => "string,optional",
        ];
      }
      $d = Validator::validateJsonAgainstSchema($dataset, $schema);
    } elseif (is_array($dataset)) {
      $d = $dataset;
      $useTransaction = false;  //callers should handle transactions when passing an array
    } else {
      throw new \InvalidArgumentException("Ungültiges Dataset.");
    }
    if (!isset($d["sourcefilepath"])) {
      return parent::save($dataset, $isUpdate);
    }
    $sourcePath = realpath(ArchivFiles::$inboxDir . $d["sourcefilepath"]);
    if (!str_starts_with($sourcePath, realpath(ArchivFiles::$inboxDir))) {
      return JsonResponse::error("Die Quelldatei existiert nicht", 404);
    }
    $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    if ($useTransaction) {
      $this->db->beginTransaction();
    }
    try {
      if (isset($d["id"])) {
        // Bei Teil-Updates (z.B. nur sourcefilepath) fehlende Felder aus dem bestehenden Datensatz ergänzen
        $existingStmt = $this->db->prepare("SELECT archivobjekt_id, titel, quellen_id, gesperrt, gesperrt_bis, dateidatum FROM digitalobjekte WHERE id = :id");
        $existingStmt->execute(['id' => $d["id"]]);
        $existing = $existingStmt->fetch();
        if (!$existing) {
          throw new \Exception("Datensatz in digitalobjekte nicht gefunden.", 404);
        }
        $d = array_merge($existing, $d);
        $stmt = $this->db->prepare("UPDATE digitalobjekte SET 
        archivobjekt_id = :archivobjekt_id, titel = :titel, dateiendung = :dateiendung, 
        quellen_id = :quellen_id, gesperrt = :gesperrt, gesperrt_bis = :gesperrt_bis, 
        dateidatum = :dateidatum, aenderungsdatum = :aenderungsdatum, 
        geaendert_durch = :geaendert_durch WHERE id = :id");
        $stmt->execute([
          'id' => $d["id"],
          'archivobjekt_id' => $d["archivobjekt_id"],
          'titel' => $d["titel"],
          'dateiendung' => $extension,
          'quellen_id' => $d["quellen_id"] ?? null,
          'gesperrt' => $d["gesperrt"] ? 1 : 0,
          'gesperrt_bis' => $d["gesperrt_bis"] ?? null,
          'dateidatum' => self::normalizeDateidatum($d["dateidatum"] ?? null),
          'aenderungsdatum' => date('Y-m-d H:i:s'),
          'geaendert_durch' => AppContext::getUsername()

        ]);
        $newId = (int)$d["id"];
      } else {
        $stmt = $this->db->prepare("INSERT INTO digitalobjekte (archivobjekt_id, titel, dateiendung, quellen_id, gesperrt, gesperrt_bis, dateidatum, archivdatum, archiviert_durch) VALUES (:archivobjekt_id, :titel, :dateiendung, :quellen_id, :gesperrt, :gesperrt_bis, :dateidatum, :archivdatum, :archiviert_durch)");
        $stmt->execute([
          'archivobjekt_id' => $d["archivobjekt_id"],
          'titel' => $d["titel"],
          'dateiendung' => $extension,
          'quellen_id' => $d["quellen_id"] ?? null,
          'gesperrt' => $d["gesperrt"] ? 1 : 0,
          'gesperrt_bis' => $d["gesperrt_bis"] ?? null,
          'dateidatum' => self::normalizeDateidatum($d["dateidatum"] ?? null),
          'archivdatum' => date('Y-m-d H:i:s'),
          'archiviert_durch' => AppContext::getUsername()
        ]);
        $newId = (int)$this->db->lastInsertId();
        $finalTargetPath = ArchivFiles::buildFilePath($newId, $extension);
        if (!rename($sourcePath, $finalTargetPath)) {
          return JsonResponse::error("Fehler beim Verschieben der Datei in den endgültigen Speicher", 500);
        }
      }
      $result = [
        'id' => $newId
      ];
      if ($useTransaction) {
        $this->db->commit();
      }
      return JsonResponse::success($result);
    } catch (\Exception $e) {
      if ($useTransaction) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }
  private static function normalizeDateidatum(?string $value): ?string {
    if (empty($value)) {
      return null;
    }
    $date = \DateTime::createFromFormat('Y-m-d', $value);
    if ($date !== false && $date->format('Y-m-d') === $value) {
      return $value;
    }
    return null;
  }
}
