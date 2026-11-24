<?php

declare(strict_types=1);

namespace Nowo\TransactionContract\Tests\Unit;

use DomainException;
use InvalidArgumentException;
use LogicException;
use Nowo\TransactionContract\ConcurrencyConflict;
use Nowo\TransactionContract\ConflictCode;
use Nowo\TransactionContract\LockConflictExceptionInterface;
use Nowo\TransactionContract\TransientFailureKind;
use Nowo\TransactionContract\TransientStorageFailure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

use function array_map;
use function file_get_contents;
use function preg_match_all;

#[CoversClass(ConcurrencyConflict::class)]
#[CoversClass(ConflictCode::class)]
final class ConflictTypesTest extends TestCase
{
    public function testConflictCodeValuesAreThePlatformCodes(): void
    {
        self::assertSame('CONFLICT_001', ConflictCode::RETRY_SAFE);
        self::assertSame('CONFLICT_002', ConflictCode::COMMITTED);
        self::assertSame(ConflictCode::RetrySafe, ConflictCode::from(ConflictCode::RETRY_SAFE));
        self::assertSame(ConflictCode::Committed, ConflictCode::from(ConflictCode::COMMITTED));
        self::assertCount(2, ConflictCode::cases());
    }

    public function testOnlyRetrySafeInvitesARetry(): void
    {
        self::assertTrue(ConflictCode::RetrySafe->retrySafe());
        self::assertFalse(ConflictCode::Committed->retrySafe());
    }

    public function testTransientFailureKindValuesAreStable(): void
    {
        self::assertSame(
            ['deadlock', 'lock_wait_timeout', 'serialization_failure', 'retryable'],
            array_map(static fn (TransientFailureKind $kind): string => $kind->value, TransientFailureKind::cases()),
        );
    }

    public function testConcurrencyConflictCarriesTheAttemptAndStaysATransientFailure(): void
    {
        $storageError = new LogicException('SQLSTATE[40001]: UPDATE orders SET …');
        $reset        = new DomainException('reset failed');
        $conflict     = new ConcurrencyConflict(3, TransientFailureKind::Deadlock, 2, $storageError, true, $reset);

        self::assertSame(3, $conflict->attempts);
        self::assertSame(TransientFailureKind::Deadlock, $conflict->kind);
        self::assertSame(2, $conflict->retryAfterSeconds());
        self::assertTrue($conflict->committed);
        self::assertSame($reset, $conflict->resetFailure);
        self::assertSame($storageError, $conflict->getPrevious());
        self::assertSame(ConcurrencyConflict::MESSAGE, $conflict->getMessage());
        self::assertInstanceOf(TransientStorageFailure::class, $conflict);
        self::assertInstanceOf(LockConflictExceptionInterface::class, $conflict);
        self::assertInstanceOf(RuntimeException::class, $conflict);
    }

    public function testConcurrencyConflictDefaultsToNothingCommittedAndNoResetFailure(): void
    {
        $conflict = new ConcurrencyConflict(1, TransientFailureKind::LockWaitTimeout, null, new LogicException('lock wait'));

        self::assertFalse($conflict->committed);
        self::assertNull($conflict->resetFailure);
        self::assertNull($conflict->retryAfterSeconds());
    }

    public function testConcurrencyConflictRejectsANegativeHint(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ConcurrencyConflict(1, TransientFailureKind::Deadlock, -1, new LogicException('deadlock'));
    }

    public function testAnyExceptionMayBeMarkedAsALockConflict(): void
    {
        $marked = new class('busy') extends RuntimeException implements LockConflictExceptionInterface {};

        try {
            throw $marked;
        } catch (LockConflictExceptionInterface $caught) {
            self::assertSame($marked, $caught);
        }
    }

    /**
     * The package stays framework-free: no source file imports or names Symfony, Doctrine or any other vendor.
     */
    public function testSourcesImportNothingButThePackageAndPhp(): void
    {
        foreach ([ConcurrencyConflict::class, ConflictCode::class, LockConflictExceptionInterface::class, TransientFailureKind::class, TransientStorageFailure::class] as $class) {
            $file = (new ReflectionClass($class))->getFileName();
            self::assertIsString($file);
            $source = file_get_contents($file);
            self::assertIsString($source);

            preg_match_all('/^use\s+([^;]+);/m', $source, $imports);
            foreach ($imports[1] as $import) {
                self::assertMatchesRegularExpression('/^(Nowo\\\\TransactionContract\\\\|function [a-z_]+$|[A-Z][A-Za-z]+$)/', $import, $class);
            }
        }
    }
}
