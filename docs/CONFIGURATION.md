# Configuration

This package has **no configuration**: it is an interface and an exception. Retry counts, backoff, which errors are
transient and how the connection is repaired belong to the adapter that implements `TransactionRunnerInterface`
(see [INSTALLATION.md](INSTALLATION.md)).

The only value you choose at the call site is the optional Retry-After hint of `TransientStorageFailure`
(`null` or zero or more seconds).
