<?php

namespace App;

class JsonDataManager
{
    private $dataDir;

    public function __construct($dataDir)
    {
        $this->dataDir = $dataDir;
    }

    private function getFilePath($filename)
    {
        return $this->dataDir . DIRECTORY_SEPARATOR . $filename . '.json';
    }

    public function readData($filename)
    {
        $filePath = $this->getFilePath($filename);
        if (!file_exists($filePath)) {
            return [];
        }
        $content = file_get_contents($filePath);
        return json_decode($content, true);
    }

    public function writeData($filename, array $data)
    {
        $filePath = $this->getFilePath($filename);
        $content = json_encode($data, JSON_PRETTY_PRINT);
        return file_put_contents($filePath, $content);
    }
}