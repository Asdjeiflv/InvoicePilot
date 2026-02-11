<?php

namespace App\Exceptions;

use Exception;

class StaleObjectException extends Exception
{
    public function __construct(string $message = 'This record has been modified by another user. Please reload and try again.')
    {
        parent::__construct($message);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'stale_object',
            ], 409);
        }

        return back()
            ->withInput()
            ->with('error', $this->getMessage());
    }
}
