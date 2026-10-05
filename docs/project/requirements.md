# Requirements

Source: `README.md`, `CONTRIBUTING.md`, and the package configuration. Progress belongs in `status.md`.

| ID | Requirement | Accepted when |
| --- | --- | --- |
| R1 | Integrate photo uploads and management with Laravel models | The documented traits/components support uploads, reorder, main-photo selection and deletion with configured storage |
| R2 | Enforce the package's documented authorization | Signed upload contexts and the management Gate reject unauthorized actions; see the security section in README |
| R3 | Register package resources without emitting response bytes | Booting `DropzoneServiceProvider` produces an empty output buffer; see `tests/RouteMiddlewareTest.php` |
| R4 | Keep installation and publishing compatible with documented commands | Installer and publish tags load configuration, migrations, views, translations and assets as described in README |

## Invariants

- PHP and Laravel compatibility comes from `composer.json`, not older planning documents.
- No whitespace or BOM before the opening PHP tag in loaded package files; bootstrap output can corrupt unrelated binary downloads in the host application.
- Documentation and code remain in English, following CONTRIBUTING.
- Do not assume a major package upgrade is compatible with an existing consumer integration.
