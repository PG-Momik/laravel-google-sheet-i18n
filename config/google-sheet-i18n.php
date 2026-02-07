<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Sheets Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the settings for the Google Sheets integration.
    |
    */

    /**
     * The path to your Google Cloud Service Account JSON key file.
     * Ensure this file is not committed to version control.
     */
    'service_account_path' => storage_path('app/google-service-account.json'),

    /**
     * The ID of the Google Spreadsheet to use for translations.
     * This ID is found in the URL of your Google Sheet:
     * https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/edit
     */
    'spreadsheet_id' => env('GOOGLE_SHEET_I18N_ID'),

    /**
     * The default source locale to translate from.
     */
    'source_locale' => 'en',

    /**
     * Polling configuration for fetching translations.
     */
    'polling' => [
        'interval' => 2, // Seconds to wait between checks
        'max_attempts' => 30, // Maximum number of attempts before giving up
    ],
];
