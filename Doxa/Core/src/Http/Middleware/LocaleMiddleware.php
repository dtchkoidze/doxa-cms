<?php

namespace Doxa\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Doxa\Core\Libraries\Language;
use Doxa\Core\Libraries\Chlo;
use Doxa\Core\Libraries\Logging\Clog;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    use Language;

    private Request $request;

    //public $log_name;

    private bool $log = true;

    private $exceptions = [
        'sitemap.xml'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

//dd(request('url_key'));
        Clog::write($this->log_name, 'LocaleMiddleware::handle', Clog::DEBUG);

        if($this->checkExeptions($request, $next)){
            return $next($request);
        }

        // TODO custom error page?? or 404 after testing
        !$this->initialize() && die($this->getErrorsString());

        Clog::write($this->log_name, 'config(app.multilanguage): '.config('app.multilanguage'), Clog::DEBUG);

        // process multilanguage is OFF
        if (!config('app.multilanguage')) {
            return $next($request);
        }

        Clog::write($this->log_name, 'app.multilanguage IS ON', Clog::DEBUG);

        Clog::write($this->log_name, '$this->routePrefix: '.$this->routePrefix, Clog::DEBUG);
        Clog::write($this->log_name, '$this->locales: '.json_encode($this->locales), Clog::DEBUG);

        if (!$this->routePrefix || !$this->locales->contains('code', $this->routePrefix)) {
            Clog::write($this->log_name, 'route prefix not found', Clog::DEBUG);
            return redirect($this->buildPathWithLocalePrefix());
        } else {
            Chlo::set(locale: $this->routePrefix);
            app()->setLocale($this->routePrefix);
            $this->setCookie($this->routePrefix);
            return $next($request);
        }
    }

    public function checkExeptions(Request $request, Closure $next)
    {
        if(!empty($this->exceptions)) {
            $_segments = collect(request()->segments());
            foreach($this->exceptions as $exception) {
                if($_segments->contains($exception)) {
                    if($_segments->count() != 1) {
                        return redirect('/'.$exception);
                    } else {
                        return true;
                    }
                }
            }
        }
    }

}
