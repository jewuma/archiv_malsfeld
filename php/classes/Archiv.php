<?php

namespace own;

use own\JsonResponse;
use own\Validator;
use own\ArchivDb;

class Archiv {
  // Class implementation
  private \PDO $db;
  public function __construct() {
    $this->db = ArchivDb::getDbInstance();
  }
  public function getAnalogobjects(int $id): JsonResponse {
    $id = (int) $id;
    if (!$id) {
      throw new \Exception("Archivobjekt-Id fehlt");
    }
    $sql = "SELECT 
      an.id,
      an.archiv_id,
      an.objekttyp_id,
      an.quellen_id,
      an.seiten,
      an.lagerort_id,
      an.regal_id,
      an.fach_id,
      an.platz_id,
      an.digitalisiert,
      an.dokumentendatum,
      DATE(an.archivdatum) AS archivdatum
      FROM analogobjekte an 
      LEFT JOIN quellen qu ON an.quellen_id=qu.id
      WHERE archivobjekt_id=?";
    return ArchivDb::preparedWebQuery($sql, [$id]);
  }
  public function getFileobjects(int $id): JsonResponse {
    $id = (int) $id;
    if (!$id) {
      throw new \Exception("Archivobjekt-Id fehlt");
    }
    $sql = "SELECT 
      da.id,
      da.pfad,
      da.dateiname,
      ob.bezeichnung as objekttyp,
      da.dateidatum,
      DATE(da.archivdatum) AS archivdatum 
      FROM dateien da 
      LEFT JOIN objekttypen ob ON da.objekttyp_id=ob.id
      WHERE archivobjekt_id=?";
    return ArchivDb::preparedWebQuery($sql, [$id]);
  }
  public function getStats(): JsonResponse {
    $sql =
      "SELECT
    (SELECT COUNT(*) 
     FROM archivobjekte) AS archivobjekte,

    (SELECT COUNT(*) 
     FROM archivobjekte
     WHERE TRIM(COALESCE(beschreibung, '')) != '') AS mitBeschreibung,

    (SELECT COUNT(*) 
     FROM archivobjekte
     WHERE status = 3) AS veroeffentlicht,

    (SELECT COUNT(*) 
     FROM analogobjekte) AS analog,

    (SELECT COUNT(*) 
     FROM dateien) AS digital";
    $sql2 = "SELECT o.name, COUNT(*) AS anzahl
      FROM archivobjekte ao
      JOIN orte o ON o.id = ao.ort_id
      GROUP BY o.id, o.name
      ORDER BY anzahl DESC";
    $res1 = $this->db->query($sql);
    $res2 = $this->db->query($sql2);
    $result = array_merge($res1->fetch(), ["orte" => $res2->fetchAll()]);
    return new JsonResponse(200, $result);
  }
  public function search(string $parameter): JsonResponse {
    $param = Validator::validateJsonAgainstSchema($parameter, [
      "schlagworte_ids" => "array,optional",
      "ort_id" => "integer,optional",
      "startJahr" => "integer,optional",
      "endJahr" => "integer,optional",
      "suchbegriff" => "string,optional",
      "beschreibung" => "string,optional",
      "archivstatus" => "integer,optional",
      "objekttyp_id" => "integer,optional",
      "analog_archiv_id" => "integer,optional",
      "digitalisiert_ohne_datei" => "boolean,optional"
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
    $beschreibungWhere = "";
    if (isset($param["beschreibung"])) {
      if ($param["beschreibung"] === "ja") {
        $beschreibungWhere = "AND TRIM(COALESCE(ao.beschreibung, '')) <> ''";
      }
      if ($param["beschreibung"] === "nein") {
        $beschreibungWhere = "AND TRIM(COALESCE(ao.beschreibung, '')) = ''";
      }
    }
    $statusWhere = isset($param["archivstatus"]) ? "AND ao.status = " . $param["archivstatus"] : "";
    $objekttypWhere = isset($param["objekttyp_id"])
      ? "AND (
        EXISTS (
          SELECT 1 FROM analogobjekte an3
          WHERE an3.archivobjekt_id = ao.id
          AND an3.objekttyp_id = " . $param["objekttyp_id"] . "
        )
        OR EXISTS (
          SELECT 1 FROM dateien df2
          WHERE df2.archivobjekt_id = ao.id
          AND df2.objekttyp_id = " . $param["objekttyp_id"] . "
        )
      )"
      : "";
    $analogArchivIdWhere = isset($param["analog_archiv_id"])
      ? "AND EXISTS (
        SELECT 1 FROM analogobjekte an4
        WHERE an4.archivobjekt_id = ao.id
        AND an4.archiv_id = " . $param["analog_archiv_id"] . "
      )"
      : "";
    $digitalisiertOhneDateiWhere = !empty($param["digitalisiert_ohne_datei"])
      ? "AND COALESCE(df.anzahl, 0) = 0
      AND EXISTS (
        SELECT 1 FROM analogobjekte an2
        WHERE an2.archivobjekt_id = ao.id
        AND an2.digitalisiert = 1
      )"
      : "";
    $sql = "SELECT
    ao.id,
    ao.titel,
    ao.beschreibung,
    th.id AS themen_id,
    o.id AS ort_id,
    ao.zeitraum_start,
    ao.zeitraum_ende,
    COALESCE(an.anzahl, 0) AS analogobjekt_anzahl,
    an.erste_id AS erste_analogobjekt_id,
    an1.objekttyp_id AS erste_analogobjekt_typ_id,
    COALESCE(df.anzahl, 0) AS datei_anzahl,
    df.erste_id AS erste_datei_id
    FROM archivobjekte ao
    LEFT JOIN orte o ON o.id = ao.ort_id
    LEFT JOIN themen th ON th.id = ao.themen_id
    LEFT JOIN (
    SELECT archivobjekt_id, COUNT(*) AS anzahl, MIN(id) AS erste_id
      FROM analogobjekte
      GROUP BY archivobjekt_id
    ) an ON an.archivobjekt_id = ao.id
    LEFT JOIN analogobjekte an1
    ON an1.id = an.erste_id
    LEFT JOIN (
        SELECT archivobjekt_id, COUNT(*) AS anzahl, MIN(id) AS erste_id
        FROM dateien GROUP BY archivobjekt_id
    ) df ON df.archivobjekt_id = ao.id
    WHERE 1 
    $schlagwortWhere 
    $ortWhere
    $beschreibungWhere
    $statusWhere
    $objekttypWhere
    $analogArchivIdWhere
    $digitalisiertOhneDateiWhere
    AND (:startJahr IS NULL OR ao.zeitraum_start >= :startJahr)
    AND (:endJahr IS NULL OR ao.zeitraum_ende <= :endJahr)
    AND (:suchbegriff IS NULL
        OR th.name LIKE CONCAT('%', :suchbegriff, '%')
        OR ao.titel LIKE CONCAT('%', :suchbegriff, '%')
        OR ao.beschreibung LIKE CONCAT('%', :suchbegriff, '%')
    )
     LIMIT 500";
    $paramArray = [
      ":startJahr" => $param["startJahr"] ?? null,
      ":endJahr" => $param["endJahr"] ?? null,
      ":suchbegriff" => $suchbegriff
    ];
    return Archivdb::preparedWebQuery($sql, $paramArray);
  }
  public function create(string $parameter): JsonResponse {
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
    $sql = "INSERT INTO archivobjekte (ort_id,zeitraum_start, zeitraum_ende, titel, `status`, archivdatum)
      VALUES (:ort_id, :abJahr, :bisJahr, :kurztitel, 1, NOW())";
    $this->db->beginTransaction();
    $this->db->prepare($sql)->execute([
      ":ort_id" => $param["ort_id"],
      ":abJahr" => $param["abJahr"] ?? null,
      ":bisJahr" => $param["bisJahr"] ?? null,
      ":kurztitel" => $param["kurztitel"],
    ]);
    $archivObjektId = (int) $this->db->lastInsertId();
    $analogSql = "INSERT INTO analogobjekte (archivobjekt_id, archiv_id, dokumentendatum, digitalisiert, archivdatum)
      VALUES (:archivobjekt_id, :analogNummer, :dokumentDatum, 0, NOW())";
    $this->db->prepare($analogSql)->execute([
      ":archivobjekt_id" => $archivObjektId,
      ":analogNummer" => $param["analogNummer"] ?? null,
      ":dokumentDatum" => $param["dokumentDatum"] ?? null,
    ]);
    $this->db->commit();
    return new JsonResponse(200, $param);
  }
}
