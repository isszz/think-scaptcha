<?php
declare (strict_types = 1);

namespace isszz\captcha\font;

use isszz\captcha\CaptchaException;

class Font
{
    /**
     * 加载字体
     * 
     * @param  string  $fontName
     * @return mixed
     */
	public static function load(string $fontFileName = '')
	{
		if(empty($fontFileName)) {
			throw new CaptchaException('Font file name cannot be empty.');
		}

		if($file = self::getFontPath($fontFileName) and !is_file($file)) {
			throw new CaptchaException('Font not found in: ' . $file);
		}

		$header = file_get_contents($file, false, null, 0, 4);
		$obj = null;
		switch ($header) {
			case "\x00\x01\x00\x00":
			case 'true':
			case 'typ1':
				$obj = new \isszz\captcha\font\lib\truetype\File;
				break;
			case 'OTTO':
				$obj = new \isszz\captcha\font\lib\opentype\File;
				break;
			case 'wOFF':
				$obj = new \isszz\captcha\font\lib\woff\File;
				break;
			case 'ttcf':
				$obj = new \isszz\captcha\font\lib\truetype\Collection;
				break;
			// Unknown type or EOT
			default:
				$magicNumber = file_get_contents($file, false, null, 34, 2);
				
				if ($magicNumber === 'LP') {
					$obj = new \isszz\captcha\font\lib\eot\File;
				}
		}
		
		if (!is_null($obj)) {
			$obj->load($file);
			return $obj;
		}
		
		return null;
	}

    /**
     * 字体路径
     * 
     * @param  string  $name
     * @return string
     */
    public static function getFontPath(string $name): string
    {
    	return root_path('config') . 'fonts' . DIRECTORY_SEPARATOR . $name;
    }
}
