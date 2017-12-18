<?php

namespace UiStd\Git;

use UiStd\Common\Factory as UisFactory;
use UiStd\Common\InvalidConfigException;

/**
 * Class Git
 * @package UiStd\Git
 */
class Git extends UisFactory
{
    /**
     * @var string 配置组名
     */
    protected static $config_group = 'uis-git';

    /**
     * 获取一个缓存实例
     * @param string $config_name
     * @return GitRepo|object
     * @throws InvalidConfigException
     */
    public static function get($config_name = 'main')
    {
        return self::getInstance($config_name);
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
