<?php

namespace own;

use Exception;

class LoginProtection {
  private const MAX_IP_ATTEMPTS = 20;      // Versuche pro IP
  private const MAX_USER_ATTEMPTS = 5;     // Versuche pro Benutzer
  private const WINDOW_MINUTES = 10;       // Zeitfenster
  private const LOCK_MINUTES = 15;         // Benutzer-Sperre

  public function __construct(private \PDO $db) {
  }
  public function checkIpLimit(string $ip): void {
    $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM login_attempts
            WHERE ip = :ip
            AND attempt_time > NOW() - INTERVAL :window MINUTE
        ");

    $stmt->execute([
      ":ip" => $ip,
      ":window" => self::WINDOW_MINUTES
    ]);

    $count = $stmt->fetchColumn();

    if ($count >= self::MAX_IP_ATTEMPTS) {
      throw new Exception("Zu viele Loginversuche von dieser IP", 429);
    }
  }
  public function checkUserLock(array $user): void {
    if (!$user["locked_until"]) {
      return;
    }

    if (strtotime($user["locked_until"]) > time()) {
      throw new Exception("Account temporär gesperrt", 429);
    }
  }
  public function registerFailedAttempt(string $ip, ?string $username, ?int $userId): void {
    // Versuch speichern
    $stmt = $this->db->prepare("INSERT INTO login_attempts (ip, username, attempt_time) VALUES (:ip, :username, NOW())");

    $stmt->execute([":ip" => $ip, ":username" => $username]);

    if (!$userId) {
      return;
    }

    // Benutzerzähler erhöhen
    $stmt = $this->db->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = :id");
    $stmt->execute([":id" => $userId]);

    // Sperre prüfen
    $stmt = $this->db->prepare("SELECT login_attempts FROM users WHERE id = :id");
    $stmt->execute([":id" => $userId]);

    $attempts = $stmt->fetchColumn();

    if ($attempts >= self::MAX_USER_ATTEMPTS) {
      $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0, locked_until = NOW() + INTERVAL :lock MINUTE WHERE id = :id");
      $stmt->execute([":lock" => self::LOCK_MINUTES, ":id" => $userId]);
    }
  }
  public function registerSuccessfulLogin(int $userId): void {
    $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = :id");
    $stmt->execute([":id" => $userId]);
  }
  public function cleanup(): void {
    $this->db->exec("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 1 DAY");
  }
}
