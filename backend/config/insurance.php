<?php

return [
    'expiring_days' => 30,
    'block_same_type_overlap' => env('INSURANCE_BLOCK_OVERLAP', true),
    'document_max_kb' => 10240,
    'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
];
