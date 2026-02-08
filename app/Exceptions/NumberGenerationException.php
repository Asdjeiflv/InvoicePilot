<?php

namespace App\Exceptions;

use RuntimeException;

class NumberGenerationException extends RuntimeException
{
    /**
     * Create a new number generation exception.
     *
     * @param  string  $type  The type of number that failed to generate (e.g., 'invoice', 'quotation')
     * @param  string  $prefix  The prefix being used
     * @param  int  $attempts  The number of attempts made
     * @return static
     */
    public static function failedAfterAttempts(string $type, string $prefix, int $attempts): static
    {
        return new static(
            "{$type}番号の生成に失敗しました（{$attempts}回試行後）。システム管理者に連絡してください。"
        );
    }
}
