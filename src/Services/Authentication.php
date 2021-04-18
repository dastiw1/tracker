<?php

namespace PragmaRX\Tracker\Services;

use Illuminate\Foundation\Application;
use PragmaRX\Support\Config as Config;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Illuminate\Support\Facades\Route;
use Request;

class Authentication
{
    private $config;

    private $authentication = [];

    private $app;
    private $request;

    public function __construct(Config $config, Application $app)
    {
        $this->app = $app;
        //$requests = Request::is('api/');
        //die($requests);
        //$this->request = $requests::is('api/');
        //$this->request = Request();
        $this->config = $config;
    }

    public function check()
    {
        return $this->executeAuthMethod($this->config->get('authenticated_check_method'));
    }

    private function executeAuthMethod($method)
    {
        //$request = new Request();
        //dd($this->request::is('api/*'));
        //dd($this->request);
        foreach ($this->getAuthentication() as $auth) {
            if (is_callable([$auth, $method], true, $callable_name)) {
                if ($data = $auth->$method()) {
                    return $data;
                }
            }
        }
        return false;
    }

    private function getAuthentication()
    {
        foreach ((array) $this->config->get('authentication_ioc_binding') as $binding) {
            $this->authentication[] = $this->app->make($binding);
        }
        return $this->authentication;
    }

    public function user()
    {
        if (Request::is('api/*') && strlen(JWTAuth::getToken()) > 0)
            return JWTAuth::parseToken()->authenticate();
        else
            return $this->executeAuthMethod($this->config->get('authenticated_user_method'));
    }

    public function getCurrentUserId()
    {
        if (Request::is('api/*') && strlen(JWTAuth::getToken()) > 0)
            return $this->user()->id;
        else if ($this->check()) {
            return $this->user()->{$this->config->get('authenticated_user_id_column')};
        }
    }
}
