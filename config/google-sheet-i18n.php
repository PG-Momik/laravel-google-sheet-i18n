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
        'interval' => 2,  // Seconds to wait between checks
        'max_attempts' => 30, // Maximum number of attempts before giving up
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Manager UI
    |--------------------------------------------------------------------------
    |
    | Configure the web UI for managing translations.
    |
    */

    'ui' => [
        /**
         * Enable or disable the Translation Manager UI.
         */
        'enabled' => env('TRANSLATION_MANAGER_ENABLED', true),

        /**
         * Custom authorization callback.
         * Return true to allow access, false to deny.
         * 
         * Example:
         * 'authorization' => function ($request) {
         *     return $request->user() && $request->user()->isAdmin();
         * }
         */
        'authorization' => null,

        /**
         * Middleware to apply to Translation Manager routes.
         */
        'middleware' => ['web'],
    ],
];
