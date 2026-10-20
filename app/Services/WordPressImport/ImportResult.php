<?php

namespace App\Services\WordPressImport;

class ImportResult
{
    public int $imported = 0;

    public int $skipped = 0;

    public int $failed = 0;

    /** @var array<int, string> */
    public array $messages = [];

    public function imported(string $message): void
    {
        $this->imported++;
        $this->messages[] = "✓ {$message}";
    }

    public function skipped(string $message): void
    {
        $this->skipped++;
        $this->messages[] = "– {$message}";
    }

    public function failed(string $message): void
    {
        $this->failed++;
        $this->messages[] = "✗ {$message}";
    }

    public function total(): int
    {
        return $this->imported + $this->skipped + $this->failed;
    }
}
