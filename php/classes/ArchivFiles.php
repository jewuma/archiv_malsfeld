<?php

namespace own;
use own\FileResponse;
use own\JsonResponse;
use own\ArchivDb;
use own\Validator;
class ArchivFiles
{
    private $baseDir = __DIR__ . "/../../../archivdateien/";
    private $inboxDir = __DIR__ . "/../../../archivdateien/archiveingang/";
    private $modifiedbaseDirLength = "";
    public function get(int $id): FileResponse
    {
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

    public function getTree(string $parameters): JsonResponse
    {
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

    private function buildNode(string $path, bool $withFiles): array
    {
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
        $sort = 0;
        $lastFile = null;
        foreach ($entries as $entry) {
            if ($entry === "." || $entry === "..") {
                continue;
            }
            $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($fullPath)) {
                $node["children"][] = $this->buildNode($fullPath, $withFiles);
            } elseif ($withFiles) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $canMoveUp = ($sort !== 0);
                $mimeType = finfo_file($finfo, $fullPath);
                $node["children"][] = [
                    "name" => $entry,
                    "path" => substr($fullPath, $this->modifiedbaseDirLength),
                    "type" => "file",
                    "mimeType" => $mimeType,
                    "sort" => $sort++,
                    "canMoveUp" => $canMoveUp,
                    "canMoveDown" => true
                ];
                $lastFile = count($node["children"]);
            }
        }
        if ($lastFile)
            $node["children"][$lastFile - 1]["canMoveDown"] = false;
        return $node;
    }
    public function implodeFiles($parameters)
    {
        $param = Validator::validateJsonAgainstSchema($parameters, [
            "files" => "array"
        ]);

        $files = $param["files"];

        if (count($files) === 0) {
            return new JsonResponse(400, [
                "error" => "Keine Dateien angegeben"
            ]);
        }

        $inputFiles = [];

        foreach ($files as $file) {
            // Hier ggf. deine eigene Prüfung gegen das Archiv-Verzeichnis einbauen
            if (!file_exists($this->inboxDir . $file)) {
                return new JsonResponse(404, [
                    "error" => "Datei nicht gefunden: " . $file
                ]);
            }

            $inputFiles[] = $this->inboxDir . escapeshellarg($file);
        }

        // Zieldatei erzeugen
        $outputDir = sys_get_temp_dir();
        $newFilename = $outputDir . "/zusammenfassung_" . uniqid() . ".pdf";

        /*
         * qpdf Syntax:
         * qpdf --empty --pages file1.pdf file2.pdf -- output.pdf
         */
        $command = sprintf(
            "qpdf --empty --pages %s -- %s 2>&1",
            implode(" ", $inputFiles),
            escapeshellarg($newFilename)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return new JsonResponse(500, [
                "error" => "PDF-Zusammenführung fehlgeschlagen",
                "details" => $output
            ]);
        }

        return new JsonResponse(200, [
            "file" => $newFilename
        ]);
    }
}