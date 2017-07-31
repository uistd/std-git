<?php

namespace ffan\php\git;

use ffan\php\utils\Factory as FFanFactory;
use ffan\php\utils\InvalidConfigException;

class Git extends FFanFactory
{
    /**
     * @var string 配置组名
     */
    protected static $config_group = 'ffan-git';

    /**
     * 获取一个缓存实例
     * @param string $config_name
     * @return GitRepo
     * @throws InvalidConfigException
     */
    public static function get($config_name = 'main')
    {
        $obj = self::getInstance($config_name);
        return $obj;
    }

    /**
     * 默认的缓存类
     * @param string $config_name
     * @param array $conf_arr
     * @return GitRepo
     */
    protected static function defaultInstance($config_name, $conf_arr)
    {
        return new GitRepo($config_name, $conf_arr);
    }
}
