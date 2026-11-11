# Gemini API Setup

TemplateCopier uses PHP and Smalot PDF Parser to extract PDF metadata/text, then sends the PDF to Google's Gemini API for visual analysis. No Python runtime or packages are used.

## Steps

1. Create or open a Gemini API key in [Google AI Studio](https://aistudio.google.com/app/apikey). Keep the key private; do not paste it into chat or commit it to the project.
2. Open XAMPP's Apache configuration file, usually `C:\xampp\apache\conf\httpd.conf`.
3. Add these directives to the Apache configuration and replace the placeholder with your key:

	```apache
	SetEnv GEMINI_API_KEY "paste-your-key-here"
	SetEnv GEMINI_MODEL "gemini-3.8-flash"
	SetEnv GEMINI_FALLBACK_MODEL "gemini-3.5-flash-lite"
	```

	`GEMINI_MODEL` is optional. If omitted, the PHP code uses `gemini-3.8-flash`. If that model returns HTTP 503 after retries, the app tries `GEMINI_FALLBACK_MODEL`, which defaults to `gemini-3.5-flash-lite`. Set the fallback value to an empty string to disable it. Restart Apache after changing these settings.
4. In XAMPP Control Panel, restart Apache so it receives the new settings.
5. To add a template to a report, sign in, open the Dashboard's **Upload Custom Template** form, and submit a PDF no larger than 10 MB. The Dashboard sends it through TemplateCopier's Gemini analysis and editor.
6. In the editor, review the detected design and select **Save & use in Dashboard**. TemplateCopier saves the editable blueprint and textless page backgrounds, then returns to the Dashboard and applies the cleaned template to the live preview.
7. To use TemplateCopier by itself, open `http://localhost/Login/TemplateCopier/`, upload a PDF, edit it, and select **Save changes**. Use **Download JSON** to export its blueprint; it is also stored in `TemplateCopier/uploads/blueprints/`.

## PHP Requirements

- Enable the PHP cURL extension in the active XAMPP `php.ini` file.
- Set `upload_max_filesize=10M`, `post_max_size=12M`, `memory_limit=512M`, and `max_execution_time=300` in that file if the current limits are lower.
- Restart Apache after changing `php.ini`.

Gemini receives the complete uploaded PDF for analysis. Detected images are currently editable placeholders with visual descriptions; their original pixels are not extracted into separate image files yet.