<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\PDO;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_replace_recursive;
use function is_array;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function passthru;
use function sprintf;
use function var_export;

/**
 * Config and database related functionality.
 */
abstract class TestCaseConfigAndDatabase extends TestCase
{
    /**
     * Config
     *
     * @var array
     * @see self::setUpBeforeClass()
     */
    protected static $config;

    /**
     * Log
     *
     * @var array
     * @see Logger::log()
     */
    protected static $log = [];

    /**
     * This method is called before the first test of this test class is run.
     *
     * Loads config ("tests/config/config.php") and create database ("tests/config/structure.sql")
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$config = require("tests/config/config.php");
        $command = sprintf(
            static::$config['db']['commands']['create'],
            static::$config['db']['username']
        );
        self::executeCommand($command);
    }

    /**
     * This method is called after the last test of this test class is run.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        if (isset(self::$config['db']['commands']['drop'])) {
            $command = sprintf(
                self::$config['db']['commands']['drop'],
                self::$config['db']['username'],
                self::$config['db']['name']
            );
            self::executeCommand($command);
        }

        parent::tearDownAfterClass();
    }

    /**
     * Executes command.
     *
     * @param string $command
     * @return void
     */
    protected static function executeCommand(string $command)
    {
        ob_start();
        passthru($command, $result);
        $output = ob_get_contents();
        ob_end_clean();
        if (0 !== $result) {
            throw new RuntimeException(sprintf(
                "Cannot execute '%s' command, output: %s",
                $command,
                var_export($output, true)
            ));
        }
    }

    /**
     * Connects to database.
     *
     * @param array $debuggingOptions
     * @param string $pdoClassName
     * @return PDOExcavated
     */
    protected static function connectDatabase(
        array $debuggingOptions = null,
        string $pdoClassName = "\\deepeloper\\PDO\\PDOExcavated"
    ): PDOExcavated {
        $options = static::$config['db']['options'];
        if (is_array($debuggingOptions)) {
            $options[PDOExcavated::ATTR_DEBUG] = array_replace_recursive(
                $options[PDOExcavated::ATTR_DEBUG],
                $debuggingOptions
            );
        }
        return new $pdoClassName(
            sprintf(static::$config['db']['dsn'], static::$config['db']['name']),
            static::$config['db']['username'],
            static::$config['db']['password'],
            $options
        );
    }
}
