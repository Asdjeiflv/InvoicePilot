<?php

namespace Tests\Unit;

use App\Exceptions\NumberGenerationException;
use PHPUnit\Framework\TestCase;

class NumberGenerationExceptionTest extends TestCase
{
    /**
     * Test that the exception can be created with failedAfterAttempts factory method
     */
    public function test_failed_after_attempts_creates_exception_with_correct_message(): void
    {
        $exception = NumberGenerationException::failedAfterAttempts('請求', 'I-2026-', 10);

        $this->assertInstanceOf(NumberGenerationException::class, $exception);
        $this->assertStringContainsString('請求番号の生成に失敗しました', $exception->getMessage());
        $this->assertStringContainsString('10回試行後', $exception->getMessage());
        $this->assertStringContainsString('システム管理者に連絡してください', $exception->getMessage());
    }

    /**
     * Test that the exception message includes the type parameter
     */
    public function test_exception_message_includes_type(): void
    {
        $exception = NumberGenerationException::failedAfterAttempts('見積', 'Q-2026-', 5);

        $this->assertStringContainsString('見積番号の生成に失敗しました', $exception->getMessage());
        $this->assertStringContainsString('5回試行後', $exception->getMessage());
    }

    /**
     * Test that the exception extends RuntimeException
     */
    public function test_exception_extends_runtime_exception(): void
    {
        $exception = NumberGenerationException::failedAfterAttempts('請求', 'I-2026-', 10);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    /**
     * Test that the exception can be thrown and caught
     */
    public function test_exception_can_be_thrown_and_caught(): void
    {
        $this->expectException(NumberGenerationException::class);
        $this->expectExceptionMessage('請求番号の生成に失敗しました（10回試行後）。システム管理者に連絡してください。');

        throw NumberGenerationException::failedAfterAttempts('請求', 'I-2026-', 10);
    }
}
