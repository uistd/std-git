<?php
use UiStd\Common\Config as UisConfig;

UisConfig::addArray(
    array(
        'uis-git:main' => array(
            'url' => 'http://github.com/dop/php.git'
        ),
        'runtime_path' => __DIR__ . '/runtime',
        'env' => 'dev'
    )
);