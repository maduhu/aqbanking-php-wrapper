<?php
define('DIR_ROOT','./');
define('DIR_CLASS', DIR_ROOT . 'class/');
define('DIR_VENDOR', DIR_ROOT . 'vendor/');
define('DIR_AQCONFIG', DIR_ROOT . 'aqbanking/config/');

define('CRYPT_KEY','q|_/,/w{wH22"\'nTIE(*F=Eo6<oY3]U[');

define('DEBUG', true);

require_once DIR_ROOT . 'functions.php';
require_once DIR_CLASS . 'AqBanking.php';
require_once DIR_CLASS . 'Db.php';
require_once DIR_CLASS . 'Account.php';
require_once DIR_CLASS . 'Bank.php';
require_once DIR_CLASS . 'StandingOrder.php';
require_once DIR_CLASS . 'Transfer.php';
require_once DIR_VENDOR . 'IBANGenerator.php';
