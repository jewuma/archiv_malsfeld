<?php

namespace own;

use own\DbAccess;

class Plaetze extends DbAccess {
  public function getTableName(): string {
    return "plaetze";
  }
}
