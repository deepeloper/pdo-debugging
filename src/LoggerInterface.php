<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\PDO;

/**
 * Logger interface.
 */
interface LoggerInterface
{
    /**
     * Logs message.
     *
     * @link ExcavatingTrait::after()
     */
    public function log(string $message, array $scope): void;
}
