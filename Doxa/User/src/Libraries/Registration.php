<?php

namespace Doxa\User\Libraries;

use App\Models\User;
use Doxa\Libraries\Utils;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Doxa\User\Mail\RecoveryEmail;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\User\Mail\VerificationEmail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use Doxa\User\Mail\AccountDeletionEmail;
use Illuminate\Support\Facades\Validator;
use Projects\Dusty\Libraries\Partner\Partner;

/**
 * @method static boolean isVerificationCodeExpired()
 * @method boolean isVerificationCodeExpired()
 * ----------------------------------------------
 * @method static string login()
 * @method string login()
 * ----------------------------------------------
 * @method static string loginType()
 * @method string loginType()
 * ----------------------------------------------
 * @method static self createUser()
 * @method $this createUser()
 * ----------------------------------------------
 * @method static self createUserProfile()
 * @method $this createUserProfile()
 * ----------------------------------------------
 * @method static self sendMessage(string $method)
 * @method $this sendMessage(string $method)
 * ----------------------------------------------
 * @method static self setAuthCookie(string $stage)
 * @method $this setAuthCookie(string $stage)
 * ----------------------------------------------
 * @method static int getResendCodeTimer()
 * @method int getResendCodeTimer()
 * ----------------------------------------------
 * @method static self refreshSecret()
 * @method self refreshSecret()
 * ------------------------------------------
 * @method static int codeExpireIn()
 * @method int codeExpireIn()
 * ------------------------------------------
 * @method static boolean isValidMethod()
 * @method boolean isValidMethod()
 * ----------------------------------------------
 * @method static self setVerificationdPendingStatus()
 * @method self setVerificationdPendingStatus()
 * ----------------------------------------------
 * @method static self setPasswordPendingStatus()
 * @method self setPasswordPendingStatus()
 * ------------------------------------------
 * @method static self setReadyStatus()
 * @method self setReadyStatus()
 * ------------------------------------------
 * @method static boolean checkNewPasswordSameAsOld()
 * @method boolean checkNewPasswordSameAsOld()
 * ----------------------------------------------
 * @method static self setUserPassword()
 * @method self setUserPassword()
 * ------------------------------------------
 * @method static boolean isAutoActivation()
 * @method boolean isAutoActivation()
 * ----------------------------------------------
 * @method static self setUserActive()
 * @method self setUserActive()
 * ----------------------------------------------
 * @method static string authWrapper()
 * @method string authWrapper()
 * ----------------------------------------------
 * @method static null|array profileRoles()
 * @method null|array profileRoles()
 * ----------------------------------------------
 * @method static string getSuccessAuthUrl()
 * @method string getSuccessAuthUrl()
 * ----------------------------------------------
 * @method static self clearAuthCookie()
 * @method self clearAuthCookie()
 * ----------------------------------------------
 * @method static false|array validateRecoveryLoginRequest()
 * @method false|array validateRecoveryLoginRequest()
 * ----------------------------------------------
 * @method static false|array validateRegistrationRequest()
 * @method false|array validateRegistrationRequest()
 * ----------------------------------------------
 * @method static false|array validateLoginRequest()
 * @method false|array validateLoginRequest()
 * ----------------------------------------------
 * @method static self getPendingUser()
 * @method self getPendingUser()
 * ----------------------------------------------
 * @method static self getUserByLogin()
 * @method self getUserByLogin()
 * ----------------------------------------------
 * @method static self setUserFromAuth()
 * @method self setUserFromAuth()
 *
 */
class Registration
{
    public static self|null $instance = null;


    const READY_STATUS = 1;

    const VERIFICATION_PENDING_STATUS = 2;

    const PASSWORD_PENDING_STATUS = 3;

    const SUSPENDED_STATUS = 4;

    const DELETED_STATUS = 5;

    const LOG = 'auth';

    public ?User $user = null;

    /**
     * User verification hash
     *
     * @var string|null
     */
    protected ?string $v_hash = null;

    /**
     * User registration mode
     * Mode is used to determine the user registration process.
     * Modes can be defined in thw project options.php, in section custom_auth.
     * If modes not defined ...
     *
     * @var string|null
     */
    protected ?string $mode = null;

    protected ?array $proflle_roles = null;

    protected string $layout = 'user::layouts.auth';

    /**
     * Default auth wrapper view
     *
     * @var string
     */
    public string $auth_wrapper = 'user::layouts.wrapper';

    protected ?array $mode_options = null;

    protected ?string $success_auth_url = '/';

    /**
     * Is current user admin
     *
     * @var boolean
     */
    protected bool $is_admin = false;

    /**
     * How many seconds to wait before resending verification code in minutes
     *
     * @var integer|float
     */
    protected int|float $verification_code_delay = 1;

    /**
     * Code expire time in minutes
     2
     * @var integer
     */
    protected int $verification_code_expire_in = 5;

    protected $auth_token_expire = 10;

    protected $v_hash_token_expire = 5;

    protected string $auth_cookie_name = 'auth_data';

    protected string $auth_mode_cookie_name = 'auth_mode';

    protected $auth_cookies_expire = 60 * 24 * 30 * 12;

    /**
     * User login types
     * @var array
     */
    protected $login_types = [
        'email',
        'phone',
    ];

    /**
     * User login type (email, phone)
     * Must be defined in $this->login_types
     *
     * @var string
     */
    public $login_type = null;

    /**
     * User login VALUE (user->email, user->phone ...)
     *
     * @var string
     */
    public $login = '';

    protected $methods = [
        'register',
        'recovery',
        'account_deletion',
    ];

    protected $method = '';

    protected $stage = '';


    // protected $log_level = 1;


    private $test_1 = 'test_1';

    private final function  __construct()
    {
        //dump( __CLASS__ . " initializes only once\n" );
    }

    public static function init($reset = false)
    {
        if (!isset(self::$instance) || $reset) {

            if (class_exists(\App\Services\Registration::class)) {
                self::$instance = new \App\Services\Registration();
            } else {
                self::$instance = new Registration();
            }

            //self::$instance = new Registration();
            self::$instance->initialize();
        }
        return self::$instance;
    }

    /**
     *
     * @return void
     */
    private function initialize()
    {
        Clog::write(self::LOG, '----- START ------ Registration::initialize() -------', Clog::NOTICE);

        $this->method = request()->route()->parameter('method');
        Clog::write(self::LOG, '$this->method: ' . $this->method, Clog::DEBUG);

        // Get mode and mode varuables
        $this->resolveMode();

        // Try set success auth url by congig or by referer
        $this->trySetSuccessUrl();

        // Try to handle referrer data: url, header
        // For example to set cookie or make some other actions 
        $this->handleExtraActions();

        // get referrer to redirect after login
        //$this->tryGetReferer();

        // get auth wrapper
        $this->tryGetCustomAuthWrapper();

        // If role_selector is true in custom auth mode, then get profile roles.
        $this->tryGetProfileRoles();

        // check is admin (chek in custom auth mode_options)
        $this->isAdmin();

        // get user by v_hash
        $this->getPendingUser();

        Clog::write(self::LOG, '----- END -------- Registration::initialize() -------', Clog::NOTICE);

        return $this;
    }


    /**
     * Resolves the current authentication mode.
     * This is nessesary for some reasons:
     * - using profile roles
     * - using custom auth page wrapper
     * - where to redirect user after successful login?
     * - is user admin?
     * Modes can be defined in project options.php file, in section custom_auth.
     * If modes not defined, then default mode will be used.
     */
    protected function resolveMode()
    {
        if (!empty(request('mode'))) {
            Clog::write(self::LOG, 'Mode exists in request: ' . request('mode') . '. Setting cookie.', Clog::DEBUG);
            $this->mode = request('mode');
            Cookie::queue($this->auth_mode_cookie_name, $this->mode, $this->auth_cookies_expire);
        } else {
            if (Cookie::has($this->auth_mode_cookie_name)) {
                Clog::write(self::LOG, 'Mode exists in cookie: ' . Cookie::get($this->auth_mode_cookie_name), Clog::DEBUG);
                $this->mode = Cookie::get($this->auth_mode_cookie_name);
                Cookie::queue($this->auth_mode_cookie_name, $this->mode, $this->auth_cookies_expire);
            } else {
                Clog::write(self::LOG, 'Mode NOT exists in cookie', Clog::DEBUG);
            }
        }

        if (!$this->mode) {
            $this->mode = 'default';
        }

        Clog::write(self::LOG, 'mode: ' . $this->mode, Clog::NOTICE);
        $this->mode_options = Utils::getCustomAuthOptions($this->mode);

        if (empty($this->mode_options)) {
            Clog::write(self::LOG, 'mode_options is empty', Clog::DEBUG);
        } else {
            Clog::write(self::LOG, 'mode_options: ' . json_encode($this->mode_options), Clog::NOTICE);
        }
    }

    protected function trySetSuccessUrl()
    {
        if (!empty($this->mode_options['success_url'])) {
            $this->setSuccessAuthUrlCookie($this->mode_options['success_url']);
        } else {
            $this->trySetSuccessAuthUrlByReferer();
        }
    }

    protected function handleExtraActions()
    {
        
    }


    /**
     * Method tries to get the referrer url and save it to the cookie.
     * If referrer does not contain auth/registration url, then save it to the cookie as 
     *     "success_auth_url"
     * and use to redirect after registration.
     */
    protected function trySetSuccessAuthUrlByReferer()
    {
        $referer = request()->headers->get('referer');
        Clog::write(self::LOG, 'Referrer: ' . $referer, Clog::DEBUG);
        if ($referer) {
            $parsed = parse_url($referer);
            if (!Str::contains($parsed['path'], '/auth/')) {
                $this->setSuccessAuthUrlCookie($parsed['path']);
                Clog::write(self::LOG, 'Add cookie back_url: ' . $parsed['path'], Clog::DEBUG);
            } else {
                Clog::write(self::LOG, 'Url contains auth, ignore', Clog::DEBUG);
            }
        }
        Clog::write(self::LOG, 'Cookie Referrer: ' . $this->getSuccessAuthUrlCookie(), Clog::DEBUG);
    }

    protected function tryGetReferer()
    {
        $referer = request()->get('referer');
        if ($referer) {
            Clog::write(self::LOG, 'Referer exists in request: ' . $referer, Clog::DEBUG);
            $this->setSuccessAuthUrlCookie($referer);
            return;
        }

        $referer = request()->headers->get('referer');
        Clog::write(self::LOG, 'Referrer: ' . $referer, Clog::DEBUG);
        if ($referer) {
            $parsed = parse_url($referer);
            if (!Str::contains($parsed['path'], '/auth/')) {
                $this->setSuccessAuthUrlCookie($parsed['path']);
                Clog::write(self::LOG, 'Add cookie back_url: ' . $parsed['path'], Clog::DEBUG);
            } else {
                Clog::write(self::LOG, 'Url contains auth, ignore', Clog::DEBUG);
            }
        }
        Clog::write(self::LOG, 'Cookie Referrer: ' . $this->getSuccessAuthUrlCookie(), Clog::DEBUG);
    }

    /**
     * Get custom auth page wrapper.
     */
    protected function tryGetCustomAuthWrapper()
    {
        //dd($this->mode_options);
        if (!empty($this->mode_options['wrapper'])) {
            if (View::exists($this->mode_options['wrapper'])) {
                $this->auth_wrapper =  $this->mode_options['wrapper'];
            }
        }
        Clog::write(self::LOG, 'auth_wrapper: ' . $this->auth_wrapper, Clog::NOTICE);
    }

    /**
     * If role_selector is true in custom auth mode, then get profile roles.
     *
     * @return void
     */
    protected function tryGetProfileRoles()
    {
        if (!empty($this->mode_options['role_selector'])) {
            $this->proflle_roles =  mr('profile_role')->getListForRegistration();
            Clog::write(self::LOG, 'proflle_roles: ' . json_encode($this->proflle_roles), Clog::NOTICE);
        }
    }

    /**
     * Set is_admin property if admin mode.
     *
     * @return void
     */
    protected function isAdmin()
    {
        if (!empty($this->mode_options['admin'])) {
            $this->is_admin =  true;
            Clog::write(self::LOG, 'admin: true', Clog::NOTICE);
        }
    }

    /**
     * Pending user is a user who is making some registration process: registration. recovery, verification.
     * We must have stored v_hash and login_type in the cookie or provided in request.
     * If both, v_hash and login_type are provided than we're trying to get user by v_hash.
     * $this->login is a user login value (email, phone ...). We're usin them in the registration process.
     */
    protected function getPendingUser()
    {
        if (request('vh') && request('lt')) {
            Clog::write(self::LOG, '$_GET: vh: ' . request('vh') . ', lt: ' . request('lt'), Clog::NOTICE);
            $this->v_hash = request('vh');
            $this->login_type = request('lt');
        } else {
            Clog::write(self::LOG, 'try get cookie', Clog::DEBUG);
            $this->getAuthCookie();
        }

        if (!$this->v_hash || !$this->login_type) {
            Clog::write(self::LOG, 'v_hash OR login_type is empty!', Clog::DEBUG);
            return false;
        } else {
            Clog::write(self::LOG, 'v_hash: ' . $this->v_hash, Clog::NOTICE);
            Clog::write(self::LOG, 'login_type: ' . $this->login_type, Clog::NOTICE);
        }

        $_user = User::where('v_hash', $this->v_hash);
        if ($this->method == 'recovery') {
            $_user->where('active', 1);
        }
        $this->user = $_user->first();

        if (!$this->user) {
            return false;
        }

        $this->login = $this->user->{$this->login_type};

        Clog::write(self::LOG, 'User login: ' . $this->login, Clog::NOTICE);
        Clog::write(self::LOG, 'User id: ' . $this->user->id, Clog::NOTICE);

        return true;
    }

    protected function method()
    {
        return $this->method;
    }

    protected function mode()
    {
        return $this->mode;
    }

    protected function user()
    {
        return $this->user;
    }

    protected function login()
    {
        return $this->login;
    }

    protected function loginType()
    {
        return $this->login_type;
    }

    protected function vhash()
    {
        return $this->v_hash;
    }

    protected function codeExpireIn()
    {
        return $this->verification_code_expire_in;
    }

    private function getValidationRuleForLoginType()
    {
        switch ($this->login_type) {
            case 'email':
                return 'required|email:rfc';
                break;
            case 'phone':
                return 'required';
                break;
            default:
                return 'required';
                break;
        }
    }

    private function validateLogin()
    {
        return true;
    }

    protected function setVerificationdPendingStatus(): self
    {
        $this->user->update([
            'status' => self::VERIFICATION_PENDING_STATUS,
            'verified_at' => now(),
        ]);

        return $this;
    }

    protected function setPasswordPendingStatus(): self
    {
        $this->user->update([
            'status' => self::PASSWORD_PENDING_STATUS,
        ]);

        return $this;
    }

    protected function setReadyStatus(): self
    {
        $this->user->update([
            'status' => self::READY_STATUS,
        ]);

        return $this;
    }

    protected function sendMessage($method)
    {
        $set = [
            'verification_link' => $this->getVerificationLink($method),
            'secret' => $this->user->secret,
            $this->login_type => $this->login,
        ];

        switch ($this->login_type) {
            case 'email':
                switch ($method) {
                    case 'register':
                        $re = Mail::to($this->login)->send(new VerificationEmail($set));
                        Clog::write(self::LOG, 'Sending verification email: ' . json_encode($re), Clog::NOTICE);
                        break;
                    case 'recovery':
                        Mail::to($this->login)->send(new RecoveryEmail($set));
                        break;
                    case 'account_deletion':
                        Mail::to($this->login)->send(new AccountDeletionEmail($set));
                        break;
                }
                break;
            default:
                break;
        }

        return $this;
    }




    protected function getUserByLogin()
    {
        $this->user = User::where($this->login_type, $this->login)->first();

        return $this;
    }

    public function getUserById($id)
    {
        $this->user = User::where('id', $id)->first();
        return $this;
    }

    protected function getReadyUser()
    {
        $this->user = User::where($this->login_type, $this->login)->where('status', self::READY_STATUS)->first();

        return $this;
    }

    protected function setUserFromAuth()
    {
        $this->user = Auth::user();

        Clog::write(self::LOG, 'setUserFromAuth() this.user: ' . json_encode($this->user), Clog::DEBUG);

        return $this;
    }

    /**
     * Clears the authentication token cookie.
     *
     */
    protected function clearAuthCookie()
    {
        //dump('clearAuthCookie');
        Cookie::queue(Cookie::forget($this->auth_cookie_name));

        return $this;
    }

    protected function setAuthCookie($stage, $refresh_v_hash = false)
    {
        Clog::write(self::LOG, 'setAuthCookie() $stage: ' . $stage . ', $refresh_v_hash: ' . $refresh_v_hash . ', $this->user->v_hash: ' . $this->user->v_hash, Clog::DEBUG);
        if ($refresh_v_hash) {
            $this->user->update([
                'v_hash' => Str::random(32)
            ]);
        }
        Clog::write(self::LOG, '002 $this->user->v_hash: ' . $this->user->v_hash, Clog::DEBUG);
        $data = (['v_hash' => $this->user->v_hash, 'login_type' => $this->login_type, 'stage' => $stage]);
        Clog::write(self::LOG, 'Write cookie ' . $this->auth_cookie_name . ': ' . json_encode($data), Clog::DEBUG);
        Cookie::queue($this->auth_cookie_name, json_encode($data), $this->auth_token_expire);

        return $this;
    }

    protected function getAuthCookie()
    {
        $cookie = Cookie::get($this->auth_cookie_name);
        //dd($cookie);
        if ($cookie) {
            $data = json_decode($cookie);
            Clog::write(self::LOG, 'Got cookie: ' . json_encode($data), Clog::DEBUG);
            if (!empty($data->v_hash)) {
                $this->v_hash = $data->v_hash;
            }
            if (!empty($data->login_type)) {
                $this->login_type = $data->login_type;
            }
            if (!empty($data->stage)) {
                $this->stage = $data->stage;
            }
            return true;
        }
        return false;
    }

    protected function getSuccessAuthUrl()
    {
        Clog::write(self::LOG, 'getSuccessAuthUrl()', Clog::DEBUG);

        $this->success_auth_url = $this->getSuccessAuthUrlCookie();
        if ($this->success_auth_url) {
            Clog::write(self::LOG, 'success_auth_url exists in cookie: ' . $this->success_auth_url, Clog::DEBUG);
            Clog::write(self::LOG, 'METHOD ENDS, $this->success_auth_url: ' . $this->success_auth_url, Clog::DEBUG);
            return $this->success_auth_url;
        } else {
            Clog::write(self::LOG, 'success_auth_url NOT exists in cookie', Clog::DEBUG);
        }

        if (!$this->user && Auth::check()) {
            Clog::write(self::LOG, '!$this->user && Auth::check()', Clog::DEBUG);
            $this->user = Auth::user();
        }

        if (!$this->user) {
            Clog::write(self::LOG, 'User not auth, success_auth_url NOT exists in cookie, set to /', Clog::DEBUG);
            return '/';
        }

        if ($this->user->isAdmin()) {
            Clog::write(self::LOG, '$this->user->isAdmin(), return /admin/', Clog::DEBUG);
            return '/admin/';
        }

        $_profile = mr('user_profile');
        if (method_exists($_profile, 'getSuccessAuthUrl')) {
            Clog::write(self::LOG, 'UserProfile exists in project', Clog::DEBUG);
            $this->success_auth_url = mr('user_profile')->getSuccessAuthUrl($this->user);
            Clog::write(self::LOG, '$this->success_auth_url from project UserProfile::getSuccessAuthUrl(): ' . $this->success_auth_url, Clog::DEBUG);
            if ($this->success_auth_url) {
                return $this->success_auth_url;
            }
        }

        Clog::write(self::LOG, 'User not auth, success_auth_url NOT exists in cookie, getSuccessAuthUrl() in profile returns false, so set to /', Clog::DEBUG);
        return '/';
    }


    protected function isAutoActivation()
    {
        return !(bool) $this->user->isAdmin();
    }

    /**
     *
     * @return boolean
     */
    protected function isVerificationCodeExpired(): bool
    {
        if (!$this->user) {
            return false;
        }
        return Carbon::parse($this->user->code_sent_at)->addMinutes($this->verification_code_expire_in) < now();
    }


    protected function getResendCodeTimer()
    {
        if (!$this->user) {
            return 0;
        }
        $timer = Carbon::parse($this->user->code_sent_at)->addMinutes($this->verification_code_delay)->timestamp - time();
        if ($timer <= 0) {
            return 0;
        }
        return $timer;
    }


    /**
     * Creates a new user record in the database.
     *
     *
     * @return $this
     */
    protected function createUser(): self
    {
        $set[$this->login_type] = $this->login;
        $set['admin'] = $this->is_admin ? 1 : 0;

        $set = array_merge($set, [
            'secret' => generateSecret(),
            'status' => self::VERIFICATION_PENDING_STATUS,
            'code_sent_at' => now(),
            'v_hash' => Str::random(32),
        ]);

        $this->user = User::create($set);

        return $this;
    }

    protected function createUserProfile(): self
    {
        mr('user_profile')->create($this->user, request('role'));

        return $this;
    }


    protected function refreshSecret(): self
    {
        $vhash = Str::random(32);

        $this->user->update([
            'secret' => generateSecret(),
            'v_hash' => $vhash,
            'code_sent_at' => now(),
        ]);

        $this->setVhashCookie($vhash);

        return $this;
    }

    public function setVhashCookie($vhash): self
    {
        Cookie::queue('v_hash', $vhash, $this->v_hash_token_expire);
        return $this;
    }

    public function getVhashCookie(): string|null
    {
        return Cookie::get('v_hash');
    }

    protected function setUserActive()
    {
        $this->user->update([
            'active' => 1,
        ]);

        return $this;
    }

    public function getLogin()
    {
        return $this->login;
    }

    protected function checkNewPasswordSameAsOld($password)
    {
        if (Hash::check($password, $this->user->password)) {
            return true;
        }
        return false;
    }

    protected function setUserPassword($password)
    {
        $this->user->update([
            'password' => Hash::make($password),
        ]);

        return $this;
    }

    protected function validateLoginRequest()
    {
        foreach ($this->login_types as $login_type) {
            if (request($login_type)) {

                $this->login_type = $login_type;
                $this->login = trim(strtolower(request($login_type)));

                $rules[$login_type] = $this->getValidationRuleForLoginType();
            }
        }

        $validator = Validator::make(request()->all(), $rules);

        $errors = [];
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
        }

        $login_error = $this->validateLogin();
        if ($login_error !== true) {
            $errors[$this->login_type] = $login_error;
        }

        return empty($errors) ? false : $errors;
    }

    protected function validateRegistrationRequest()
    {
        $rules = [
            'role' => $this->proflle_roles ? 'required|in:' . implode(',', collect($this->proflle_roles)->pluck('id')->toArray()) : '',
        ];

        foreach ($this->login_types as $login_type) {
            if (request($login_type)) {

                $this->login_type = $login_type;
                $this->login = trim(strtolower(request($login_type)));

                $rules[$login_type] = $this->getValidationRuleForLoginType();
            }
        }

        $validator = Validator::make(request()->all(), $rules);

        $errors = [];
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
        }

        $login_error = $this->validateLogin();
        if ($login_error !== true) {
            $errors[$this->login_type] = $login_error;
        }

        return empty($errors) ? false : $errors;
    }

    protected function validateRecoveryLoginRequest()
    {
        foreach ($this->login_types as $login_type) {
            if (request($login_type)) {

                $this->login_type = $login_type;
                $this->login = trim(strtolower(request($login_type)));

                $rules[$login_type] = $this->getValidationRuleForLoginType();
            }
        }

        $validator = Validator::make(request()->all(), $rules);

        $errors = [];
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
        }

        $login_error = $this->validateLogin();
        if ($login_error !== true) {
            $errors[$this->login_type] = $login_error;
        }

        return empty($errors) ? false : $errors;
    }

    protected function getVerificationLink($method)
    {
        Clog::write('auth', 'getVerificationLink base()', [
            'method' => $method,
        ]);
        if ($method == 'account_deletion') {
            return '/my-profile/settings';
        }
        return route('auth.verification_link', ['method' => $method]) . '?vh=' . $this->user->v_hash . '&lt=' . $this->login_type;
    }

    protected function isValidMethod()
    {
        return in_array($this->method, $this->methods);
    }

    /**
     * Returns auth wrapper view path.
     * Default $this->auth_wrapper defined in class property.
     *
     * @return void
     */
    protected function authWrapper()
    {
        return $this->auth_wrapper;
    }

    protected function profileRoles()
    {
        return $this->proflle_roles;
    }


    private function getSuccessAuthUrlCookie()
    {
        return Cookie::get('success_auth_url');
    }

    private function setSuccessAuthUrlCookie($path)
    {
        Cookie::queue('success_auth_url', $path, $this->auth_cookies_expire);
    }

    protected function afterSetPassword()
    {
        
    }

    public static function __callStatic($name, $arguments)
    {
        if (!method_exists(self::$instance, $name)) {
            abort(500, 'Method does not exist: ' . $name);
        }
        return call_user_func_array([self::$instance, $name], $arguments);
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name)) {
            abort(500, 'Method does not exist: ' . $name);
        }
        return call_user_func_array([$this, $name], $arguments);
    }
}
