<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController
{
    public function index()
    {
        return view('documents.index', [
            'documents' => Document::with('user')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $max = (int) config('caisse.document_max_kilobytes');
        $data = $request->validate([
            'document' => ['required', 'file', 'max:'.$max, 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt,csv'],
        ], [
            'document.max' => 'Le document ne doit pas dépasser 10 Mo.',
            'document.mimes' => 'Formats acceptés : PDF, Word, Excel, image, texte et CSV.',
        ]);

        $file = $data['document'];
        [$contents, $compressed] = $this->compressedContents($file);
        $path = now()->format('Y/m').'/'.Str::uuid().'.bin';
        $disk = Storage::disk($this->documentsDisk());

        abort_unless($disk->put($path, $contents), 500, 'Le document n’a pas pu être stocké.');

        try {
            Document::create([
                'user_id' => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'original_size' => $file->getSize(),
                'stored_size' => strlen($contents),
                'is_compressed' => $compressed,
            ]);
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        return back()->with('success', 'Document ajouté à l’espace de stockage.');
    }

    public function download(Document $document)
    {
        $contents = Storage::disk($this->documentsDisk())->get($document->storage_path);
        if ($document->is_compressed) {
            $contents = gzdecode($contents);
            abort_if($contents === false, 500, 'Le document stocké est illisible.');
        }

        return response($contents)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', 'attachment; filename="'.addcslashes($document->original_name, '"\\').'"');
    }

    public function destroy(Request $request, Document $document)
    {
        abort_unless($document->canBeDeletedBy($request->user()), 403);
        Storage::disk($this->documentsDisk())->delete($document->storage_path);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }

    private function compressedContents(UploadedFile $file): array
    {
        $original = file_get_contents($file->getRealPath());
        abort_if($original === false, 422, 'Le document est illisible.');

        $compressed = gzencode($original, 9);
        if ($compressed !== false && strlen($compressed) < strlen($original)) {
            return [$compressed, true];
        }

        return [$original, false];
    }

    private function documentsDisk(): string
    {
        return config('caisse.documents_disk') ?: config('filesystems.default');
    }
}
