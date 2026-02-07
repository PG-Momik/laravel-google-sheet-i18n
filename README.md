# Laravel Google Sheet I18n

Translate your Laravel language files (`lang/*.json`) using Google Sheets and the Google Translate API (via the `=GOOGLETRANSLATE()` formula).

This package uploads your source strings (masking placeholders like `:name` to protect them) to a Google Sheet, applies the translation formula, and downloads the results back to your application.

## Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher
- A Google Cloud Project with the **Google Sheets API** enabled
- A Service Account with access to the spreadsheet

## Installation

Since this is a local package, you can include it in your main Laravel application by adding a repository path to `composer.json`.

1.  **Add the repository to your application's `composer.json`:**

    ```json
    "repositories": [
        {
            "type": "path",
            "url": "./path/to/laravel-google-sheet-i18n" 
        }
    ],
    ```
    *(Adjust the path to point to where you cloned this package)*

2.  **Require the package:**

    ```bash
    composer require momik/laravel-google-sheet-i18n
    ```

## Configuration

### 1. Publish the Configuration

Publish the package configuration file to your application's `config` directory:

```bash
php artisan vendor:publish --provider="LaravelGoogleSheetI18n\GoogleSheetI18nServiceProvider" --tag="config"
```

This will create `config/google-sheet-i18n.php`.

### 2. Google Cloud Setup

1.  Go to the [Google Cloud Console](https://console.cloud.google.com/).
2.  Create a new project or select an existing one.
3.  Enable the **Google Sheets API**.
4.  Create a **Service Account**:
    - Go to "IAM & Admin" > "Service Accounts".
    - Create a new service account.
    - Create a JSON Key for this account and download it.
5.  Save the JSON key file in your project (e.g., `storage/app/google-service-account.json`).
    - **IMPORTANT**: Do not commit this file to version control (add it to `.gitignore`).

### 3. Create a Google Sheet

1.  Create a new Google Sheet at [docs.google.com](https://docs.google.com/spreadsheets).
2.  Note the **Spreadsheet ID** from the URL:
    `https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/edit`
3.  **Share the sheet** with your Service Account email address (found in the JSON file) and give it **Editor** access.

### 4. Environment Variables

Add the following to your `.env` file:

```env
GOOGLE_SHEET_I18N_ID=your_spreadsheet_id_here
```

Review `config/google-sheet-i18n.php` and ensure `service_account_path` points to your JSON key file.

## Usage

Use the `translate:sheet` command to translate a language file.

### translating from English (Default)

To translate your `lang/en.json` (source) to Spanish (`es.json`):

```bash
php artisan translate:sheet es
```

### Specifying a Source Locale

To translate from French (`fr.json`) to German (`de.json`):

```bash
php artisan translate:sheet de --source=fr
```

## How It Works

1.  **Reads Source**: The command reads the JSON language file (e.g., `lang/en.json`).
2.  **Masking**: It identifies placeholders (like `:attribute`, `:name`) and replaces them with obscure strings to prevent Google Translate from altering them.
3.  **Upload**: It clears `Sheet1` in your spreadsheet and uploads the source text to Column A.
4.  **Formula**: It writes the `=GOOGLETRANSLATE()` formula into Column B.
5.  **Polling**: The command waits for Google Sheets to execute the formulas.
6.  **Download**: Once translated, it downloads the results.
7.  **Unmasking**: It restores the original placeholders.
8.  **Save**: It saves the user-facing valid JSON to the target language file (e.g., `lang/es.json`).
