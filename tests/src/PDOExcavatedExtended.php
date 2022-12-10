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
     * @see ExcavatingTrait::after()
     */
    protected function prepareQueryForLogging(string &$query): void
    {
    }

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
     * Method used to replace PDOStatementExcavated by possible child.
     *
     * @see self::getResultStatement()
     */
    protected function getStatement(string $template, PDOStatement $stmt): PDOStatementExcavated
    {
        return new PDOStatementExcavatedExtended($template, $this, $stmt);
    }
}
