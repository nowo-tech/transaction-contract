# Contributing

Thanks for helping. This package is deliberately tiny: **one interface and one exception**, with no dependencies
beyond PHP.

## Table of contents

- [What belongs here](#what-belongs-here)
- [Getting started](#getting-started)
- [Quality gates](#quality-gates)
- [Backward compatibility](#backward-compatibility)
- [Commits and pull requests](#commits-and-pull-requests)
- [Cursor Agent chat](#cursor-agent-chat)

## What belongs here

Only what application and domain code needs in order to run a unit of work atomically and to recognise a transient
storage failure. Adapters (Doctrine, a Symfony bundle) live elsewhere: the Doctrine adapter with retries ships in
[`nowo-tech/doctrine-deadlock-retry-bundle`](https://github.com/nowo-tech/DoctrineDeadlockRetryBundle).

If a change would add a `require` entry, it is almost certainly in the wrong repository.

## Getting started

```bash
make setup-hooks     # once: installs .githooks (commit-msg strips AI co-author trailers, REQ-GIT-001)
make up              # PHP 8.2 dev container (Docker)
make qa              # cs-check + phpstan + test
make release-check   # the full chain before a release (REQ-MAKE-002)
```

Without Docker, the same gates run with `composer install && composer qa`.

## Quality gates

- `composer cs-check` / `composer cs-fix` — PHP-CS-Fixer.
- `composer phpstan` — level 8, empty `ignoreErrors`.
- `composer test` — PHPUnit; `composer test-coverage` must stay at 100% of lines and methods (CI enforces it).
- `composer rector-dry` — Rector, no pending changes.
- `composer igor` — Igor worker-state audit (REQ-CS-008).
- `composer validate --strict` and `composer audit`.

CI runs the tests on PHP 8.2–8.5 plus PHPStan and the style check; all must pass.

## Backward compatibility

Every element here is public API for packages released independently of this one, so treat it as frozen:

- Adding a method to `TransactionRunnerInterface` is a **major** change: every adapter breaks.
- Tightening the contract described in the interface docblock (what a runner must do) is a major change.
- Adding a **required** constructor argument to `TransientStorageFailure` is a major change; an optional one at the
  end is a minor.
- Removing or renaming anything is a major change.

## Commits and pull requests

- Conventional commit subjects (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`).
- No AI co-author trailers of any kind (REQ-GIT-001). Run `make setup-hooks` before your first commit, and
  `make check-no-cursor-coauthor` after the release commit and **before** `git push`.
- One topic per pull request, with the changelog entry in the same pull request.
- `main` is protected: pull request plus green CI, no force-push, no deletion (REQ-GIT-002).

## Cursor Agent chat

Submit with **Ctrl+Enter** (macOS: **⌘+Enter**) so Enter inserts a newline, and queue follow-up messages instead of
interrupting the running agent:

```json
{
  "cursor.composer.queueMessageDefaultBehavior": "queue"
}
```
