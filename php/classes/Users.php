<?php

namespace own;

use own\Validator;
use own\JsonResponse;

class Users extends DbAccess
{
  protected $table = "users";
  public function changePassword(string $sessionId, string $parameter): JsonResponse
  {
    $stmt = $this->db->prepare("SELECT userId FROM usersessions WHERE sessionId=?");
    $stmt->execute([$sessionId]);
    $userId = $stmt->fetchColumn();

    if ($userId !== false) {
      $schema = [
        "currentPassword" => "string",
        "newPassword" => "string",
      ];
      $p = Validator::validateJsonAgainstSchema($parameter, $schema);
      $currentPassword = $p["currentPassword"];
      $stmt = $this->db->prepare("SELECT password FROM users WHERE id=?");
      $stmt->execute([$userId]);
      if ($hashedPassword = $stmt->fetchColumn()) {
        if (!password_verify($currentPassword, $hashedPassword)) {
          throw new \Exception("Altes Passwort nicht korrekt", 400);
        }
      } else {
        throw new \Exception("Benutzer nicht gefunden", 400);
      }
      $newHash = password_hash($p["newPassword"], \PASSWORD_DEFAULT);
      $stmt = $this->db->prepare("UPDATE users SET password=:newHash WHERE id=:id");
      $stmt->execute([":newHash" => $newHash, ":id" => $userId]);
      return JsonResponse::success("Passwort geändert");
    } else {
      throw new \Exception("Usersession nicht vorhanden", 401);
    }
  }
  private function exists(string $username): bool
  {
    $stmt = $this->db->prepare("SELECT id FROM users WHERE `username`=:username");
    $stmt->execute([":username" => $username]);
    return $stmt->rowCount() > 0;
  }
  public function get(string|array|int $id): JsonResponse
  {
    $result = parent::get($id);
    unset($result["data"]["password"]);
    return $result;
  }
  public function getAll(): JsonResponse
  {
    $res = $this->db->query(
      "SELECT id,`username`,`firstname`,`name`,'***unchanged***' AS password,'***unchanged***' AS password_repeat,`position` FROM users"
    );
    $users = $res->fetchAll();
    return JsonResponse::success($users);
  }
  public function getPermissions(string $sessionId): array
  {
    $stmt = $this->db->prepare("SELECT userId FROM usersessions WHERE sessionId=?");
    $stmt->execute([$sessionId]);
    $userId = $stmt->fetchColumn();

    if ($userId !== false) {
      $stmt = $this->db->prepare("SELECT `position` FROM users WHERE id=?");
      $stmt->execute([$userId]);
      $result = $stmt->fetch();
      if ($result) {
        $position = $result["position"];
        return $this->getRights($position);
      } else {
        return [];
      }
    } else {
      return [];
    }
  }

  private function getRights(int $position): array
  {
    //"Administrator"   => 1
    //"Normaler User"   => 2
    $result["admin_right"] = $position == 1 ? 1 : 0;
    $result["user_right"] = 1;
    return $result;
  }
  protected function getTableName(): string
  {
    return $this->table;
  }
  public function login(string $data): JsonResponse
  {

    $ip = $_SERVER['REMOTE_ADDR'];

    $protection = new LoginProtection($this->db);
    $protection->cleanup();
    $protection->checkIpLimit($ip);

    $this->db->exec("DELETE FROM usersessions WHERE started_at < NOW() - INTERVAL 24 HOUR;");

    $loginData = json_decode($data, true);

    $username = $loginData["username"] ?? "";
    $password = $loginData["password"] ?? "";

    // User laden
    $stmt = $this->db->prepare("SELECT * FROM users WHERE username=:username");
    $stmt->execute([":username" => $username]);
    $user = $stmt->fetch();

    if ($user) {
      $protection->checkUserLock($user);
    }

    // Passwort prüfen
    if ($user && password_verify($password, $user["password"])) {

      $sessionId = session_create_id();
      $userId = $user["id"];

      $protection->registerSuccessfulLogin($userId);

      $stmt = $this->db->prepare("INSERT INTO usersessions VALUES(:sessionId,:userId,NOW())");
      $stmt->execute([":sessionId" => $sessionId, ":userId" => $userId]);
      $rights = $this->getRights($user["position"]);
      return JsonResponse::success(
        [
          "sessionId" => $sessionId,
          "rights" => [
            "admin" => $rights["admin_right"],
            "user" => $rights["user_right"],
          ],
        ]
      );
    }
    $protection->registerFailedAttempt($ip, $username, $user["id"] ?? null);
    throw new \Exception("Nicht authorisiert!", 401);
  }
  public function logout(string $sessionId): JsonResponse
  {
    $stmt = $this->db->prepare("DELETE FROM usersessions WHERE sessionId=?");
    $stmt->execute([$sessionId]);
    if ($stmt->rowCount()) {
      return JsonResponse::success("");
    } else {
      throw new \Exception("Session nicht gefunden", 404);
    }
  }
  public function save(string|array $data, bool $isUpdate = false): JsonResponse
  {
    if (!is_string($data)) {
      throw new \InvalidArgumentException("Ungültige Daten. String erwartet.");
    }
    $saveData = json_decode($data, true);
    foreach ($saveData as $field => $value) {
      if ($field == "username" && $this->exists($value) && !$isUpdate) {
        throw new \Exception("Benutzername existiert bereits", 400);
      }
      if (str_contains($field, "password")) {
        if ($value == "***unchanged***") {
          unset($saveData[$field]);
          continue;
        }
        $saveData[$field] = password_hash($value, \PASSWORD_DEFAULT);
      }
    }
    return parent::save(json_encode($saveData), $isUpdate);
  }
  public function update(string $data): JsonResponse
  {
    return $this->save($data, true);
  }
}
