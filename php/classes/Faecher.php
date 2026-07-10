<?php

namespace own;

use own\DbAccess;

class Faecher extends DbAccess {
  public function getTableName(): string {
    return "faecher";
  }
}
