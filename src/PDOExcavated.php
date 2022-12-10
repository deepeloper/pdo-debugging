<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\PDO;

use PDO;
use PDOException;
use PDOStatement;

use function is_array;
use function is_object;
use function microtime;
use function sprintf;

/**
 * PDO having benchmarking and debugging abilities class.
 */
class PDOExcavated extends PDO
{
    use ExcavatingTrait;

    public const ATTR_DEBUG = "debug";

    /**
     * Represents a connection between PHP and a database server.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @see https://www.php.net/manual/en/class.pdo.php
     */
    public function __construct(string $dsn, string $username = null, string $password = null, array $options = [])
    {
        if (isset($options[self::ATTR_DEBUG])) {
            $this->initDebugging(
                [
                    'dsn' => $dsn,
                    'username' => $username,
                ] + $options[self::ATTR_DEBUG]
            );
            if (isset($options[self::ATTR_DEBUG]['logger'])) {
                $this->skipLogging = false;
            }
            unset($options[self::ATTR_DEBUG]);
        }
        $this->before(null, [2, 3]);
        $timeStamp = microtime(true);
        parent::__construct($dsn, $username, $password, $options);
        $delay = microtime(true) - $timeStamp;
        $this->benchmarks->container['total']['time'] += $delay;
        $this->after();
    }

    /**
     * Initiates a transaction.
     *
     * @return bool
     * @see https://www.php.net/manual/en/pdo.begintransaction.php
     */
    public function beginTransaction(): bool
    {
        $this->before();
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            $result = parent::beginTransaction();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        return $this->getResult($delay, $result, $e);
    }

    /**
     * Commits a transaction.
     *
     * @return bool
     * @see https://www.php.net/manual/en/pdo.commit.php
     */
    public function commit(): bool
    {
        $this->before();
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            $result = parent::commit();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        $this->benchmarks->container['commit']['time'] += $delay;
        $this->benchmarks->container['commit']['count']++;
        return $this->getResult($delay, $result, $e);
    }

    /**
     * Executes an SQL statement and returns the number of affected rows.
     *
     * @param string $statement
     * @return int
     * @see https://www.php.net/manual/en/pdo.exec.php
     */
    public function exec($statement): int
    {
        $this->before([
            'source' => __METHOD__,
            'query' => $statement,
        ]);
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            $result = parent::exec($statement);
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        $this->benchmarks->container['query']['time'] += $delay;
        $this->benchmarks->container['query']['count']++;
        return $this->getResult($delay, $result, $e);
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $query
     * @param array $options
     * @return PDOStatement
     * @see https://www.php.net/manual/en/pdo.prepare.php
     */
    public function prepare($query, $options = null): PDOStatement
    {
        $this->before();
        $stmt = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            $stmt = is_array($options) ? parent::prepare($query, $options) : parent::prepare($query);
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        return $this->getResultStatement($delay, "prepare", $query, $stmt, $e);
    }

    /**
     * Prepares and executes an SQL statement without placeholders.
     *
     * @param string $statement
     * @param int $mode
     * @param mixed $arg3
     * @param array $constructorArgs
     * @return PDOStatement
     * @see https://www.php.net/manual/en/pdo.query.php
     */
    public function query(
        string $statement,
        int $mode = PDO::ATTR_DEFAULT_FETCH_MODE,
        $arg3 = null,
        array $constructorArgs = []
    ): PDOStatement {
        $this->before([
            'source' => sprintf("%s::query", __CLASS__),
            'query' => $statement,
        ]);
        $stmt = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            switch ($mode) {
                case PDO::ATTR_DEFAULT_FETCH_MODE:
                    $stmt = parent::query($statement);
                    break;
                case PDO::FETCH_CLASS:
                    $stmt = parent::query($statement, $mode, $arg3, $constructorArgs);
                    break;
                default:
                    if (null === $arg3) {
                        $stmt = parent::query($statement, $mode);
                    } else {
                        $stmt = parent::query($statement, $mode, $arg3);
                    }
            }
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        return $this->getResultStatement($delay, "query", $statement, $stmt, $e);
    }

    /**
     * Rolls back a transaction.
     *
     * @return bool
     * @see https://www.php.net/manual/en/pdo.rollback.php
     */
    public function rollBack(): bool
    {
        $this->before();
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            $result = parent::rollBack();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        // @codeCoverageIgnoreEnd
        $delay = microtime(true) - $timeStamp;
        $this->benchmarks->container['rollBack']['time'] += $delay;
        $this->benchmarks->container['rollBack']['count']++;
        return $this->getResult($delay, $result, $e);
    }

    /**
     * Prepares query for logging.
     *
     * @param string $query
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
    }

    /**
     * Updates total time, returns statement object or throws an exception.
     *
     * @param float $delay
     * @param string $key
     * @param string $template
     * @param PDOStatement $stmt
     * @param PDOException|null $e
     * @return PDOStatement
     * @see self::prepare()
     * @see self::query()
     */
    protected function getResultStatement(
        float $delay,
        string $key,
        string $template,
        PDOStatement $stmt = null,
        PDOException $e = null
    ): PDOStatement {
        $this->benchmarks->container[$key]['count']++;
        $this->benchmarks->container[$key]['time'] += $delay;
        $this->benchmarks->container['total']['time'] += $delay;
        $this->after();
        if ($stmt) {
            return is_object($stmt) ? $this->getStatement($template, $stmt) : $stmt;
        } else {
            throw $e;
        }
    }

    /**
     * Method used to replace PDOStatementExcavated by possible child.
     *
     * @param string $template
     * @param PDOStatement $stmt
     * @return PDOStatementExcavated
     * @see self::getResultStatement()
     */
    protected function getStatement(string $template, PDOStatement $stmt): PDOStatementExcavated
    {
        return new PDOStatementExcavated($template, $this, $stmt);
    }
}
