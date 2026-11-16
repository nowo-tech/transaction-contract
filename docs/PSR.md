# PSR evaluation (REQ-CS-007)

| PSR | Status | Notes |
| --- | --- | --- |
| PSR-1 / PSR-12 (coding style) | Adopted | PHP-CS-Fixer `@PSR12` + `@Symfony` (`.php-cs-fixer.dist.php`), CI gate. |
| PSR-4 (autoloading) | Adopted | `Nowo\TransactionContract\` → `src/`. |
| PSR-3 (logger) | N/A | The contract logs nothing; adapters choose their logging. |
| PSR-11 (container) | N/A | No services; hosts bind the interface in their own container. |
| PSR-14 (events) | N/A | No events. |
| PSR-20 (clock) | N/A | No time-dependent code; backoff timing belongs to adapters. |
| PSR-7/15/17/18 (HTTP) | N/A | No HTTP. |

Adding a PSR dependency would break the "nothing but PHP" promise of this package and needs a major release.
