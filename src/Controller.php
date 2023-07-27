<?php
declare (strict_types = 1);

namespace isszz\captcha;

class Controller
{
    /**
     * 输出为SVG代码，一般用于接口
     */
    public function index(Captcha $captcha, \think\Request $request)
    {
		$config = [];
		if($request->param()) {
			$config = $this->BuildParam($request->param());
		}

		if (isset($config['reset'])) {
			$captcha->deleteCache();
		}

		$data = [];
		try {
			$data['code'] = 0;
			$data['msg'] = 'success';

			$content = (string) $captcha->create($config, true)->base64(isset($config['compress']) ? 2 : 1);

			if (app()->isDebug()) {
				$data['mtime'] = $captcha->mctime(true);
			}

			$data['token'] = $captcha->getToken();
			$data['svg'] = $content;
			unset($content);

		} catch (\Exception $e) {
			$data['code'] = 1;
			$data['svg'] = null;
			$data['msg'] = $e->getMessage() ?? 'Unknown error';
		}

		return json($data);
    }

    /**
     * 输出为可视SVG图片
     */
    public function svg(Captcha $captcha, \think\Request $request)
    {
		$config = [];
		if($request->param()) {
			$config = $this->buildParam($request->param());
		}

		if (isset($config['reset'])) {
			$captcha->deleteCache();
		}

		$content = (string) $captcha->create($config);

		$headers['Content-Length'] = strlen($content);

		if (app()->isDebug()) {
			$headers['X-Scaptcha-Mtime'] = $captcha->mctime(true);
		}

		return response($content, 200, $headers)->contentType('image/svg+xml');
    }

    /**
     * 验证|输出json
     */
    public function check(Captcha $captcha, \think\Request $request)
    {
    	$code = $request->param('code') ?? null;
    	$token = $request->param('token') ?? null;

        $json = [
            'code' => 0,
            'msg' => 'success',
        ];

        if (!$code) {
            $json['code'] = 2;
            $json['msg'] = 'The Captcha code cannot be empty';
        	return json($json);
        }

		try {
	    	if (!$captcha->check($code, $token)) {
	            $json['code'] = 1;
	            $json['msg'] = 'Captcha code error';
	    	}
		} catch (\Exception $e) {
			$json['code'] = 3;
			$json['msg'] = $e->getMessage() ?? 'Unknown error';
		}

        return json($json);
    }

	/**
	 * 根据url传入参数组装配置
	 *
	 * /scaptcha/w/200/h/60/s/72/l/5
	*/
	protected function buildParam($params = [])
	{
		$config = [];

		if(empty($params)) {
			return [];
		}

		// 额外配置类型
		if(isset($params['t'])) {
			$config['type'] = $params['t'];
		}

		// 运算模式，1=加法，2=减法，3=乘法，4=除法，或者随机四种
		if(!empty($params['m'])) {
			if($params['m'] == 1) {
				$config['math'] = '+';
			} elseif($params['m'] == 2) {
				$config['math'] =  '-';
			} elseif($params['m'] == 3) {
				$config['math'] =  '*';
			} elseif($params['m'] == 4) {
				$config['math'] =  '/';
			} else {
				$config['math'] = 'rand';
			}
		}

		// 验证码宽度
		if(!empty($params['w'])) {
			$config['width'] = $params['w'];
		}

		// 验证码高度
		if(!empty($params['h'])) {
			$config['height'] = $params['h'];
		}

		// 文字大小
		if(!empty($params['s'])) {
			$config['fontSize'] = $params['s'];
		}

		// 显示文字数量, 非算数模式有效
		if(!empty($params['l'])) {
			$config['size'] = $params['l'];
		}

		// 干扰线条数量
		if(!empty($params['n'])) {
			$config['noise'] = $params['n'];
		}

		// 文字是否随机色
		if(isset($params['c'])) {
			$config['color'] = $params['c'] != '0';
		}

		// 背景色, fefefe
		if(!empty($params['b'])) {
			$config['background'] = $params['b'];
		}

		if(!empty($params['cs'])) {
			$config['compress'] = true;
		}

		if(!empty($params['rt'])) {
			$config['cache'] = false;
		}

		if(!empty($params['reset'])) {
			$config['reset'] = true;
		}

		return $config;
	}
}
