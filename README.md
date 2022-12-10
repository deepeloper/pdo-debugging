# PDO Debugging & Benchmarking Tools
[![GitHub license](https://img.shields.io/github/license/deepeloper/pdo-debugging.svg)](https://github.com/deepeloper/pdo-debugging/blob/master/LICENSE)

## Compatibility
[![PHP ^7.4](https://img.shields.io/badge/PHP-%5E7.4-%237A86B8)]() &oline;&oline;
[![2.3.0](https://img.shields.io/badge/Release-2.3.0-%233fb950)](https://github.com/deepeloper/pdo-debugging/releases/tag/2.3.0)

[![PHP >=7.3 < 7.4](https://img.shields.io/badge/PHP-%3E%3D7.3%20%3C7.4-%237A86B8)]() &oline;&oline;
[![2.2.0](https://img.shields.io/badge/Release-2.2.0-%233fb950)](https://github.com/deepeloper/pdo-debugging/releases/tag/2.2.0)

[![PHP >=7.2.5 < 7.3](https://img.shields.io/badge/PHP-%3E%3D7.2.5%20%3C7.3-%237A86B8)]() &oline;&oline;
[![2.1.0](https://img.shields.io/badge/Release-2.1.0-%233fb950)](https://github.com/deepeloper/pdo-debugging/releases/tag/2.1.0)

[![PHP ^7.0](https://img.shields.io/badge/PHP-%5E7.0-%237A86B8)]() &oline;&oline;
[![2.0.0](https://img.shields.io/badge/Release-2.0.0-%233fb950)](https://github.com/deepeloper/pdo-debugging/releases/tag/2.0.0)

[![PHP ^5.6](https://img.shields.io/badge/PHP-%5E5.6-%237A86B8)]() &oline;&oline;
[![1.2.0](https://img.shields.io/badge/Release-1.2.0-%233fb950)](https://github.com/deepeloper/pdo-debugging/releases/tag/1.2.0)

[![PHP ^5.5](https://img.shields.io/badge/PHP-%5E5.5-%237A86B8)]() &oline;&oline;
[![1.1.0](https://img.shields.io/badge/Release-1.1.0-%233fb950)](https://github.com/deepeloper/pdo-debugging/releases/tag/1.1.0)

[![PHP ^5.4](https://img.shields.io/badge/PHP-%5E5.4-%237A86B8)]() &oline;&oline;
[![1.0.0](https://img.shields.io/badge/Release-1.0.0-%233fb950)](https://github.com/deepeloper/pdo-debugging/releases/tag/1.0.0)

## Installation
`composer require deepeloper/pdo-debugging "^2.3"`   

or add to "composer.json" section
```json
"require": {
    "deepeloper/pdo-debugging": "^2.3"
}
```

## Benchmarking
Allows to collect following benchmarks per PDO connection: 
* Global:
* * queries count/time;
* * preparation count/time;
* * fetching count/time;
* * commits count/time;
* * roll back count/time;
* * sum of time calling:
* * * PDO::__construct();
* * * PDO::beginTransaction();
* * * PDO::commit();
* * * PDO::exec();
* * * PDO::prepare();
* * * PDO::query();
* * * PDO::rollBack();
* * * PDOStatement::execute(); 
* * * PDOStatement::fetch*();
* Statement:
* * queries count/time;
* * fetching count/time;
* * sum of time calling:
* * * PDOStatement::execute();
* * * PDOStatement::fetch*().

### Benchmarking usage
```php
use deepeloper\PDO\PDOExcavated;

$pdo = new PDOExcavated(
    $dsn,
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDOExcavated::ATTR_DEBUG => [],
    ]
);

$pdo->beginTransaction();
$stmt = $pdo->query("SELECT 1");
$stmt->fetch();
$pdo->commit();

$pdo->beginTransaction();
$stmt = $pdo->prepare("SELECT ?");
$stmt->bindValue(1, 100500, PDO::PARAM_INT);
$stmt->execute();
$stmt->fetch();
$pdo->rollBack();

echo "Global benchmarks: ";
print_r($pdo->getBenchmarks());
echo "\nStatement benchmarks: ";print_r($stmt->getBenchmarks());
echo sprintf("\nLast query: %s\n", $stmt->getLastExecutedQuery());
```
will output:
```
Global benchmarks: Array
(
    [query] => Array
        (
            [count] => 2
            [time] => ...
        )
    [prepare] => Array
        (
            [count] => 1
            [time] => ...
        )
    [fetch] => Array
        (
            [count] => 2
            [time] => ...
        )
    [commit] => Array
        (
            [count] => 1
            [time] => ...
        )
    [rollBack] => Array
        (
            [count] => 1
            [time] => ...
        )
    [total] => Array
        (
            [time] => ...
        )
)

Statement benchmarks: Array
(
    [query] => Array
        (
            [count] => 1
            [time] => ...
        )
    [fetch] => Array
        (
            [count] => 1
            [time] => ...
        )
    [total] => Array
        (
            [time] => ...
        )
)

Last query: SELECT 100500
```

## Debugging
Allows to process next methods calls:
* PDO::__construct();
* PDO::beginTransaction();
* PDO::commit();
* PDO::exec();
* PDO::prepare();
* PDO::query();
* PDO::rollBack();
* PDOStatement::execute(). 

Allows to log queries with replaced placeholders from next methods:
* PDO::exec();
* PDO::query();
* PDOStatement::execute(). 

### Debugging usage
```php
use deepeloper\PDO\LoggerInterface;
use deepeloper\PDO\PDOExcavated;

class Logger implements LoggerInterface
{
    /**
     * Logs message.
     *
     * @param string $message
     * @return void
     */
    public function log($message, array $scope)
    {
        echo "$message\n";
    }
}

$dsn = "...";
$username = "...";
$password = "...";

$logger = new Logger();
$pdo = new PDOExcavated(
    $dsn,
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDOExcavated::ATTR_DEBUG => [
            'logger' => $logger,
            /**
             * Defaults.
             * 
             * @see deepeloper\PDO\ExcavatingTrait::$debuggingOptions
             */
            'format' => [
                'timeStamp' => "Y-m-d H:i:s",
                'count' => "%03d",
                'precision' => "%.05f",
                'call'  => "[ %TIME_STAMP% ] [ %DSN%;user=%USER_NAME% ] [ %EXECUTION_TIME% ] [ CALL  ] [ %SOURCE%(%ARGS%) ]",
                'query' => "[ %TIME_STAMP% ] [ %DSN%;user=%USER_NAME% ] [ %EXECUTION_TIME% ] [ QUERY ] [ #%COUNT% ] [ %SOURCE% ] [ %QUERY% ]",
            ],
            'sources' => [
            ],
        ],
    ]
);

$pdo->exec("UPDATE `test` SET `foo` = 0");

$stmt = $pdo->query("SELECT 1");
$stmt->fetch();

$stmt = $pdo->prepare("SELECT ?");
$stmt->bindValue(1, 100500, PDO::PARAM_INT);
$stmt->execute();
```
will output:
```
[ ****-**-** **:**:** ] [ $dsn;user=$username ] [ *.***** ] [ CALL  ] [ PDOExcavated::__construct(["$dsn","$username"]) ]
[ ****-**-** **:**:** ] [ $dsn;user=$username ] [ *.***** ] [ QUERY ] [ #001 ] [ PDOExcavated::exec ] [ UPDATE `test` SET `foo` = 0 ]
[ ****-**-** **:**:** ] [ $dsn;user=$username ] [ *.***** ] [ QUERY ] [ #002 ] [ PDOExcavated::query ] [ SELECT 1 ]
[ ****-**-** **:**:** ] [ $dsn;user=$username ] [ *.***** ] [ CALL  ] [ PDOExcavated::prepare(["SELECT ?"]) ]
[ ****-**-** **:**:** ] [ $dsn;user=$username ] [ *.***** ] [ QUERY ] [ #003 ] [ PDOStatementExcavated::execute ] [ SELECT 100500 ]
```
If you wish to log only queries, pass following 'sources' section:
```php
            'sources' => [
                "/^PDOExcavated::(?:exec|query)/",
                "/^PDOStatementExcavated::execute/",
            ],
```
If source started with "/" it will be processed like regular expression, otherwise will be compared like string.

### Customizing debugging
You can customize log message scope and process result message before logging:
```php
use deepeloper\PDO\LoggerInterface;
use deepeloper\PDO\PDOExcavated;
use deepeloper\PDO\PDOStatementExcavated;

class class PDOExcavatedExtended extends PDOExcavated
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
     * @see ExcavatingTrait::after()
     */
    protected function prepareQueryForLogging(&$query)
    {
        // Modify query here.
    }

    /**
     * Method used to replace PDOStatementExcavated by possible child.
     *
     * @param string $template
     * @param PDOStatementExcavated $stmt
     * @return PDOStatementExcavatedExtended
     * @see self::getResultStatement()
     */
    protected function getStatement($template, PDOStatement $stmt)
    {
        return new PDOStatementExcavatedExtended($template, $this, $stmt);
    }
}

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
     * @see ExcavatingTrait::after()
     */
    protected function prepareQueryForLogging(&$query)
    {
        // Modify query here.
    }
}

class Logger implements LoggerInterface
{
    /**
     * Logs message.
     *
     * @param string $message
     * @param array $scope
     * @return void
     */
    public function log($message, array $scope)
    {
        echo "$message\n";
    }
}

$dsn = "...";
$username = "...";
$password = "...";

$logger = new Logger();
$pdo = new PDOExcavatedExtended(
    $dsn,
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDOExcavated::ATTR_DEBUG => [
            'logger' => $logger,
            'format' => [
                'call'  => "%FOO%",
                'query' => "%FOO%",
            ],
        ],
    ]
);

$pdo->exec("UPDATE `test` SET `bar` = ''");
$pdo->query("SELECT 1");
$stmt = $pdo->prepare("SELECT ?, ?");
$stmt->bindValue(1, 100500, PDO::PARAM_INT);
$stmt->bindValue(2, "blah");
$stmt->execute();
```
will output:
```
deepeloper\PDO\PDOExcavatedExtended::scope
deepeloper\PDO\PDOExcavatedExtended::scope
deepeloper\PDO\PDOExcavatedExtended::scope
deepeloper\PDO\PDOExcavatedExtended::scope
deepeloper\PDO\PDOStatementExcavatedExtended::scope
```
