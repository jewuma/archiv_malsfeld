<?php

namespace own;
use own\FileResponse;
use own\JsonResponse;
use own\ArchivDb;
class ArchivFiles
{
    private $basedir = __DIR__ . "/../../../archivdateien/";
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
        $pfad = $this->basedir . $file["pfad"] . "/" . $dateiname;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $pfad);
        return new FileResponse(
            $dateiname,
            $mimeType,
            null,
            $pfad
        );
    }
    public function getTree(): JsonResponse
    {
        $db = ArchivDb::getDbInstance();

        $sql = "SELECT
            id,
            parent_id,
            name,
            verzeichnis
        FROM archivpfad
        WHERE aktiv = 1
        ORDER BY parent_id,id";

        $stmt = $db->query($sql);

        $nodes = [];

        while ($row = $stmt->fetch()) {

            $nodes[$row['id']] = [
                'id' => (int) $row['id'],
                'parent_id' => $row['parent_id'],
                'name' => $row['name'],
                'folder' => $row['verzeichnis'],
                'children' => []
            ];
        }
        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parent = $node["parent_id"];
            if ($parent !== 0) {
                $nodes[$parent]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        return new JsonResponse(200, $tree);
    }
}