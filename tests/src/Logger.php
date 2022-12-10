<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\PDO;

/**
 * Logger for unit tests.
 */
class Logger implements LoggerInterface
{
    /**
     * Array containing log messages
     */
    protected array $log;

    /**
     * Constructor.
     */
    public function __construct(array &$log)
    {
        $this->log = &$log;
    }

    /**
     * Logs message.
     */
    public function log(string $message, array $scope): void
    {
        $this->log[] = $message;
    }
}
