<?php

namespace own;

use own\DbAccess;
use own\Validator;

class Schlagworte extends DbAccess {
  public function getTableName(): string {
    return "schlagworte";
  }
  public function save(string|array $dataset, bool $isUpdate = false): JsonResponse {
    if (!is_string($dataset) && !is_array($dataset)) {
      throw new \InvalidArgumentException("Ungültiges Dataset.");
    }
    $param = Validator::validateJsonAgainstSchema($dataset, [
      'bezeichnung' => 'string'
    ]);
    $bezeichnung = trim($param['bezeichnung']);
    $existing = $this->db->prepare("SELECT id FROM schlagworte WHERE lower(bezeichnung)=lower(:bezeichnung)");
    $existing->execute([':bezeichnung' => $bezeichnung]);
    if ($existing->rowCount() > 0) {
      throw new \Exception("Schlagwort '$bezeichnung' existiert bereits.", 400);
    }
    return parent::save($param, $isUpdate);
  }
}
