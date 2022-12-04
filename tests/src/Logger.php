<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author <a href="https://github.com/deepeloper" target="_blank">deepeloper</a>
 * @license https://opensource.org/licenses/mit-license.php
 */

namespace deepeloper\PDO;

/**
 * Logger for unit tests.
 */
class Logger implements LoggerInterface
{
    /**
     * Array containing log messages
     *
     * @var &array
     */
    protected $log;

    /**
     * Constructor.
     *
     * @param array &$log
     */
    public function __construct(array &$log)
    {
        $this->log = &$log;
    }

    /**
     * Logs message.
     *
     * @param string $message
     * @param array $scope
     * @return void
     */
    public function log($message, array $scope)
    {
        // echo "$message\n";
        $this->log[] = $message;
    }
}
