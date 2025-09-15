<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessException extends Exception
{
    /**
     * Report the exception.
     */
    public function report(): void
    {
        // Log to monitoring or analytics
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'business_rule_violation'
        ], 422);
    }
}
