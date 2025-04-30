<?php

namespace App\Helpers;

class LogTailHelper
{
    protected string $logPath;
    protected int $startPosition = 0;

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    public function markPosition(): void
    {
        if (file_exists($this->logPath)) {
            $this->startPosition = filesize($this->logPath);
        }
    }

    public function getNewLogContent(): string
    {
        if (!file_exists($this->logPath)) {
            return '';
        }

        $handle = fopen($this->logPath, 'r');
        if ($handle === false) {
            return '';
        }

        fseek($handle, $this->startPosition);
        $newContent = stream_get_contents($handle);
        fclose($handle);

        return $newContent ?: '';
    }

    public function getNewLogLines(): string
    {
        $newLines = explode(PHP_EOL, trim($this->getNewLogContent()));
        $result="";
        foreach ($newLines as $line) {
            $result=$result.$line."<br>";
        }
        //return array_filter(explode(PHP_EOL, $this->getNewLogContent()));
        return $result;
    }
}
