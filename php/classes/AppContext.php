<?php

namespace own;

class AppContext {
  private static bool $hasAdminRights = false;
  private static bool $hasUserRights = false;
  public static string $username = "";
  public static function setRights(array $rights) {
    self::$hasAdminRights = ($rights["admin_right"] ?? 0) == 1;
    self::$hasUserRights = ($rights["user_right"] ?? 0) == 1;
  }
  public static function getUsername(): string {
    return self::$username;
  }
  public static function setUsername(string $username): void {
    self::$username = $username;
  }
  public static function hasAdminRights(): bool {
    return self::$hasAdminRights;
  }
  public static function hasUserRights(): bool {
    return self::$hasUserRights;
  }
}
