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
use PDOStatement;

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
     *
     * @var string
     */
    protected $template;

    /**
     * PDOExcavated object
     *
     * @var PDOExcavated
     */
    protected $pdo;

    /**
     * "Parent" statement object
     *
     * @var PDOStatement
     */
    protected $stmt;

    /**
     * Binded values
     *
     * @var array
     */
    protected $values = [];

    /**
     * Last executed parsed query
     *
     * @var string
     */
    protected $lastExecutedQuery;

    /**
     * Statement benchmarks
     *
     * @var int[][]
     * @link self::getBenchmarks()
     */
    protected $statementBenchmarks = [
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
     * @param PDOExcavated $pdo
     * @param PDOStatement $stmt
     */
    public function __construct($template, PDOExcavated $pdo, PDOStatement $stmt)
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
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->template;
    }

    /**
     * Returns statement benchmarks.
     *
     * @return array
     */
    public function getBenchmarks()
    {
        return $this->statementBenchmarks;
    }

    /**
     * Returns last executed parsed query.
     *
     * @return string
     */
    public function getLastExecutedQuery()
    {
        return $this->lastExecutedQuery;
    }

    /**
     * Binds a column to a PHP variable.
     *
     * @param $column
     * @param $var
     * @param $type
     * @param $maxLength
     * @param $driverOptions
     * @return bool
     * @see https://www.php.net/manual/en/pdostatement.bindcolumn.php
     * @codeCoverageIgnore
     */
    public function bindColumn($column, &$var, $type = PDO::PARAM_STR, $maxLength = 0, $driverOptions = null)
    {
        return $this->stmt->bindColumn($column, $var, $type, $maxLength, $driverOptions);
    }

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param string|int $param
     * @param mixed &$var
     * @param int $type
     * @param int $maxLength
     * @param mixed $driverOptions
     * @return bool
     * @see https://www.php.net/manual/en/pdostatement.bindparam.php
     * @codeCoverageIgnore
     */
    public function bindParam($param, &$var, $type = PDO::PARAM_STR, $maxLength = 0, $driverOptions = null)
    {
        $this->values[$param] = [&$var, $type];
        return $this->stmt->bindParam($param, $var, $type);
    }

    /**
     * Binds a value to a parameter.
     *
     * @param int|string $param
     * @param mixed $value
     * @param int $type
     * @return bool
     * @see https://www.php.net/manual/en/pdostatement.bindvalue.php
     * @codeCoverageIgnore
     */
    public function bindValue($param, $value, $type = PDO::PARAM_STR)
    {
        $this->values[$param] = [$value, $type];
        return $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return bool
     * @see https://www.php.net/manual/en/pdostatement.closecursor.php
     * @codeCoverageIgnore
     */
    public function closeCursor()
    {
        return $this->stmt->closeCursor();
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return int
     * @see https://www.php.net/manual/en/pdostatement.columncount.php
     * @codeCoverageIgnore
     */
    public function columnCount()
    {
        return $this->stmt->columnCount();
    }

    /**
     * Dump an SQL prepared command.
     *
     * @return void
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
     * @return string
     * @see https://www.php.net/manual/en/pdostatement.errorcode.php
     * @codeCoverageIgnore
     */
    public function errorCode()
    {
        return $this->stmt->errorCode();
    }

    /**
     * Fetches extended error information associated with the last operation on the statement handle.
     *
     * @return array
     * @see https://www.php.net/manual/en/pdostatement.errorinfo.php
     * @codeCoverageIgnore
     */
    public function errorInfo()
    {
        return $this->stmt->errorInfo();
    }

    /**
     * Executes a prepared statement.
     *
     * @param array $inputParameters
     * @return bool
     * @see https://www.php.net/manual/en/pdostatement.execute.php
     */
    public function execute($inputParameters = null)
    {
        if (\is_array($inputParameters)) {
            foreach ($inputParameters as $parameter => $value) {
                $this->bindValue(\is_int($parameter) ? $parameter + 1 : $parameter, $value);
            }
        }
        $this->benchmarks->container['query']['count']++;
        $this->statementBenchmarks['query']['count']++;
        $this->render();
        $result = null;
        $e = null;
        $timeStamp = \microtime(true);
        try {
            $result = $this->stmt->execute();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = \microtime(true) - $timeStamp;
        $this->benchmarks->container['query']['time'] += $delay;
        $this->statementBenchmarks['query']['time'] += $delay;
        return $this->getResult($delay, $result, $e);
    }

    /**
     * Fetches the next row from a result set.
     *
     * @param int $fetchStyle
     * @param int $cursorOrientation
     * @param int $cursorOffset
     * @return mixed
     * @see https://www.php.net/manual/en/pdostatement.fetch.php
     */
    public function fetch($fetchStyle = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        $result = null;
        $e = null;
        $timeStamp = \microtime(true);
        try {
            $result = $this->stmt->fetch($fetchStyle, $cursorOrientation, $cursorOffset);
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = \microtime(true) - $timeStamp;
        return $this->getFetchResult($delay, $result, $e);
    }

    /**
     * Fetches the remaining rows from a result set.
     *
     * @param int $fetchStyle
     * @param mixed $fetchArgument
     * @param array $constructorArgs
     * @return array
     * @see https://www.php.net/manual/en/pdostatement.fetchall.php
     */
    public function fetchAll($fetchStyle = null, $fetchArgument = null,  $constructorArgs = null)
    {
        $result = null;
        $e = null;
        $timeStamp = \microtime(true);
        try {
            if (null === $fetchStyle) {
                $result = $this->stmt->fetchAll();
            } elseif (null === $fetchArgument) {
                $result = $this->stmt->fetchAll($fetchStyle);
            } elseif (null === $constructorArgs) {
                $result = $this->stmt->fetchAll($fetchStyle, $fetchArgument);
            } else {
                $result = $this->stmt->fetchAll($fetchStyle, $fetchArgument, $constructorArgs);
            }
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = \microtime(true) - $timeStamp;
        return $this->getFetchResult($delay, $result, $e);
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param int $columnNumber
     * @return mixed
     * @see https://www.php.net/manual/en/pdostatement.fetchcolumn.php
     */
    public function fetchColumn($columnNumber = 0)
    {
        $result = null;
        $e = null;
        $timeStamp = \microtime(true);
        try {
            $result = $this->stmt->fetchColumn($columnNumber);
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = \microtime(true) - $timeStamp;
        return $this->getFetchResult($delay, $result, $e);
    }

    /**
     * Fetches the next row and returns it as an object.
     *
     * @param string $className
     * @param array $constructorArgs
     * @return array
     * @see https://www.php.net/manual/en/pdostatement.fetchobject.php
     */
    public function fetchObject($className = null, $constructorArgs = null)
    {
        $result = null;
        $e = null;
        $timeStamp = \microtime(true);
        try {
            if (null === $constructorArgs) {
                $constructorArgs = [];
            }
            $result = $this->stmt->fetchObject($className, $constructorArgs);
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            // @codeCoverageIgnoreEnd
        }
        $delay = \microtime(true) - $timeStamp;
        return $this->getFetchResult($delay, $result, $e);
    }

    /**
     * Retrieves a statement attribute.
     *
     * @param int $attribute
     * @return mixed
     * @see https://www.php.net/manual/en/pdostatement.getattribute.php
     * @codeCoverageIgnore
     */
    public function getAttribute($attribute)
    {
        return $this->stmt->getAttribute($attribute);
    }

    /**
     * Returns metadata for a column in a result set.
     *
     * @param int $column
     * @return array
     * @see https://www.php.net/manual/en/pdostatement.getcolumnmeta.php
     * @codeCoverageIgnore
     */
    public function getColumnMeta($column)
    {
        return $this->stmt->getColumnMeta($column);
    }

    /**
     * Advances to the next rowset in a multi-rowset statement handle.
     *
     * @return bool
     * @see https://www.php.net/manual/en/pdostatement.nextrowset.php
     * @codeCoverageIgnore
     */
    public function nextRowset()
    {
        return $this->stmt->nextRowset();
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @return int
     * @see https://www.php.net/manual/en/pdostatement.rowcount.php
     * @codeCoverageIgnore
     */
    public function rowCount()
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
    public function setAttribute($attribute, $value)
    {
        return $this->stmt->setAttribute($attribute, $value);
    }

    /**
     * Sets the default fetch mode for this statement.
     *
     * @param int $mode
     * @param mixed $params
     * @return bool
     * @see https://www.php.net/manual/en/pdostatement.setfetchmode.php
     * @codeCoverageIgnore
     */
    public function setFetchMode($mode, $params = null)
    {
        return $this->stmt->setFetchMode($mode);
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
     * Prepares query for logging.
     *
     * @param string &$query
     * @return void
     * @see ExcavatingTrait::after()
     */
    protected function prepareQueryForLogging(&$query)
    {
    }

    /**
     * Replaces placeholders, sets {@link self::$lastExecutedQuery} and prepares to log message.
     *
     * @return void
     * @see self::execute()
     */
    protected function render()
    {
        $query = $this->template;
        if (\sizeof($this->values) > 0) {
            $marker = \uniqid("-") . "-";
            $query = \str_replace("?", $marker, $query);
            $search = \sprintf("/%s/", preg_quote($marker, "/"));
            foreach ($this->values as $field => $data) {
                $dataType = $data[1] & ~PDO::PARAM_INPUT_OUTPUT;
                switch ($dataType) {
                    case PDO::PARAM_BOOL:
                        $value = \is_int($data[0]) ? (int)(bool)$data[0] : $data[0];
                        break;
                    case PDO::PARAM_INT:
                        $value = (int)$data[0];
                        break;
                    default:
                        $value = $this->pdo->quote($data[0], $data[1]);
                        break;
                }
                $query = \is_string($field)
                    ? \str_replace(
                        ":$field",
                        $value,
                        $query
                    )
                    : preg_replace($search, $value, $query);
            }
            $query = \str_replace($marker, "?", $query);
        }
        $this->lastExecutedQuery = $query;
        $this->before([
            'source' => \sprintf("%s::execute", __CLASS__),
            'query' => $query,
        ]);
    }

    /**
     * Updates benchmarks, returns result or throws an exception.
     *
     * @param float $delay
     * @param $result
     * @param PDOException|null $e
     * @return mixed
     * @see self::fetch()
     * @see self::fetchAll()
     * @see self::fetchColumn()
     * @see self::fetchObject()
     */
    protected function getFetchResult($delay, $result, PDOException $e = null)
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
