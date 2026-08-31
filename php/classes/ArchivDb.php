<?php

namespace own;

use own\JsonResponse;

require_once __DIR__ . "/../.clientData.inc.php";

class ArchivDb {
  private static ?\PDO $db = null; // Singleton-Instanz

  private function __construct() {
  }

  public static function getDbInstance(): \PDO {
    if (self::$db === null) {
      $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
      try {
        self::$db = new \PDO($dsn, DB_USER, DB_PASSWORD, [
          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
          \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
      } catch (\PDOException $e) {
        throw new \Exception("Datenbankverbindung fehlgeschlagen: " . $e->getMessage(), 500);
      }
    }
    return self::$db;
  }
  public static function getAllowedColumns(string $table): array {
    // Abfrage, um die Spalten und deren Datentypen zu ermitteln
    $query = "DESCRIBE $table";
    $stmt = self::getDbInstance()->query($query);
    $tableColumns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $autoIncrement = null;
    // Erlaubte Spalten und ihre Typen speichern
    $allowedColumns = [];
    $primary = [];
    $nullOK = [];
    foreach ($tableColumns as $column) {
      $allowedColumns[$column["Field"]] = $column["Type"];
      if ($column["Key"] === "PRI") {
        $primary[$column["Field"]] = $column["Type"];
      }
      if ($column["Null"] === "YES") {
        $nullOK[$column["Field"]] = true;
      }
      if (str_contains($column["Extra"], "auto_increment")) {
        $autoIncrement = $column["Field"];
      }
    }

    return ["allowed" => $allowedColumns, "primary" => $primary, "auto_increment" => $autoIncrement, "nullOK" => $nullOK];
  }
  public static function webQuery(string $sql): JsonResponse {
    try {
      $res = self::getDbInstance()->query($sql);
      return JsonResponse::success($res->fetchAll());
    } catch (\PDOException $e) {
      error_log("Datenbankfehler bei SQL-Abfrage: " . $e->getMessage());
      throw new \Exception("Datenbankfehler: " . $e->getMessage(), 500);
    }
  }
  // Beispielhafte Umwandlungsfunktion für verschiedene Datentypen
  public static function preparedWebQuery(string $sql, array $paramArray): JsonResponse {
    try {
      $stmt = self::getDbInstance()->prepare($sql);
      $stmt->execute($paramArray);

      return JsonResponse::success($stmt->fetchAll());
    } catch (\PDOException $e) {
      error_log("Datenbankfehler bei SQL-Abfrage: " . $e->getMessage());
      throw new \Exception("Datenbankfehler: " . $e->getMessage(), 500);
    }
  }
  private static function transformData(mixed $value, string $columnType): mixed {
    // Je nach Datentyp Umwandlungen vornehmen
    $columnType = explode("(", $columnType)[0];
    switch ($columnType) {
      case "varchar":
      case "text":
        // String-Werte ohne besondere Umwandlung
        return (string) $value;

      case "int":
      case "tinyint":
      case "smallint":
      case "mediumint":
      case "bigint":
        // Integer-Werte sicherstellen
        return (int) $value;

      case "decimal":
      case "float":
        // Dezimal- oder Float-Werte umwandeln
        return (float) $value;

      case "date":
        // Datum/Uhrzeit-Werte umwandeln
        if ($value == "") {
          return null;
        }
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
          throw new \InvalidArgumentException("Ungültiges Datum: " . $value);
        }
        return date("Y-m-d", $timestamp);

      case "datetime":
        if ($value == "") {
          return null;
        }
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
          throw new \InvalidArgumentException("Ungültiges Datum/Zeit: " . $value);
        }
        return date("Y-m-d H:i:s", $timestamp);

      case "boolean":
        // Boolean-Werte umwandeln
        return (bool) $value;
      case "tinyblob":
      case "blob":
      case "mediumblob":
      case "longblob":
        if ($value == "") {
          return null;
        }
        return base64_decode($value);
      default:
        return $value;
    }
  }
  public static function saveDataSets(array $mapping, array $data, string $table): void {
    try {
      // Schritt 1: Datenbankfelder und Platzhalter ermitteln
      $fields = array_keys($mapping); // z. B. ['id', 'first_name', 'last_name']
      $columns = implode(", ", $fields); // "id, first_name, last_name"

      // Schritt 2: Daten konvertieren (Mapping anwenden)
      $databaseEntries = [];
      foreach ($data as $entry) {
        $dbEntry = [];
        foreach ($mapping as $dbField => $apiPath) {
          $dbEntry[$dbField] = self::getValueByPath($entry, $apiPath);
        }
        $databaseEntries[] = $dbEntry;
      }

      // Schritt 3: Batch-Insert vorbereiten
      $values = [];
      $params = [];
      foreach ($databaseEntries as $index => $entry) {
        $placeholders = [];
        foreach ($fields as $field) {
          $paramKey = ":{$field}_$index"; // z. B. ":id_0"
          $placeholders[] = $paramKey;
          $params[$paramKey] = $entry[$field];
        }
        $values[] = "(" . implode(", ", $placeholders) . ")";
      }
      $sql = "REPLACE INTO $table ($columns) VALUES " . implode(", ", $values);
      $stmt = self::getDbInstance()->prepare($sql);
      $stmt->execute($params);
    } catch (\PDOException $e) {
      error_log("Datenbankfehler beim Einfügen in Tabelle '$table': " . $e->getMessage());
      throw new \Exception("Datenbankfehler: " . $e->getMessage(), 500);
    }
  }
  private static function getValueByPath(array $data, string $path): mixed {
    $keys = explode(".", $path);
    $value = $data;

    foreach ($keys as $key) {
      if (isset($value[$key])) {
        $value = $value[$key];
      } else {
        return null; // Fallback, wenn ein Teil des Pfads fehlt
      }
    }

    return $value;
  }
  public static function updateDataSet(string $table, array $dataset): void {
    self::saveDataSet($table, $dataset, true);
  }

  public static function saveDataSet(string $table, array $data, bool $isUpdate = false): array {
    $db = self::getDbInstance();

    // 1. Metadaten holen
    $info = self::getAllowedColumns($table);
    $allowedColumns = $info["allowed"];
    $nullOK = $info["nullOK"];
    $dateColumn = $isUpdate ? "aenderungsdatum" : "archivdatum";
    $userColumn = $isUpdate ? "geaendert_durch" : "archiviert_durch";
    if (array_key_exists($dateColumn, $allowedColumns)) {
      $data[$dateColumn] = date("Y-m-d H:i:s");
    }
    if (array_key_exists($userColumn, $allowedColumns)) {
      $username = AppContext::getUsername();
      $data[$userColumn] = $username;
    }
    $primary = $info["primary"];
    $autoIncrement = $info["auto_increment"];
    if ($autoIncrement && isset($data[$autoIncrement]) && (($data[$autoIncrement] === "") || ($data[$autoIncrement] === null))) {
      unset($data[$autoIncrement]);
    }
    // 2. Daten filtern + transformieren
    $filteredData = [];
    foreach ($data as $column => $value) {
      if (array_key_exists($column, $allowedColumns)) {
        if ($value === null && isset($nullOK[$column])) {
          $filteredData[$column] = null;
          continue;
        }
        $filteredData[$column] = self::transformData($value, $allowedColumns[$column]);
      }
    }

    if (empty($filteredData)) {
      throw new \InvalidArgumentException("Keine gültigen Daten für Tabelle $table.");
    }

    // 3. Prüfen: Sind alle Primary Keys vorhanden?
    $primaryValues = array_intersect_key($filteredData, $primary);
    $allPrimaryKeysPresent = count($primaryValues) === count($primary);

    if (!$autoIncrement && !$allPrimaryKeysPresent) {
      throw new \InvalidArgumentException(
        "Fehlender Primärschlüssel: Alle Primärschlüssel müssen angegeben werden (kein auto_increment vorhanden)."
      );
    }

    // 4. SQL bauen (INSERT + UPSERT)
    $columns = implode(", ", array_keys($filteredData));
    $placeholders = ":" . implode(", :", array_keys($filteredData));

    $updateParts = [];
    foreach ($filteredData as $column => $value) {
      if (!isset($primary[$column])) {
        $updateParts[] = "$column = VALUES($column)";
      }
    }
    if ($isUpdate) {
      $setParts = [];
      foreach ($filteredData as $col => $val) {
        if (!isset($primary[$col])) {
          $setParts[] = "$col = :$col";
        }
      }

      $whereParts = [];
      foreach ($primary as $col => $_) {
        $whereParts[] = "$col = :w_$col";
      }

      $sql = "UPDATE $table SET " . implode(", ", $setParts) . " WHERE " . implode(" AND ", $whereParts);
      $stmt = $db->prepare($sql);
      // normale Werte
      foreach ($filteredData as $column => $value) {
        if (!isset($primary[$column])) {
          $stmt->bindValue(":$column", $value);
        }
      }
      // WHERE-Werte
      foreach ($primaryValues as $column => $value) {
        $stmt->bindValue(":w_$column", $value);
      }
    } else {
      $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
      if (!empty($updateParts)) {
        $sql .= " ON DUPLICATE KEY UPDATE " . implode(", ", $updateParts);
      }
      $stmt = $db->prepare($sql);

      foreach ($filteredData as $column => $value) {
        $stmt->bindValue(":$column", $value);
      }
    }

    try {
      $stmt->execute();

      // 5. Primary Keys final bestimmen
      $finalKeys = $primaryValues;
      if ($autoIncrement && !isset($finalKeys[$autoIncrement])) {
        $lastId = $db->lastInsertId();
        if ($lastId) {
          $finalKeys[$autoIncrement] = $lastId;
        }
      }

      if (empty($finalKeys)) {
        throw new \Exception("Primärschlüssel konnte nicht bestimmt werden.", 500);
      }


      // 6. WHERE-Clause bauen
      $whereParts = [];
      foreach ($finalKeys as $column => $value) {
        $whereParts[] = "$column = :w_$column";
      }

      $stmt = $db->prepare("SELECT * FROM $table WHERE " . implode(" AND ", $whereParts));

      foreach ($finalKeys as $column => $value) {
        $stmt->bindValue(":w_$column", $value);
      }

      $stmt->execute();
      $result = $stmt->fetch(\PDO::FETCH_ASSOC);

      if (!$result) {
        throw new \Exception("Datensatz konnte nach dem Speichern nicht geladen werden.", 500);
      }

      return $result;
    } catch (\PDOException $e) {
      error_log("DB-Fehler: " . $e->getMessage());
      throw new \Exception("Datenbankfehler: " . $e->getMessage(), 500);
    }
  }

  public static function getCounter(string $type, string|int $year): string {
    $sql = "SELECT `value` FROM counters WHERE type=:typ AND year=:year";
    try {
      $db = self::getDbInstance();
      $stmt = $db->prepare($sql);
      $stmt->bindParam(":typ", $type, \PDO::PARAM_STR);
      $stmt->bindParam(":year", $year, \PDO::PARAM_INT);
      $stmt->execute();
      $value = $stmt->fetchColumn();
      $sql = "UPDATE counters SET `value`=" . ($value + 1) . " WHERE type=:typ AND year=:year";
      if (!$stmt->rowCount()) {
        $sql = "INSERT INTO counters SET `value`=2,type=:typ,year=:year";
        $value = 1;
      }
      $stmt = self::getDbInstance()->prepare($sql);
      $stmt->bindParam(":typ", $type, \PDO::PARAM_STR);
      $stmt->bindParam(":year", $year, \PDO::PARAM_INT);
      $stmt->execute();
      return str_pad($value, 5, "0", STR_PAD_LEFT);
    } catch (\PDOException $e) {
      error_log("Datenbankfehler: " . $e->getMessage());
      throw new \Exception("Datenbankfehler: " . $e->getMessage(), 500);
    }
  }
}
