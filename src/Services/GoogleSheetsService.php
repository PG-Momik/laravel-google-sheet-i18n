declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;
use Exception;

class GoogleSheetsService
{
    protected Client $client;
    protected Sheets $service;
    protected string $spreadsheetId;

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
     * Clear the sheet and write the source text and formulas.
     *
     * @param array $rows Array of ['source_text']
     * @param string $sourceLocale
     * @param string $targetLocale
     * @return void
     */
    public function writeTranslationRequest(array $rows, string $sourceLocale, string $targetLocale): void
    {
        // 1. Clear existing data
        $range = 'Sheet1!A:B';
        $clearBody = new ClearValuesRequest();
        $this->service->spreadsheets_values->clear($this->spreadsheetId, $range, $clearBody);

        // 2. Prepare data for batch update
        $data = [];
        foreach ($rows as $row) {
            $sourceText = $row[0];
            // Escape double quotes for the formula
            $escapedText = str_replace('"', '""', (string)$sourceText);

            // Excel/Sheets formula: =GOOGLETRANSLATE("text", "en", "es")
            $formula = '=GOOGLETRANSLATE("' . $escapedText . '", "' . $sourceLocale . '", "' . $targetLocale . '")';

            $data[] = [$sourceText, $formula];
        }

        // 3. Write data
        $body = new ValueRange([
            'values' => $data
        ]);

        $params = [
            'valueInputOption' => 'USER_ENTERED' // Important for formulas to be parsed
        ];

        $this->service->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);
    }

    /**
     * Poll the sheet until "Loading..." disappears.
     *
     * @return array The translated strings (Column B)
     * @throws Exception
     */
    public function pollTranslations(): array
    {
        $range = 'Sheet1!B:B';
        /** @var int $maxAttempts */
        $maxAttempts = config('google-sheet-i18n.polling.max_attempts', 30);
        /** @var int $interval */
        $interval = config('google-sheet-i18n.polling.interval', 2);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                // Should at least have something corresponding to what we uploaded
                sleep($interval);
                continue;
            }

            $isLoading = false;
            $translations = [];

            foreach ($values as $row) {
                // If cell is empty or explicitly says "Loading..."
                $val = $row[0] ?? '';
                if ($val === 'Loading...') {
                    $isLoading = true;
                    break;
                }
                $translations[] = $val;
            }

            if (!$isLoading) {
                return $translations;
            }

            sleep($interval);
        }

        throw new Exception("Timed out waiting for Google Sheets translations.");
    }
}
