<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author <a href="https://github.com/deepeloper" target="_blank">deepeloper</a>
 * @license https://opensource.org/licenses/mit-license.php
 */

namespace deepeloper\PDO;

use PDO;
use PDOException;
use stdClass;

use function array_values;
use function get_class;
use function is_object;
use function sizeof;

/**
 * Common tests.
 */
class CommonTest extends TestCaseConfigAndDatabase
{
    /**
     * Initial benchmarks
     *
     * @var int[][]
     */
    protected $benchmarks = [
        'query' => [
            'count' => 0,
            'time' => 0,
        ],
        'prepare' => [
            'count' => 0,
            'time' => 0,
        ],
        'fetch' => [
            'count' => 0,
            'time' => 0,
        ],
        'commit' => [
            'count' => 0,
            'time' => 0,
        ],
        'rollBack' => [
            'count' => 0,
            'time' => 0,
        ],
    ];

    /**
     * Tests PDOExcavated::getAvailableDrivers().
     *
     * @return void
     * @cover PDOExcavated::testGetAvailableDrivers
     */
    public function testGetAvailableDrivers()
    {
        self::assertEquals(PDO::getAvailableDrivers(), PDOExcavated::getAvailableDrivers());
    }

    /**
     * Tests without logger.
     *
     * @return void
     * @cover ExcavatingTrait
     * @cover PDOExcavated
     * @cover PDOStatementExcavated
     * @cover Tools
     */
    public function testClasses()
    {
        $pdo = self::connectDatabase();
        $benchmarks = $pdo->getBenchmarks();
        $totalTime = $benchmarks['total']['time'];
        unset($benchmarks['total']);
        self::assertEquals($this->benchmarks, $benchmarks);
        self::assertGreaterThan(0, $totalTime);

        list (, $benchmarks) = $pdo->getDebuggingEnvironment();

        $pdo->beginTransaction();
        self::assertGreaterThan($totalTime, $benchmarks->container['total']['time']);
        $totalTime = $benchmarks->container['total']['time'];

        $record = ['foo' => 100500, 'bar' => 'blah blah blah'];
        /**
         * @var PDOStatementExcavated $stmt
         */
        $stmt = Tools::prepareModifyingStatement(
            $pdo,
            "INSERT INTO `test` %s",
            $record,
            ['foo' => PDO::PARAM_INT],
            ['date' => "NOW()"]
        );
        self::assertEquals(
            "INSERT INTO `test` (`foo`, `bar`, `date`) VALUES (:foo, :bar, NOW())",
            $stmt->getQueryString()
        );
        $result = $stmt->execute();
        self::assertTrue($result);
        self::assertEquals(
            "INSERT INTO `test` (`foo`, `bar`, `date`) VALUES (100500, 'blah blah blah', NOW())",
            $stmt->getLastExecutedQuery()
        );
        self::assertEquals(1, $benchmarks->container['query']['count']);
        self::assertGreaterThan(0, $benchmarks->container['query']['time']);
        self::assertEquals(1, $benchmarks->container['prepare']['count']);
        self::assertGreaterThan(0, $benchmarks->container['prepare']['time']);
        self::assertGreaterThan($totalTime, $benchmarks->container['total']['time']);
        $statementBenchmarks = $stmt->getBenchmarks();
        self::assertEquals(1, $statementBenchmarks['query']['count']);
        self::assertGreaterThan(0, $benchmarks->container['query']['time']);
        self::assertGreaterThan(0, $benchmarks->container['total']['time']);

        $pdo->commit();
        self::assertEquals(1, $benchmarks->container['commit']['count']);
        self::assertGreaterThan(0, $benchmarks->container['commit']['time']);

        $pdo->beginTransaction();
        $pdo->rollBack();
        self::assertEquals(1, $benchmarks->container['rollBack']['count']);
        self::assertGreaterThan(0, $benchmarks->container['rollBack']['time']);

        $query = "SELECT `foo`, `bar` FROM `test` WHERE `id` = ?";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(1, 1, PDO::PARAM_INT);
        $stmt->execute();
        self::assertEquals([$record], $stmt->fetchAll());
        self::assertEquals(2, $benchmarks->container['query']['count']);
        self::assertEquals(2, $benchmarks->container['prepare']['count']);
        self::assertEquals(1, $benchmarks->container['fetch']['count']);
        self::assertGreaterThan(0, $benchmarks->container['fetch']['time']);
        $stmt->closeCursor();
        $stmt->execute();
        self::assertEquals([array_values($record)], $stmt->fetchAll(PDO::FETCH_NUM));
        $stmt->closeCursor();
        $stmt->execute();
        self::assertEquals([100500], $stmt->fetchAll(PDO::FETCH_COLUMN, 0));
        $stmt->closeCursor();
        $stmt->execute();
        self::assertEquals([(object)$record], $stmt->fetchAll(PDO::FETCH_CLASS, "stdClass", []));

        $stmt = Tools::prepareModifyingStatement(
            $pdo,
            "UPDATE `test` %s WHERE `id` = :id",
            ['foo' => 0],
            ['foo' => PDO::PARAM_INT]
        );
        $stmt->bindValue('id', 1, PDO::PARAM_INT);
        $stmt->execute();
        self::assertEquals(6, $benchmarks->container['query']['count']);
        self::assertEquals(3, $benchmarks->container['prepare']['count']);
        self::assertEquals(1, $stmt->rowCount());
        self::assertEquals(
            1,
            $pdo->exec("UPDATE `test` SET `bar` = ''")
        );
        self::assertEquals(7, $benchmarks->container['query']['count']);

        $stmt = $pdo->query("SELECT * FROM `test` WHERE `bar` != ''");
        self::assertEquals(0, $stmt->rowCount());
        self::assertEquals(8, $benchmarks->container['query']['count']);
        $stmt = $pdo->query(
            "SELECT `id`, `foo`, `bar` FROM `test` WHERE `bar` = ''",
            PDO::FETCH_CLASS,
            "stdClass"
        );
        self::assertEquals(9, $benchmarks->container['query']['count']);
        /**
         * @var stdClass $result
         */
        $result = $stmt->fetch();
        self::assertTrue(is_object($result));
        self::assertEquals("stdClass", get_class($result));
        self::assertEquals(5, $benchmarks->container['fetch']['count']);

        $stmt = $pdo->query(
            "SELECT `id`, `foo`, `bar` FROM `test`",
            PDO::FETCH_NUM
        );
        self::assertEquals(["1", "0", ""], $stmt->fetch());
        self::assertEquals(10, $benchmarks->container['query']['count']);

        $expected = (object)['id' => "1", 'foo' => "0", 'bar' => ""];
        $actual = new stdClass();
        $stmt = $pdo->query(
            "SELECT `id`, `foo`, `bar` FROM `test` WHERE `bar` = ''",
            PDO::FETCH_INTO,
            $actual
        );
        self::assertEquals(11, $benchmarks->container['query']['count']);
        $stmt->fetch();
        self::assertEquals($expected, $actual);
        $stmt->closeCursor();
        $stmt->execute();
        self::assertEquals("1", $stmt->fetchColumn());
        $stmt->closeCursor();
        $stmt->execute();
        self::assertEquals($expected, $stmt->fetchObject());

        $stmt = $pdo->prepare("SELECT ?");
        $stmt->bindValue(1, 100500, PDO::PARAM_BOOL);
        $stmt->execute();
        self::assertEquals("1", $stmt->fetchColumn());
        $stmt->closeCursor();
        $stmt->bindValue(1, null, PDO::PARAM_NULL);
        $stmt->execute();
        self::assertNull($stmt->fetchColumn());
    }

    /**
     * Tests with logger and nonempty sources.
     *
     * @return void
     * @cover ExcavatingTrait::after
     * @cover PDOExcavated::prepareQueryForLogging
     */
    public function testWithLoggerAndNonemptySources()
    {
        self::$log = [];

        $logger = new Logger(self::$log);
        $pdo = self::connectDatabase([
            'logger' => $logger,
            'sources' => [
                "/^PDOStatementExcavated::/",
            ],
        ]);
        $stmt = $pdo->prepare("SELECT ?");
        $stmt->execute(["ED"]);
        self::assertEquals(1, sizeof(self::$log));
        $pdo->query("SELECT 1");
        self::assertEquals(1, sizeof(self::$log));
        $stmt = $pdo->prepare("SELECT ?");
        $stmt->bindValue(1, 100500, PDO::PARAM_INT);
        $stmt->execute();
        self::assertEquals(2, sizeof(self::$log));

        $stmt = $pdo->prepare("SELECT ?");
        $stmt->bindValue(1, 100500, PDO::PARAM_INT);
        $stmt->execute();

        self::$log = [];
        $pdo = self::connectDatabase([
            'logger' => $logger,
            'sources' => [
                "/^PDOExcavated::/",
            ],
        ]);
        $stmt = $pdo->prepare("SELECT ?");
        $stmt->bindValue(1, 100500, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Tests exception on wrong query.
     *
     * @return void
     * @cover PDOExcavated::getResultStatement
     */
    public function testExceptionOnWrongQuery()
    {
        $pdo = self::connectDatabase();
        $this->expectException(PDOException::class);
        $pdo->query("SOME KIND OF SHIT");
    }

    /**
     * Tests exception on invalid commit.
     *
     * @return void
     * @cover ExcavatingTrait::getResult
     */
    public function testExceptionOnInvalidCommit()
    {
        $pdo = self::connectDatabase();
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage("There is no active transaction");
        $pdo->commit();
    }
}
