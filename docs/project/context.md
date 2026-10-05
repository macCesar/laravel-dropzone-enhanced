# Context

## Architecture

- Composer library `maccesar/laravel-dropzone-enhanced`; `composer.json` currently supports PHP ^8.2 and Laravel 12/13.
- `src/DropzoneServiceProvider.php` registers configuration, routes, views, translations, migrations and installation commands.
- `src/Models/Photo.php` and `src/Traits/HasPhotos.php` provide polymorphic photo ownership and management; controllers/services handle uploads and thumbnails.
- `routes/web.php` must load silently. Any byte emitted here becomes output in unrelated responses of a host Laravel application.
- Blade components live in `resources/views/components/`; assets come from `resources/assets/` and the build script documented in CONTRIBUTING.
- PHPUnit with Orchestra Testbench: `vendor/bin/phpunit`; focused bootstrap coverage is `tests/RouteMiddlewareTest.php`.
- Release history includes separate 2.x compatibility and current main. A consumer on 2.x must not jump to 4.x without reviewing authorization and thumbnail-cache changes.
- Existing contribution instructions mention a develop branch; the inspected checkout exposes main and 2.x, with no develop branch. Do not invent a release or PR outcome from local commits.

## Documentation map

- `README.md`: current public installation, features, usage, authorization and configuration; first reference for consumers.
- `CONTRIBUTING.md`: language, style, tests, asset workflow and contribution process.
- `CHANGELOG.md`: published versions and unreleased changes; release history rather than session state.
- `AGENTS.md` / `CLAUDE.md`: assistant instructions. Some older CLAUDE examples describe Laravel 8/PHP 7.4; composer.json and README supersede those compatibility claims.
- The following files are ignored local reference material and are not available in a fresh clone; they are proposals/history, not proof of implementation:
  - `docs/package-conversion-plan.md`: original package extraction layout.
  - `docs/implementation-guide.md`: original Laravel 8+ conversion guide; compatibility assumptions are obsolete.
  - `docs/PLAN_MODULARIZACION.md`: proposed split into photos/upload/Glide packages; not the current package contract.
  - `docs/INTEGRACION_TRAITS.md`: notes on combining photo/image traits.
  - `docs/laravel-glide-enhanced-readme.md`: related Glide package overview, not this package's README.
  - `docs/laravel-package-development.md`: general Laravel package-development reference.
  - `docs/sugerencias-desarrollar-paquetes-laravel.md`: general package organization guidance.
  - `docs/laravel-dropzone-enhanced-README-improvements.md`: historical README improvement suggestions.
  - `docs/MULTILINGUAL-UI-REDESIGN-PROPOSAL.md`: UI proposal dated 2025-12-19; compare against current components before reusing.
  - `docs/dropzone-zones-refactor.md`: multi-locale layout proposal.
  - `docs/mejoras.md`: historical thumbnail/srcset integration discussion.
  - `docs/sugerencias-mejoras.md`: historical improvement review; not a current task list.
- `docs/project/requirements.md`: acceptance contract and invariants.
- `docs/project/decisions.md`: reasoning behind choices.
- `docs/project/status.md`: volatile working state, never imported at startup.

## Provenance

| When | Assistant · model | Work produced |
| --- | --- | --- |
| 2026-10-05 | Codex · GPT-6 (family identified by the environment; exact model ID unavailable) | Bootstrap-output regression coverage and project notes during a consumer download incident |
