declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Console\Commands;

use Illuminate\Console\Command;
use LaravelGoogleSheetI18n\Services\GoogleSheetsService;
use LaravelGoogleSheetI18n\Services\PlaceholderMasker;
use Illuminate\Support\Facades\File;

class TranslateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:sheet {target_locale : The target locale code (e.g., es, fr)} {--source=en : The source locale code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate language files using Google Sheets';

    protected GoogleSheetsService $sheetsService;
    protected PlaceholderMasker $masker;

    /**
     * Create a new command instance.
     *
     * @param GoogleSheetsService $sheetsService
     * @param PlaceholderMasker $masker
     */
    public function __construct(GoogleSheetsService $sheetsService, PlaceholderMasker $masker)
    {
        parent::__construct();
        $this->sheetsService = $sheetsService;
        $this->masker = $masker;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        /** @var string $targetLocale */
        $targetLocale = $this->argument('target_locale');
        /** @var string $sourceLocale */
        $sourceLocale = $this->option('source');

        $sourcePath = lang_path("{$sourceLocale}.json");

        if (!File::exists($sourcePath)) {
            $this->error("Source file not found: {$sourcePath}");
            return Command::FAILURE;
        }

        $this->info("Reading source file: {$sourceLocale}.json...");
        
        $content = File::get($sourcePath);
        if ($content === false) {
             $this->error("Could not read source file.");
             return Command::FAILURE;
        }
        
        $translations = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($translations)) {
            $this->error("Invalid JSON in source file.");
            return Command::FAILURE;
        }

        $executionData = [];
        $maskedData = [];

        $this->info("Masking placeholders...");
        foreach ($translations as $key => $text) {
             if (!is_string($text)) {
                 $this->warn("Skipping non-string value for key: {$key}");
                 continue;
             }
            $maskResult = $this->masker->mask($text);
            $maskedData[$key] = $maskResult;
            $executionData[] = [$maskResult['masked_text']]; 
        }

        $this->info("Uploading to Google Sheet...");
        $this->sheetsService->writeTranslationRequest($executionData, $sourceLocale, $targetLocale);

        $this->info("Waiting for translations (Polling)...");
        try {
            $translatedValues = $this->sheetsService->pollTranslations();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $this->info("Processing translations...");

        $finalTranslations = [];
        $keys = array_keys($maskedData); // Use maskedData keys to match what we sent

        foreach ($keys as $index => $key) {
            if (!isset($translatedValues[$index])) {
                $this->warn("Missing translation for key: {$key}");
                continue;
            }

            // Sheets might return "Loading..." or empty if failed, but pollTranslations handles catching loading state.
            // Be careful if sheets returned fewer rows than sent.
            
            /** @var string $rawTranslation */
            $rawTranslation = $translatedValues[$index];
            $originalPlaceholders = $maskedData[$key]['placeholders'];

            $unmaskedTranslation = $this->masker->unmask($rawTranslation, $originalPlaceholders);
            $finalTranslations[$key] = $unmaskedTranslation;
        }

        $targetPath = lang_path("{$targetLocale}.json");
        $this->info("Saving to: {$targetPath}");

        File::put($targetPath, json_encode($finalTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Translation complete!");
        return Command::SUCCESS;
    }
}
