<?php

namespace own;

spl_autoload_register(function ($class) {
  $file = __DIR__ . '/' . str_replace('own\\', 'classes/', $class) . '.php';
  if (file_exists($file)) {
    require_once $file;
  }
});
require_once __DIR__ . "/.clientData.inc.php";

use own\AppContext;
use own\Users;
use ReflectionMethod;
use own\FileResponse;
use own\JsonResponse;

const DEBUG = false;
const HTTP_BAD_REQUEST = 400;
const HTTP_UNAUTHORIZED = 401;
const HTTP_FORBIDDEN = 403;
const HTTP_NOT_FOUND = 404;
const HTTP_METHOD_NOT_ALLOWED = 405;
const HTTP_SERVER_ERROR = 500;

$httpMethod = $_SERVER["REQUEST_METHOD"];
$allowedOrigins = ["http://localhost:5173", "http://localhost:5174", "https://wulfkg.com"];
if (isset($_SERVER["HTTP_ORIGIN"])) {
  if (in_array($_SERVER["HTTP_ORIGIN"], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
    header("Access-Control-Allow-Credentials: true"); // falls Cookies / Auth nötig
  } else {
    http_response_code(403);
    exit("Origin not allowed.");
  }
}
// CORS-Header
//header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
if ($httpMethod == "OPTIONS") {
  header("Access-Control-Allow-Methods: GET,POST,OPTIONS");
  header("Access-Control-Allow-Headers: *");
  header("Access-Control-Max-Age: 86400");
  http_response_code(200);
  exit();
}
$req = null;
$pathInfo = $_SERVER["PATH_INFO"] ?? ($_SERVER["ORIG_PATH_INFO"] ?? "");
$req = explode("/", trim($pathInfo, "/"));
$className = $req[0];
$methodName = $req[1] ?? "";
$parameter = $req[2] ?? "";
if ($httpMethod == "POST") {
  if (!empty($_FILES)) {
    $parameter = json_encode($_POST);
  } else {
    $parameter = file_get_contents("php://input");
  }
}
if ($httpMethod != "GET" && $httpMethod != "POST") {
  API::sendErrorResponse("Ungültige Methode: $httpMethod", HTTP_METHOD_NOT_ALLOWED);
}
API::handleRequest($className, $methodName, $parameter);

class API {
  public static bool $debug = DEBUG;
  public static array $permissions = [];
  public static function handleRequest(string $className, string $methodName, string $parameter): void {
    if ($className == "Users" && $methodName == "login") {
      try {
        self::sendJsonResponse((new Users())->login($parameter));
      } catch (\Exception $e) {
        self::sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
      }
      exit();
    }
    $sessionId = isset($_SERVER["HTTP_X_SESSION_ID"]) ? $_SERVER["HTTP_X_SESSION_ID"] : null;
    if ($className == "Users" && $methodName == "logout" && $sessionId) {
      self::sendJsonResponse((new Users())->logout($sessionId));
      exit();
    }
    if ($className == "Users" && $methodName == "changePassword" && $sessionId) {
      self::sendJsonResponse((new Users())->changePassword($sessionId, $parameter));
      exit();
    }
    if (!self::$debug && !$sessionId) {
      self::sendErrorResponse("Session-ID fehlt");
    }
    if (self::$debug) {
      self::$permissions = ["admin_right" => 1, "client_right" => 1, "staff_right" => 1];
    } else {
      self::$permissions = (new Users())->getPermissions($sessionId);
      AppContext::setStaffId(self::$permissions["staff_id"]);
      AppContext::setRights(self::$permissions);
    }
    if (empty(self::$permissions)) {
      self::sendErrorResponse("Ungültige oder abgelaufene Session", HTTP_UNAUTHORIZED);
    }
    self::dispatch($className, $methodName, $parameter);
  }
  private static function checkPermission(string $type, int $level): void {
    if (
      (isset(self::$permissions[$type]) && self::$permissions[$type] >= $level) ||
      (self::$permissions["admin_right"] ?? false)
    ) {
      return;
    }
    self::sendErrorResponse("Berechtigung nicht ausreichend", HTTP_FORBIDDEN);
    exit();
  }
  public static function dispatch(string $className, string $methodName, string $params): void {
    if (str_ends_with($className, "MeDb")) {
      self::sendErrorResponse("Nicht erlaubt", HTTP_FORBIDDEN);
    }
    if ($className == "Users" || $className == "Settings") {
      self::checkPermission("admin_right", 1);
    } elseif ($className == "Staff") {
      self::checkPermission("staff_right", 1);
    } else {
      self::checkPermission("client_right", 1);
    }
    try {
      $fullClassName = __NAMESPACE__ . "\\" . $className;
      if (!class_exists($fullClassName)) {
        self::sendErrorResponse("Klasse $fullClassName existiert nicht.", HTTP_NOT_FOUND);
      }
      if (!method_exists($fullClassName, $methodName)) {
        self::sendErrorResponse("Methode $methodName existiert nicht.", HTTP_NOT_FOUND);
      }

      $reflectionMethod = new ReflectionMethod($fullClassName, $methodName);

      $response = $reflectionMethod->isStatic()
        ? $reflectionMethod->invokeArgs(null, [$params])
        : $reflectionMethod->invokeArgs(new $fullClassName(), [$params]);

      if ($response instanceof FileResponse) {
        self::sendFileResponse($response);
      } elseif ($response instanceof JsonResponse) {
        self::sendJsonResponse($response);
      } else {
        self::sendErrorResponse("Ungültiges Antwortformat.", HTTP_SERVER_ERROR);
      }
    } catch (\Exception $e) {
      if ($e->getCode()) {
        self::sendErrorResponse($e->getMessage(), $e->getCode());
      } else {
        self::sendErrorResponse($e->getMessage(), 500);
      }
    }
  }
  private static function sendJsonResponse(JsonResponse $response): void {
    http_response_code($response->code);
    header("Content-Type: application/json");
    echo json_encode($response->toArray());
    exit();
  }
  private static function sendFileResponse(FileResponse $response): void {
    header("Access-Control-Expose-Headers: Content-Disposition");
    header("Content-Description: File Transfer");
    header("Content-Type: " . $response->mimeType);
    header('Content-Disposition: attachment; filename="' . $response->fileName . '"');
    header("Content-Length: " . strlen($response->fileContent));
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: public");
    echo $response->fileContent;
    exit();
  }
  public static function sendErrorResponse(string $message, mixed $code = HTTP_BAD_REQUEST): void {
    if (is_integer($code)) {
      http_response_code($code);
    } else {
      http_response_code(HTTP_SERVER_ERROR);
    }
    header("Content-Type: application/json");
    echo json_encode([
      "status" => "error",
      "message" => $message,
    ]);
    exit(); // Beende die Verarbeitung
  }
  // private static function sendFileContentResponse(array $fileData): void {
  //   $contentLength = 0;
  //   if (isset($fileData["fileContent"])) {
  //     $contentLength = strlen($fileData["fileContent"]);
  //   } else {
  //     $contentLength = filesize($fileData["filePath"]);
  //   }

  //   if (!$contentLength) {
  //     self::sendErrorResponse("Keine Datei-Inhalte vorhanden.", HTTP_NOT_FOUND);
  //   }
  //   $mimeType = $fileData["mimeType"];

  //   header("Access-Control-Expose-Headers: Content-Disposition");
  //   header("Content-Description: File Transfer");
  //   header("Content-Type: " . $mimeType);
  //   header('Content-Disposition: attachment; filename="' . $fileData["fileName"] . '"');
  //   header("Content-Length: " . $contentLength);
  //   header("Cache-Control: no-store, no-cache, must-revalidate");
  //   header("Pragma: public");
  //   // Datei-Inhalte ausgeben
  //   if (isset($fileData["fileContent"])) {
  //     echo $fileData["fileContent"];
  //   } else {
  //     readfile($fileData["filePath"]);
  //   }
  //   if (isset($fileData["delete"]) && $fileData["delete"]) {
  //     unlink($fileData["filePath"]);
  //   }
  //   exit();
  // }
}
