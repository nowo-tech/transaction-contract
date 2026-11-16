# Feature Specification: TransactionContract baseline (100% code coverage)

**Feature Branch**: `001-baseline`
**Created**: 2026-11-16
**Status**: Implemented (1.0.x)
**Input**: Existing package `nowo-tech/transaction-contract` (Beacon spec 239, load readiness)

## Summary

`nowo-tech/transaction-contract` gives application and domain code a framework-free way to run a unit of work
atomically (`TransactionRunnerInterface::run(callable): mixed`) and to recognise a storage failure worth retrying later
(`TransientStorageFailure` with a Retry-After hint). It depends on nothing but PHP; adapters implement it.

## User Scenarios & Testing

### User Story 1 — Run a unit of work through the port (Priority: P1)

A bundle's application service type-hints the interface and runs one operation in one transaction.

**Acceptance**: **Given** a runner bound in the container, **When** the service calls `run($operation)`, **Then** the
operation's return value comes back and the package adds no dependency to the service.

### User Story 2 — Answer "retry later" at the edge (Priority: P1)

**Acceptance**: **Given** the runner throws `TransientStorageFailure` with `retryAfterSeconds() === 5`, **When** the
HTTP edge catches it, **Then** it can answer 503 with `Retry-After: 5`; **Given** a negative hint, **Then** the
constructor throws `InvalidArgumentException`.

### User Story 3 — Wrap the storage error (Priority: P2)

**Acceptance**: **Given** a driver exception, **When** an adapter calls `TransientStorageFailure::fromPrevious($e, 2)`,
**Then** the message is the generic default, `getPrevious()` is the driver exception and the hint is 2.

## Requirements

### Port

- **FR-PORT-001**: `TransactionRunnerInterface::run(callable $operation): mixed` runs the operation in one transaction
  the runner opens and commits, and returns the operation's result; the runner may replay the operation after a
  transient failure, so callers keep side effects until `run()` returned.

### Failure

- **FR-FAIL-001**: `TransientStorageFailure` extends `RuntimeException`, carries `retryAfterSeconds(): ?int` (null or
  ≥ 0, validated in the constructor) and a generic default message.
- **FR-FAIL-002**: `TransientStorageFailure::fromPrevious(Throwable, ?int)` keeps the storage error as the previous
  exception without copying its message.

### Runtime

- **FR-WORKER-001**: No state: safe under FrankenPHP worker mode with the kernel kept alive; PHPStan FrankenPHP
  classic + worker rulesets and Igor are clean.

## Success Criteria

- **SC-001**: **2/2** files under `src/` mapped in [`code-inventory.md`](code-inventory.md).
- **SC-002**: Line and method coverage of `src/` = 100% (CI gate).
- **SC-003**: No `require` entry besides `php`.

## Non-goals

- Retries, backoff, connection repair, deadlock detection (adapters: `transaction-doctrine`,
  `doctrine-deadlock-retry-bundle`).
- Symfony or Doctrine integration code.

## Validation

```bash
make release-check
git ls-files src | wc -l   # 2
```
