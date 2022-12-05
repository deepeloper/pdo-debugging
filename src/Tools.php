<?php

/**
 * PDO related library.
 *
 * @author <a href="https://github.com/deepeloper" target="_blank">deepeloper</a>
 * @license https://opensource.org/licenses/mit-license.php
 */

namespace deepeloper\PDO;

use PDO;
use PDOStatement;

/**
 * PDO related library.
 *
 * @static
 */
class Tools
{
    /**
     * Prepares modifying (INSERT/UPDATE) statement, binds values with raw values support.
     *
     * Useful when you don't know, which fields will be inserted/updated and static SQL-template couldn't be used.
     *
     * Example:
     * ```php
     * use deepeloper\PDO\Tools;
     *
     * // ...
     *
     * $template = "UPDATE `table` %s WHERE `id` = :id";
     * $stmt = Tools::prepareModifyingStatement(
     *     $pdo,
     *     $template,
     *     ['field' => "value"], // once time this fields, other time other fields
     *     ['field' => PDO::PARAM_STR],
     *     ['time_updated' => "NOW()"]
     * );
     * $stmt->bindValue('id', 100500, PDO::PARAM_INT);
     * $stmt->execute();
     * ```
     * SQL query template that will be generated for PDO::prepare() inside method:
     * ```sql
     * UPDATE `table` SET `field` = :field, `time_updated` = NOW() WHERE `id` = :id
     * ```
     *
     * @param PDO $pdo PDO instance
     * @param string $template SQL query template i. e. "sql UPDATE `table` %s WHERE `id` = :id"
     * @param array $record Record
     * @param array $types Values data types
     * @param array $rawValues Raw values
     * @return PDOStatement
     */
    public static function prepareModifyingStatement(
        $pdo,
        $template,
        array $record,
        array $types = [],
        array $rawValues = []
    ) {
        $fields = \array_map(
            function ($field) {
                return \sprintf("`%s`", $field);
            },
            array_merge(array_keys($record), array_keys($rawValues))
        );
        $placeholders = \array_merge(
            \array_map(
                function ($field) {
                    return \sprintf(":%s", $field);
                },
                \array_keys($record)
            ),
            \array_values($rawValues)
        );
        if (\preg_match("/^\s?insert\s+/i", $template)) {
            $query = \sprintf(
                $template,
                \sprintf(
                    "(%s) VALUES (%s)",
                    \implode(", ", $fields),
                    \implode(", ", $placeholders)
                )
            );
        } else {
            $set = [];
            foreach ($fields as $index => $field) {
                $set[] = \sprintf("%s = %s", $field, $placeholders[$index]);
            }
            $query = \sprintf(
                $template,
                \sprintf("SET %s", implode(", ", $set))
            );
        }

        $stmt = $pdo->prepare($query);
        foreach ($record as $placeholder => $value) {
            isset($types[$placeholder])
                ? $stmt->bindValue($placeholder, $value, $types[$placeholder])
                : $stmt->bindValue($placeholder, $value);
        }

        return $stmt;
    }
}