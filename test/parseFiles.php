<?php
const DB_HOST = "localhost";
const DB_USER = "archiv_malsfeld";
const DB_PASSWORD = "archiv_malsfeld";
const DB_NAME = "archiv_malsfeld";
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
  die("Connection failed: " . $db->connect_error);
}
$orte = $db->query("SELECT * FROM orte ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$ortMap = [];
foreach ($orte as $o) {
  $ortMap[$o['name']] = $o['id'];
}
$schlagworte = $db->query("SELECT * FROM temp_schlagworte ORDER BY alt")->fetch_all(MYSQLI_ASSOC);
$schlagwortMap = [];
foreach ($schlagworte as $s) {
  $schlagwortMap[$s['alt']] = $s['id_neu'];
}
$stmt = $db->prepare("INSERT INTO dateien (pfad, dateiname, ort_id,dateiart_id,zeitraum_start,zeitraum_ende,gesperrt,aufnahmedatum) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt2 = $db->prepare("INSERT IGNORE INTO datei_schlagwort (datei_id, schlagwort_id) VALUES (?, ?)");
$types = [["type" => "Video", "id" => 15], ["type" => "Audio", "id" => 16], ["type" => "Bilder", "id" => 13], ["type" => "Dokumente", "id" => 14]];
$files = file_get_contents(__DIR__ . "/../docs/archivliste.txt");
$paths = explode("\n", $files);
$saveparts = [];
$db->begin_transaction();
foreach ($paths as $path) {
  $zeitraum_start = null;
  $zeitraum_ende = null;
  $gesperrt = 0;
  $aufnahmedatum = null;
  if ($path != "") {
    $parts = explode("/", $path);
    $filename = array_pop($parts);
    if (!str_contains($filename, ".")) {
      continue;
    }
    $pfad = implode("/", $parts);
    $ortName = $parts[0] ?? null;
    $ortId = $ortMap[$ortName] ?? null;
    if (!isset($parts[1])) {
      echo "Fehler: Kein Typ für Datei '$path'\n";
      continue;
    }
    if (preg_match('/(\d{4})bis(\d{4})/', $filename, $matches)) {
      $zeitraum_start = (int)$matches[1];
      $zeitraum_ende = (int)$matches[2];
    }
    if (preg_match('/(\d{4})_(\d{4})/', $filename, $matches)) {
      $jahr = (int)$matches[1];
      $month = substr($matches[2], 0, 2);
      $day = substr($matches[2], 2, 2);
      if ($month == "00") $month = "01";
      if ($day == "00") $day = "01";
      if (date("Y-m-d", strtotime("$jahr-$month-$day")) == "$jahr-$month-$day") {
        $aufnahmedatum = "$jahr-$month-$day";
      }
      $zeitraum_start = $jahr;
      $zeitraum_ende = $jahr;
    }
    if ($zeitraum_start === null && $zeitraum_ende === null && $aufnahmedatum === null) {
      if (preg_match('/^(zyx_|x)?(\d{4})_/i', $filename, $matches)) {
        $zeitraum_start = (int)$matches[2];
        $zeitraum_ende = (int)$matches[2];
      }
    }
    if (str_contains(strtolower($filename), "zyx")) {
      $gesperrt = 1;
    }
    $typName = preg_replace('/^(Ma_|Be_|Si_|El_|Os_|Da_|Re_|Mo_)/', '', $parts[1]);
    $formatId = array_values(array_filter($types, fn($t) => $t["type"] === $typName))[0]["id"] ?? null;
    $stmt->bind_param("ssiiiiis", $pfad, $filename, $ortId, $formatId, $zeitraum_start, $zeitraum_ende, $gesperrt, $aufnahmedatum);
    $stmt->execute();
    $dateiId = $db->insert_id;
    for ($i = 2; $i < count($parts); $i++) {
      $parts[$i] = preg_replace('/^(Ma_|Be_|Si_|El_|Os_|Da_|Re_|Mo_)/', '', $parts[$i]);
      $schlagwortId = $schlagwortMap[$parts[$i]] ?? null;
      if ($schlagwortId) {
        $stmt2->bind_param("ii", $dateiId, $schlagwortId);
        $stmt2->execute();
      }
    }
  }
}
$db->commit();
