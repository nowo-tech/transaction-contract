# Upgrading

## Table of contents

- [To 1.1.0](#to-110)
- [To 1.0.x](#to-10x)

## To 1.1.0

New types only; nothing to change for existing runners and callers.

`ConflictCode`, `TransientFailureKind`, `LockConflictExceptionInterface` and `ConcurrencyConflict` now live here,
framework-free. With transaction-doctrine >= 1.10 its old names are aliases of them (same class: `catch`, `instanceof`
and type declarations match either name). Bundles that expose a conflict exception (REQ-RUNTIME-003 rule 6) migrate by
changing the namespace only:

| Before (transaction-doctrine, deprecated in 1.10, removed in 2.0) | After |
|---|---|
| `Nowo\TransactionDoctrine\ConflictCode` | `Nowo\TransactionContract\ConflictCode` |
| `Nowo\TransactionDoctrine\TransientFailureKind` | `Nowo\TransactionContract\TransientFailureKind` |
| `Nowo\TransactionDoctrine\WriteUnit\ConcurrencyConflict` | `Nowo\TransactionContract\ConcurrencyConflict` |
| `Nowo\TransactionDoctrine\WriteUnit\LockConflictExceptionInterface` | `Nowo\TransactionContract\LockConflictExceptionInterface` |
| `ConflictCode::forFailure()` / `ConflictCode::forRequest()` | `Nowo\TransactionDoctrine\ConflictCodeResolver::forFailure()` / `forRequest()` (returns the contract enum) |

- **Require both:** a bundle that names the contract types must require `nowo-tech/transaction-contract: ^1.1`
  **and** `nowo-tech/transaction-doctrine: ^1.10` (when it uses transaction-doctrine at all). With transaction-doctrine
  <= 1.9 the old and new types are different classes: a contract `ConcurrencyConflict(committed: true)` would not be
  seen by its resolver and would be answered `CONFLICT_001` (retried, a double write). transaction-contract 1.1
  declares `conflict: nowo-tech/transaction-doctrine <1.10` so Composer refuses that mix.
- **Compare codes by meaning, not by identity:** use `$code->retrySafe()` or `$code->value`, never
  `=== ConflictCode::Committed` across the two enums (`Nowo\TransactionDoctrine\ConflictCode::Committed !==
  Nowo\TransactionContract\ConflictCode::Committed`).

`Nowo\TransactionDoctrine\ConflictCode` is the one type that is not an alias (it keeps its Doctrine / Symfony
helpers): convert with `->toContract()` / `ConflictCode::fromContract()`, or compare values. transaction-doctrine's
`ConflictResponsePolicy::headersFor()` / `httpExceptionFor()` and `ConflictResponseContext` accept both enums.

## To 1.0.x

First release line. Nothing to migrate. 1.0.1 only changed documentation and the package metadata.

Future breaking changes (see [CONTRIBUTING.md](CONTRIBUTING.md#backward-compatibility)) are announced here with the
steps for adapters and callers.
