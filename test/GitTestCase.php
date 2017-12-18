<?php

namespace UiStd\Git;

use UiStd\Logger\FileLogger;

require_once '../vendor/autoload.php';
require_once 'config.php';

new FileLogger('logs', 'git');

$git = Git::get('main');

$git->init();