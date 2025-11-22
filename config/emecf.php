<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration e-MECeF
    |--------------------------------------------------------------------------
    |
    | Configuration pour le service e-MECeF (Module de contrôle dématérialisé)
    | Ce service permet de communiquer avec l'API de la DGI du Bénin
    | pour la gestion des factures normalisées
    |
    */

    "test_mode" => env("EMECF_TEST_MODE", true),

    "token" => env("EMECF_TOKEN"),

    "urls" => [
        "test" => [
            "invoice" => "https://developper.impots.bj/sygmef-emcf/api/",
            "info" => "https://developper.impots.bj/sygmef-emcf/api/",
        ],
        "production" => [
            "invoice" => "https://sygmef.impots.bj/emcf/api/",
            "info" => "https://sygmef.impots.bj/emcf/api/",
        ],
    ],

    "http" => [
        "timeout" => env("EMECF_TIMEOUT", 30),
        "connect_timeout" => env("EMECF_CONNECT_TIMEOUT", 10),
        "retry" => env("EMECF_RETRY", 3),
        "retry_delay" => env("EMECF_RETRY_DELAY", 1000),
    ],

    "database" => [
        "save_invoices" => env("EMECF_SAVE_INVOICES", true),
        "save_logs" => env("EMECF_SAVE_LOGS", true),
        "log_level" => env("EMECF_LOG_LEVEL", "error"),
    ],

    "default_tax_rates" => [
        "a" => 0,
        "b" => 18,
        "c" => 0,
        "d" => 18,
        "e" => 0,
        "f" => 0,
        "aib_a" => 1,
        "aib_b" => 5,
    ],

    "timeouts" => [
        "invoice_submission" => env("EMECF_INVOICE_TIMEOUT", 30),
        "finalization" => env("EMECF_FINALIZATION_TIMEOUT", 30),
        "status_check" => env("EMECF_STATUS_TIMEOUT", 10),
        "info_request" => env("EMECF_INFO_TIMEOUT", 10),
    ],

    "retry" => [
        "max_attempts" => env("EMECF_MAX_RETRY", 3),
        "delay" => env("EMECF_RETRY_DELAY", 1000),
        "backoff_multiplier" => env("EMECF_BACKOFF_MULTIPLIER", 2),
    ],

    "cache" => [
        "tax_groups_ttl" => env("EMECF_CACHE_TAX_GROUPS", 3600),
        "invoice_types_ttl" => env("EMECF_CACHE_INVOICE_TYPES", 3600),
        "payment_types_ttl" => env("EMECF_CACHE_PAYMENT_TYPES", 3600),
        "emcf_info_ttl" => env("EMECF_CACHE_EMCF_INFO", 300),
    ],
];
