# Decisions

## 2026-10-05 — Guard against bootstrap output

A consumer using version 2.7.1 emitted a newline from the package routes during Laravel startup. That byte altered a PDF download response even though the PDF file itself was complete. The source routes on main and the maintained 2.x line already had the correction; the work here adds a regression test, rather than duplicating an existing source fix.

Capture output while booting `DropzoneServiceProvider` and require an empty buffer. This exercises route/resource loading and fails when the historical leading newline is restored. The consumer moved to the existing compatible 2.9.1 release; it did not require a new release from this checkout.

## 2026-10-05 — Shared project notes

Keep current state in `docs/project/status.md`, read on demand. Import only requirements, context and decisions at startup. Preserve the rest of `docs/` as ignored local development material, while tracking `docs/project/` explicitly.
