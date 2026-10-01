@extends('layout')
@section('title', 'Documents')
@section('content')
@include('partials.sidebar')
@php
    $fileSize = function (int $bytes): string {
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', ' ').' Mo';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1, ',', ' ').' Ko';
        return $bytes.' o';
    };
@endphp
<div class="main-shell">
    <header class="topbar"><span>Mon espace <span class="muted mx-2">/</span> <strong>Documents</strong></span><span class="top-date">{{ now()->translatedFormat('l d F Y') }}</span></header>
    <main class="dashboard">
        <div class="page-heading"><div><p class="eyebrow">ESPACE DOCUMENTAIRE PARTAGÉ</p><h1>Documents<span class="heading-dot">.</span></h1><p class="muted">Centralisez les documents utiles de l’entreprise sans stocker leur contenu dans la base de données.</p></div></div>

        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Le document n’a pas pu être ajouté.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="panel document-upload-panel">
            <div class="panel-heading"><div><h2>Ajouter un document</h2><p>PDF, Word, Excel, JPG, PNG, TXT ou CSV · 10 Mo maximum</p></div></div>
            <form method="post" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="document-upload-form">@csrf
                <div class="document-description-field"><label class="form-label" for="description">Description du document</label><textarea class="form-control" id="description" name="description" rows="3" maxlength="1000" placeholder="Exemple : Rapport mensuel des dépenses de septembre 2026" required>{{ old('description') }}</textarea><div class="form-text">Indiquez clairement le contenu ou l’utilité du document.</div></div>
                <div class="document-file-field"><label class="form-label" for="document">Choisir un fichier</label><input class="form-control" id="document" type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt,.csv" required><div class="form-text">Le fichier est compressé lorsque cela réduit sa taille. Son contenu n’est jamais enregistré dans MySQL.</div></div>
                <button class="btn btn-primary" type="submit">Ajouter le document</button>
            </form>
        </section>

        <section class="panel operations mt-4">
            <div class="panel-heading"><div><h2>Documents partagés <span class="count-tag">{{ $documents->total() }}</span></h2><p>Tous les utilisateurs autorisés peuvent consulter et télécharger ces fichiers.</p></div></div>
            <div class="table-responsive"><table class="table ledger-table"><thead><tr><th>Document</th><th>Ajouté par</th><th>Date</th><th>Taille stockée</th><th class="text-end">Actions</th></tr></thead><tbody>
            @forelse($documents as $document)
                <tr><td><div class="operation-cell"><span class="operation-icon neutral">▤</span><div><strong>{{ $document->original_name }}</strong><small class="document-description">{{ $document->description ?: 'Aucune description renseignée' }}</small><small>{{ $document->mime_type }}</small></div></div></td><td>{{ $document->user->name }}</td><td><span class="text-nowrap">{{ $document->created_at->format('d/m/Y') }}</span><small class="d-block muted">{{ $document->created_at->format('H:i') }}</small></td><td>{{ $fileSize($document->stored_size) }}@if($document->is_compressed)<small class="d-block text-income">Compressé · original : {{ $fileSize($document->original_size) }}</small>@endif</td><td class="text-end"><a class="btn btn-sm btn-light" href="{{ route('documents.download', $document) }}">Télécharger</a>@if($document->canBeDeletedBy(auth()->user()))<form class="d-inline" method="post" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Supprimer définitivement ce document ?')">@csrf @method('DELETE')<button class="btn btn-sm cancel-button" type="submit">Supprimer</button></form>@endif</td></tr>
            @empty
                <tr><td colspan="5"><div class="empty-state"><span>▤</span><h3>Aucun document</h3><p>Ajoutez le premier document partagé de l’entreprise.</p></div></td></tr>
            @endforelse
            </tbody></table></div><div class="pagination-wrap">{{ $documents->links('pagination::bootstrap-5') }}</div>
        </section>
        <footer class="page-footer"><span>CaisseFlow <span class="muted">Documents partagés de l’entreprise.</span></span><span>{{ $documents->total() }} documents</span></footer>
    </main>
</div>
@endsection
