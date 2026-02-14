<?php

declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Routing\Controller;

/**
 * Dashboard Controller
 *
 * Displays overview statistics and recent activity.
 */
class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $stats = $this->getStatistics();

        return view('translation-manager::dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Get translation statistics.
     */
    protected function getStatistics(): array
    {
        $langPath = lang_path();
        $locales = $this->getAvailableLocales();
        $sourceFiles = $this->getSourceFiles();

        $totalStrings = 0;
        $coverage = [];

        foreach ($locales as $locale) {
            $coverage[$locale] = $this->calculateCoverage($locale, $sourceFiles);
            $totalStrings += $coverage[$locale]['total'];
        }

        return [
            'total_locales' => count($locales),
            'total_files' => count($sourceFiles),
            'total_strings' => $totalStrings,
            'locales' => $locales,
            'coverage' => $coverage,
            'source_locale' => config('app.locale', 'en'),
            'spreadsheet_id' => config('google-sheet-i18n.spreadsheet_id'),
        ];
    }

    /**
     * Get available locales.
     */
    protected function getAvailableLocales(): array
    {
        $langPath = lang_path();
        $locales = [];

        // Get directories (PHP array translations)
        if (File::isDirectory($langPath)) {
            $directories = File::directories($langPath);
            foreach ($directories as $dir) {
                $locales[] = basename($dir);
            }
        }

        // Get JSON files
        $jsonFiles = File::glob($langPath . '/*.json');
        foreach ($jsonFiles as $file) {
            $locale = basename($file, '.json');
            if (!in_array($locale, $locales)) {
                $locales[] = $locale;
            }
        }

        return array_unique($locales);
    }

    /**
     * Get source translation files.
     */
    protected function getSourceFiles(): array
    {
        $sourceLocale = config('app.locale', 'en');
        $files = [];

        // JSON file
        $jsonPath = lang_path("{$sourceLocale}.json");
        if (File::exists($jsonPath)) {
            $files[] = [
                'name' => "{$sourceLocale}.json",
                'type' => 'json',
                'path' => $jsonPath,
            ];
        }

        // PHP files
        $phpDir = lang_path($sourceLocale);
        if (File::isDirectory($phpDir)) {
            $phpFiles = File::files($phpDir);
            foreach ($phpFiles as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = [
                        'name' => $file->getFilename(),
                        'type' => 'php',
                        'path' => $file->getPathname(),
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * Calculate translation coverage for a locale.
     */
    protected function calculateCoverage(string $locale, array $sourceFiles): array
    {
        $total = 0;
        $translated = 0;

        foreach ($sourceFiles as $file) {
            if ($file['type'] === 'json') {
                $targetPath = lang_path("{$locale}.json");
                if (File::exists($targetPath)) {
                    $content = json_decode(File::get($targetPath), true);
                    if (is_array($content)) {
                        $translated += count($content);
                    }
                }
                $sourceContent = json_decode(File::get($file['path']), true);
                if (is_array($sourceContent)) {
                    $total += count($sourceContent);
                }
            } else {
                $targetPath = lang_path($locale . '/' . $file['name']);
                if (File::exists($targetPath)) {
                    $content = File::getRequire($targetPath);
                    if (is_array($content)) {
                        $translated += $this->countStrings($content);
                    }
                }
                $sourceContent = File::getRequire($file['path']);
                if (is_array($sourceContent)) {
                    $total += $this->countStrings($sourceContent);
                }
            }
        }

        $percentage = $total > 0 ? round(($translated / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'translated' => $translated,
            'percentage' => $percentage,
        ];
    }

    /**
     * Count strings in nested array.
     */
    protected function countStrings(array $data): int
    {
        $count = 0;
        foreach ($data as $value) {
            if (is_array($value)) {
                $count += $this->countStrings($value);
            } elseif (is_string($value)) {
                $count++;
            }
        }
        return $count;
    }
}
