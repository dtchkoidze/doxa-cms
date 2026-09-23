<?php

namespace Doxa\User\Http\Controllers\TwoFactor;

use App\Http\Controllers\Controller;
use Doxa\User\Libraries\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Настройки 2FA, пароль, резерв и смена почты. Auth уже есть; product — текущий запрос.
 */
class TwoFactorSettingsController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
    ) {
    }

    /**
     * Возвращает состояние способов текущего пользователя.
     */
    public function state(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'two_factor' => $this->twoFactor->profileState($this->userId()),
        ]);
    }

    /**
     * Начинает привязку Authenticator.
     */
    public function startTotp(): JsonResponse
    {
        $result = $this->twoFactor->startTotp($this->userId());
        if (is_string($result)) {
            return $this->fail($result);
        }

        return response()->json(['success' => true, 'totp' => $result]);
    }

    /**
     * Подтверждает Authenticator.
     */
    public function confirmTotp(): JsonResponse
    {
        $result = $this->twoFactor->confirmTotp($this->userId(), (string) request('code'));
        if ($result !== true) {
            return $this->fail($result);
        }

        return $this->okState();
    }

    /**
     * Шлёт код на основную почту для включения email-2FA.
     */
    public function startEmail(): JsonResponse
    {
        $result = $this->twoFactor->startEmailEnable($this->userId());
        if (is_string($result)) {
            return $this->fail($result);
        }

        return response()->json(['success' => true, ...$result]);
    }

    /**
     * Подтверждает email-2FA.
     */
    public function confirmEmail(): JsonResponse
    {
        $result = $this->twoFactor->confirmEmailEnable($this->userId(), (string) request('code'));
        if ($result !== true) {
            return $this->fail($result);
        }

        return $this->okState();
    }

    /**
     * Начинает снятие способа.
     */
    public function startDisable(): JsonResponse
    {
        $result = $this->twoFactor->startDisable($this->userId(), (string) request('method'));
        if (is_string($result)) {
            return $this->fail($result);
        }

        return response()->json(['success' => true, ...$result]);
    }

    /**
     * Подтверждает снятие способа.
     */
    public function confirmDisable(): JsonResponse
    {
        $code = request('code');
        $result = $this->twoFactor->confirmDisable(
            $this->userId(),
            (string) request('method'),
            is_string($code) ? $code : null,
            request()->boolean('accept_risk'),
        );
        if ($result !== true) {
            return $this->fail($result);
        }

        return $this->okState();
    }

    /**
     * Запрашивает код на резервный ящик.
     */
    public function startBackup(): JsonResponse
    {
        $result = $this->twoFactor->startBackupEmail($this->userId(), (string) request('email'));
        if (is_string($result)) {
            return $this->fail($result);
        }

        return response()->json(['success' => true, ...$result]);
    }

    /**
     * Подтверждает резервный ящик.
     */
    public function confirmBackup(): JsonResponse
    {
        $result = $this->twoFactor->confirmBackupEmail($this->userId(), (string) request('code'));
        if ($result !== true) {
            return $this->fail($result);
        }

        return $this->okState();
    }

    /**
     * Шлёт код на текущий резервный ящик для удаления.
     */
    public function startRemoveBackup(): JsonResponse
    {
        $result = $this->twoFactor->startRemoveBackupEmail($this->userId());
        if (is_string($result)) {
            return $this->fail($result);
        }

        return response()->json(['success' => true, ...$result]);
    }

    /**
     * Подтверждает удаление резервного ящика.
     */
    public function confirmRemoveBackup(): JsonResponse
    {
        $result = $this->twoFactor->confirmRemoveBackupEmail($this->userId(), (string) request('code'));
        if ($result !== true) {
            return $this->fail($result);
        }

        return $this->okState();
    }

    /**
     * Меняет основную и резервную местами.
     */
    public function swapBackup(): JsonResponse
    {
        $result = $this->twoFactor->swapBackupToPrimary($this->userId(), request()->boolean('via_backup_login'));
        if ($result !== true) {
            return $this->fail($result);
        }

        return $this->okState();
    }

    /**
     * Начинает смену основной почты (нужна 2FA).
     */
    public function startEmailChange(): JsonResponse
    {
        $result = $this->twoFactor->startEmailChange($this->userId());
        if (is_string($result)) {
            return $this->fail($result);
        }

        return response()->json(['success' => true, ...$result]);
    }

    /**
     * Подтверждает 2FA на смене почты.
     */
    public function confirmEmailChangeTwoFactor(): JsonResponse
    {
        $result = $this->twoFactor->confirmEmailChangeTwoFactor($this->userId(), (string) request('code'));
        if ($result !== true) {
            return $this->fail($result);
        }

        return response()->json(['success' => true, 'step' => 'new_email']);
    }

    /**
     * Шлёт код на новый основной ящик.
     */
    public function requestEmailChangeNew(): JsonResponse
    {
        $result = $this->twoFactor->requestEmailChangeNew($this->userId(), (string) request('email'));
        if (is_string($result)) {
            return $this->fail($result);
        }

        return response()->json(['success' => true, ...$result]);
    }

    /**
     * Подтверждает новый основной ящик.
     */
    public function confirmEmailChangeNew(): JsonResponse
    {
        $result = $this->twoFactor->confirmEmailChangeNew($this->userId(), (string) request('code'));
        if ($result !== true) {
            return $this->fail($result);
        }

        return $this->okState();
    }

    /**
     * Создаёт или меняет пароль.
     */
    public function changePassword(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $result = $this->twoFactor->changePassword($this->userId(), (string) request('password'));
        if ($result !== true) {
            return response()->json([
                'status' => 'error',
                'message' => $result,
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => vocab('password_changed_successfully'),
        ]);
    }

    private function userId(): int
    {
        return (int) Auth::id();
    }

    private function okState(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'two_factor' => $this->twoFactor->profileState($this->userId()),
        ]);
    }

    private function fail(string $error): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
        ], 422);
    }
}
