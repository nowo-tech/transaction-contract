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
- **`TransientStorageFailure`.** A `RuntimeException` with a generic, client-safe message, the storage error as
  `previous`, and an optional `retryAfterSeconds()` hint (negative values rejected); `fromPrevious()` wraps a
  storage error.

### Documentation

- `README`, `docs/CONTRIBUTING.md`, `docs/SECURITY.md` and `CODE_OF_CONDUCT.md` (REQ-DOCS-003).
