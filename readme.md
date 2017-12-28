## Requirements
To run this quickstart, you'll need:

1. PHP 5.4 or greater with the command-line interface (CLI) and JSON extension installed.
1. The [Composer](https://getcomposer.org/) dependency management tool.
1. Access to the internet and a web browser.
1. A Google account with Gmail enabled.

## Step 1: Turn on the Gmail API

1. Use [this wizard](https://console.developers.google.com/start/api?id=gmail) to create or select a project in the Google Developers Console and automatically turn on the API. Click **Continue**, then **Go to credentials**.
1. On the **Add credentials to your project** page, click the **Cancel** button.
1. At the top of the page, select the **OAuth consent screen** tab. Select an **Email address**, enter a **Product name** if not already set, and click the **Save** button.
1. Select the **Credentials** tab, click the **Create credentials** button and select **OAuth client ID**.
1. Select the application type **Other**, enter the name "Gmail API Quickstart", and click the **Create** button.
1. Click **OK** to dismiss the resulting dialog.
1. Click the file_download (Download JSON) button to the right of the client ID.
1. Move this file to your working directory and rename it **client_secret.json**.

## Step 2: Run the sample

**Parameters** :
1. date_start "Y-m-d" ex: `2017-12-01`
1. date_end "Y-m-d" ex: `2017-12-31`

**Run**
`php example.php date_start="2017-12-01" date_end="2017-12-31" > scrapmail.html`


