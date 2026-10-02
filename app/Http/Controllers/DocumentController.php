<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\PrivateFileStorage;
use Illuminate\Http\Request;

class DocumentController
{
    public function index()
    {
        return view('documents.index', [
            'documents' => Document::with('user')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request, PrivateFileStorage $storage)
    {
        $max = (int) config('caisse.document_max_kilobytes');
        $data = $request->validate([
            'description' => ['required', 'string', 'max:1000'],
            'document' => ['required', 'file', 'max:'.$max, 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt,csv'],
        ], [
            'description.required' => 'Décrivez brièvement le contenu ou l’utilité du document.',
            'description.max' => 'La description ne doit pas dépasser 1 000 caractères.',
            'document.max' => 'Le document ne doit pas dépasser 10 Mo.',
            'document.mimes' => 'Formats acceptés : PDF, Word, Excel, image, texte et CSV.',
        ]);

        $file = $data['document'];
        $stored = $storage->store($file, 'documents');

        try {
            Document::create([
                'user_id' => $request->user()->id,
                'description' => $data['description'],
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $stored['path'],
                'mime_type' => $stored['mime_type'],
                'original_size' => $stored['original_size'],
                'stored_size' => $stored['stored_size'],
                'is_compressed' => $stored['is_compressed'],
            ]);
        } catch (\Throwable $exception) {
            $storage->delete($stored['path']);
            throw $exception;
        }

        return back()->with('success', 'Document ajouté à l’espace de stockage.');
    }

    public function download(Document $document, PrivateFileStorage $storage)
    {
        $contents = $storage->contents($document->storage_path, $document->is_compressed);

        return response($contents)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', 'attachment; filename="'.addcslashes($document->original_name, '"\\').'"');
    }

    public function destroy(Request $request, Document $document, PrivateFileStorage $storage)
    {
        abort_unless($document->canBeDeletedBy($request->user()), 403);
        $storage->delete($document->storage_path);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }

}
