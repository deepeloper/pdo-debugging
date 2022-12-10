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
use function is_int;
use function is_string;
use function microtime;
use function sizeof;
use function sprintf;
use function str_replace;
use function uniqid;

/**
 * PDOStatement having benchmarking and debugging abilities class.
 *
 * @todo PHP >=8: Explore PDOStatement::getIterator().
 */
class PDOStatementExcavated extends PDOStatement
{
    use ExcavatingTrait;

    /**
     * SQL query template
     */
    protected string $template;

    /**
     * PDOExcavated object
     */
    protected PDOExcavated $pdo;

    /**
     * "Parent" statement object
     */
    protected PDOStatement $stmt;

    /**
     * Binded values
     */
    protected array $values = [];

    /**
     * Last executed parsed query
     */
    protected ?string $lastExecutedQuery;

    /**
     * Statement benchmarks
     *
     * @see PDOStatementExcavated::getBenchmarks()
     */
    protected array $statementBenchmarks = [
        'query' => [
            'count' => 0,
            'time' => 0,
        ],
        'fetch' => [
            'count' => 0,
            'time' => 0,
        ],
        'total' => [
            'time' => 0,
        ],
    ];

    /**
     * Constructor.
     *
     * @param string $template SQL query template i. e. "UPDATE `table` %s WHERE `id` = :id"
     */
    public function __construct(string $template, PDOExcavated $pdo, PDOStatement $stmt)
    {
        $this->template = $template;
        $this->pdo = $pdo;
        $this->stmt = $stmt;
        list ($debuggingOptions, $benchmark) = $pdo->getDebuggingEnvironment();
        $this->initDebugging($debuggingOptions, $benchmark);
        if (isset($debuggingOptions['logger'])) {
            $this->skipLogging = false;
        }
    }

    /**
     * Returns query string.
     */
    public function getQueryString(): string
    {
        return $this->template;
    }

    /**
     * Returns statement benchmarks.
     */
    public function getBenchmarks(): array
    {
        return $this->statementBenchmarks;
    }

    /**
     * Returns last executed parsed query.
     */
    public function getLastExecutedQuery(): string
    {
        return $this->lastExecutedQuery;
    }

    /**
     * Binds a column to a PHP variable.
     *
     * @see https://www.php.net/manual/en/pdostatement.bindcolumn.php
     * @codeCoverageIgnore
     */
    public function bindColumn($column, &$var, $type = PDO::PARAM_STR, $maxLength = 0, $driverOptions = null): bool
    {
        return $this->stmt->bindColumn($column, $var, $type, $maxLength, $driverOptions);
    }

    /**
     * Binds a parameter to the specified variable name.
     *
     * @see https://www.php.net/manual/en/pdostatement.bindparam.php
     * @codeCoverageIgnore
     */
    public function bindParam($param, &$var, $type = PDO::PARAM_STR, $maxLength = 0, $driverOptions = null): bool
    {
        $this->values[$param] = [&$var, $type];
        return $this->stmt->bindParam($param, $var, $type);
    }

    /**
     * Binds a value to a parameter.
     *
     * @see https://www.php.net/manual/en/pdostatement.bindvalue.php
     * @codeCoverageIgnore
     */
    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        $this->values[$param] = [$value, $type];
        return $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @see https://www.php.net/manual/en/pdostatement.closecursor.php
     * @codeCoverageIgnore
     */
    public function closeCursor(): bool
    {
        return $this->stmt->closeCursor();
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @see https://www.php.net/manual/en/pdostatement.columncount.php
     * @codeCoverageIgnore
     */
    public function columnCount(): int
    {
        return $this->stmt->columnCount();
    }

    /**
     * Dump an SQL prepared command.
     *
     * @see https://www.php.net/manual/en/pdostatement.debugdumpparams.php
     * @codeCoverageIgnore
     */
    public function debugDumpParams()
    {
        $this->stmt->debugDumpParams();
    }

    /**
     * Fetches the SQLSTATE associated with the last operation on the statement handle.
     *
     * @see https://www.php.net/manual/en/pdostatement.errorcode.php
     * @codeCoverageIgnore
     */
    public function errorCode(): string
    {
        return $this->stmt->errorCode();
    }

    /**
     * Fetches extended error information associated with the last operation on the statement handle.
     *
     * @see https://www.php.net/manual/en/pdostatement.errorinfo.php
     * @codeCoverageIgnore
     */
    public function errorInfo(): array
    {
        return $this->stmt->errorInfo();
    }

    /**
     * Executes a prepared statement.
     *
     * @param ?array $params
     * @see https://www.php.net/manual/en/pdostatement.execute.php
     */
    public function execute($params = null): bool
    {
        if (is_array($params)) {
            foreach ($params as $param => $value) {
                $this->bindValue(is_int($param) ? $param + 1 : $param, $value);
            }
        }
        $this->benchmarks->container['query']['count']++;
        $this->statementBenchmarks['query']['count']++;
        $this->render();
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            $result = $this->stmt->execute();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        $this->benchmarks->container['query']['time'] += $delay;
        $this->statementBenchmarks['query']['time'] += $delay;
        return $this->getResult($delay, $result, $e);
    }

    /**
     * Fetches the next row from a result set.
     *
     * @param ?int $mode
     * @param ?int $cursorOrientation
     * @param ?int $cursorOffset
     * @return mixed
     * @see https://www.php.net/manual/en/pdostatement.fetch.php
     */
    public function fetch($mode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            $result = $this->stmt->fetch($mode, $cursorOrientation, $cursorOffset);
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        return $this->getFetchResult($delay, $result, $e);
    }

    /**
     * Fetches the remaining rows from a result set.
     *
     * @param ?int $mode
     * @param ?mixed $fetch_argument
     * @param ?array $args Constructor arguments
     * @see https://www.php.net/manual/en/pdostatement.fetchall.php
     */
    public function fetchAll($mode = null, $fetch_argument = null, $args = null): array
    {
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            if (null === $mode) {
                $result = $this->stmt->fetchAll();
            } elseif (null === $fetch_argument) {
                $result = $this->stmt->fetchAll($mode);
            } elseif (null === $args) {
                $result = $this->stmt->fetchAll($mode, $fetch_argument);
            } else {
                $result = $this->stmt->fetchAll($mode, $fetch_argument, $args);
            }
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        return $this->getFetchResult($delay, $result, $e);
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param ?int $column Column number
     * @return mixed
     * @see https://www.php.net/manual/en/pdostatement.fetchcolumn.php
     */
    public function fetchColumn($column = 0)
    {
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            $result = $this->stmt->fetchColumn($column);
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        return $this->getFetchResult($delay, $result, $e);
    }

    /**
     * Fetches the next row and returns it as an object.
     *
     * @param ?string $class Class name
     * @param ?array $constructorArgs
     * @see https://www.php.net/manual/en/pdostatement.fetchobject.php
     */
    public function fetchObject($class = null, $constructorArgs = null): object
    {
        $result = null;
        $e = null;
        $timeStamp = microtime(true);
        try {
            if (null === $constructorArgs) {
                $constructorArgs = [];
            }
            $result = $this->stmt->fetchObject($class, $constructorArgs);
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = microtime(true) - $timeStamp;
        return $this->getFetchResult($delay, $result, $e);
    }

    /**
     * Retrieves a statement attribute.
     *
     * @param int $name
     * @return mixed
     * @see https://www.php.net/manual/en/pdostatement.getattribute.php
     * @codeCoverageIgnore
     */
    public function getAttribute($name)
    {
        return $this->stmt->getAttribute($name);
    }

    /**
     * Returns metadata for a column in a result set.
     *
     * @param int $column
     * @return array
     * @see https://www.php.net/manual/en/pdostatement.getcolumnmeta.php
     * @codeCoverageIgnore
     */
    public function getColumnMeta($column): array
    {
        return $this->stmt->getColumnMeta($column);
    }

    /**
     * Advances to the next rowset in a multi-rowset statement handle.
     *
     * @see https://www.php.net/manual/en/pdostatement.nextrowset.php
     * @codeCoverageIgnore
     */
    public function nextRowset(): bool
    {
        return $this->stmt->nextRowset();
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @see https://www.php.net/manual/en/pdostatement.rowcount.php
     * @codeCoverageIgnore
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Sets a statement attribute.
     *
     * @param int $attribute
     * @param mixed $value
     * @return bool
     * @see https://www.php.net/manual/en/pdostatement.setattribute.php
     * @codeCoverageIgnore
     */
    public function setAttribute($attribute, $value): bool
    {
        return $this->stmt->setAttribute($attribute, $value);
    }

    /**
     * Sets the default fetch mode for this statement.
     *
     * @param int $mode
     * @param ?null|string|object $className
     * @param ?array $params
     * @see https://www.php.net/manual/en/pdostatement.setfetchmode.php
     * @codeCoverageIgnore
     */
    public function setFetchMode($mode, $className = null, array $params = []): bool
    {
        if (null === $className) {
            return $this->stmt->setFetchMode($mode);
        } elseif ([] === $params) {
            return $this->stmt->setFetchMode($mode, $className);
        } else {
            return $this->stmt->setFetchMode($mode, $className, $params);
        }
    }

    /**
     * Allows to customize log message scope.
     *
     * @see ExcavatingTrait::after()
     */
    protected function scope(array &$scope): void
    {
    }

    /**
     * Prepares query for logging.
     *
     * @see ExcavatingTrait::after()
     */
    protected function prepareQueryForLogging(string &$query): void
    {
    }

    /**
     * Replaces placeholders, sets {@link self::$lastExecutedQuery} and prepares to log message.
     *
     * @see self::execute()
     */
    protected function render()
    {
        $query = $this->template;
        if (sizeof($this->values) > 0) {
            $marker = uniqid("-") . "-";
            $query = str_replace("?", $marker, $query);
            $search = sprintf("/%s/", preg_quote($marker, "/"));
            foreach ($this->values as $field => $data) {
                $dataType = $data[1] & ~PDO::PARAM_INPUT_OUTPUT;
                switch ($dataType) {
                    case PDO::PARAM_BOOL:
                        $value = is_int($data[0]) ? (int)(bool)$data[0] : $data[0];
                        break;
                    case PDO::PARAM_INT:
                        $value = (int)$data[0];
                        break;
                    default:
                        $value = $this->pdo->quote($data[0], $data[1]);
                        break;
                }
                $query = is_string($field)
                    ? str_replace(
                        ":$field",
                        $value,
                        $query
                    )
                    : preg_replace($search, $value, $query);
            }
            $query = str_replace($marker, "?", $query);
        }
        $this->lastExecutedQuery = $query;
        $this->before([
            'source' => sprintf("%s::execute", __CLASS__),
            'query' => $query,
        ]);
    }

    /**
     * Updates benchmarks, returns result or throws an exception.
     *
     * @param float $delay
     * @param mixed $result
     * @param ?PDOException|null $e
     * @see self::fetch()
     * @see self::fetchAll()
     * @see self::fetchColumn()
     * @see self::fetchObject()
     */
    protected function getFetchResult(float $delay, $result, PDOException $e = null)
    {
        $this->benchmarks->container['fetch']['count']++;
        $this->statementBenchmarks['fetch']['count']++;
        $this->benchmarks->container['fetch']['time'] += $delay;
        $this->statementBenchmarks['fetch']['time'] += $delay;
        $this->benchmarks->container['total']['time'] += $delay;
        $this->statementBenchmarks['total']['time'] += $delay;
        if (null !== $e) {
            // @codeCoverageIgnoreStart
            throw $e;
            // @codeCoverageIgnoreEnd
        } else {
            return $result;
        }
    }
}
