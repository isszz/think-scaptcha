<?php

use isszz\captcha\facade\Captcha;

if (!function_exists('scaptcha')) {
    /**
     * @param array $config
     */
    function scaptcha(array $config = []): string
    {
        return (string) Captcha::create($config);
    }
}

if (!function_exists('scaptcha_src')) {
    /**
     * @param array $config
     * @return string
     */
    function scaptcha_src(array $config = []): string
    {
        $defaults = [
            't' => null,
            'm' => null,
            'w' => 150,
            'h' => 50,
            's' => 52,
            'l' => 4,
            'n' => 3,
            'c' => 1,
            'b' => 'fefefe',
        ];

        $confs = [];
        foreach ($config as $key => $value) {
            if (!isset($defaults[$key])) {
                continue;
            }

            $confs[] = $key . '/' . $value ?: $defaults[$key];
        }

        $urls = implode('/', $confs);



        return \think\facade\Route::buildUrl('/scaptcha/svg/'. $urls);
    }
}

if (!function_exists('scaptcha_img')) {
    /**
     * @param array $config
     * @param string $id
     * @return string
     */
    function scaptcha_img(array|string $config = [], string $id = ''): string
    {
        if (is_string($config)) {
            $id = $config;
            $config = [];
        }

        $src = scaptcha_src($config);

        return '<img'. ($id ? ' id="'. $id .'"' : '') .' src="'. $src .'" alt="scaptcha" onclick="this.src=\''. $src .'?\'+Math.random();" />';
    }
}


if (!function_exists('scaptcha_check')) {
    /**
     * @param string|int|float $value
     * @return bool
     */
    function scaptcha_check(string|int|float $value): bool
    {
        return Captcha::check($value);
    }
}
