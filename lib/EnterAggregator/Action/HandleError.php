<?php

namespace EnterAggregator\Action;

use EnterAggregator\LoggerTrait;

class HandleError {
    use LoggerTrait;

    /**
     * @param $error
     */
    public function execute(&$error) {
        set_error_handler(function($level, $message, $file, $line) use (&$error) {
            static $levels = [
                E_WARNING           => 'Warning',
                E_NOTICE            => 'Notice',
                E_USER_ERROR        => 'User Error',
                E_USER_WARNING      => 'User Warning',
                E_USER_NOTICE       => 'User Notice',
                E_STRICT            => 'Runtime Notice',
                E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
                E_DEPRECATED        => 'Deprecated',
                E_USER_DEPRECATED   => 'User Deprecated',
            ];

            switch ($level) {
                case E_USER_ERROR:
                case E_WARNING:
                case E_NOTICE:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                case E_USER_WARNING:
                case E_USER_NOTICE:
                    if (0 !== error_reporting()) {
                        $error = new \ErrorException($message, 0, $level, $file, $line);
                    }

                    return true;
            }

            if (error_reporting() & $level) {
                throw new \ErrorException(sprintf('%s: %s in %s line %d', isset($levels[$level]) ? $levels[$level] : $level, $message, $file, $line));
            }

            return false;
        });
    }
}