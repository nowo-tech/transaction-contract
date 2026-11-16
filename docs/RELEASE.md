# Release

## Table of contents

- [Checklist](#checklist)
- [Tag and publish](#tag-and-publish)

## Checklist

1. `make release-check` is green (co-author check, open PRs, composer sync, style, Rector, PHPStan, Igor, coverage).
2. `docs/CHANGELOG.md`: move `[Unreleased]` to `## [X.Y.Z] - YYYY-MM-DD`; add `docs/UPGRADING.md` notes when needed.
3. Walk the release security checklist in [SECURITY.md](SECURITY.md#release-security-checklist-1241).
4. Commit the release (`chore(release): X.Y.Z`), then run `make check-no-cursor-coauthor` **after** the commit and
   **before** `git push` (REQ-GIT-001).
5. Wait for CI to be green on that exact commit.

## Tag and publish

```bash
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git push origin vX.Y.Z
```

`.github/workflows/release.yml` creates the GitHub release from the tag and the changelog entry;
`.github/workflows/sync-releases.yml` backfills releases for tags that have none. Packagist updates from the tag.
