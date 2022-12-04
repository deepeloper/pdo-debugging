<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author <a href="https://github.com/deepeloper" target="_blank">deepeloper</a>
 * @license https://opensource.org/licenses/mit-license.php
 */

namespace deepeloper\PDO;

use PDO;

/**
 * Extended PDOExcavated and PDOStatementExcavated classes tests.
 */
class ExtendedClassesTest extends TestCaseConfigAndDatabase
{
    public function testGetAvailableDrivers()
    {
        $logger = new Logger(self::$log);

        self::$log = [];

        $logger = new Logger(self::$log);
        $pdo = $this->connectDatabase(
            [
                'logger' => $logger,
                'format' => [
                    'call'  => "%FOO%",
                    'query' => "%FOO%",
                ],
            ],
            "\\deepeloper\\PDO\\PDOExcavatedExtended"
        );
        $pdo->exec("UPDATE `test` SET `bar` = ''");
        $pdo->query("SELECT 1");
        $stmt = $pdo->prepare("SELECT ?, ?");
        $stmt->bindValue(1, 100500, PDO::PARAM_INT);
        $stmt->bindValue(2, "blah");
        $stmt->execute();
        self::assertEquals(
            [
                "deepeloper\PDO\PDOExcavatedExtended::scope",
                "deepeloper\PDO\PDOExcavatedExtended::scope",
                "deepeloper\PDO\PDOExcavatedExtended::scope",
                "deepeloper\PDO\PDOExcavatedExtended::scope",
                "deepeloper\PDO\PDOStatementExcavatedExtended::scope",
            ],
            self::$log
        );
    }
}
