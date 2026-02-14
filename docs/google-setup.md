# Setting up Google Sheets API

To use this package, you need a Google Cloud Project with the Sheets API enabled and a Service Account.

## 1. Create a Google Cloud Project
1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
   ![Create Project Button](screenshots/00_page_with_create_project_button.png)
2. Create a new project (e.g., `laravel-i18n`).
   ![Project Creation](screenshots/01_project_creation_page.png)

## 2. Enable Google Sheets API
1. In the sidebar, go to **APIs & Services > Library**.
   ![Enable APIs](screenshots/02_enable_apis_n_services_page.png)
2. Search for **"Google Sheets API"**.
   ![API Library](screenshots/03_welcome_to_the_api_library.png)
3. Click **Enable**.
   ![Enable Button](screenshots/04_google_sheets_api_enable_button.png)

## 3. Create a Service Account
1. Go to **APIs & Services > Credentials**.
   ![Credentials Page](screenshots/05_credentials_page.png)
2. Click **Create Credentials > Service Account**.
   ![Create Service Account Menu](screenshots/06_credentials_page_create_credentials_hover_service_account.png)
3. Name it (e.g., `laravel-translator`) and click **Create**.
   ![Service Account Form](screenshots/07_create_service_account.png)

## 4. Download Service Account Key
1. Find your new Service Account in the list and copy its email address (you'll need this later). Then **click on the email** to open details.
   ![Copy Email](screenshots/08_credentials_page_copy_the_email_from_the_table_and_then_click_it.png)
   ![Service Account Details](screenshots/09_service_accounts_detail_page.png)
2. Go to the **Keys** tab.
   ![Keys Tab](screenshots/10_service_account_keys_page.png)
3. Click **Add Key > Create new key**.
   ![Add Key](screenshots/11_service_account_key_page_add_key.png)
4. Select **JSON** and click **Create**. A file will download.
   ![Key Downloaded](screenshots/12_service_account_keys_downloaded_to_computer_next_step_is_copying_to_laravel_project.png)

## 5. Share Your Google Sheet
1. Create a new Google Sheet.
   ![Fresh Sheet](screenshots/13_fresh_excel_sheet.png)
2. Click **Share** and add the **Service Account Email** you copied earlier as an **Editor**.
   ![Share with Service Account](screenshots/14_google_sheet_with_share_modal_open_and_service_account_invited.png)

## 6. Configure Laravel
1. Put the downloaded JSON file in your project (e.g., `storage/app/google-service-account.json`).
2. Get your **Spreadsheet ID** from the URL.
   ![Sheet ID](screenshots/15_browser_url_with_sheet_id_highlighted_not_full_screen_screenshot.png)
3. Update your `.env` file:
   ```env
   GOOGLE_SHEET_I18N_ID=your_spreadsheet_id
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-service-account.json
   ```

