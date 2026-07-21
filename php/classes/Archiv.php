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
  public function getAnalogobjects($id): JsonResponse
  {
    $id = (int) $id;
    if (!$id) {
      throw new \Exception("Archivobjekt-Id fehlt");
    }
    $sql = "SELECT 
      an.id,
      ob.bezeichnung as objekttyp,
      an.seiten,
      la.bezeichnung as lagerort,
      re.kurzbezeichnung as regal,
      fa.kurzbezeichnung as fach,
      pl.kurzbezeichnung as platz,
      an.digitalisiert,
      an.dokumentendatum,
      DATE(an.archivdatum) AS archivdatum, 
      CONCAT(qu.name,', ',qu.vorname) as quelle
      FROM analogobjekte an 
      LEFT JOIN faecher fa ON an.fach_id=fa.id
      LEFT JOIN lagerorte la ON an.lagerort_id=la.id
      LEFT JOIN regale re ON an.regal_id=re.id
      LEFT JOIN objekttypen ob ON an.objekttyp_id=ob.id
      LEFT JOIN plaetze pl ON an.platz_id=pl.id
      LEFT JOIN quellen qu ON an.quellen_id=qu.id
      WHERE archivobjekt_id=?";
    return ArchivDb::preparedWebQuery($sql, [$id]);
  }
  public function getFileobjects($id): JsonResponse
  {
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
      DATE(da.archivdatum) AS archivdatum, 
      CONCAT('/ArchivFiles/get/',da.id) AS filelink
      FROM dateien da 
      LEFT JOIN objekttypen ob ON da.objekttyp_id=ob.id
      WHERE archivobjekt_id=?";
    return ArchivDb::preparedWebQuery($sql, [$id]);

  }
  public function getStats(): JsonResponse
  {
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
