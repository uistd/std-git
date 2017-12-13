<?php
use FFan\Std\Common\Config as FFanConfig;

FFanConfig::addArray(
    array(
        'ffan-logger:web' => array(
            'file' => 'test',
            'path' => 'test'
        ),
        'ffan-git:main' => array(
            'url' => 'http://github.com/dop/php.git'
        ),
        'runtime_path' => __DIR__ . '/runtime',
        'env' => 'dev'
    )
);