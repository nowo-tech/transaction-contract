# Constitution — Nowo TransactionContract

## Purpose

Framework-free transaction port for nowo-tech bundles and DDD modules: `TransactionRunnerInterface::run(callable)` and the
`TransientStorageFailure` exception with a Retry-After hint. Namespace `Nowo\TransactionContract\`.

## Non-negotiables

1. No runtime dependency beyond PHP (no Symfony, Doctrine or PSR packages in `require`).
2. The public surface is frozen within a major: adding an interface method or tightening the contract is a major change.
3. No mutable state (FrankenPHP worker mode, kernel reset false); PHPStan FrankenPHP rulesets and Igor stay clean.
4. PHPStan level 8 with empty `ignoreErrors`; line coverage of `src/` = 100%.
5. REQ-GIT-001: no AI co-author trailers in commits.
6. Adapters live in other packages (`transaction-doctrine`, `doctrine-deadlock-retry-bundle`).
