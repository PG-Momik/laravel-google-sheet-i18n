<?php

declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use LaravelGoogleSheetI18n\Services\GoogleSheetsService;
use LaravelGoogleSheetI18n\Services\PlaceholderMasker;

/**
 * Translation Command
 *
 * Translates Laravel language files using Google Sheets and GOOGLETRANSLATE formula.
 * Supports both JSON and PHP array files with nested structures.
 */
class TranslateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:sheet 
                            {target_locale : The target locale to translate to (e.g., es, fr, de)}
                            {--source=en : The source locale to translate from}
                            {--tag= : Optional tag for traceability (e.g., JIRA-1234)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and update language files using Google Sheets';

    /**
     * Google Sheets service instance.
     */
    protected GoogleSheetsService $sheetsService;

    /**
     * Placeholder masker service instance.
     */
    protected PlaceholderMasker $masker;

    /**
     * Create a new command instance.
     */
    public function __construct(GoogleSheetsService $sheetsService, PlaceholderMasker $masker)
    {
        parent::__construct();
        $this->sheetsService = $sheetsService;
        $this->masker = $masker;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetLocale = $this->argument('target_locale');
        $sourceLocale = $this->option('source');
        $tag = $this->option('tag');

        // Validate configuration
        if (!$this->validateConfiguration()) {
            return Command::FAILURE;
        }

        // Generate sheet name based on current date and optional tag
        $sheetName = $this->generateSheetName($tag);
        $tagValue = $tag ?: 'default';

        $this->info("Using Sheet: {$sheetName}");

        // Initialize statistics tracking
        $stats = [
            'files_processed' => 0,
            'strings_translated' => 0,
            'files' => []
        ];

        // Process language files
        $this->processLanguageFiles($sourceLocale, $targetLocale, $sheetName, $tagValue, $stats);

        // Validate results
        if ($stats['files_processed'] === 0) {
            $this->error("No language files found for locale: {$sourceLocale}");
            return Command::FAILURE;
        }

        // Display summary
        $this->displaySummary($sourceLocale, $targetLocale, $sheetName, $tagValue, $stats);

        return Command::SUCCESS;
    }

    /**
     * Validate that the spreadsheet ID is configured.
     */
    protected function validateConfiguration(): bool
    {
        $spreadsheetId = config('google-sheet-i18n.spreadsheet_id');

        if (empty($spreadsheetId)) {
            $this->error("Spreadsheet ID is not configured.");
            $this->newLine();
            $this->info("Setup Instructions:");
            $this->info("1. Create a Google Sheet manually.");
            $this->info("2. Share it with your Service Account email (Editor access).");
            $this->info("3. Add GOOGLE_SHEET_I18N_ID=your_id to your .env file.");
            return false;
        }

        return true;
    }

    /**
     * Generate sheet name based on current date and optional tag.
     */
    protected function generateSheetName(?string $tag): string
    {
        return date('Y-m-d');
    }

    /**
     * Process all language files (JSON and PHP).
     */
    protected function processLanguageFiles(
        string $sourceLocale,
        string $targetLocale,
        string $sheetName,
        string $tag,
        array &$stats
    ): void {
        $jsonPath = lang_path("{$sourceLocale}.json");
        $phpDir = lang_path($sourceLocale);

        // Process JSON file if exists
        if (File::exists($jsonPath)) {
            $this->info("Processing JSON file: {$sourceLocale}.json");
            $count = $this->processJsonFile(
                $jsonPath,
                $sourceLocale,
                $targetLocale,
                $sheetName,
                $tag,
                "{$sourceLocale}.json"
            );
            $stats['files_processed']++;
            $stats['strings_translated'] += $count;
            $stats['files'][] = ["{$sourceLocale}.json", $count];
        }

        // Process PHP files in directory if exists
        if (File::isDirectory($phpDir)) {
            $files = File::files($phpDir);

            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $this->info("Processing PHP file: {$file->getFilename()}");
                    $count = $this->processPhpFile(
                        $file,
                        $sourceLocale,
                        $targetLocale,
                        $sheetName,
                        $tag,
                        $file->getFilename()
                    );
                    $stats['files_processed']++;
                    $stats['strings_translated'] += $count;
                    $stats['files'][] = [$file->getFilename(), $count];
                }
            }
        }
    }

    /**
     * Display summary statistics.
     */
    protected function displaySummary(
        string $sourceLocale,
        string $targetLocale,
        string $sheetName,
        string $tag,
        array $stats
    ): void {
        $this->newLine();
        $this->info("✓ Locale generation completed successfully!");
        $this->newLine();

        // Summary table
        $this->table(
            ['Metric', 'Value'],
            [
                ['Source Locale', strtoupper($sourceLocale)],
                ['Target Locale', strtoupper($targetLocale)],
                ['Sheet Name', $sheetName],
                ['Tag', $tag],
                ['Files Processed', $stats['files_processed']],
                ['Total Strings', $stats['strings_translated']],
            ]
        );

        // File breakdown
        if (!empty($stats['files'])) {
            $this->newLine();
            $this->line('<fg=cyan>File Breakdown:</>');
            $this->table(
                ['File', 'Strings'],
                $stats['files']
            );
        }
    }

    /**
     * Process a JSON language file.
     */
    protected function processJsonFile(
        string $filePath,
        string $sourceLocale,
        string $targetLocale,
        string $sheetName,
        string $tag,
        string $filename
    ): int {
        $content = File::get($filePath);
        $translations = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($translations)) {
            $this->error("Invalid JSON in file: {$filePath}");
            return 0;
        }

        $translated = $this->translateData(
            $translations,
            $sourceLocale,
            $targetLocale,
            $sheetName,
            $tag,
            $filename
        );

        if (empty($translated)) {
            $this->warn("No translations received for {$filePath}");
            return 0;
        }

        // Save translated file
        $this->saveJsonFile($targetLocale, $translated);

        return count($translated);
    }

    /**
     * Save JSON translation file.
     */
    protected function saveJsonFile(string $targetLocale, array $translated): void
    {
        $targetPath = lang_path("{$targetLocale}.json");

        // Merge with existing translations if file exists
        $existing = [];
        if (File::exists($targetPath)) {
            $existingContent = File::get($targetPath);
            $decoded = json_decode($existingContent, true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $final = array_merge($existing, $translated);

        File::put($targetPath, json_encode($final, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Saved: {$targetPath}");
    }

    /**
     * Process a PHP language file.
     */
    protected function processPhpFile(
        \SplFileInfo $file,
        string $sourceLocale,
        string $targetLocale,
        string $sheetName,
        string $tag,
        string $filename
    ): int {
        $data = File::getRequire($file->getPathname());

        if (!is_array($data)) {
            $this->warn("File does not return an array: {$file->getFilename()}");
            return 0;
        }

        // Flatten nested arrays using dot notation
        $flatData = Arr::dot($data);

        // Filter out empty or non-string values
        $stringsToTranslate = array_filter($flatData, function ($value) {
            return is_string($value) && !empty($value);
        });

        if (empty($stringsToTranslate)) {
            $this->warn("No translatable strings found in: {$file->getFilename()}");
            return 0;
        }

        $translatedFlat = $this->translateData(
            $stringsToTranslate,
            $sourceLocale,
            $targetLocale,
            $sheetName,
            $tag,
            $filename
        );

        if (empty($translatedFlat)) {
            $this->warn("No translations received for {$file->getFilename()}");
            return 0;
        }

        // Save translated file
        $this->savePhpFile($file, $targetLocale, $translatedFlat);

        return count($translatedFlat);
    }

    /**
     * Save PHP translation file.
     */
    protected function savePhpFile(
        \SplFileInfo $file,
        string $targetLocale,
        array $translatedFlat
    ): void {
        $targetDir = lang_path($targetLocale);

        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $file->getFilename();

        // Merge with existing translations if file exists
        $existing = [];
        if (File::exists($targetPath)) {
            $existingData = File::getRequire($targetPath);
            if (is_array($existingData)) {
                $existing = Arr::dot($existingData);
            }
        }

        $merged = array_merge($existing, $translatedFlat);

        // Convert back to nested array
        $nested = Arr::undot($merged);

        // Export as PHP array
        $exported = var_export($nested, true);
        $content = "<?php\n\nreturn {$exported};\n";

        File::put($targetPath, $content);
        $this->info("Saved: {$targetPath}");
    }

    /**
     * Core translation logic: mask, upload, poll, unmask.
     */
    protected function translateData(
        array $flatData,
        string $sourceLocale,
        string $targetLocale,
        string $sheetName,
        string $tag,
        string $filename
    ): array {
        $executionData = [];
        $maskedData = [];
        $indexToKeyMap = [];
        $currentIndex = 0;

        // Mask placeholders in all strings
        foreach ($flatData as $key => $text) {
            $maskResult = $this->masker->mask((string) $text);
            $maskedData[$key] = $maskResult;

            // Prepare data for upload: [key, masked_text]
            $executionData[] = [$key, $maskResult['masked_text']];
            $indexToKeyMap[$currentIndex] = $key;
            $currentIndex++;
        }

        if (empty($executionData)) {
            return [];
        }

        // Upload to Google Sheets
        $this->info("Uploading " . count($executionData) . " strings to Google Sheet...");
        $this->sheetsService->writeTranslationRequest(
            $executionData,
            $sourceLocale,
            $targetLocale,
            $sheetName,
            $tag,
            $filename
        );

        // Poll for translations
        $this->info("Waiting for Google Sheets...");
        try {
            $translatedValues = $this->sheetsService->pollTranslations(
                $sheetName,
                $targetLocale,
                $filename
            );
        } catch (\Exception $e) {
            $this->error("Generation failed: " . $e->getMessage());
            return [];
        }

        // Unmask placeholders in translated strings
        $result = [];
        foreach ($translatedValues as $key => $translatedText) {
            if (isset($maskedData[$key])) {
                $unmasked = $this->masker->unmask($translatedText, $maskedData[$key]['placeholders']);
                $result[$key] = $unmasked;
            }
        }

        return $result;
    }
}
