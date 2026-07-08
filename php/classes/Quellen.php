<?php

namespace own;

use own\DbAccess;

class Quellen extends DbAccess {
  public function getTableName(): string {
    return "quellen";
  }
}
