<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaleObjectException extends Exception
{
    public function __construct(string $message = 'This record has been modified by another user. Please reload and try again.')
    {
        parent::__construct($message);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
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
