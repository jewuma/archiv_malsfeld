<?php

namespace own;

class AppContext {
  private static ?int $staffId = null;
  private static bool $hasAdminRights = false;
  private static bool $hasClientRights = false;
  private static bool $hasStaffRights = false;

  public static function setRights(array $rights) {
    self::$hasAdminRights = ($rights["admin_right"] ?? 0) == 1;
    self::$hasClientRights = ($rights["client_right"] ?? 0) == 1;
    self::$hasStaffRights = ($rights["staff_right"] ?? 0) == 1;
  }
  public static function setStaffId(int $staffId) {
    self::$staffId = $staffId;
  }
  public static function getStaffId(): ?int {
    return self::$staffId;
  }
  public static function hasAdminRights(): bool {
    return self::$hasAdminRights;
  }
  public static function hasClientRights(): bool {
    return self::$hasClientRights;
  }
  public static function hasStaffRights(): bool {
    return self::$hasStaffRights;
  }
}
