<?php

namespace FFan\Std\Git;

use FFan\Std\Logger\FileLogger;

require_once '../vendor/autoload.php';
require_once 'config.php';

new FileLogger('logs', 'git');

$git = Git::get('main');

$git->init();