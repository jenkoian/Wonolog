<?php // phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

putenv('TESTS_PATH=' . __DIR__);
putenv('LIBRARY_PATH=' . dirname(__DIR__));

$vendor = dirname(__DIR__) . '/vendor';

if (!realpath($vendor)) {
    die('Please install via Composer before running tests.');
}

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', "{$vendor}/autoload.php");
}

error_reporting(E_ALL); // phpcs:ignore

require_once "{$vendor}/antecedent/patchwork/Patchwork.php";
require_once "{$vendor}/autoload.php";
unset($vendor);
