<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Support\Traits;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait StreamDownload
{
    protected function streamFile(string $filePath, string $filename, array $headers = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($filePath) {
            $handle = fopen($filePath, 'r');
            if ($handle) {
                while (! feof($handle)) {
                    echo fread($handle, 8192);
                }
                fclose($handle);
            }

            @unlink($filePath);
        }, $filename, $headers);
    }
}
