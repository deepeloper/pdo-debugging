<?php

/**
 * PDO Debugging & Benchmarking Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\PDO;

/**
 * Benchmarks container class.
 *
 * Used to store common benchmarks for {@see PDOExcavated} and {@see PDOStatementExcavated}.
 */
class BenchmarksContainer
{
    /**
     * Benchmarks.
     */
    public array $container = [
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
        'total' => [
            'time' => 0,
        ],
    ];
}
