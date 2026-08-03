<?php

namespace Doxa\User\Http\Controllers\SocialAuth;

use App\Http\Controllers\Controller;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\User\Libraries\SocialAuthService;
use Doxa\User\Libraries\Registration as REG;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class FacebookController extends Controller
{
    private const PROVIDER = 'facebook';

    public function __construct(
        private SocialAuthService $socialAuth
    ) {}

    public function redirect()
    {
        $this->ensureEnabled();

        return Socialite::driver('facebook')
            ->scopes(['email', 'public_profile'])
            ->redirect();
    }

    public function callback()
    {
        $this->ensureEnabled();

        try {
            $facebookUser = Socialite::driver('facebook')->user();
        } catch (\Throwable $e) {
            Clog::write('auth_facebook', 'Facebook callback failed: ' . $e->getMessage(), Clog::ERROR);
            return redirect()->route('auth.login')->with('error', 'Facebook sign-in failed. Please try again.');
        }

        $result = $this->socialAuth->handleFacebookUser($facebookUser);

        return $this->respond($result);
    }

    public function linkPage()
    {
        $this->ensureEnabled();

        $pending = $this->socialAuth->getPending(self::PROVIDER);
        if (!$pending) {
            return redirect()->route('auth.login')->with('error', 'Session expired. Please sign in with Facebook again.');
        }

        REG::init();

        return view('user::auth.facebook-link', [
            'wrapper' => REG::authWrapper(),
            'title' => 'Link Facebook account',
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

    public function magicLink(string $token)
    {
        $this->ensureEnabled();

        $result = $this->socialAuth->linkWithMagicToken($token);

        return $this->respond($result);
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
