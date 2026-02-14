<?php

declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use Illuminate\Routing\Controller;

/**
 * Translation Controller
 *
 * Manages viewing and editing translation files.
 */
class TranslationController extends Controller
{
    /**
     * Display all translations.
     */
    public function index(Request $request)
    {
        $locale = $request->get('locale', config('app.locale', 'en'));
        $search = $request->get('search');
        $file = $request->get('file');

        $translations = $this->getTranslations($locale, $file, $search);
        $files = $this->getAvailableFiles($locale);
        $locales = $this->getAvailableLocales();

        return view('translation-manager::translations.index', [
            'translations' => $translations,
            'files' => $files,
            'locales' => $locales,
            'currentLocale' => $locale,
            'currentFile' => $file,
            'search' => $search,
        ]);
    }

    /**
     * Show specific translation file.
     */
    public function show(string $locale, string $file)
    {
        $translations = $this->getFileTranslations($locale, $file);
        $sourceLocale = config('app.locale', 'en');
        $sourceTranslations = $this->getFileTranslations($sourceLocale, $file);

        return view('translation-manager::translations.show', [
            'locale' => $locale,
            'file' => $file,
            'translations' => $translations,
            'source' => $sourceTranslations,
        ]);
    }

    /**
     * Update translations.
     */
    public function update(Request $request, string $locale, string $file)
    {
        $validated = $request->validate([
            'translations' => 'required|array',
        ]);

        $this->saveTranslations($locale, $file, $validated['translations']);

        return response()->json([
            'success' => true,
            'message' => 'Translations updated successfully.',
        ]);
    }

    /**
     * Get translations for a locale.
     */
    protected function getTranslations(string $locale, ?string $file = null, ?string $search = null): array
    {
        $allTranslations = [];

        // Get JSON translations
        $jsonPath = lang_path("{$locale}.json");
        if (File::exists($jsonPath)) {
            $content = json_decode(File::get($jsonPath), true);
            if (is_array($content)) {
                foreach ($content as $key => $value) {
                    // Only include string values
                    if (is_string($value)) {
                        $allTranslations[] = [
                            'file' => "{$locale}.json",
                            'key' => $key,
                            'value' => $value,
                        ];
                    }
                }
            }
        }

        // Get PHP translations
        $phpDir = lang_path($locale);
        if (File::isDirectory($phpDir)) {
            $files = File::files($phpDir);
            foreach ($files as $phpFile) {
                if ($phpFile->getExtension() === 'php') {
                    $content = File::getRequire($phpFile->getPathname());
                    if (is_array($content)) {
                        $flat = Arr::dot($content);
                        foreach ($flat as $key => $value) {
                            // Only include string values to avoid array-to-string conversion
                            if (is_string($value)) {
                                $allTranslations[] = [
                                    'file' => $phpFile->getFilename(),
                                    'key' => $key,
                                    'value' => $value,
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Filter by file
        if ($file) {
            $allTranslations = array_filter($allTranslations, function ($item) use ($file) {
                return $item['file'] === $file;
            });
        }

        // Filter by search (with type safety)
        if ($search) {
            $allTranslations = array_filter($allTranslations, function ($item) use ($search) {
                $keyMatch = is_string($item['key']) && stripos($item['key'], $search) !== false;
                $valueMatch = is_string($item['value']) && stripos($item['value'], $search) !== false;
                return $keyMatch || $valueMatch;
            });
        }

        return array_values($allTranslations);
    }

    /**
     * Get translations for a specific file.
     */
    protected function getFileTranslations(string $locale, string $file): array
    {
        if (str_ends_with($file, '.json')) {
            $path = lang_path($file);
            if (File::exists($path)) {
                return json_decode(File::get($path), true) ?? [];
            }
        } else {
            $path = lang_path($locale . '/' . $file);
            if (File::exists($path)) {
                $content = File::getRequire($path);
                return is_array($content) ? Arr::dot($content) : [];
            }
        }

        return [];
    }

    /**
     * Save translations to file.
     */
    protected function saveTranslations(string $locale, string $file, array $translations): void
    {
        if (str_ends_with($file, '.json')) {
            $path = lang_path($file);
            File::put($path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $path = lang_path($locale . '/' . $file);
            $nested = Arr::undot($translations);
            $export = var_export($nested, true);
            $content = "<?php\n\nreturn {$export};\n";
            File::put($path, $content);
        }
    }

    /**
     * Get available files for a locale.
     */
    protected function getAvailableFiles(string $locale): array
    {
        $files = [];

        // JSON file
        $jsonPath = lang_path("{$locale}.json");
        if (File::exists($jsonPath)) {
            $files[] = "{$locale}.json";
        }

        // PHP files
        $phpDir = lang_path($locale);
        if (File::isDirectory($phpDir)) {
            $phpFiles = File::files($phpDir);
            foreach ($phpFiles as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getFilename();
                }
            }
        }

        return $files;
    }

    /**
     * Get available locales.
     */
    protected function getAvailableLocales(): array
    {
        $langPath = lang_path();
        $locales = [];

        if (File::isDirectory($langPath)) {
            $directories = File::directories($langPath);
            foreach ($directories as $dir) {
                $locales[] = basename($dir);
            }
        }

        $jsonFiles = File::glob($langPath . '/*.json');
        foreach ($jsonFiles as $file) {
            $locale = basename($file, '.json');
            if (!in_array($locale, $locales)) {
                $locales[] = $locale;
            }
        }

        return array_unique($locales);
    }
}
