<?php
declare(strict_types=1);
define('APP_ROOT', dirname(__DIR__));
$GLOBALS['CFG'] = require APP_ROOT . '/config.php';
date_default_timezone_set($GLOBALS['CFG']['app']['tz']);
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
