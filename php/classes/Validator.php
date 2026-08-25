<?php

namespace own;

class Validator {
  private static function isValidDateTime(string $value, string $format): bool {
    $date = \DateTime::createFromFormat($format, $value);
    $errors = \DateTime::getLastErrors();
    return $errors === false && $date !== false;
  }
  public static function validateJsonAgainstSchema(string $jsonData, array $schema): array {
    $data = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception("Ungültiges JSON.", 400);
    }
    $optionalContainers = [];
    $exists = false;
    foreach ($schema as $path => $definition) {
      if (is_string($definition) && self::isContainerDefinition($definition)) {
        if (self::hasWildcard($path)) {
          $matches = self::getWildcardMatches($data, $path);
          $exists = count($matches) > 0;
        } else {
          $exists = false;
          self::getValue($data, $path, $exists);
        }

        if (!$exists && str_contains($definition, "optional")) {
          $optionalContainers[] = $path;
        }
      }
    }
    foreach ($schema as $key => $definition) {
      foreach ($optionalContainers as $object) {
        if (str_starts_with($key . ".", $object . ".")) {
          continue 2;
        }
      }
      $isOptional = false;
      $isEmptyOK = false;
      $isNullOK = false;
      // Support für den Select-Typ
      $allowedValues = [];
      if (is_array($definition) && $definition[0] === "select") {
        $expectedType = "select";
        $allowedValues = $definition[1];
      } else {
        $parts = explode(',', $definition);

        $expectedType = trim(array_shift($parts));

        $isOptional = in_array('optional', $parts, true);
        $isEmptyOK  = in_array('emptyOK', $parts, true);
        $isNullOK  = in_array('nullOK', $parts, true);
      }
      $keysToValidate = [];
      if (self::hasWildcard($key)) {
        $matches = self::getWildcardMatches($data, $key);

        if (count($matches) === 0) {
          if ($isOptional) {
            continue;
          }
          throw new \Exception("Fehlender Parameter $key", 400);
        }

        foreach ($matches as $match) {
          $keysToValidate[] = $match["path"];
        }
      } else {
        $exists = false;
        self::getValue($data, $key, $exists);
        if (!$exists) {
          if ($isOptional) {
            continue;
          } else {
            throw new \Exception("Fehlender Parameter $key", 400);
          }
        }
        $keysToValidate[] = $key;
      }

      foreach ($keysToValidate as $resolvedKey) {
        $exists = false;
        $value = self::getValue($data, $resolvedKey, $exists);
        if ($isEmptyOK && $value === "") {
          continue;
        }
        if ($isNullOK && $value === null) {
          continue;
        }

        switch ($expectedType) {
          case "array":
            if (!is_array($value)) {
              throw new \Exception("Ungültiger Datentyp für $resolvedKey. Erwartet: array.");
            }
            break;

          case "base64":
            if (!is_string($value)) {
              throw new \Exception("Ungültiger Datentyp für $resolvedKey. Erwartet: base64-kodierter String.");
            }
            $decodedValue = base64_decode($value, true);
            if ($decodedValue === false) {
              throw new \Exception("Ungültiger base64-kodierter String für $resolvedKey.");
            }
            self::setValue($data, $resolvedKey, $decodedValue);
            break;
          case "integer":
            if (!is_numeric($value)) {
              throw new \Exception("Ungültiger Datentyp für $resolvedKey. Erwartet: integer.");
            }

            self::setValue($data, $resolvedKey, (int) $value);
            break;

          case "boolean":
            $value = (bool)$value;
            self::setValue($data, $resolvedKey, $value);
            break;

          case "date":
            if (!self::isValidDateTime($value, "Y-m-d")) {
              throw new \Exception("Ungültiges Datumsformat für $resolvedKey. Erwartet: YYYY-MM-DD.");
            }
            break;

          case "datetime":
            if (!self::isValidDateTime($value, "Y-m-d\\TH:i")) {
              throw new \Exception("Ungültiges Datums-Zeit-Format für $resolvedKey. Erwartet: YYYY-MM-DDTHH:MM.");
            }
            break;

          case "month":
            if (!is_numeric($value)) {
              throw new \Exception("Ungültiger Monat für $resolvedKey.");
            }
            $value = (int) $value;
            if ($value < 1 || $value > 12) {
              throw new \Exception("Ungültiger Monat für $resolvedKey. Erlaubt: 1-12.");
            }
            self::setValue($data, $resolvedKey, $value);
            break;

          case "float":
            if (!is_numeric($value)) {
              throw new \Exception("Ungültiger Datentyp für $resolvedKey. Erwartet: Zahl.");
            }
            $value = (float) $value;
            self::setValue($data, $resolvedKey, $value);
            break;
          case "object":
            if (!is_array($value)) {
              throw new \Exception("Ungültiger Datentyp für $resolvedKey. Erwartet: Objekt.");
            }
            break;
          case "select":
            if (!in_array($value, $allowedValues, true)) {
              $allowedList = implode(", ", $allowedValues);
              throw new \Exception("Ungültiger Wert für $resolvedKey. Erlaubt: $allowedList.");
            }
            break;
          case "string":
            if (!is_string($value) && !is_numeric($value)) {
              throw new \Exception("Ungültiges Format - kein String");
            }
            break;

          case "time":
            if (!self::isValidDateTime($value, "H:i")) {
              throw new \Exception("Ungültiges Zeitformat für $resolvedKey. Erwartet: HH:MM.");
            }
            break;

          case "year":
            if (!is_numeric($value)) {
              throw new \Exception("Ungültiges Jahr für $resolvedKey.");
            }
            $value = (int) $value;
            // Optional: dynamische Begrenzung
            $currentYear = (int) date("Y");
            if ($value < 1900 || $value > $currentYear + 5) {
              throw new \Exception("Ungültiges Jahr für $resolvedKey.");
            }
            self::setValue($data, $resolvedKey, $value);
            break;

          case "filename":
            if (!is_string($value)) {
              throw new \Exception("Ungültiger Dateiname für $resolvedKey.");
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

            self::setValue($data, $resolvedKey, $value);
            break;
          case "fileContent":
            if (!is_string($value)) {
              throw new \Exception("Ungültiger Dateinhalt für $resolvedKey.");
            }
            // Optional: Maximale Größe des Inhalts prüfen (z.B. 5 MB)
            $maxSize = 5 * 1024 * 1024; // 5 MB
            if (strlen($value) > $maxSize) {
              throw new \Exception("Dateinhalt für $resolvedKey ist zu groß. Maximal erlaubt: 5 MB.");
            }
            $decodedContent = base64_decode($value, true);
            if ($decodedContent === false) {
              throw new \Exception("Ungültiger Base64-kodierter Inhalt für $resolvedKey.");
            }
            self::setValue($data, $resolvedKey, $decodedContent);
            break;
          default:
            throw new \Exception("Unbekannter Typ '$expectedType' für $resolvedKey im Schema.");
        }
      }
    }
    return $data;
  }
  private static function isContainerDefinition(string $definition): bool {
    $type = trim(explode(',', $definition)[0]);

    return in_array($type, ['object', 'array'], true);
  }
  private static function hasWildcard(string $path): bool {
    return str_contains($path, "*");
  }
  private static function getWildcardMatches(array $data, string $path): array {
    $parts = explode('.', $path);
    $matches = [];

    self::collectWildcardMatches($data, $parts, 0, [], $matches);

    return $matches;
  }
  private static function collectWildcardMatches(mixed $current, array $parts, int $index, array $resolvedParts, array &$matches): void {
    if ($index >= count($parts)) {
      $matches[] = [
        "path" => implode('.', $resolvedParts),
        "value" => $current
      ];
      return;
    }

    $part = $parts[$index];

    if ($part === "*") {
      if (!is_array($current)) {
        return;
      }

      foreach ($current as $childKey => $childValue) {
        $nextParts = $resolvedParts;
        $nextParts[] = (string)$childKey;
        self::collectWildcardMatches($childValue, $parts, $index + 1, $nextParts, $matches);
      }
      return;
    }

    if (!is_array($current) || !array_key_exists($part, $current)) {
      return;
    }

    $resolvedParts[] = $part;
    self::collectWildcardMatches($current[$part], $parts, $index + 1, $resolvedParts, $matches);
  }
  private static function getValue(array $data, string $path, &$exists = false) {
    $parts = explode('.', $path);

    foreach ($parts as $part) {
      if (!is_array($data) || !array_key_exists($part, $data)) {
        $exists = false;
        return null;
      }

      $data = $data[$part];
    }

    $exists = true;
    return $data;
  }
  private static function setValue(array &$data, string $path, mixed $value): void {
    $parts = explode('.', $path);

    $ref = &$data;

    foreach ($parts as $part) {
      if (!isset($ref[$part]) || !is_array($ref[$part])) {
        $ref[$part] = [];
      }

      $ref = &$ref[$part];
    }

    $ref = $value;
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
// $schema = [
//   "archivobjekt.ort_id"       => "integer",
//   "archivobjekt.status"       => "integer",
//   "archivobjekt.beschreibung" => "string,optional",
//   "datei"                     => "object,optional",
//   "datei.*.pfad"              => "string",
//   "datei.*.originalname"      => "filename",

//   "analogobjekt.modus"        => ["select", ["neu", "vorhanden", "keines"]],
//   "analogobjekt.id"           => "integer,optional",
//   "analogobjekt.objekttyp_id" => "integer,optional"
// ];
// $json = '{
//     "archivobjekt": {
//         "ort_id": 5,
//         "status": 1,
//         "beschreibung": "Ein Beispielobjekt"
//     },
//     "datei": [
//         {
//             "pfad": "/uploads/beisp:iel.pdf",
//             "originalname": "beispiel.pdf"
//         },
//         {
//             "pfad": "/uploads/beispiel2.pdf",
//             "originalname": "beispiel2.pdf"
//         }
//     ],
//     "analogobjekt": {
//         "modus": "neu",
//         "id": 42,
//         "objekttyp_id": 3
//     }
// }';

// $result = Validator::validateJsonAgainstSchema($json, $schema);
// print_r($result);
