<?php

namespace own;

use own\JsonResponse;
use own\Validator;
use own\ArchivDb;

class Archiv
{
  // Class implementation
  private \PDO $db;
  public function __construct()
  {
    $this->db = ArchivDb::getDbInstance();
  }

  public function search(string $parameter): JsonResponse
  {
    $param = Validator::validateJsonAgainstSchema($parameter, [
      "schlagworte_ids" => "array,optional",
      "ort_id" => "integer,optional",
      "startJahr" => "integer,optional",
      "endJahr" => "integer,optional",
      "suchbegriff" => "string,optional"
    ]);
    $schlagwortWhere = "";
    if (isset($param["schlagworte_ids"]) && count($param["schlagworte_ids"]) > 0) {
      $schlagwortIds = array_map('intval', $param["schlagworte_ids"]);
      $schlagwortCount = count($schlagwortIds);
      $schlagwortWhere = "AND ao.id IN (SELECT archivobjekt_id FROM archivobjekt_schlagwort WHERE schlagwort_id 
      IN (" . implode(",", $schlagwortIds) . ") GROUP BY archivobjekt_id
      HAVING COUNT(DISTINCT schlagwort_id) = $schlagwortCount)";
    }
    $ortWhere = isset($param["ort_id"]) ? "AND ao.ort_id = " . $param["ort_id"] : "";
    $suchbegriff = isset($param["suchbegriff"]) && !empty($param["suchbegriff"]) ? "%" . $param["suchbegriff"] . "%" : null;
    $sql = "SELECT
    ao.id,
    ao.titel,
    ao.beschreibung,
    th.name AS thema,
    o.name AS ort,
    ao.zeitraum_start,
    ao.zeitraum_ende,
    ao.datum,
    EXISTS(
        SELECT 1
        FROM analogobjekte an
        WHERE an.archivobjekt_id = ao.id
    ) AS hat_analog,

    (
        SELECT COUNT(*)
        FROM dateien d
        WHERE d.archivobjekt_id = ao.id
    ) AS datei_anzahl,

    (
        SELECT d.id
        FROM dateien d
        WHERE d.archivobjekt_id = ao.id
        ORDER BY d.id
        LIMIT 1
    ) AS erste_datei_id

    FROM archivobjekte ao

    LEFT JOIN orte o ON o.id = ao.ort_id
    LEFT JOIN themen th ON th.id = ao.themen_id
    WHERE 1 
    $schlagwortWhere 
    $ortWhere
    AND (:startJahr IS NULL OR ao.zeitraum_start >= :startJahr)
    AND (:endJahr IS NULL OR ao.zeitraum_ende <= :endJahr)
    AND (:suchbegriff IS NULL
        OR th.name LIKE CONCAT('%', :suchbegriff, '%')
        OR ao.titel LIKE CONCAT('%', :suchbegriff, '%')
        OR ao.beschreibung LIKE CONCAT('%', :suchbegriff, '%')
    )
     GROUP BY ao.id LIMIT 500";
    $paramArray = [
      ":startJahr" => $param["startJahr"] ?? null,
      ":endJahr" => $param["endJahr"] ?? null,
      ":suchbegriff" => $suchbegriff
    ];
    return Archivdb::preparedWebQuery($sql, $paramArray);
  }
  public function create(string $parameter): JsonResponse
  {
    $param = Validator::validateJsonAgainstSchema(
      $parameter,
      [
        "abJahr" => "integer,emptyOK",
        "bisJahr" => "integer,emptyOK",
        "analogNummer" => "integer,emptyOK",
        "dokumentDatum" => "date,emptyOK",
        "gesperrt" => "boolean",
        "kurztitel" => "string",
        "ort_id" => "integer",
        "dateiPfad" => "string",
      ]
    );
    return new JsonResponse(200, $param);
  }
}
