<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><style>
@page{margin:18mm 16mm}*{box-sizing:border-box}body{margin:0;font-family:DejaVu Sans,sans-serif;color:#171717;font-size:10px}.sheet{position:relative;height:258mm}.voucher{height:120mm;position:absolute;left:0;right:0;padding:0 8mm}.voucher-copy-1{top:0}.voucher-copy-2{top:128mm;border-top:1px dashed #8b8b8b;padding-top:8mm}.top{width:100%;border-collapse:collapse;margin-bottom:6mm}.top td{vertical-align:middle;width:33.33%}.logo{max-width:92px;max-height:48px}.company{font-size:9px;font-weight:bold;margin-top:3px}.title{border:1px solid #545454;text-align:center;font-size:12px;font-weight:bold;padding:4px 8px;letter-spacing:.4px}.number{text-align:right;font-size:9px;line-height:1.7}.details{margin-top:7mm;width:100%;border-collapse:collapse}.details td{padding:3px 0;vertical-align:top}.label{width:24mm;font-weight:bold}.value{border-bottom:1px dotted #555;min-height:16px}.amount{font-weight:bold}.object{height:13mm;line-height:1.65}.meta{color:#555;font-size:8px;margin-top:3mm}.sign-title{text-align:center;font-weight:bold;font-size:11px;margin:7mm 0 5mm}.signatures{width:100%;border-collapse:collapse}.signatures td{width:33.33%;text-align:center;height:20mm;vertical-align:top;font-weight:bold;text-decoration:underline}.cancelled{position:absolute;top:48mm;left:48mm;transform:rotate(-16deg);border:3px solid #a12626;color:#a12626;padding:6px 18px;font-size:20px;font-weight:bold;opacity:.65}
</style></head><body>
@php
    $company = $transaction->company ? config('caisse.companies.'.$transaction->company) : null;
    $logoFile = $company ? public_path($company['logo']) : null;
    $logo = $logoFile && is_file($logoFile) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoFile)) : null;
    $amount = number_format($transaction->amount_minor / 100, 2, ',', ' ').' '.config('caisse.currency');
@endphp
<div class="sheet">
@foreach([1, 2] as $copy)
<section class="voucher voucher-copy-{{ $copy }}">
<table class="top"><tr><td>@if($logo)<img class="logo" src="{{ $logo }}" alt="Logo">@endif @if($company)<div class="company">{{ $company['name'] }}</div>@endif</td><td><div class="title">BON DE CAISSE</div></td><td class="number"><strong>N° {{ $transaction->reference }}</strong><br>Date : {{ $transaction->occurred_on->format('d/m/Y') }}</td></tr></table>
@if($transaction->cancelled_at)<div class="cancelled">ANNULÉ</div>@endif
<table class="details"><tr><td class="label">Bénéficiaire :</td><td class="value">{{ $transaction->beneficiary ?: '—' }}</td></tr><tr><td class="label">MONTANT :</td><td class="value amount">{{ $amount }}</td></tr><tr><td class="label">OBJET :</td><td class="value object">{{ $transaction->description }}@if($transaction->justification)<br>{{ $transaction->justification }}@endif</td></tr></table>
<div class="meta">Mode de paiement : {{ App\Models\Transaction::METHODS[$transaction->payment_method] }} · Enregistré par {{ $transaction->user->name }} le {{ $transaction->created_at->format('d/m/Y à H:i') }}</div>
<div class="sign-title">SIGNATURES</div><table class="signatures"><tr><td>GÉRANT</td><td>CAISSE</td><td>BÉNÉFICIAIRE</td></tr></table>
</section>
@endforeach
</div>
</body></html>
