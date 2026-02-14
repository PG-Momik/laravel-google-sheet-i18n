<?php

declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Routing\Controller;
use LaravelGoogleSheetI18n\Services\GoogleSheetsService;

/**
 * Sync Controller
 *
 * Handles synchronization with Google Sheets.
 */
class SyncController extends Controller
{
    protected GoogleSheetsService $sheetsService;

    public function __construct(GoogleSheetsService $sheetsService)
    {
        $this->sheetsService = $sheetsService;
    }

    /**
     * Upload translations to Google Sheets.
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'target_locale' => 'required|string',
            'source_locale' => 'nullable|string',
            'tag' => 'nullable|string',
        ]);

        $targetLocale = $validated['target_locale'];
        $sourceLocale = $validated['source_locale'] ?? config('app.locale', 'en');
        $tag = $validated['tag'] ?? null;

        // Increase execution time limit to 5 minutes for this request
        set_time_limit(300);

        try {
            $targetLocales = explode(',', $validated['target_locale']);
            $results = [];
            $allSuccess = true;
            $aggregatedOutput = "";

            foreach ($targetLocales as $locale) {
                $locale = trim($locale);
                if (empty($locale))
                    continue;

                $aggregatedOutput .= "> Processing locale: {$locale}...\n";

                // Run the translate command directly for each locale
                $exitCode = Artisan::call('translate:sheet', [
                    'target_locale' => $locale,
                    '--source' => $sourceLocale,
                    '--tag' => $tag,
                ]);

                $output = Artisan::output();
                $aggregatedOutput .= $output . "\n";

                if ($exitCode !== 0) {
                    $allSuccess = false;
                }
            }

            return response()->json([
                'success' => $allSuccess,
                'message' => $allSuccess ? 'Translation generation completed successfully for all locales.' : 'Translation generation finished with some errors.',
                'output' => $aggregatedOutput,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'output' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Download translations from Google Sheets.
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'target_locale' => 'required|string',
            'source_locale' => 'nullable|string',
            'tag' => 'nullable|string',
        ]);

        // Same as upload - the command handles both upload and download
        return $this->upload($request);
    }

    /**
     * Get sync status.
     */
    public function status()
    {
        $spreadsheetId = config('google-sheet-i18n.spreadsheet_id');
        $configured = !empty($spreadsheetId);

        return response()->json([
            'configured' => $configured,
            'spreadsheet_id' => $spreadsheetId,
            'service_account' => config('google-sheet-i18n.service_account_path') ? 'Configured' : 'Not configured',
        ]);
    }

    /**
     * List available sheets.
     */
    public function sheets()
    {
        try {
            $spreadsheetId = config('google-sheet-i18n.spreadsheet_id');

            if (empty($spreadsheetId)) {
                return view('translation-manager::sheets.index', [
                    'sheets' => [],
                    'error' => 'Spreadsheet ID not configured. Set GOOGLE_SHEET_I18N_ID in your .env file.',
                ]);
            }

            // Get list of sheets from Google Sheets
            $sheets = $this->getAvailableSheets();

            return view('translation-manager::sheets.index', [
                'sheets' => $sheets,
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return view('translation-manager::sheets.index', [
                'sheets' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get available sheets from Google Sheets.
     */
    /**
     * Get available sheets from Google Sheets.
     */
    protected function getAvailableSheets(): array
    {
        try {
            $sheets = $this->sheetsService->getSheets();

            return array_map(function ($sheet) {
                return [
                    'name' => $sheet['title'],
                    'created_at' => 'Sheet ID: ' . $sheet['id'], // Valid info since creation date isn't available
                    'rows' => $sheet['gridProperties']['rowCount'] ?? 0,
                ];
            }, $sheets);
        } catch (\Exception $e) {
            // Fallback if API fails
            return [];
        }
    }
}
