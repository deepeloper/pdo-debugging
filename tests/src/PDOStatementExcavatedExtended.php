<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author <a href="https://github.com/deepeloper" target="_blank">deepeloper</a>
 * @license https://opensource.org/licenses/mit-license.php
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
     * @param array &$scope
     * @return void
     * @see ExcavatingTrait::after()
     */
    protected function scope(array &$scope)
    {
        $scope['FOO'] = __METHOD__;
    }

    /**
     * Prepares query for logging.
     *
     * @param string &$query
     * @return void
     * @link ExcavatingTrait::after()
     */
    protected function prepareQueryForLogging(&$query)
    {
    }
}
