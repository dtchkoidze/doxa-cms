<?php

namespace Doxa\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Doxa\User\Libraries\AddEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AddEmailController extends Controller
{
    public function __construct(
        private AddEmailService $addEmailService
    ) {
    }

    public function requestVerification(): JsonResponse
    {
        $result = $this->addEmailService->requestVerification((string) request('email'));

        if (is_string($result)) {
            $status = $result === 'Unauthorized' ? 401 : 422;

            return response()->json([
                'status' => 'error',
                'error' => $result,
            ], $status);
        }

        return response()->json([
            'status' => 'success',
            'timer' => $result['timer'],
            'code_expire_in' => $result['code_expire_in'],
            'throttled' => $result['throttled'] ?? false,
            'message' => vocab('email_verification_code_sent'),
        ]);
    }

    public function confirm(): JsonResponse
    {
        $result = $this->addEmailService->confirm((string) request('code'));

        if ($result !== true) {
            $status = $result === 'Unauthorized' ? 401 : 422;

            return response()->json([
                'status' => 'error',
                'error' => $result,
            ], $status);
        }

        return response()->json([
            'status' => 'success',
            'email' => Auth::user()->email,
            'message' => vocab('email_added_successfully'),
        ]);
    }
}
