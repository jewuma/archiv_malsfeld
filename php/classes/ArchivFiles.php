<?php

namespace own;

use own\FileResponse;
use own\JsonResponse;
use own\ArchivDb;
use own\Validator;
use Imagick;

require_once __DIR__ . "/../.clientData.inc.php";

class ArchivFiles {
    private string $baseDir = ARCHIV_FILE_PATH;
    private string $inboxDir = ARCHIV_FILE_PATH . "archiveingang/";
    //private string $cacheDir = ARCHIV_FILE_PATH . "cache/";
    private int $modifiedbaseDirLength = 0;
    private \finfo|bool $finfo;
    public function __construct() {
        $this->finfo = finfo_open(FILEINFO_MIME_TYPE);
    }
    public function createFolder(string $parameters): JsonResponse {
        $param = Validator::validateJsonAgainstSchema($parameters, ["directory" => "string", "folderName" => "filename"]);
        $directory = realpath($this->baseDir . $param["directory"]);
        if (!$directory || !is_dir($directory)) {
            throw new \Exception("Verzeichnis existiert nicht", 404);
        }
        if (substr($directory, -1) !== DIRECTORY_SEPARATOR)
            $directory .= DIRECTORY_SEPARATOR;
        $newFolderPath = $directory . $param["folderName"];
        if (file_exists($newFolderPath)) {
            throw new \Exception("Ordner existiert bereits", 400);
        }
        if (!mkdir($newFolderPath, 0777, true)) {
            throw new \Exception("Ordner konnte nicht erstellt werden", 500);
        }
        return new JsonResponse(200, [], "OK");
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
    public function getBrowserCompatible(int $id): FileResponse {
        $file = $this->get($id);
        $path = $file->filePathForStreaming;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'pdf') {
            return $file;
        }
        $file->filePathForStreaming = substr($file->filePathForStreaming, strlen($this->baseDir));
        return $this->getPreviewByPath(json_encode(["path" => $file->filePathForStreaming, "fromInbox" => false]));
    }
    public function getPreviewByPath(string $parameters): FileResponse {
        $file = $this->getByPath($parameters);
        $path = $file->filePathForStreaming;

        $tmpFile = tempnam(sys_get_temp_dir(), 'preview_') . '.jpg';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        switch ($extension) {

            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                $image = new Imagick($path);
                $image->setImageFormat('webp');
                $image->thumbnailImage(1200, 1200, true);
                $image->setImageFormat('webp');
                $image->writeImage($tmpFile);
                break;
            case 'tif':
            case 'tiff':
                $image = new Imagick($path . '[0]');      // erste Seite
                $image->thumbnailImage(1200, 1200, true);
                $image->setImageFormat('webp');
                $image->writeImage($tmpFile);
                break;

            case 'pdf':
                $image = new Imagick();
                $image->setResolution(150, 150);
                $image->readImage($path . '[0]');          // erste Seite
                $image->thumbnailImage(1200, 1200, true);
                $image->setImageFormat('webp');
                $image->writeImage($tmpFile);
                break;

            default:
                throw new \Exception(
                    "Für diesen Dateityp ist keine Vorschau verfügbar.",
                    400
                );
        }

        return new FileResponse(
            basename($tmpFile),
            'image/webp',
            null,
            $tmpFile
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
            "targetFilename" => "string",
            "keepSource" => "boolean,optional",
            "combine" => "boolean,optional",
            "useFilePrefix" => "string,optional,nullOK"
        ]);

        $files = $param["files"];
        $keepSource = $param["keepSource"] ?? false;
        $combine = $param["combine"] ?? false;
        $useFilePrefix = null;
        if (isset($param["useFilePrefix"]) && is_string($param["useFilePrefix"]) && $param["useFilePrefix"] !== "") {
            $useFilePrefix = $param["useFilePrefix"];
        }

        if (count($files) === 0) {
            throw new \Exception("Keine Dateien angegeben", 400);
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
            throw new \Exception("Zielpfad nicht vorhanden", 404);
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
        if ($combine && count($inputFiles) > 1) {
            $escapedInputFiles = array_map("escapeshellarg", $inputFiles);
            $command = sprintf(
                "qpdf --warning-exit-0 --empty --pages %s -- %s 2>&1",
                implode(" ", $escapedInputFiles),
                escapeshellarg($targetFile)
            );
            exec($command, $output, $returnCode);
            if ($returnCode !== 0) {
                throw new \Exception("PDF-Zusammenführung fehlgeschlagen: " . implode("\n", $output), 500);
            }
            if (!$keepSource) {
                foreach ($inputFiles as $file) {
                    unlink($file);
                }
            }
        } else {
            if (count($inputFiles) > 1) {
                $savedFiles = [];
                $index = 1;
                foreach ($inputFiles as $sourceFile) {
                    $sourceExtension = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));
                    $sourceBasename = basename($sourceFile);
                    if ($useFilePrefix !== null) {
                        $targetName = $useFilePrefix . "_" . str_pad((string) $index, 5, "0", STR_PAD_LEFT);
                        if ($sourceExtension !== "") {
                            $targetName .= "." . $sourceExtension;
                        }
                    } else {
                        $targetName = $sourceBasename;
                    }

                    if (basename($targetName) !== $targetName) {
                        throw new \Exception("Ungültiger Dateiname", 400);
                    }

                    $targetFileForSource = $targetPath . $targetName;
                    if (file_exists($targetFileForSource)) {
                        throw new \Exception("Datei existiert bereits", 400);
                    }

                    if ($keepSource) {
                        if (!copy($sourceFile, $targetFileForSource)) {
                            throw new \Exception("Speichern fehlgeschlagen");
                        }
                    } elseif (!rename($sourceFile, $targetFileForSource)) {
                        throw new \Exception("Speichern fehlgeschlagen");
                    }
                    $savedFiles[] = $targetFileForSource;
                    $index++;
                }
                return new JsonResponse(200, ["files" => $savedFiles]);
            }
            $sourceFile = $inputFiles[0];
            if ($keepSource) {
                if (!copy($sourceFile, $targetFile)) {
                    throw new \Exception("Speichern fehlgeschlagen");
                }
            } elseif (!rename($sourceFile, $targetFile)) {
                throw new \Exception("Speichern fehlgeschlagen");
            }
        }
        return new JsonResponse(200, ["file" => $targetFile]);
    }

    public function finalizeSavedFiles(string $parameters): JsonResponse {
        $param = Validator::validateJsonAgainstSchema($parameters, [
            "files" => "array"
        ]);

        foreach ($param["files"] as $file) {
            $path = realpath($this->inboxDir . $file);
            if (!$path) {
                continue;
            }
            if (!unlink($path)) {
                throw new \Exception("Datei " . basename($path) . " konnte nicht gelöscht werden", 500);
            }
        }
        return new JsonResponse(200, [], "OK");
    }

    public function cleanupSavedFile(string $parameters): JsonResponse {
        $param = Validator::validateJsonAgainstSchema($parameters, [
            "targetPath" => "string",
            "targetFilename" => "string",
            "files" => "array,optional",
            "combine" => "boolean,optional",
            "combineFiles" => "boolean,optional",
            "useFilePrefix" => "string,optional,nullOK"
        ]);

        $targetPath = realpath($this->baseDir . $param["targetPath"]);
        if (!$targetPath || !is_dir($targetPath)) {
            return new JsonResponse(200, [], "OK");
        }
        if (substr($targetPath, -1) !== DIRECTORY_SEPARATOR) {
            $targetPath .= DIRECTORY_SEPARATOR;
        }

        $testName = basename($targetPath . $param["targetFilename"]);
        if ($testName !== $param["targetFilename"]) {
            throw new \Exception("Ungültiger Dateiname", 400);
        }

        $combine = $param["combine"] ?? ($param["combineFiles"] ?? false);
        $useFilePrefix = null;
        if (isset($param["useFilePrefix"]) && is_string($param["useFilePrefix"]) && $param["useFilePrefix"] !== "") {
            $useFilePrefix = $param["useFilePrefix"];
        }

        if (!$combine && isset($param["files"]) && is_array($param["files"]) && count($param["files"]) > 1) {
            $index = 1;
            foreach ($param["files"] as $file) {
                $sourceExtension = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
                $sourceBasename = basename((string) $file);
                if ($useFilePrefix !== null) {
                    $targetName = $useFilePrefix . "_" . str_pad((string) $index, 5, "0", STR_PAD_LEFT);
                    if ($sourceExtension !== "") {
                        $targetName .= "." . $sourceExtension;
                    }
                } else {
                    $targetName = $sourceBasename;
                }

                if (basename($targetName) !== $targetName) {
                    throw new \Exception("Ungültiger Dateiname", 400);
                }

                $targetFile = $targetPath . $targetName;
                if (file_exists($targetFile) && !unlink($targetFile)) {
                    throw new \Exception("Gespeicherte Datei konnte nicht entfernt werden", 500);
                }
                $index++;
            }
            return new JsonResponse(200, [], "OK");
        }

        $targetFile = $targetPath . $param["targetFilename"];
        if (file_exists($targetFile)) {
            if (!unlink($targetFile)) {
                throw new \Exception("Gespeicherte Datei konnte nicht entfernt werden", 500);
            }
        }
        return new JsonResponse(200, [], "OK");
    }
}
