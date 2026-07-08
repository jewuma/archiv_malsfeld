<?php

namespace own;

class Validator {
  private static function isValidDateTime(string $value, string $format): bool {
    $date = \DateTime::createFromFormat($format, $value);
    $errors = \DateTime::getLastErrors();
    return $errors === false;
  }
  public static function validateJsonAgainstSchema(string $jsonData, array $schema): array {
    $data = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception("Ungültiges JSON.", 400);
    }

    foreach ($schema as $key => $definition) {
      $isOptional = false;
      $isEmptyOK = false;
      // Support für den Select-Typ
      $allowedValues = [];
      if (is_array($definition) && $definition[0] === "select") {
        $expectedType = "select";
        $allowedValues = $definition[1];
      } else {
        $definitions = explode(",", $definition);
        $expectedType = array_shift($definitions);
        while (count($definitions)) {
          $option = array_shift($definitions);
          if ($option === "optional") $isOptional = true;
          if ($option === "emptyOK") $isEmptyOK = true;
        }
        $expectedType = explode(",", $definition)[0];
        $options =
          $isOptional = strpos($definition, "optional") !== false;
      }

      // Pflichtfeld prüfen
      if (!array_key_exists($key, $data)) {
        if ($isOptional) {
          continue;
        } else {
          throw new \Exception("Fehlender Parameter $key", 400);
        }
      }

      $value = $data[$key];
      if ($isEmptyOK && $value === "") continue;
      switch ($expectedType) {
        case "array":
          if (!is_array($value)) {
            throw new \Exception("Ungültiger Datentyp für $key. Erwartet: array.");
          }
          break;

        case "base64":
          if (!is_string($value)) {
            throw new \Exception("Ungültiger Datentyp für $key. Erwartet: base64-kodierter String.");
          }
          $decodedValue = base64_decode($value, true);
          if ($decodedValue === false) {
            throw new \Exception("Ungültiger base64-kodierter String für $key.");
          }
          $data[$key] = $decodedValue;
          break;
        case "integer":
          if (!is_numeric($value)) {
            throw new \Exception("Ungültiger Datentyp für $key. Erwartet: integer.");
          }
          $data[$key] = (int) $value;
          break;

        case "boolean":
          $value = (bool)$value;
          $data[$key] = $value;
          break;

        case "date":
          if (!self::isValidDateTime($value, "Y-m-d")) {
            throw new \Exception("Ungültiges Datumsformat für $key. Erwartet: YYYY-MM-DD.");
          }
          break;

        case "datetime":
          if (!self::isValidDateTime($value, "Y-m-d\TH:i")) {
            throw new \Exception("Ungültiges Datums-Zeit-Format für $key. Erwartet: YYYY-MM-DDTHH:MM.");
          }
          break;

        case "month":
          if (!is_numeric($value)) {
            throw new \Exception("Ungültiger Monat für $key.");
          }
          $value = (int) $value;
          if ($value < 1 || $value > 12) {
            throw new \Exception("Ungültiger Monat für $key. Erlaubt: 1-12.");
          }
          $data[$key] = $value;
          break;

        case "float":
          if (!is_numeric($value)) {
            throw new \Exception("Ungültiger Datentyp für $key. Erwartet: Zahl.");
          }
          $value = (float) $value;
          $data[$key] = $value;
          break;

        case "select":
          if (!in_array($value, $allowedValues, true)) {
            $allowedList = implode(", ", $allowedValues);
            throw new \Exception("Ungültiger Wert für $key. Erlaubt: $allowedList.");
          }
          break;
        case "string":
          if (!is_string($value) && !is_numeric($value)) {
            throw new \Exception("Ungültiges Format - kein String");
          }
          break;

        case "time":
          if (!self::isValidDateTime($value, "H:i")) {
            throw new \Exception("Ungültiges Zeitformat für $key. Erwartet: HH:MM.");
          }
          break;

        case "year":
          if (!is_numeric($value)) {
            throw new \Exception("Ungültiges Jahr für $key.");
          }
          $value = (int) $value;
          // Optional: dynamische Begrenzung
          $currentYear = (int) date("Y");
          if ($value < 1900 || $value > $currentYear + 5) {
            throw new \Exception("Ungültiges Jahr für $key.");
          }
          $data[$key] = $value;
          break;

        case "filename":
          if (!is_string($value)) {
            throw new \Exception("Ungültiger Dateiname für $key.");
          }

          // Nur Dateiname erlauben (kein Pfad)
          if (basename($value) !== $value) {
            throw new \Exception("Pfadangaben sind im Dateinamen nicht erlaubt.");
          }

          // Keine Verzeichnis-Traversal
          if (str_contains($value, "..")) {
            throw new \Exception("Ungültiger Dateiname.");
          }

          // Optional: nur bestimmte Zeichen erlauben
          if (!preg_match('/^[ a-zA-ZÄÖÜääöüß0-9.,_-]+$/', $value)) {
            throw new \Exception("Dateiname enthält ungültige Zeichen.");
          }

          $data[$key] = $value;
          break;
        case "fileContent":
          if (!is_string($value)) {
            throw new \Exception("Ungültiger Dateinhalt für $key.");
          }
          // Optional: Maximale Größe des Inhalts prüfen (z.B. 5 MB)
          $maxSize = 5 * 1024 * 1024; // 5 MB
          if (strlen($value) > $maxSize) {
            throw new \Exception("Dateinhalt für $key ist zu groß. Maximal erlaubt: 5 MB.");
          }
          $decodedContent = base64_decode($value, true);
          if ($decodedContent === false) {
            throw new \Exception("Ungültiger Base64-kodierter Inhalt für $key.");
          }
          $data[$key] = $decodedContent;
          break;
        default:
          throw new \Exception("Unbekannter Typ '$expectedType' für $key im Schema.");
      }
    }
    return $data;
  }
  public static function validateFileUpload(array $fileTypes, int $maxSize, string $inputName): array {
    if (!isset($_FILES[$inputName])) {
      throw new \Exception("Keine Datei hochgeladen.", 400);
    }

    $file = $_FILES[$inputName];

    if ($file["error"] !== UPLOAD_ERR_OK) {
      throw new \Exception("Fehler beim Hochladen der Datei.", 400);
    }

    // MIME-Typ validieren
    $fileMimeType = mime_content_type($file["tmp_name"]);
    if (!in_array($fileMimeType, $fileTypes)) {
      throw new \Exception("Ungültiger Dateityp: " . $fileMimeType, 400);
    }

    // Dateigröße validieren
    if ($file["size"] > $maxSize) {
      throw new \Exception("Datei ist zu groß. Maximal erlaubt: " . $maxSize / 1024 / 1024 . " MB.", 400);
    }

    // Dateiinhalt auslesen
    $fileContent = file_get_contents($file["tmp_name"]);
    if ($fileContent === false) {
      throw new \Exception("Fehler beim Lesen der Datei.", 400);
    }

    // Ursprünglichen Dateinamen bereinigen
    $originalFileName = $_FILES[$inputName]["name"];
    $safeFileName = self::sanitizeFileName($originalFileName);

    return [$fileContent, $safeFileName];
  }
  /**
   * Bereinigt den Dateinamen für eine sichere Verwendung in E-Mail-Anhängen.
   */
  private static function sanitizeFileName(string $fileName): string {
    // Unerlaubte Zeichen entfernen oder ersetzen
    $fileName = preg_replace("/[^\w.-]/u", "_", $fileName);

    // Doppelte Unterstriche vermeiden
    $fileName = preg_replace("/_{2,}/", "_", $fileName);

    // Sicherstellen, dass der Name nicht leer ist
    return $fileName ?: "datei_" . time();
  }
}
// Beispielverwendung
// $json = '{"clientId": 123, "fromTime": "28:00", "toTime": "2025-03-15 17:00:00", "id": 1}';
// $schema = [
//   "clientId" => "integer",
//   "fromTime" => "time",
//   "toTime" => "time",
//   "id" => "integer",
//   "optionaleVariabe" => "date,optional"
// ];

// try {
//   $result = Validator::validateJsonAgainstSchema($json, $schema);
//   echo "JSON ist gültig. Ergebnis: " . print_r($result, true);
// } catch (\Exception $e) {
//   echo "Fehler: " . $e->getMessage();
// }
