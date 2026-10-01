<?php

return [
    'currency' => env('CAISSE_CURRENCY', 'XOF'),
    'operation_start_date' => '2024-01-01',
    'documents_disk' => env('DOCUMENTS_DISK'),
    'document_max_kilobytes' => 10240,
    'companies' => [
        'fid' => ['name' => 'FID', 'logo' => 'images/companies/fid.jpeg'],
        'voyage_edifiant' => ['name' => 'VOYAGEDIFIANT', 'logo' => 'images/companies/voyage-edifiant.jpeg'],
        'firme_attou_co' => ['name' => 'FIRME ATTOU & CO', 'logo' => 'images/companies/firme-attou-co.jpeg'],
        'fac_immobilier' => ['name' => 'FAC IMMOBILIER', 'logo' => 'images/companies/fac-immobilier.jpeg'],
    ],
];
