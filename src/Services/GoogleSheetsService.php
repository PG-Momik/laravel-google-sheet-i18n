<?php

declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Exception;

/**
 * Google Sheets Service
 *
 * Handles all interactions with Google Sheets API for translation management.
 * Manages sheet creation, data writing, and polling for translated results.
 */
class GoogleSheetsService
{
    /**
     * Google API client instance.
     */
    protected Client $client;

    /**
     * Google Sheets service instance.
     */
    protected Sheets $service;

    /**
     * Target spreadsheet ID.
     */
    protected string $spreadsheetId;

    /**
     * Create a new Google Sheets service instance.
     */
    public function __construct(array $config)
    {
        $this->client = new Client();
        $this->client->setApplicationName('Laravel Google Sheet I18n');
        $this->client->setScopes([Sheets::SPREADSHEETS]);

        /** @var string|array $authConfig */
        $authConfig = $config['service_account_path'];
        $this->client->setAuthConfig($authConfig);
        $this->client->setAccessType('offline');

        $this->service = new Sheets($this->client);

        /** @var string $spreadsheetId */
        $spreadsheetId = $config['spreadsheet_id'];
        $this->spreadsheetId = $spreadsheetId;
    }

    /**
     * Ensure a sheet with the given title exists.
     * Creates the sheet if it doesn't already exist.
     */
    public function ensureSheetExists(string $title): void
    {
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);

        // Check if sheet already exists
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $title) {
                return;
            }
        }

        // Create new sheet
        $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
            'requests' => [
                'addSheet' => [
                    'properties' => [
                        'title' => $title
                    ]
                ]
            ]
        ]);

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }

    /**
     * Write translation request to Google Sheets.
     *
     * Sheet Layout:
     * | File      | Key      | Tag     | EN (source) | ES | FR | ...
     * | auth.php  | failed   | default | Failed      | =GOOGLETRANSLATE(...) | ...
     * | auth.php  | throttle | default | Throttle    | =GOOGLETRANSLATE(...) | ...
     *
     * @param array $rows Array of [key, source_text] pairs
     * @param string $sourceLocale Source language code (e.g., 'en')
     * @param string $targetLocale Target language code (e.g., 'es')
     * @param string $sheetName Name of the sheet to write to
     * @param string $tag Tag for traceability (e.g., 'JIRA-1234')
     * @param string $filename Source filename (e.g., 'auth.php')
     */
    public function writeTranslationRequest(
        array $rows,
        string $sourceLocale,
        string $targetLocale,
        string $sheetName,
        string $tag,
        string $filename
    ): void {
        $this->ensureSheetExists($sheetName);

        // Read existing sheet data
        $existingData = $this->readSheetData($sheetName);
        $headers = $existingData['headers'];
        $dataRows = $existingData['rows'];

        // Update headers with new target locale if needed
        $headers = $this->updateHeaders($headers, $sourceLocale, $targetLocale);
        $targetColumnIndex = array_search(strtoupper($targetLocale), $headers);

        // Build row lookup map for existing data
        $existingRowsMap = $this->buildRowLookupMap($dataRows);

        // Process new rows
        $finalDataRows = $this->processRows(
            $rows,
            $headers,
            $existingRowsMap,
            $filename,
            $tag,
            $targetColumnIndex,
            $sourceLocale,
            $targetLocale
        );

        // Add remaining existing rows (from other files/tags)
        // We pass context to regenerate formulas for these rows too, ensuring self-healing
        $finalDataRows = $this->mergeExistingRows(
            $finalDataRows,
            $existingRowsMap,
            $headers,
            $targetColumnIndex,
            $sourceLocale,
            $targetLocale
        );

        // Write all data to sheet
        $this->writeToSheet($sheetName, $headers, $finalDataRows);
    }

    /**
     * Poll the sheet until translations are complete.
     *
     * Reads from the target locale column for the specified file.
     * Tag is ignored for downloads - it's purely for traceability.
     *
     * @param string $sheetName Name of the sheet to poll
     * @param string $targetLocale Target language code
     * @param string $filename Source filename to filter by
     * @return array Translated strings indexed by key
     * @throws Exception If polling times out or target column not found
     */
    public function pollTranslations(
        string $sheetName,
        string $targetLocale,
        string $filename
    ): array {
        $targetLocaleUpper = strtoupper($targetLocale);

        /** @var int $maxAttempts */
        $maxAttempts = config('google-sheet-i18n.polling.max_attempts', 30);
        /** @var int $interval */
        $interval = config('google-sheet-i18n.polling.interval', 2);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $range = "'{$sheetName}'!A1:ZZ10000";
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                sleep($interval);
                continue;
            }

            // Find target locale column
            $headers = $values[0] ?? [];
            $targetColumnIndex = array_search($targetLocaleUpper, $headers);

            if ($targetColumnIndex === false) {
                throw new Exception("Target locale column '{$targetLocaleUpper}' not found in sheet.");
            }

            // Check if translations are complete
            $result = $this->extractTranslations($values, $filename, $targetColumnIndex);

            if ($result['complete']) {
                return $result['translations'];
            }

            sleep($interval);
        }

        throw new Exception("Timed out waiting for Google Sheets translations.");
    }

    /**
     * Read existing sheet data.
     */
    protected function readSheetData(string $sheetName): array
    {
        $existingRange = "'{$sheetName}'!A1:ZZ10000";
        $headers = [];
        $dataRows = [];

        try {
            $params = ['valueRenderOption' => 'FORMULA'];
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $existingRange, $params);
            $existingData = $response->getValues() ?? [];

            if (!empty($existingData)) {
                $headers = $existingData[0] ?? [];
                $dataRows = array_slice($existingData, 1);
            }
        } catch (\Exception $e) {
            // Sheet is empty or doesn't exist yet
        }

        return ['headers' => $headers, 'rows' => $dataRows];
    }

    /**
     * Update headers with source and target locales.
     */
    protected function updateHeaders(array $headers, string $sourceLocale, string $targetLocale): array
    {
        $sourceLocaleUpper = strtoupper($sourceLocale);
        $targetLocaleUpper = strtoupper($targetLocale);

        // Initialize headers if empty
        if (empty($headers)) {
            $headers = ['File', 'Key', 'Tag', $sourceLocaleUpper];
        }

        // Add target locale column if it doesn't exist
        if (array_search($targetLocaleUpper, $headers) === false) {
            $headers[] = $targetLocaleUpper;
        }

        return $headers;
    }

    /**
     * Build lookup map for existing rows.
     * Key format: "filename|key|tag"
     */
    protected function buildRowLookupMap(array $dataRows): array
    {
        $map = [];

        foreach ($dataRows as $row) {
            if (count($row) >= 4) {
                $lookupKey = ($row[0] ?? '') . '|' . ($row[1] ?? '') . '|' . ($row[2] ?? '');
                $map[$lookupKey] = $row;
            }
        }

        return $map;
    }

    /**
     * Process new rows and prepare them for writing.
     */
    protected function processRows(
        array $rows,
        array $headers,
        array &$existingRowsMap,
        string $filename,
        string $tag,
        int $targetColumnIndex,
        string $sourceLocale,
        string $targetLocale
    ): array {
        $finalDataRows = [];
        $rowsNeedingFormulas = [];

        foreach ($rows as $row) {
            $key = $row[0];
            $sourceText = $row[1];
            $lookupKey = $filename . '|' . $key . '|' . $tag;

            // Check if row already exists
            if (isset($existingRowsMap[$lookupKey])) {
                $dataRow = $existingRowsMap[$lookupKey];
                $dataRow = $this->ensureRowLength($dataRow, count($headers));
                unset($existingRowsMap[$lookupKey]);
            } else {
                $dataRow = $this->createNewRow($headers, $filename, $key, $tag, $sourceText);
            }

            $finalDataRows[] = $dataRow;
            $rowsNeedingFormulas[] = count($finalDataRows) - 1;
        }

        // Add formulas with correct row numbers
        $this->addFormulas(
            $finalDataRows,
            $rowsNeedingFormulas,
            $targetColumnIndex,
            $sourceLocale,
            $targetLocale
        );

        return $finalDataRows;
    }

    /**
     * Ensure row has enough columns.
     */
    protected function ensureRowLength(array $row, int $length): array
    {
        while (count($row) < $length) {
            $row[] = '';
        }
        return $row;
    }

    /**
     * Create a new data row.
     */
    protected function createNewRow(
        array $headers,
        string $filename,
        string $key,
        string $tag,
        string $sourceText
    ): array {
        $dataRow = array_fill(0, count($headers), '');
        $dataRow[0] = $filename;
        $dataRow[1] = $key;
        $dataRow[2] = $tag;
        $dataRow[3] = $sourceText;

        return $dataRow;
    }

    /**
     * Add GOOGLETRANSLATE formulas to rows.
     */
    protected function addFormulas(
        array &$finalDataRows,
        array $rowsNeedingFormulas,
        int $targetColumnIndex,
        string $sourceLocale,
        string $targetLocale
    ): void {
        foreach ($rowsNeedingFormulas as $dataRowIndex) {
            $rowNumber = $dataRowIndex + 2; // +2: 1 for header, 1 for 1-indexed
            $formula = '=GOOGLETRANSLATE(D' . $rowNumber . ', "' . $sourceLocale . '", "' . $targetLocale . '")';
            $finalDataRows[$dataRowIndex][$targetColumnIndex] = $formula;
        }
    }

    /**
     * Merge remaining existing rows that weren't updated.
     */
    /**
     * Merge remaining existing rows that weren't updated.
     */
    protected function mergeExistingRows(
        array $finalDataRows,
        array $existingRowsMap,
        array $headers,
        int $targetColumnIndex,
        string $sourceLocale,
        string $targetLocale
    ): array {
        foreach ($existingRowsMap as $row) {
            $row = $this->ensureRowLength($row, count($headers));

            // Calculate correct row number for the formula (1-based index)
            // Current count + 1 (header) + 1 (next row) = count + 2
            $rowNumber = count($finalDataRows) + 2;

            // Force regenerate formula to fix any broken/static cells
            // Source text is always at index 3 (Column D)
            $formula = '=GOOGLETRANSLATE(D' . $rowNumber . ', "' . $sourceLocale . '", "' . $targetLocale . '")';
            $row[$targetColumnIndex] = $formula;

            $finalDataRows[] = $row;
        }

        return $finalDataRows;
    }

    /**
     * Write data to Google Sheets.
     */
    protected function writeToSheet(string $sheetName, array $headers, array $dataRows): void
    {
        $allData = array_merge([$headers], $dataRows);

        // Clear existing data
        $fullRange = "'{$sheetName}'!A:ZZ";
        $clearBody = new ClearValuesRequest();
        $this->service->spreadsheets_values->clear($this->spreadsheetId, $fullRange, $clearBody);

        // Write new data
        $body = new ValueRange(['values' => $allData]);
        $params = ['valueInputOption' => 'USER_ENTERED'];

        $this->service->spreadsheets_values->update($this->spreadsheetId, "'{$sheetName}'!A1", $body, $params);
    }

    /**
     * Extract translations from sheet values.
     */
    protected function extractTranslations(array $values, string $filename, int $targetColumnIndex): array
    {
        $isLoading = false;
        $translations = [];
        $errors = [];

        // Google Sheets Error Strings
        $errorStrings = ['#ERROR!', '#VALUE!', '#NAME?', '#N/A', '#REF!', '#DIV/0!', '#NUM!'];

        // Skip header row (index 0)
        for ($i = 1; $i < count($values); $i++) {
            $row = $values[$i];

            // Filter by filename (ignore tag for downloads)
            $rowFile = $row[0] ?? '';
            $rowKey = $row[1] ?? '';

            if ($rowFile !== $filename) {
                continue;
            }

            $val = $row[$targetColumnIndex] ?? '';

            // Check for Google Sheets errors
            if (in_array($val, $errorStrings, true)) {
                $errors[] = "Key '{$rowKey}' returned error: {$val}";
                continue;
            }

            // Check if still loading
            if ($val === 'Loading...' || empty($val)) {
                $isLoading = true;
                break;
            }

            $translations[$rowKey] = $val;
        }

        if (!empty($errors)) {
            // Throw exception to stop process and alert user
            throw new Exception("Google Sheets Errors Detected:\n" . implode("\n", array_slice($errors, 0, 5)));
        }

        return [
            'complete' => !$isLoading && !empty($translations),
            'translations' => $translations
        ];
    }
    /**
     * Get a list of all sheets in the spreadsheet.
     * 
     * @return array List of sheets with title and properties
     */
    public function getSheets(): array
    {
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
        $sheets = [];

        foreach ($spreadsheet->getSheets() as $sheet) {
            $props = $sheet->getProperties();
            $sheets[] = [
                'id' => $props->getSheetId(),
                'title' => $props->getTitle(),
                'index' => $props->getIndex(),
                'gridProperties' => $props->getGridProperties(),
            ];
        }

        // Sort by index (usually creation order or user order)
        usort($sheets, fn($a, $b) => $b['index'] <=> $a['index']); // Newest/Last first

        return $sheets;
    }
}
