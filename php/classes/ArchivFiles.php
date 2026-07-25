<?php

namespace own;

use own\FileResponse;
use own\JsonResponse;
use own\ArchivDb;
use own\Validator;

require_once __DIR__ . "/../.clientData.inc.php";

class ArchivFiles {
    private string $baseDir = ARCHIV_FILE_PATH;
    private string $inboxDir = ARCHIV_FILE_PATH . "archiveingang/";
    private int $modifiedbaseDirLength = 0;
    private \finfo|bool $finfo;
    public function __construct() {
        $this->finfo = finfo_open(FILEINFO_MIME_TYPE);
    }
    public function deleteMulti(string $parameters): JsonResponse {
        $param = Validator::validateJsonAgainstSchema($parameters, ["files" => "array"]);
        foreach ($param["files"] as $file) {
            $path = realpath($this->inboxDir . $file);
            if (!$path) {
                throw new \Exception("Datei nicht gefunden", 404);
            } else {
                $deleteOK = unlink($path);
                if (!$deleteOK) {
                    throw new \Exception("Datei " . basename($path) . " konnte nicht gelöscht werden", 500);
                }
            }
        }
        return new JsonResponse(200, [], "OK");
    }
    public function get(int $id): FileResponse {
        $db = ArchivDb::getDbInstance();
        $sql = "SELECT pfad, dateiname FROM dateien WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        $file = $stmt->fetch();
        if (!$file) {
            throw new \Exception("Id nicht gefunden", 400);
        }
        $dateiname = $file["dateiname"];
        $pfad = $this->baseDir . $file["pfad"] . "/" . $dateiname;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $pfad);
        return new FileResponse(
            $dateiname,
            $mimeType,
            null,
            $pfad
        );
    }
    public function getByPath(string $parameters): FileResponse {
        $param = Validator::validateJsonAgainstSchema($parameters, ["path" => "string", "fromInbox" => "boolean"]);
        $path = $param["fromInbox"] ? realpath($this->inboxDir . $param["path"]) : realpath($this->baseDir . $param["path"]);
        if (!$path) {
            throw new \Exception("Datei existiert nicht");
        }
        $dateiname = basename($path);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        return new FileResponse(
            $dateiname,
            $mimeType,
            null,
            $path
        );
    }
    public function getTree(string $parameters): JsonResponse {
        $param = Validator::validateJsonAgainstSchema(
            $parameters,
            ["directory" => "string,optional", "withFiles" => "boolean,optional"]
        );
        $directory = $param["directory"] ?? "";
        $withFiles = $param["withFiles"] ?? false;
        $directory = $this->baseDir . $directory;
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->modifiedbaseDirLength = strlen($directory . DIRECTORY_SEPARATOR);
        return new JsonResponse(200, [$this->buildNode($directory, $withFiles)]);
    }

    private function buildNode(string $path, bool $withFiles): array {
        $node = [
            "name" => basename($path),
            "path" => substr($path, $this->modifiedbaseDirLength),
            "type" => "directory",
            "children" => []
        ];

        $entries = @scandir($path);
        if ($entries === false) {
            return $node;
        }

        natcasesort($entries);

        $directories = [];
        $files = [];

        foreach ($entries as $entry) {
            if ($entry === "." || $entry === "..") {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($fullPath)) {
                $directories[] = $fullPath;
            } elseif ($withFiles) {
                $files[] = $fullPath;
            }
        }

        // Erst Verzeichnisse
        foreach ($directories as $dir) {
            $node["children"][] = $this->buildNode($dir, $withFiles);
        }

        // Dann Dateien
        $sort = 0;
        $fileCount = count($files);

        foreach ($files as $file) {
            $mimeType = finfo_file($this->finfo, $file);

            $node["children"][] = [
                "name" => basename($file),
                "path" => substr($file, $this->modifiedbaseDirLength),
                "type" => "file",
                "mimeType" => $mimeType,
                "sort" => $sort,
                "canMoveUp" => ($sort > 0),
                "canMoveDown" => ($sort < $fileCount - 1)
            ];

            $sort++;
        }

        return $node;
    }
    public function saveFiles(string $parameters) {
        $param = Validator::validateJsonAgainstSchema($parameters, [
            "files" => "array",
            "targetPath" => "string",
            "targetFilename" => "string"
        ]);

        $files = $param["files"];

        if (count($files) === 0) {
            return new JsonResponse(400, [
                "error" => "Keine Dateien angegeben"
            ]);
        }

        $inputFiles = [];

        foreach ($files as $file) {
            $path = realpath($this->inboxDir . $file);
            if (!$path) {
                throw new \Exception("Datei nicht gefunden", 404);
            }
            $inputFiles[] = $path;
        }
        if (count($inputFiles) === 0) {
            throw new \Exception("Keine Quelldateien übergeben", 400);
        }
        $targetPath = realpath($this->baseDir . $param["targetPath"]);
        if (!is_dir($targetPath)) {
            throw new \Exception("Zielpafd nicht vorhanden", 404);
        }
        if (substr($targetPath, -1) !== DIRECTORY_SEPARATOR)
            $targetPath .= DIRECTORY_SEPARATOR;
        $testName = basename($targetPath . $param["targetFilename"]);
        if ($testName !== $param["targetFilename"]) {
            throw new \Exception("Ungültiger Dateiname", 400);
        }
        $targetFile = $targetPath . $param["targetFilename"];
        if (file_exists($targetFile)) {
            throw new \Exception("Datei existiert bereits", 400);
        }
        /*
         * qpdf Syntax:
         * qpdf --empty --pages file1.pdf file2.pdf -- output.pdf
         */
        if (count($inputFiles) === 1) {
            if (!rename($inputFiles[0], $targetFile))
                throw new \Exception("Speichern fehlgeschlagen");
        } else {
            $command = sprintf(
                "qpdf --warning-exit-0 --empty --pages %s -- %s 2>&1",
                implode(" ", $inputFiles),
                escapeshellarg($targetFile)
            );
            exec($command, $output, $returnCode);
            if ($returnCode !== 0) {
                return new JsonResponse(500, [
                    "error" => "PDF-Zusammenführung fehlgeschlagen",
                    "details" => $output
                ]);
            }
            foreach ($inputFiles as $file) {
                unlink($file);
            }
        }
        return new JsonResponse(200, ["file" => $targetFile]);
    }
}
