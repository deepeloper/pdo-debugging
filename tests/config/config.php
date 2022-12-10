<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\PDO;

use PDO;

use function error_reporting;
use function ini_set;

error_reporting(E_ALL);
ini_set("display_errors", 1);

return [
    'db' => [
        'dsn' => "mysql:host=127.0.0.1;dbname=%s;charset=utf8mb4",
        'username' => "root",
        'password' => "",
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 1,
            PDOExcavated::ATTR_DEBUG => [
                'format' => [
                    'call'  => "[ %DSN%;user=%USER_NAME% ] [ CALL  ] [ %SOURCE%(%ARGS%) ]",
                    'query' => "[ %DSN%;user=%USER_NAME% ] [ QUERY ] [ #%COUNT% ] [ %SOURCE% ] [ %QUERY% ]",
                ],
            ],
        ],
        'name' => "pdo_debugging_tests",
        'commands' => [
            'create' => "mysql -u %s < \"tests/config/structure.sql\"",
            'drop' => "mysqladmin -u %s -f drop %s",
        ],
    ],
];
