<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author <a href="https://github.com/deepeloper" target="_blank">deepeloper</a>
 * @license https://opensource.org/licenses/mit-license.php
 */

namespace deepeloper\PDO;

use DateTime;
use PDOException;

/**
 * PDO debugging trait.
 *
 * @see PDOExcavated
 * @see PDOStatementExcavated
 */
trait ExcavatingTrait
{
    /**
     * Flag specifies to skip logging
     *
     * @var bool
     */
    protected $skipLogging = true;

    /**
     * Default debugging options
     *
     * @var array
     */
    protected $debuggingOptions = [
        'logger' => null,
        'format' => [
            'timeStamp' => "Y-m-d H:i:s.u",
            'precision' => "%.05f",
            'count' => "%03d",
            // @codingStandardsIgnoreStart
            'call'  => "[ %TIME_STAMP% ] [ %EXECUTION_TIME% ] [ CALL  ] [ %DSN%;user=%USER_NAME% ] [ %SOURCE%(%ARGS%) ]",
            'query' => "[ %TIME_STAMP% ] [ %EXECUTION_TIME% ] [ QUERY ] [ %DSN%;user=%USER_NAME% ] [ #%COUNT% ] [ %SOURCE% ] [ %QUERY% ]",
            // @codingStandardsIgnoreEnd
        ],
        'sources' => [
        ],
    ];

    /**
     * BenchmarkContainer object
     *
     * @var BenchmarksContainer
     */
    protected $benchmarks;

    /**
     * Array containing useful info for logging.
     *
     * @var array[]
     * @link self::before()
     * @link self::after()
     */
    protected $stack = [];

    /**
     * Allows to customize log message scope.
     *
     * @param array &$scope
     * @return void
     * @link self::after()
     */
    abstract protected function scope(array &$scope);

    /**
     * Prepares query for logging.
     *
     * @param string &$query
     * @return void
     * @link self::after()
     */
    abstract protected function prepareQueryForLogging(&$query);


    /**
     * Initializes debugging.
     *
     * @param array $options
     * @param BenchmarksContainer|null $benchmarkContainer
     * @return void
     */
    public function initDebugging(array $options = [], BenchmarksContainer $benchmarkContainer = null)
    {
        $this->debuggingOptions = \array_replace_recursive($this->debuggingOptions, $options);
        $this->benchmarks = null !== $benchmarkContainer ? $benchmarkContainer : new BenchmarksContainer();
    }

    /**
     * Returns array containing debugging options as first element and benchmark container as second.
     *
     * @return array
     * @see PDOStatementExcavated::__construct()
     */
    public function getDebuggingEnvironment()
    {
        return [$this->debuggingOptions, $this->benchmarks];
    }

    /**
     * Returns array containing benchmarks.
     *
     * @return array
     */
    public function getBenchmarks()
    {
        return $this->benchmarks->container;
    }

    /**
     * Pushes useful data to internal stack before PDO/PDOStatement method execution.
     *
     * @param array $args
     * @param array $excludeArgIndexes
     * @return void
     */
    protected function before(array $args = null, array $excludeArgIndexes = null)
    {
        if ($this->skipLogging) {
            return;
        }
        if (null === $args) {
            $trace = \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            $source = \sprintf("%s::%s", $trace[1]['class'], $trace[1]['function']);
            $args = $trace[1]['args'];
        } else {
            $source = $args['source'];
            unset($args['source']);
        }
        if (null !== $excludeArgIndexes) {
            $args = \array_diff_key($args, array_combine($excludeArgIndexes, $excludeArgIndexes));
        }
        \preg_match("/\w+::.+$/", $source, $matches);
        $source = $matches[0];
        $dateTime = new DateTime();
        $call = [
            'timeStamp' => $dateTime->format($this->debuggingOptions['format']['timeStamp']),
            'microTime' => \microtime(true),
            'source' => $source,
        ];
        if (empty($args['query'])) {
            $call['args'] = $args;
        } else {
            $call['query'] = $args['query'];
        }
        $this->stack[] = $call;
    }

    /**
     * Pops useful data from internal stack after PDO/PDOStatement method execution and logs info about which method
     * called or about query.
     *
     * @return void
     * @todo PHP >=7: Add comma after last element of array, no problem with code coverage.
     */
    protected function after()
    {
        if ($this->skipLogging) {
            return;
        }
        $call = \array_pop($this->stack);
        $scope = [
            'TIME_STAMP' => $call['timeStamp'],
            'DSN' => $this->debuggingOptions['dsn'],
            'USER_NAME' => $this->debuggingOptions['username'],
            'EXECUTION_TIME' => \sprintf(
                $this->debuggingOptions['format']['precision'],
                \microtime(true) - $call['microTime']
            ),
            'SOURCE' => $call['source'],
        ];
        if (empty($call['query'])) {
            $scope['ARGS'] = \json_encode($call['args'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $this->prepareQueryForLogging($call['query']);
            $scope += [
                'QUERY' => $call['query'],
                'COUNT' => $this->benchmarks->container['query']['count'],
            ];
        }
        $log = 0 === \sizeof($this->debuggingOptions['sources']);
        if (!$log) {
            foreach ($this->debuggingOptions['sources'] as $pattern) {
                $log = substr($pattern, 0, 1) === "/"
                    ? \preg_match($pattern, $call['source'])
                    : $pattern === $call['source'];
                if ($log) {
                    break;
                }
            }
        }
        $format = empty($scope['QUERY']) ? "call" : "query";
        if ($log) {
            $this->scope($scope);
            if (isset($scope['COUNT'])) {
                $scope['COUNT'] = \sprintf($this->debuggingOptions['format']['count'], $scope['COUNT']);
            }
            $this->debuggingOptions['logger']->log(
                $this->renderLogMessage($this->debuggingOptions['format'][$format], $scope),
                $scope
            );
        }
    }

    /**
     * Updates total time, returns result or throws an exception.
     *
     * @param float $delay
     * @param $result
     * @param PDOException|null $e
     * @return mixed
     */
    protected function getResult($delay, $result, PDOException $e = null)
    {
        $this->benchmarks->container['total']['time'] += $delay;
        if (isset($this->statementBenchmarks)) {
            $this->statementBenchmarks['total']['time'] += $delay;
        }
        $this->after();
        if (isset($result)) {
            return $result;
        } else {
            throw $e;
        }
    }

    /**
     * Renders log message.
     *
     * @param string $template
     * @param array $scope
     * @return string
     */
    protected function renderLogMessage($template, array $scope)
    {
        foreach ($scope as $name => $value) {
            $template = \str_replace("%$name%", $value, $template);
        }
        return $template;
    }
}
