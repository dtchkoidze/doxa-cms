<?php

namespace Doxa\User\Http\Controllers\SocialAuth;

use App\Http\Controllers\Controller;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\User\Libraries\SocialAuthService;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function __construct(
        private SocialAuthService $socialAuth
    ) {}

    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Clog::write(SocialAuthService::LOG, 'Google callback failed: ' . $e->getMessage(), Clog::ERROR);
            return redirect()->route('auth.login')->with('error', 'Google sign-in failed. Please try again.');
        }

        $result = $this->socialAuth->handleGoogleUser($googleUser);

        return $this->respond($result);
    }

    public function linkPage()
    {
        $pending = $this->socialAuth->getPending();
        if (!$pending) {
            return redirect()->route('auth.login')->with('error', 'Session expired. Please sign in with Google again.');
        }

        return view('user::auth.google-link', [
            'wrapper' => 'user::layouts.wrapper',
            'title' => 'Link Google account',
            'email' => $pending['email'] ?? '',
        ]);
    }

    public function linkWithPassword()
    {
        $validator = Validator::make(request()->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $result = $this->socialAuth->linkWithPassword(request('password'));

        return $this->jsonRespond($result);
    }

    public function sendMagicLink()
    {
        $result = $this->socialAuth->sendMagicLink();

        return $this->jsonRespond($result);
    }

    public function magicLink(string $token)
    {
        $result = $this->socialAuth->linkWithMagicToken($token);

        return $this->respond($result);
    }

    public function cancelLink()
    {
        $this->socialAuth->clearPending();
        return redirect()->route('auth.login');
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
