<?php
declare (strict_types = 1);

namespace isszz\captcha\support;

class Cache
{
    public string $fontName;
    
	public function __construct(string $fontName)
	{
        $this->fontName = mb_substr($fontName, 0, strpos($fontName, '.'));
	}

    public static function make(string $fontName = '')
    {
    	return new static($fontName);
    }

    /**
     * 获取字形缓存
     * 
     * @param  string|int  $text
     * @return string
     */
    public function get(string|int $text, $type = 'glyf')
    {
        $path = $this->getPath($text, $type);

        if(is_file($path)) {
            return (static function () use ($path) {
                return require $path;
            })();
		}
		
        return '';
    }

    /**
     * 写入字形缓存
     * 
     * @param  string|int  $text
     * @param  ?array  $data
     * @return string
     */
    public function put(string|int $text, ?array $data = null, string $type = 'glyf')
    {
        $path = $this->getPath($text, $type);

        return File::savePhpData($path, $data) ? true : false;
    }

    public function getPath(string|int $text, string $type = 'glyf')
    {
        $path = root_path('runtime') . DIRECTORY_SEPARATOR .'scaptcha'. DIRECTORY_SEPARATOR .'glyph'. DIRECTORY_SEPARATOR . $this->fontName . DIRECTORY_SEPARATOR;

        return $path .($type === 'base' ? '' : $type . DIRECTORY_SEPARATOR) . md5($text) .'.php';
    }

    /**
     * Recursively delete a directory.
     *
     * @param  string  $directory
     * @return void
     */
    public static function delete($directory = null)
    {
        if(is_null($directory)) {
            $directory = runtime_path('scaptcha') .'glyph'. DIRECTORY_SEPARATOR;
        }

        if (!is_dir($directory)) return;

        $items = new \FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir()) {
                static::delete($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        unset($items);
    }
}
