<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author <a href="https://github.com/deepeloper" target="_blank">deepeloper</a>
 * @license https://opensource.org/licenses/mit-license.php
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
     * @param string $message
     * @param array $scope
     * @return void
     * @link ExcavatingTrait::after()
     */
    public function log($message, array $scope);
}