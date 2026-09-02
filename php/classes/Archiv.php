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
      an.gesperrt,
      an.gesperrt_bis,
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
      di.id,
      di.dateidatum,
      di.titel,
      di.quellen_id,
      ob.bezeichnung AS objekttyp,
      di.dateiendung,
      DATE(di.archivdatum) AS archivdatum 
      FROM digitalobjekte di
      LEFT JOIN endungen_objekttypen eo ON di.dateiendung=eo.dateiendung
      LEFT JOIN objekttypen ob ON eo.objekttyp_id=ob.id
      WHERE di.archivobjekt_id=?";
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
     FROM digitalobjekte) AS digital";
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
      "archiviert_geaendert" => "string,optional",
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
    $suchbegriffe = isset($param["suchbegriff"]) && trim($param["suchbegriff"]) !== ""
      ? explode(' ', trim($param["suchbegriff"]))
      : [];
    if (count($suchbegriffe) > 0) {
      $suchbegriffWhereParts = [];
      foreach ($suchbegriffe as $index => $wort) {
        $suchbegriffWhereParts[] = "(ao.titel LIKE :suchbegriff$index OR ao.beschreibung LIKE :suchbegriff$index)";
      }
      $suchbegriffWhere = "AND (" . implode(" AND ", $suchbegriffWhereParts) . ")";
    } else {
      $suchbegriffWhere = "";
    }
    $archiviertGeaendertWhere = "";
    if (isset($param["archiviert_geaendert"])) {
      if ($param["archiviert_geaendert"] === "last_week") {
        $archiviertGeaendertWhere = "AND (ao.archivdatum >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK) OR ao.aenderungsdatum >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)) ";
      }
      if ($param["archiviert_geaendert"] === "last_month") {
        $archiviertGeaendertWhere = "AND (ao.archivdatum >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) OR ao.aenderungsdatum >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) ";
      }
      if ($param["archiviert_geaendert"] === "last_year") {
        $archiviertGeaendertWhere = "AND (ao.archivdatum >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) OR ao.aenderungsdatum >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)) ";
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
          SELECT 1 FROM digitalobjekte df2
          WHERE df2.archivobjekt_id = ao.id
          AND " . $param["objekttyp_id"] . " IN 
          (SELECT objekttyp_id FROM endungen_objekttypen WHERE dateiendung = df2.dateiendung)
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
        df.erste_id AS erste_datei_id,
        df.erster_typ AS erste_datei_typ
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
          SELECT
              archivobjekt_id,
              COUNT(*) AS anzahl,
              MIN(id) AS erste_id,
              LOWER(
                  SUBSTRING_INDEX(
                      GROUP_CONCAT(dateiendung ORDER BY id),
                      ',',
                      1
                  )
              ) AS erster_typ
          FROM digitalobjekte
          GROUP BY archivobjekt_id
        ) df ON df.archivobjekt_id = ao.id
            WHERE 1 
            $schlagwortWhere 
            $ortWhere
            $archiviertGeaendertWhere
            $statusWhere
            $objekttypWhere
            $analogArchivIdWhere
            $digitalisiertOhneDateiWhere
            $suchbegriffWhere
            AND (:startJahr IS NULL OR ao.zeitraum_start >= :startJahr)
            AND (:endJahr IS NULL OR ao.zeitraum_ende <= :endJahr)
        LIMIT 500";
    $paramArray = [
      ":startJahr" => $param["startJahr"] ?? null,
      ":endJahr" => $param["endJahr"] ?? null,
    ];
    if (count($suchbegriffe) > 0) {
      foreach ($suchbegriffe as $index => $wort) {
        $paramArray[":suchbegriff$index"] = "%" . $wort . "%";
      }
    }
    return Archivdb::preparedWebQuery($sql, $paramArray);
  }
}
