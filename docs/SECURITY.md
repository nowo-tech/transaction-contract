# Security

Security posture of `nowo-tech/transaction-contract`.

## Table of contents

- [Scope](#scope)
- [Reporting a vulnerability](#reporting-a-vulnerability)
- [Attack surface](#attack-surface)
- [Information disclosure](#information-disclosure)
- [Dependencies and update policy](#dependencies-and-update-policy)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)

## Scope

This package contains **one interface and one exception**: `TransactionRunnerInterface` and
`TransientStorageFailure`. It has no services, no I/O, no database access, no file access, no network access, no
configuration and no runtime dependencies beyond PHP itself.

## Reporting a vulnerability

Report privately to <hectorfranco@nowo.tech>. Do not open a public issue for an unfixed vulnerability.

## Attack surface

Effectively none. The only executable code is the exception's constructor, which rejects a negative Retry-After
hint, a named constructor and an accessor. Nothing here parses untrusted input, builds SQL, or touches a resource.

## Information disclosure

Storage errors often contain SQL and bound parameters (e-mail addresses, tokens, personal data).
`TransientStorageFailure` keeps them out of its message: `fromPrevious()` uses a fixed generic message and keeps the
storage error as `previous`, for the logs. An HTTP edge may show the message and `retryAfterSeconds()` to a client;
it must never show `getPrevious()`. Adapters must not put storage details in the message either.

## Dependencies and update policy

`require` is `php: >=8.2 <8.6` and nothing else, so there is no third-party supply chain to track. Development
tooling (PHPUnit, PHPStan, PHP-CS-Fixer, Rector) is `require-dev` and never ships: `.gitattributes` marks
`tests/`, `.github/` and every tooling config `export-ignore`, so the distributed archive contains only `src/`,
`LICENSE`, `README.md`, `composer.json` and `docs/CHANGELOG.md`.

## Release security checklist (12.4.1)

| Item | Status (1.0.x) |
| --- | --- |
| `docs/SECURITY.md` and `.github/SECURITY.md` present | ✅ |
| `.env` files ignored; no secrets in the repository | ✅ |
| Safe defaults | ✅ (no configuration, no I/O) |
| Input validation | ✅ (`retryAfterSeconds` must be null or ≥ 0) |
| `composer audit` clean | ✅ (CI) |
| No secrets / personal data in logs | ✅ (no logging; the previous exception is kept, its message is not copied) |
| Cryptography, permissions, limits | — (none) |
| REQ-SEC-004 AI audit recorded | ✅ (`BUNDLES_SECURITY_ANALYSIS.md` Appendix QA) |
