<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('title', 'Tableau de bord') · CaisseFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
<link href="{{ asset('css/readability.css') }}?v=20261001-4" rel="stylesheet">
<link href="{{ asset('css/auth.css') }}?v=20261001-2" rel="stylesheet">
<link href="{{ asset('css/auth-fit.css') }}?v=20261001-1" rel="stylesheet"></head>
<body>@yield('content')<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>@stack('scripts')</body></html>
