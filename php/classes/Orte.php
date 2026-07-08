<?php

namespace own;

use own\DbAccess;

class Orte extends DbAccess {
  public function getTableName(): string {
    return "orte";
  }
}
