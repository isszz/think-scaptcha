<?php
declare(strict_types=1);

namespace isszz\captcha;

use think\Route;
use think\Validate;

class Service extends \think\Service
{
    public function boot()
    {
        Validate::maker(function ($validate) {
            $validate->extend('scaptcha', function ($value) {
                return scaptcha_check($value);
            }, ':attribute错误!');
        });

        $this->registerRoutes(function (Route $route) {
            
            $route->get('scaptcha/check', "\\isszz\\captcha\\Controller@check");
            $route->get('scaptcha/svg', "\\isszz\\captcha\\Controller@svg");
            $route->get('scaptcha', "\\isszz\\captcha\\Controller@index");
        });
    }
}