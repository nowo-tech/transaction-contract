# Feature Specification: Framework-free conflict types

**Feature Branch**: `feat/framework-free-conflict-types`
**Created**: 2026-11-24
**Status**: Implemented (1.1.0)
**Input**: Owner approval 2026-11-24, prerequisite of the REQ-ARCH-002 (Deptrac) rollout: bundles following
REQ-RUNTIME-003 rule 6 expose a public `*ConflictException` that names transaction-doctrine types, and
transaction-doctrine is not framework-free (its `ConflictCode` imports HttpFoundation and Messenger).

## Summary

Move the value side of the conflict vocabulary into the contract, with the names, values and semantics of
transaction-doctrine 1.9, so a bundle's `Domain` / `Contract` layer depends on PHP and this package only.
transaction-doctrine 1.10 aliases its old names to these types and keeps the Doctrine / Symfony logic (resolver,
HTTP policy).

## Requirements

- **FR-CONF-001**: `ConflictCode` is a string enum with `RetrySafe = 'CONFLICT_001'`, `Committed = 'CONFLICT_002'`,
  constants `RETRY_SAFE` / `COMMITTED` and `retrySafe(): bool` (true for `RetrySafe` only). No resolver, no HTTP.
- **FR-CONF-002**: `TransientFailureKind` is a string enum `Deadlock = 'deadlock'`, `LockWaitTimeout =
  'lock_wait_timeout'`, `SerializationFailure = 'serialization_failure'`, `Retryable = 'retryable'`.
- **FR-CONF-003**: `LockConflictExceptionInterface extends Throwable` marks a retryable lock conflict.
- **FR-CONF-004**: `ConcurrencyConflict` is final, extends `TransientStorageFailure`, implements
  `LockConflictExceptionInterface`, and takes `(int $attempts, TransientFailureKind $kind, ?int $retryAfterSeconds,
  Throwable $previous, bool $committed = false, ?Throwable $resetFailure = null)` as public readonly promoted
  properties (except the hint and previous), with the constant client-safe `MESSAGE`.
- **FR-CONF-005**: No source file imports anything but PHP and this package (tested).

## Success Criteria

- **SC-001**: 6/6 files under `src/` mapped in [`../001-baseline/code-inventory.md`](../001-baseline/code-inventory.md).
- **SC-002**: Line and method coverage of `src/` = 100%; PHPStan, Rector, PHP-CS-Fixer and Igor clean.
- **SC-003**: `require` still lists `php` only.
