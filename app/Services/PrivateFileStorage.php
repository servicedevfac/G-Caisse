<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivateFileStorage
{
    public function store(UploadedFile $file, string $folder): array
    {
        $original = file_get_contents($file->getRealPath());
        abort_if($original === false, 422, 'Le fichier est illisible.');

        $compressedContents = gzencode($original, 9);
        $isCompressed = $compressedContents !== false && strlen($compressedContents) < strlen($original);
        $contents = $isCompressed ? $compressedContents : $original;
        $path = trim($folder, '/').'/'.now()->format('Y/m').'/'.Str::uuid().'.bin';

        abort_unless($this->disk()->put($path, $contents), 500, 'Le fichier n’a pas pu être stocké.');

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'original_size' => $file->getSize(),
            'stored_size' => strlen($contents),
            'is_compressed' => $isCompressed,
        ];
    }

    public function contents(string $path, bool $isCompressed): string
    {
        $contents = $this->disk()->get($path);
        if ($isCompressed) {
            $contents = gzdecode($contents);
            abort_if($contents === false, 500, 'Le fichier stocké est illisible.');
        }

        return $contents;
    }

    public function delete(?string $path): void
    {
        if ($path) $this->disk()->delete($path);
    }

    private function disk()
    {
        return Storage::disk(config('caisse.documents_disk') ?: config('filesystems.default'));
    }
}
