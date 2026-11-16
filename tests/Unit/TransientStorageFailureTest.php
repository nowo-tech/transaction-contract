<?php

declare(strict_types=1);

namespace Nowo\TransactionContract\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use Nowo\TransactionContract\TransactionRunnerInterface;
use Nowo\TransactionContract\TransientStorageFailure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(TransientStorageFailure::class)]
final class TransientStorageFailureTest extends TestCase
{
    public function testDefaultsToAGenericMessageAndNoHint(): void
    {
        $failure = new TransientStorageFailure();

        self::assertSame(TransientStorageFailure::DEFAULT_MESSAGE, $failure->getMessage());
        self::assertNull($failure->retryAfterSeconds());
        self::assertNull($failure->getPrevious());
        self::assertSame(0, $failure->getCode());
        self::assertInstanceOf(RuntimeException::class, $failure);
    }

    public function testCarriesTheHintTheMessageAndThePreviousError(): void
    {
        $storageError = new LogicException('SQLSTATE[40001]: UPDATE orders SET …');
        $failure      = new TransientStorageFailure(3, 'Busy, retry.', $storageError);

        self::assertSame(3, $failure->retryAfterSeconds());
        self::assertSame('Busy, retry.', $failure->getMessage());
        self::assertSame($storageError, $failure->getPrevious());
    }

    public function testZeroIsAValidHint(): void
    {
        self::assertSame(0, (new TransientStorageFailure(0))->retryAfterSeconds());
    }

    public function testRejectsANegativeHint(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TransientStorageFailure(-1);
    }

    public function testFromPreviousKeepsTheStorageErrorOutOfTheMessage(): void
    {
        $storageError = new LogicException('SQLSTATE[HY000]: 1213 Deadlock found; UPDATE secrets SET …');
        $failure      = TransientStorageFailure::fromPrevious($storageError, 2);

        self::assertSame(TransientStorageFailure::DEFAULT_MESSAGE, $failure->getMessage());
        self::assertStringNotContainsString('UPDATE', $failure->getMessage());
        self::assertSame($storageError, $failure->getPrevious());
        self::assertSame(2, $failure->retryAfterSeconds());
        self::assertNull(TransientStorageFailure::fromPrevious($storageError)->retryAfterSeconds());
    }

    public function testARunnerCanBeImplementedWithoutAnyFramework(): void
    {
        $runner = new class implements TransactionRunnerInterface {
            public function run(callable $operation): mixed
            {
                return $operation();
            }
        };

        self::assertSame(42, $runner->run(static fn (): int => 42));
    }
}
