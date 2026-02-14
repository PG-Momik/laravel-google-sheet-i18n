# Laravel Google Sheets I18n

Manage your Laravel language files using Google Sheets with effortless locale generation and auto-translation.

- **Sync Translations**: Upload and download translations instantly.
- **Auto-translate**: Use `GOOGLETRANSLATE()` formulas directly in Google Sheets.
- **Visualize Progress**: See real-time sync status in your browser.

## Getting Started

1. **Google Setup**: Follow the [Step-by-Step Google Setup Guide](google-setup.md) (with screenshots) to get your credentials.
2. **Install**: Follow the [Installation Guide](installation.md) to add the package to your project.
3. **Usage**: See the [Usage Guide](usage.md) for CLI and Web Dashboard instructions.

## Quick Setup
If you already have a Google Sheet and Service Account JSON:

```bash
composer require momik/laravel-google-sheet-i18n
php artisan google-sheet-i18n:install
```

Then configure your `.env`:
```env
GOOGLE_SHEET_I18N_ID=your_spreadsheet_id
GOOGLE_APPLICATION_CREDENTIALS=path/to/credentials.json
```

Run the sync:
```bash
php artisan translate:sheet es,fr
```
