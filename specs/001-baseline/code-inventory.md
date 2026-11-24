# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)
**Package**: `nowo-tech/transaction-contract`
**Last audited**: 2026-11-24 (`git ls-files src | wc -l` → 6)

## Contract (`src/`)

| Source file | Spec section | Requirement IDs | Status |
| --- | --- | --- | --- |
| `TransactionRunnerInterface.php` | Port | FR-PORT-001, FR-WORKER-001 | Mapped |
| `TransientStorageFailure.php` | Failure | FR-FAIL-001, FR-FAIL-002, FR-WORKER-001 | Mapped |
| `ConflictCode.php` | [002 Conflict types](../002-conflict-types/spec.md) | FR-CONF-001, FR-WORKER-001 | Mapped |
| `TransientFailureKind.php` | [002 Conflict types](../002-conflict-types/spec.md) | FR-CONF-002 | Mapped |
| `LockConflictExceptionInterface.php` | [002 Conflict types](../002-conflict-types/spec.md) | FR-CONF-003 | Mapped |
| `ConcurrencyConflict.php` | [002 Conflict types](../002-conflict-types/spec.md) | FR-CONF-004, FR-WORKER-001 | Mapped |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Contract | 6 | 6 |
| **Total production sources** | **6** | **6** |
