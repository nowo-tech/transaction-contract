# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Table of contents

- [[Unreleased]](#unreleased)

## [Unreleased]

Target 1.0.0.

### Added

- **Transaction port.** `TransactionRunnerInterface::run(callable): mixed`: runs a replayable unit of work in one
  transaction, may replay it after a transient failure, leaves no transaction of its own open, holds no per-call
  state (FrankenPHP worker mode).
- **Failure and nesting semantics.** When `run()` throws, nothing of the operation is pending in the backing unit of
  work (an ORM runner clears, or closes and resets, the manager). Inside a caller's transaction `run()` does not
  retry; only the outermost one replays, and the caller's transaction may already have been ended by the storage.
- **`TransientStorageFailure`.** A `RuntimeException` with a generic, client-safe message, the storage error as
  `previous`, and an optional `retryAfterSeconds()` hint (negative values rejected); `fromPrevious()` wraps a
  storage error. Transient means deadlock, lock wait timeout or serialization failure; a failure whose outcome is
  unknown (connection lost during the commit) is not transient. The exception's string form includes the previous
  chain and must never be rendered to clients.

### Documentation

- `README`, `docs/CONTRIBUTING.md`, `docs/SECURITY.md` and `CODE_OF_CONDUCT.md` (REQ-DOCS-003).
