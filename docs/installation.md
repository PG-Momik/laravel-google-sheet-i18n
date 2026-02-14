# Installation

## Requirements
- PHP 8.0 or higher
- Laravel 8.0 or higher

## Composer Install
Run the following command in your terminal:

```bash
composer require momik/laravel-google-sheet-i18n
```

## Publish Configuration
Publish the configuration file to customize the package settings:

```bash
php artisan vendor:publish --tag=google-sheet-i18n-config
```

This will create a `config/google-sheet-i18n.php` file.

## Environment Setup
Add your Google Sheets credentials to your `.env` file:

```env
GOOGLE_SHEET_I18N_ID=your_spreadsheet_id
GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-service-account.json
```
