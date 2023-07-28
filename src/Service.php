<?php
declare(strict_types=1);

namespace isszz\captcha;

use think\Route;
use think\Validate;

class Service extends \think\Service
{
    public function boot()
    {
        // 首次运行复制字体Comismsh.ttf到tp的/config/fonts目录
        if (!is_file($file = root_path('config') .'fonts'. DIRECTORY_SEPARATOR . 'Comismsh.ttf')) {
            if ((!is_dir($path = dirname($file)))) {
                mkdir($path, 0777, true);
            }

            $fontFile = __DIR__ . DIRECTORY_SEPARATOR .'fonts'. DIRECTORY_SEPARATOR .'Comismsh.ttf';

            if (!copy($fontFile, $file)) {
                throw new CaptchaException('Failed to copy thesaurus. Please manually copy "'. $fontFile .'" to "'. $file .'" manually.');
            }
        }


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