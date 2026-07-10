<?php

namespace own;

use own\DbAccess;

class Regale extends DbAccess {
  public function getTableName(): string {
    return "regale";
  }
}
