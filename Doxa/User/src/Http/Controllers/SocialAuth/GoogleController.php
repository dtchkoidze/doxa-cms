<?php

namespace Doxa\User\Http\Controllers\SocialAuth;

use App\Http\Controllers\Controller;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\User\Libraries\SocialAuthService;
use Doxa\User\Libraries\Onboarding;
use Doxa\User\Libraries\Registration as REG;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleController extends Controller
{
    private const PROVIDER = 'google';

    public function __construct(
        private SocialAuthService $socialAuth
    ) {}

    public function redirect()
    {
        $this->ensureEnabled();

        Clog::write(REG::LOG, 'Onboarding Google redirect', [
            'uri' => request()->getRequestUri(),
            'query' => request()->query(),
        ], Clog::NOTICE);

        Onboarding::saveRequestToSession();

        Clog::write(REG::LOG, 'Onboarding Google redirect session after save', [
            'session' => session(Onboarding::SESSION_KEY),
        ], Clog::NOTICE);

        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback()
    {
        $this->ensureEnabled();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Clog::write('auth_google', 'Google callback failed: ' . $e->getMessage(), Clog::ERROR);
            return redirect()->route('auth.login')->with('error', 'Google sign-in failed. Please try again.');
        }

        Clog::write(REG::LOG, 'Onboarding Google callback', [
            'session' => session(Onboarding::SESSION_KEY),
        ], Clog::NOTICE);

        $result = $this->socialAuth->handleGoogleUser($googleUser);

        Clog::write(REG::LOG, 'Onboarding Google callback result', $result, Clog::NOTICE);

        return $this->respond($result);
    }

    public function linkPage()
    {
        $this->ensureEnabled();

        $pending = $this->socialAuth->getPending(self::PROVIDER);
        if (!$pending) {
            return redirect()->route('auth.login')->with('error', 'Session expired. Please sign in with Google again.');
        }

        // Keep wrapper consistent with other auth pages (custom_auth.wrapper / mode cookie)
        REG::init();

        return view('user::auth.google-link', [
            'wrapper' => REG::authWrapper(),
            'title' => 'Link Google account',
            'email' => $pending['email'] ?? '',
        ]);
    }

    public function linkWithPassword()
    {
        $this->ensureEnabled();

        $validator = Validator::make(request()->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $result = $this->socialAuth->linkWithPassword(request('password'), self::PROVIDER);

        return $this->jsonRespond($result);
    }

    public function sendMagicLink()
    {
        $this->ensureEnabled();

        $result = $this->socialAuth->sendMagicLink(self::PROVIDER);

        return $this->jsonRespond($result);
    }

    /**
     * Подтверждение привязки кодом из письма.
     */
    public function verifyLinkCode()
    {
        $this->ensureEnabled();

        $validator = Validator::make(request()->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $result = $this->socialAuth->linkWithCode((string) request('code'), self::PROVIDER);

        return $this->jsonRespond($result);
    }

    public function cancelLink()
    {
        $this->ensureEnabled();

        $this->socialAuth->clearPending(self::PROVIDER);
        return redirect()->route('auth.login');
    }

    protected function ensureEnabled(): void
    {
        if (!SocialAuthService::isAuthEnabled(self::PROVIDER)) {
            abort(404);
        }
    }

    protected function respond(array $result)
    {
        if (($result['action'] ?? '') === 'redirect' || ($result['action'] ?? '') === 'link') {
            return redirect()->to($result['url']);
        }

        $message = $result['message'] ?? 'Something went wrong.';
        return redirect()->route('auth.login')->with('error', $message);
    }

    protected function jsonRespond(array $result)
    {
        if (($result['action'] ?? '') === 'redirect') {
            return response()->json([
                'success' => true,
                'redirect' => $result['url'],
            ]);
        }

        if (($result['action'] ?? '') === 'ok') {
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'OK',
                'timer' => $result['timer'] ?? 0,
                'confirmation' => $result['confirmation'] ?? null,
            ]);
        }

        if (($result['action'] ?? '') === 'wait') {
            return response()->json([
                'success' => false,
                'timer' => $result['timer'] ?? 0,
                'confirmation' => $result['confirmation'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? null,
            'errors' => $result['errors'] ?? null,
            'error' => $result['message'] ?? 'Request failed.',
        ]);
    }
}
