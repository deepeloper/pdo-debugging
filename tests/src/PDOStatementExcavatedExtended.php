<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\PDO;

/**
 * PDOStatementExcavated for unit tests.
 */
class PDOStatementExcavatedExtended extends PDOStatementExcavated
{
    /**
     * Allows to customize log message scope.
     *
     * @see ExcavatingTrait::after()
     */
    protected function scope(array &$scope): void
    {
        $scope['FOO'] = __METHOD__;
    }

    /**
     * Prepares query for logging.
     *
     * @link ExcavatingTrait::after()
     */
    protected function prepareQueryForLogging(string &$query): void
    {
    }
}
