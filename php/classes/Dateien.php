<?php

namespace own;

use own\DbAccess;

class Dateien extends DbAccess {
  public function getTableName(): string {
    return "dateien";
  }
}
