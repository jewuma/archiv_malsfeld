<?php

namespace own;

use own\DbAccess;

class Objekttypen extends DbAccess {
  public function getTableName(): string {
    return "objekttypen";
  }
}
