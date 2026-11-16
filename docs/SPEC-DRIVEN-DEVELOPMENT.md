# Spec-driven development

## Table of contents

- [Layers](#layers)
- [User stories](#user-stories)
- [Validation](#validation)

## Layers

1. **Product behaviour** — what `nowo-tech/transaction-contract` guarantees: the README contract table and
   [USAGE.md](USAGE.md). PHPUnit and PHPStan enforce it in CI.
2. **Spec Kit baseline** — [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md) and the 100% code inventory
   [`specs/001-baseline/code-inventory.md`](../specs/001-baseline/code-inventory.md); the operator manual is
   [SPEC-KIT.md](SPEC-KIT.md).
3. **Repository REQs** — the nowo-tech bundle standards (`BUNDLES_FULL_SPECS_DETAILS.md`: Makefile, CI, docs,
   security) mapped to Makefile targets: `release-check` (REQ-MAKE-002), `check-no-cursor-coauthor` (REQ-GIT-001),
   `igor` (REQ-CS-008), `test-coverage` / `test-coverage-100` (REQ-TEST-003/006/008).

## User stories

- **US-01** — A bundle's application layer type-hints `TransactionRunnerInterface` and runs a unit of work without
  depending on Doctrine or Symfony.
- **US-02** — An HTTP edge or a message consumer catches `TransientStorageFailure` and answers "retry later" with the
  Retry-After hint.
- **US-03** — An adapter package implements the interface and keeps working across 1.x releases of this package.

## Validation

```bash
make release-check
git ls-files src | wc -l   # must equal the inventory count
```
