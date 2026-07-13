# Verification

## Local Checks

Use PHP 8.3 or newer.

```powershell
php -v
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
node --check assets\dist\app.js
node -e "JSON.parse(require('fs').readFileSync('composer.json','utf8')); JSON.parse(require('fs').readFileSync('package.json','utf8'));"
```

## Current Environment Note

This local Codex shell does not currently expose a `php` executable on `PATH`, so PHP syntax verification must run in a local WordPress/PHP environment or in GitHub Actions.

## GitHub Actions

The workflow at `.github/workflows/ci.yml` verifies:

- PHP 8.3 availability.
- `composer.json` JSON validity.
- `package.json` JSON validity.
- PHP syntax for every plugin PHP file.
- JavaScript syntax for the distributable app bundle.

## Runtime Smoke Test

After installing the plugin in WordPress:

1. Activate Zadora LMS.
2. Confirm dedicated Zadora tables are created.
3. Add `[zadora_app]` to a page.
4. Visit the page while logged in.
5. Call `/wp-json/zadora/v1/courses` with a valid WordPress REST nonce.
6. Create a course, add lessons, enroll a learner, complete lessons, and confirm learner reporting updates.
7. Attach a certificate template to the course and confirm course completion issues a certificate.
