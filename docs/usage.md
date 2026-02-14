# Usage Guide

This package provides both a command-line interface (CLI) and a web dashboard for managing translations.

## Command Line Interface (CLI)

Use the `translate:sheet` Artisan command to generate and update language files using Google Sheets.

### Basic Syntax
```bash
php artisan translate:sheet {target_locales} --source={source_locale} --tag={tag}
```

### Examples

**1. Translate to Spanish:**
```bash
php artisan translate:sheet es
```

**2. Translate to multiple languages (e.g., Spanish, French, German):**
```bash
php artisan translate:sheet es,fr,de
```

**3. Specify a custom source language (default is 'en'):**
```bash
php artisan translate:sheet ja --source=en
```

**4. Add a tracking tag (for your own reference):**
```bash
php artisan translate:sheet es --tag=release-v1.2
```

## Web Dashboard

The package includes a translation manager dashboard.

1.  Navigate to `/translation-manager` in your browser.
2.  Use the interface to input target locales and click **Generate Locales**.
3.  The dashboard provides real-time logs and progress updates.

## How it Works
1.  **Reads** your local language files (`resources/lang` or `lang/`).
2.  **Uploads** keys and source text to Google Sheets.
3.  **Applies** the `=GOOGLETRANSLATE()` formula for new keys.
4.  **Polls** the sheet until Google returns the translation.
5.  **Downloads** the translated text and saves it back to your local files.
