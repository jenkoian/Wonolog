<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;
use Monolog\Logger;

/**
 * Looks a wp_die() and try to find and log DB errors.
 */
final class WpDieHandlerListener implements FilterListener
{
    /**
     * @var int
     */
    private $logLevel;

    /**
     * @param int $logLevel
     */
    public function __construct(int $logLevel = Logger::ERROR)
    {
        $this->logLevel = LogLevel::normalizeLevel($logLevel) ?? Logger::ERROR;
    }

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['wp_die_ajax_handler', 'wp_die_xmlrpc_handler', 'wp_die_handler'];
    }

    /**
     * Run as handler for wp_die() and checks if it was called by
     * wpdb::bail() or wpdb::print_error() so something gone wrong on db.
     * After logging error, the method calls original handler.
     *
     * @wp-hook wp_die_ajax_handler
     * @wp-hook wp_die_handler
     *
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $subscriber
     * @return mixed
     */
    public function filter(string $hook, array $args, LogActionUpdater $subscriber)
    {
        $handler = $args ? reset($args) : null;

        if (!$handler || !is_callable($handler) || !$this->stackTraceHasDbError()) {
            return $handler;
        }

        $level = $this->logLevel;
        $updater = static function (string $msg, array $context) use ($subscriber, $level) {
            // Log the wp_die() error message.
            $subscriber->update(new Log($msg, $level, Channels::DB, $context));
        };

        /**
         * @param string $message
         * @param string $title
         * @param array $args
         * @return mixed
         *
         * @wp-hook wp_die_ajax_handler
         * @wp-hook wp_die_handler
         */
        return static function ($message, $title = '', $args = []) use ($handler, $updater) {
            $msg = (string)(filter_var($message ?: $title, FILTER_SANITIZE_STRING) ?: '');
            $context = is_array($args) ? $args : [];
            $context['title'] = $title;
            $updater($msg, $context);

            return $handler($message, $title, $args);
        };
    }

    /**
     * @return bool
     */
    private function stackTraceHasDbError(): bool
    {
        $stacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($stacktrace as $item) {
            $function = $item['function'] ?? null;
            $class = $item['class'] ?? null;
            if ($class === 'wpdb' && ($function === 'bail' || $function === 'print_error')) {
                return true;
            }
        }

        return false;
    }
}
