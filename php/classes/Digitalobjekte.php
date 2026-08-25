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
  public function save(string|array $dataset, bool $isUpdate = false): JsonResponse {
    $useTransaction = true;
    if (is_string($dataset)) {
      $d = Validator::validateJsonAgainstSchema($dataset, [
        "id" => "int,optional",
        "archivobjekt_id" => "int",
        "titel" => "string",
        "dateiendung" => "string",
        "quellen_id" => "int,emptyOK",
        "gesperrt" => "boolean",
        "gesperrt_bis" => "string,emptyOK",
        "dateidatum" => "date,emptyOK",
        "sourcefilepath" => "string",
      ]);
    } elseif (is_array($dataset)) {
      $d = $dataset;
      $useTransaction = false;  //callers should handle transactions when passing an array
    } else {
      throw new \InvalidArgumentException("Ungültiges Dataset.");
    }
    $sourcePath = realpath(ArchivFiles::$inboxDir . $d["sourcefilepath"]);
    if (!str_starts_with($sourcePath, realpath(ArchivFiles::$inboxDir))) {
      return JsonResponse::error("Die Quelldatei existiert nicht", 404);
    }
    $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    if ($useTransaction) {
      $this->db->beginTransaction();
    }
    $dateidatum = null;

    if (!empty($d["dateidatum"])) {
      $date = \DateTime::createFromFormat('Y-m-d', $d["dateidatum"]);

      if (
        $date !== false &&
        $date->format('Y-m-d') === $d["dateidatum"]
      ) {
        $dateidatum = $d["dateidatum"];
      }
    }
    try {
      if (isset($d["id"])) {
        $stmt = $this->db->prepare("UPDATE digitalobjekte SET 
        archivobjekt_id = :archivobjekt_id, titel = :titel, dateiendung = :dateiendung, 
        quellen_id = :quellen_id, gesperrt = :gesperrt, gesperrt_bis = :gesperrt_bis, 
        dateidatum = :dateidatum, aenderungsdatum = :aenderungsdatum, 
        geaendert_durch = :geaendert_durch WHERE id = :id");
        $stmt->execute([
          ':id' => $d["id"],
          ':archivobjekt_id' => $d["archivobjekt_id"],
          ':titel' => $d["titel"],
          ':dateiendung' => $d["dateiendung"],
          ':quellen_id' => $d["quellen_id"] ?? null,
          ':gesperrt' => $d["gesperrt"] ? 1 : 0,
          ':gesperrt_bis' => $d["gesperrt_bis"] ?? null,
          ':dateidatum' => $dateidatum,
          ':aenderungsdatum' => date('Y-m-d H:i:s'),
          ':geaendert_durch' => AppContext::getUsername()

        ]);
        $newId = (int)$d["id"];
      } else {
        $stmt = $this->db->prepare("INSERT INTO digitalobjekte (archivobjekt_id, titel, dateiendung, quellen_id, gesperrt, gesperrt_bis, dateidatum, archivdatum, archiviert_durch) VALUES (:archivobjekt_id, :titel, :dateiendung, :quellen_id, :gesperrt, :gesperrt_bis, :dateidatum, :archivdatum, :archiviert_durch)");
        $stmt->execute([
          ':archivobjekt_id' => $d["archivobjekt_id"],
          ':titel' => $d["titel"],
          ':dateiendung' => $d["dateiendung"],
          ':quellen_id' => $d["quellen_id"] ?? null,
          ':gesperrt' => $d["gesperrt"] ? 1 : 0,
          ':gesperrt_bis' => $d["gesperrt_bis"] ?? null,
          ':dateidatum' => $dateidatum,
          ':archivdatum' => date('Y-m-d H:i:s'),
          ':archiviert_durch' => AppContext::getUsername()
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
}
