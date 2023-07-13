<?php

function mapPathToJson($path) {
    $folders = ['zdenektomis'];
    $filename = basename($path) . '.json';

    foreach ($folders as $folder) {
        $jsonPath = searchJsonFile($folder, $filename);

        if ($jsonPath !== null) {
            return $jsonPath;
        }
    }

    return null;
}

function searchJsonFile($folder, $filename) {
    $folderPath = $folder . '/';
    $files = scandir($folderPath);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $filePath = $folderPath . $file;

        if (is_dir($filePath)) {
            $jsonPath = searchJsonFile($filePath, $filename);

            if ($jsonPath !== null) {
                return $jsonPath;
            }
        } elseif (is_file($filePath) && $file === $filename) {
            return $filePath;
        }
    }

    return null;
}

// Example usage:
$path = $_SERVER['REQUEST_URI'];
$path = str_replace('/api/', '', $path);
$path = preg_replace("/[^a-zA-Z0-9-]+/", "", $path);
$result = mapPathToJson($path);

if ($result !== null) {
    $modified = date("F d Y H:i:s.", filemtime($result));
    header("Last-Modified: {$modified}");
    header('Content-Type: application/json, charset=utf-8');
    echo mb_convert_encoding( file_get_contents($result), 'HTML-ENTITIES', "UTF-8");
} else {
    http_response_code(404);
    echo "Tournament not found.";
}

?>
