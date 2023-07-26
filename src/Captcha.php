<?php
declare (strict_types = 1);

namespace isszz\captcha;

use think\Session;
use think\facade\Config;

use isszz\captcha\support\Str;
use isszz\captcha\support\encrypter\Encrypter;

/**
 * SVG 验证码
 */
class Captcha
{
    /**
     * Default config
     */
    protected $config = [
        'type' => null,
        'cache' => true,
        'width' => 150,
        'height' => 50,
        'noise' => 5, // 干扰线条的数量
        'inverse' => false, // 反转颜色
        'color' => false, // 文字是否随机色
        'background' => 'fefefe', // 验证码背景色
        'size' => 4, // 验证码字数
        'ignoreChars' => '', // 验证码字符中排除
        'fontSize' => 52, // 字体大小
        'charPreset' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', // 预设随机字符
        'math' => '', // 计算类型, 如果设置不是+或-则随机两种
        'mathMin' => 1, // 用于计算的最小值
        'mathMax' => 9, // 用于计算的最大值
        'salt' => '^%$YU$%%^U#$5', // 用于加密验证码的盐
        'fontName' => 'Comismsh.ttf', // 用于验证码的字体, 字体文件需要放置根目录config/fonts/目录下面
    ];

    /**
     * think session
     */
    protected ?object $session;

    /**
     * Encrypter object
     */
    protected ?object $encrypter = null;

    /**
     * Font random
     */
    protected ?object $random;

    /**
     * Get font path
     */
    protected ?object $ch2path;


    /**
     * To svg string
     */
    private ?string $text;

    /**
     * Encode hash
     */
    private ?string $hash;

    /**
     * To svg string
     */
    public ?string $svg;

    public ?float $mctime;


    /**
     * 初始化
     *
     * @param ?object $session
     * @return self
     */
    public function __construct(Session $session)
    {
        $this->session = $session;

        if (app()->isDebug()) {
            $this->mctime();
        }

        return $this;
    }

    /**
     * 创建文字验证码
     *
     * @param array $config
     * @return self
     */
    public function create(array $config = []): self
    {

        if(!empty($config['math'])) {
            return $this->createMath($config);
        }

        $this->config = $this->config($config);

        $this->initFont($this->config['fontName']);

        $text = $this->random->captchaText($this->config);

        $this->svg = $this->generate($text);

        return $this;
    }

    /**
     * 创建计算类型验证码
     *
     * @param array $config
     * @return self
     */
    public function createMath(array $config = []): self
    {
        $this->config = $this->config($config);

        $this->initFont($this->config['fontName']);

        [$answer, $equation] = $this->random->mathExpr($this->config['mathMin'], $this->config['mathMax'], $this->config['math']);
        $this->svg = $this->generate($equation, $answer);

        return $this;
    }

    /**
     * 生成验证码
     *
     * @param string $text
     * @return string
     */
    protected function generate(string $text, ?string $answer = null): string
    {
        $text = $text ?: $this->random->captchaText();

        $this->setHash($answer ?: $text);

        $width = $this->config['width'];
        $height = $this->config['height'];

        $rect = '<rect width="100%" height="100%" fill="#' . ($this->config['background'] ?: 'fefefe') . '"/>';
        $paths = array_merge($this->getLineNoise($width, $height), $this->getText($text, $width, $height,));

        shuffle($paths);

        $paths = implode('', $paths);

        $start = '<svg xmlns="http://www.w3.org/2000/svg" width="'. $width .'" height="'. $height .'" viewBox="0,0,'. $width .','. $height .'" author="CFYun">';

        return $start . $rect . $paths . '</svg>';
    }

    /**
     * 生成并写入hash的session
     *
     * @param string $text
     * @return bool
     */
    private function setHash(string $text): bool
    {
        $text = mb_strtolower($text, 'UTF-8');
        $hash = $this->encrypter()->encrypt($text);

        $this->session->set('scaptcha', $hash);

        $this->text = $text;
        $this->hash = $hash;

        return true;
    }

    /**
     * 验证验证码是否正确
     *
     * @param string $code 验证码
     * @return bool 验证码是否正确
     */
    public function check(string $code): bool
    {
        if (!$this->session->has('scaptcha')) {
            return false;
        }

        $hash = $this->session->get('scaptcha');

        $text = is_null($hash) ? null : $this->encrypter()->decrypt($hash);
        $res = $code === $text;

        if ($res) {
            $this->session->delete('scaptcha');
        }

        return $res;
    }

    /**
     * 生成干扰线条
     *
     * @param int $width
     * @param int $height
     * @return array
     */
    private function getLineNoise ($width, $height): array
    {
        $width = (int) $width;
        $height = (int) $height;

        $min = isset($this->config['inverse']) ? 7 : 1;
        $max = isset($this->config['inverse']) ? 15 : 9;
        $i = -1;

        $noiseLines = [];
        while (++$i < $this->config['noise']) {
            $start = Random::randomInt(1, 21) . ' ' . Random::randomInt(1, $height - 1);
            $end = Random::randomInt($width - 21, $width - 1) . ' ' . Random::randomInt(1, $height - 1);
            $mid1 = Random::randomInt(($width / 2) - 21, ($width / 2) + 21) . ' ' . Random::randomInt(1, $height - 1);
            $mid2 = Random::randomInt(($width / 2) - 21, ($width / 2) + 21) . ' ' . Random::randomInt(1, $height - 1);

            $color = $this->config['color'] ? $this->random->color() : $this->random->greyColor($min, $max);

            $noiseLines[] = '<path d="M' . $start . ' C' . $mid1 . ',' . $mid2 . ',' . $end . '" stroke="' . $color . '" fill="none"/>';
        }

        return $noiseLines;
    }

    /**
     * 获取文字svg path
     *
     * @param string $text
     * @param int $width
     * @param int $height
     * @return array
     */
    private function getText(string $text, $width, $height): array
    {
        $width = (int) $width;
        $height = (int) $height;

        $len = Str::strlen($text);

        $spacing = ($width - 2) / ($len + 1);
        $min = $max = 0;

        if(!empty($this->config['inverse'])) {
            $min = 10;
            $max = 14;
        }

        $i = -1;

        // 中文, 不建议使用
        if(preg_match ("/[\x{4e00}-\x{9fa5}]/u", $text)) {
            $text = preg_split('/(?<!^)(?!$)/u', $text);
        } else {
            $text = str_split($text);
        }

        $out = [];
        $opts = [
            'size' => $this->config['fontSize'],
            'cache' => $this->config['cache'],
        ];

        $opts['x'] = $spacing * (0 + 1);
        $opts['y'] = $height / 2;

        while (++$i < $len) {
            $opts['x'] = $spacing * ($i + 1);
            $opts['y'] = $height / 2;

            $charPath = $this->ch2path->get($text[$i], $opts);

            $color = empty($this->config['color']) ? $this->random->greyColor($min, $max) : $this->random->color();
            $out[] = '<path fill="' . $color . '" d="' . $charPath . '"/>';
        }

        return $out;
    }

    /**
     * 载入字体初始化相关
     *
     * @param string|null $fontName
     */
    private function initFont($fontName = null)
    {
        $this->random = $this->random ?? new Random;
        $this->ch2path = $this->ch2path ?? new Ch2Path($fontName);
    }

    /**
     * Initialize encrypter
     *
     * @return object
     */
    private function encrypter(): object
    {
        if(!is_null($this->encrypter)) {
            return $this->encrypter;
        }

        return $this->encrypter = new Encrypter($this->config['salt']);
    }

    /**
     * 获取配置
     *
     * @param array $config
     * @return array
     */
    public function config($config = []): array
    {
        if (!empty($config['type'])) {
            return array_merge($this->config, Config::get('scaptcha', []), Config::get('scaptcha.'. $config['type'], []), $config);
        }

        return array_merge($this->config, Config::get('scaptcha', []), (array) $config);
    }

    /**
     * 清空字形缓存
     */ 
    public function deleteCache()
    {
        \isszz\captcha\support\Cache::delete();
    }

    public function mctime($isReturn = false)
    {
        [$msec, $sec] = explode(' ', microtime());
        $mctime = floatval($msec) + floatval($sec);

        if ($isReturn) {
            return round(($mctime - $this->mctime) * 1000, 3) . ' ms';
        }

        $this->mctime = $mctime;
    }

    /**
     * 获取验证码
     */
    public function __toString() {
        return $this->svg ?: '';
    }
}
