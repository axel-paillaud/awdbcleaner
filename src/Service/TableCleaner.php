<?php
/**
 * @author    Axelweb <contact@axelweb.fr>
 * @copyright 2026 Axelweb
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Axelweb
 */
declare(strict_types=1);

namespace Axelweb\AwDbCleaner\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Truncates PrestaShop tables and reads their row count and size.
 *
 * Shared by the CLI command and the back office page. Uses the legacy Db
 * class on purpose: it behaves the same on PrestaShop 8 and 9.
 * Table names are given without the database prefix.
 */
class TableCleaner
{
    /**
     * Statistics tables cleaned by default.
     */
    public const DEFAULT_TABLES = [
        'connections',
        'connections_source',
        'guest',
        'page_viewed',
        'pagenotfound',
    ];

    public function getPrefix(): string
    {
        return _DB_PREFIX_;
    }

    /**
     * Rows and size of each table, in the order given.
     *
     * The size comes from information_schema (approximate, may lag behind
     * on MySQL 8). The row count is exact.
     *
     * @param string[] $tables
     *
     * @return array<string, array{table:string, exists:bool, rows:int, size:int}>
     */
    public function getStats(array $tables): array
    {
        $sizes = $this->getSizes($tables);
        $stats = [];

        foreach ($tables as $table) {
            $exists = array_key_exists($table, $sizes);
            $stats[$table] = [
                'table' => $this->getPrefix() . $table,
                'exists' => $exists,
                'rows' => $exists ? $this->countRows($table) : 0,
                'size' => $sizes[$table] ?? 0,
            ];
        }

        return $stats;
    }

    /**
     * Tables of the list that do not exist in the database.
     *
     * @param string[] $tables
     *
     * @return string[]
     */
    public function getMissingTables(array $tables): array
    {
        return array_values(array_diff($tables, array_keys($this->getSizes($tables))));
    }

    /**
     * @return int number of rows removed
     *
     * @throws \RuntimeException
     */
    public function truncate(string $table): int
    {
        $rows = $this->countRows($table);
        $db = \Db::getInstance();

        if (!$db->execute('TRUNCATE TABLE `' . $this->getPrefix() . $this->checkName($table) . '`')) {
            throw new \RuntimeException(sprintf('Unable to truncate %s%s: %s', $this->getPrefix(), $table, $db->getMsgError()));
        }

        return $rows;
    }

    public function countRows(string $table): int
    {
        return (int) \Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . $this->getPrefix() . $this->checkName($table) . '`'
        );
    }

    /**
     * Data + index size in bytes of the existing tables, indexed by name without prefix.
     *
     * @param string[] $tables
     *
     * @return array<string, int>
     */
    private function getSizes(array $tables): array
    {
        if (empty($tables)) {
            return [];
        }

        $prefix = $this->getPrefix();
        $names = array_map(function (string $table) use ($prefix): string {
            return "'" . pSQL($prefix . $this->checkName($table)) . "'";
        }, $tables);

        $result = \Db::getInstance()->executeS(
            'SELECT TABLE_NAME AS name, DATA_LENGTH + INDEX_LENGTH AS size
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (' . implode(',', $names) . ')'
        );

        $sizes = [];
        foreach ($result ?: [] as $row) {
            $sizes[substr($row['name'], strlen($prefix))] = (int) $row['size'];
        }

        return $sizes;
    }

    /**
     * Table names come from the CLI: only allow plain identifiers.
     *
     * @throws \InvalidArgumentException
     */
    private function checkName(string $table): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException(sprintf('Invalid table name "%s".', $table));
        }

        return $table;
    }
}
