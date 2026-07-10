<?php

namespace own;

use own\DbAccess;

class Lagerorte extends DbAccess {
  public function getTableName(): string {
    return "lagerorte";
  }
}
