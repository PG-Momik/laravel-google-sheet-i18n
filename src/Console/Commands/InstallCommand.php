<?php

declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-sheet-i18n:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure the Google Sheets I18n Manager';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayWelcome();

        // 1. Publish Configuration
        $this->section('1. Publishing Configuration');
        $this->call('vendor:publish', [
            '--tag' => 'google-sheet-i18n-config',
            '--force' => $this->confirm('Overwrite existing configuration if it exists?')
        ]);

        // 2. Interactive Setup
        $this->section('2. Environment Configuration');

        if ($this->confirm('Would you like to configure your .env variables now?', true)) {
            $sheetId = $this->ask('What is your Google Spreadsheet ID?');
            $credentialsPath = $this->ask('Where will you store your Google Service Account JSON file?', 'storage/app/google-service-account.json');

            if ($sheetId) {
                $this->updateEnv('GOOGLE_SHEET_I18N_ID', $sheetId);
            }

            if ($credentialsPath) {
                $this->updateEnv('GOOGLE_APPLICATION_CREDENTIALS', $credentialsPath);
            }

            $this->info('✓ .env variables updated successfully.');
        }

        $this->newLine();
        $this->info('✓ Installation complete! You can now access the dashboard at /translation-manager');
        $this->line('  Run "php artisan translate:sheet {locale}" to generate your first locale.');
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Display welcome banner.
     */
    protected function displayWelcome(): void
    {
        $this->newLine();
        $this->line('<fg=white;bg=blue;options=bold>  GOOGLE SHEETS I18N  </>');
        $this->line('Professional Translation Workflow for Laravel');
        $this->newLine();
    }

    /**
     * Display a section header.
     */
    protected function section(string $title): void
    {
        $this->newLine();
        $this->line("<fg=cyan;options=bold>{$title}</>");
        $this->line(str_repeat('-', strlen($title)));
    }

    /**
     * Update the .env file.
     */
    protected function updateEnv(string $key, string $value): void
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            $this->warn(".env file not found at {$path}. Skipping automatic update.");
            return;
        }

        $content = File::get($path);

        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content = trim($content) . "\n{$key}={$value}\n";
        }

        File::put($path, $content);
    }
}
