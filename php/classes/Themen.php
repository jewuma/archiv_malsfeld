<?php

namespace own;

use own\DbAccess;

class Themen extends DbAccess {
  public function getTableName(): string {
    return "themen";
  }
}