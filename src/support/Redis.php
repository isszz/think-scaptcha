<?php
namespace isszz\captcha\support;

class Redis
{
    /**
     * Redis instance
     */
    protected $handler = null;

    /**
     * Default configuration
     */
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'persistent' => false,
    ];

    protected static object|null $connection = null;

    protected static array $config = [];

    public function __construct($options)
    {
        if (!extension_loaded('redis') && !class_exists('\Predis\Client')) {
            throw new \BadFunctionCallException('Please install the Redis extension!');
        }

        if (!is_null($this->handler)) {
            return;
        }

        if(empty($options)) {
            $this->handler = \think\facade\Cache::store('redis')->handler();
        } else {
            $this->options = array_merge($this->options, (array) $options);

            // 连接redis，来自tp缓存类的redis驱动
            if (extension_loaded('redis')) {
                $this->handler = new \Redis;

                if ($this->options['persistent']) {
                    $this->handler->pconnect(
                        $this->options['host'],
                        (int) $this->options['port'],
                        (int) $this->options['timeout'],
                        'persistent_id_' . $this->options['select']
                    );
                } else {
                    $this->handler->connect(
                        $this->options['host'],
                        (int) $this->options['port'],
                        (int) $this->options['timeout']
                    );
                }

                if ($this->options['password'] == '') {
                    $this->handler->auth($this->options['password']);
                }
            } elseif (class_exists('\Predis\Client')) {
                $params = [];
                foreach ($this->options as $key => $val) {
                    if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'profile', 'replication', 'parameters'])) {
                        $params[$key] = $val;
                        unset($this->options[$key]);
                    }
                }

                if ($this->options['password'] == '') {
                    unset($this->options['password']);
                }

                $this->handler = new \Predis\Client($this->options, $params);
            }

            if ($this->options['select'] != 0) {
                $this->handler->select((int) $this->options['select']);
            }
        }
    }

    public static function connection(?array $config = []): self
    {
        if(is_null(self::$connection)) {
            self::$connection = new static($config);
        }

        return self::$connection;
    }

    /**
     * Determine if an item exists in the redis.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key): bool
    {
        return $this->handler->exists($key) ? true : false;
    }

    /**
     * Get an item from the redis.
     *
     * @param  string  $key
     * @return void
     */
    public function get(string $key)
    {
        if (!empty($result = $this->handler->get($key))) {
            return unserialize($result);
        }
    }

    /**
     * Write an item to the redis for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $expire
     * @return bool
     */
    public function put(string $key, mixed $value, int $expire = null): bool
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        if (is_null($expire)) {
            $this->handler->set($key, $value);
        } else {
            $this->handler->setex($key, $expire, serialize($value));
        }

        return true;
    }

    /**
     * Write an item to the redis that lasts forever.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return bool
     */
    public function forever($key, $value): bool
    {
        $this->handler->set($key, serialize($value));
        return true;
    }

    /**
     * Delete an item from the redis.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key): bool
    {
        $result = $this->handler->del($key);
        return $result > 0;
    }

    /**
     * Clear redis.
     *
     * @return bool
     */
    public function clear(): bool
    {
        $this->handler->flushDB();
        return true;
    }
}