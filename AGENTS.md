# Repository Guidelines

## Project Structure & Module Organization
- `src/`: Core package code (Service Provider, Http controllers, Models, Services, Traits).
- `resources/views/components/`: Blade components (`area.blade.php`, `photos.blade.php`, `lightbox.blade.php`, `component.blade.php`).
- `resources/assets/`: Bundled Dropzone.js assets managed by `scripts/build-assets.js`.
- `config/dropzone.php`: Package configuration (routes, storage, images, security).
- `routes/web.php`: Package routes (upload, delete, reorder, main-photo, status).
- `database/`: Package migrations.
- `docs/`, `example-component/`: Internal docs and example scaffolding.
- `tests/`: PHPUnit tests for the package (create if adding tests).

## Build, Test, and Development Commands
- `composer install`: Install PHP dependencies for local development.
- `vendor/bin/phpunit [--testdox]`: Run the test suite (Orchestra Testbench).
- `npm run build-assets`: Copy Dropzone assets into `resources/assets/`.
- `npm run update-dropzone`: Update Dropzone then rebuild assets (Node >= 16).
- In a host Laravel app: `php artisan dropzoneenhanced:install` (alias supported: `dropzone-enhanced:install`), optional publishes (`--tag=dropzoneenhanced-*`, aliases `dropzone-enhanced-*`), `php artisan migrate`, `php artisan storage:link`.

## Coding Style & Naming Conventions
- **PHP**: PSR-12, 2-space indentation, descriptive names.
- **Classes**: PascalCase. **Methods/variables**: camelCase. **Blade components**: kebab-case via `dropzone-enhanced::` namespace.
- Write all code and documentation in English. Add concise PHPDoc for public APIs.

## Testing Guidelines
- **Framework**: PHPUnit + Orchestra Testbench.
- **Location/Namespace**: `tests/` using `MacCesar\LaravelDropzoneEnhanced\Tests`.
- **Naming**: `*Test.php`; group by feature (e.g., `Http/DropzoneControllerTest.php`).
- **Coverage**: Maintain or improve; include positive/negative and authorization cases; use `Storage::fake()` when writing files.
- **Run**: `vendor/bin/phpunit`.

## Commit & Pull Request Guidelines
- Branch from `develop`; open PRs against `develop`.
- **Commits**: Imperative mood (“Add X”), focused scope, reference issues (e.g., `#123`).
- **PRs**: Clear description, linked issues, screenshots for UI changes, test instructions, updated docs/CHANGELOG for breaking changes.
- Ensure tests pass and assets are rebuilt when relevant.

## Security & Configuration Tips
- Tune routes prefix/middleware and storage disks in `config/dropzone.php`.
- Required extensions: `ext-exif` (orientation) and `ext-gd` (image processing).
- Do not log secrets or commit generated assets from consumer apps; use `.env` for keys.
