<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\PDO;

use PDOStatement;

/**
 * PDOExcavated for unit tests.
 */
class PDOExcavatedExtended extends PDOExcavated
{
    /**
     * Prepares query for logging.
     *
     * @param string &$query
     * @return void
     * @see ExcavatingTrait::after()
     */
    protected function prepareQueryForLogging(string &$query)
    {
    }

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
     * Method used to replace PDOStatementExcavated by possible child.
     *
     * @param string $template
     * @param PDOStatement $stmt
     * @return PDOStatementExcavatedExtended
     * @see self::getResultStatement()
     */
    protected function getStatement(string $template, PDOStatement $stmt): PDOStatementExcavated
    {
        return new PDOStatementExcavatedExtended($template, $this, $stmt);
    }
}
