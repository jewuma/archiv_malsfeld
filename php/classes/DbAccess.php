<?php

namespace own;

use own\ArchivDb;
use own\JsonResponse;

require_once __DIR__ . "/../.clientData.inc.php";

class DbAccess {
  protected \PDO $db;
  public const STORAGE_DIR = ARCHIV_FILE_PATH;
  public function __construct() {
    $this->db = ArchivDb::getDbInstance();
  }
  // CRUD-Methoden verwenden die `getTableName`-Methode
  public function delete(string|array $primaryKey): JsonResponse {
    $table = $this->getTableName();
    $keyValues = $this->normalizeKey($primaryKey);
    $where = $this->buildWhere($keyValues);
    $stmt = $this->db->prepare("DELETE FROM $table WHERE " . $where['sql']);
    $stmt->execute($where['values']);
    if ($stmt->rowCount()) {
      $fileColumns = $this->getFileColumns();
      foreach ($fileColumns as $column => $folder) {
        $path = $this->buildFilePath($keyValues, $folder);
        if (file_exists($path)) {
          unlink($path);
        }
      }
      return JsonResponse::success("Datensatz gelöscht.");
    } else {
      throw new \Exception("Datensatz in " . $this->getTableName() . " nicht gefunden.", 404);
    }
  }
  public function get(string|array|int $primaryKey): JsonResponse {
    $table = $this->getTableName();
    $keyValues = $this->normalizeKey($primaryKey);
    $where = $this->buildWhere($keyValues);

    $stmt = $this->db->prepare("SELECT * FROM $table WHERE {$where['sql']}");
    $stmt->execute($where['values']);
    $result = $stmt->fetch();

    if ($result) {
      return JsonResponse::success($result);
    }
    throw new \Exception("Datensatz nicht gefunden.", 404);
  }
  public function getAll(): JsonResponse {
    $table = $this->getTableName();
    $stmt = $this->db->query("SELECT * FROM $table");
    $result = $stmt->fetchAll();
    return JsonResponse::success($result);
  }
  // Diese Methode kann von abgeleiteten Klassen überschrieben werden
  protected function getFileColumns(): array {
    return [];
  }
  // Diese Methode kann von abgeleiteten Klassen überschrieben werden
  protected static function buildFilePath(array $keyValues, string $folder): string {
    if (!isset($keyValues['id'])) {
      throw new \Exception("File handling requires 'id'.", 500);
    }
    return self::STORAGE_DIR . $folder . "/" . (int) $keyValues['id'] . ".pdf";
  }
  // Diese Methode kann von abgeleiteten Klassen überschrieben werden
  protected function getPrimaryKey(): array {
    return ['id'];
  }
  // Diese Methode sollte von abgeleiteten Klassen überschrieben werden
  protected function getTableName(): string {
    return "";
  }
  private function normalizeKey(array|int $id): array {
    $keys = $this->getPrimaryKey();

    // Einfacher Fall: nur ein Key
    if (!is_array($id)) {
      if (count($keys) !== 1) {
        throw new \Exception("Composite key erwartet ein Array.", 400);
      }
      return [$keys[0] => $id];
    }
    return $id;
  }
  public function openPdf(string $parameter): void {
    $fileColumns = $this->getFileColumns();
    $param = explode("-", $parameter);
    $fieldName = array_shift($param);
    if (!isset($fileColumns[$fieldName])) {
      throw new \Exception("Ungültiger Feldname für PDF.", 400);
    }
    $keyNames = $this->getPrimaryKey();
    $keys = [];
    foreach ($keyNames as $key) {
      if (empty($param)) {
        throw new \Exception("Ungültiger Parameter für PDF.", 400);
      }
      $keys[$key] = array_shift($param);
    }
    $path = $this->buildFilePath($keys, $fileColumns[$fieldName]);
    if (file_exists($path)) {
      header('Content-Type: application/pdf');
      header('Content-Disposition: inline; filename="' . basename($path) . '"');
      readfile($path);
      exit;
    } else {
      throw new \Exception("Datei nicht gefunden.", 404);
    }
  }
  public function save(string|array $dataset, bool $isUpdate = false): JsonResponse {
    if (is_string($dataset)) {
      $data = json_decode($dataset, true);
    } elseif (is_array($dataset)) {
      $data = $dataset;
    } else {
      throw new \InvalidArgumentException("Ungültiges Dataset.");
    }
    $fileData = $this->extractFileData($data);
    $table = $this->getTableName();
    $result = ArchivDb::saveDataSet($table, $data, $isUpdate);
    $keys = $this->extractPrimaryKeys($result);
    $this->processFilesAfterSave($fileData, $keys);

    return JsonResponse::success($result);
  }
  public function update(string $dataset): JsonResponse {
    return $this->save($dataset, true);
  }
  private function buildWhere(array $keyValues): array {
    $conditions = [];
    $values = [];

    foreach ($keyValues as $column => $value) {
      $conditions[] = "$column = ?";
      $values[] = $value;
    }

    return [
      'sql' => implode(' AND ', $conditions),
      'values' => $values
    ];
  }
  private function deleteFile(string $folder, array $keys): void {
    $path = $this->buildFilePath($keys, $folder);
    if (file_exists($path)) {
      unlink($path);
    }
  }
  private function extractFileData(array &$data): array {
    $fileColumns = $this->getFileColumns();
    $fileData = [];

    foreach ($fileColumns as $column => $folder) {

      if (!isset($data[$column])) continue;

      $value = $data[$column];

      if (is_array($value) && isset($value['action'])) {
        $fileData[$column] = [
          'action' => $value['action'],
          'value' => $value['fileContent'] ?? null,
          'folder' => $folder,
          'fileName' => $value['fileName'] ?? null
        ];
        unset($data[$column]);
      }
    }
    return $fileData;
  }
  private function extractPrimaryKeys(array $data): array {
    $info = ArchivDb::getAllowedColumns($this->getTableName());
    $primary = $info['primary'];

    return array_intersect_key($data, $primary);
  }
  protected function processFilesAfterSave(array $fileData, array $keys): void {
    foreach ($fileData as $column => $info) {

      switch ($info['action']) {

        case 'replace':
          if (!empty($info['value'])) {
            $this->storeFile($info['value'], $info['folder'], $keys);
            $columnValue = $info['fileName'] ?? true;
            $this->updateColumns($keys, [
              $column => $columnValue
            ]);
          }
          break;

        case 'delete':
          $this->deleteFile($info['folder'], $keys);
          $this->updateColumns($keys, [
            $column => ""
          ]);
          break;
      }
    }
  }
  private function storeFile(string $base64, string $folder, array $keys): void {
    $path = $this->buildFilePath($keys, $folder);

    $data = explode(',', $base64);
    $fileContent = base64_decode(end($data));

    file_put_contents($path, $fileContent);
  }
  protected function updateColumns(array $keys, array $columns): void {
    $table = $this->getTableName();

    $setParts = [];
    $values = [];

    foreach ($columns as $col => $val) {
      $setParts[] = "$col = ?";
      $values[] = $val;
    }

    $where = $this->buildWhere($keys);

    $sql = "UPDATE $table SET " . implode(", ", $setParts) . " WHERE " . $where['sql'];

    $stmt = $this->db->prepare($sql);
    $stmt->execute(array_merge($values, $where['values']));
  }
}
