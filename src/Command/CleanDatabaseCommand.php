<?php
/**
 * @author    Axelweb <contact@axelweb.fr>
 * @copyright 2026 Axelweb
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Axelweb
 */
declare(strict_types=1);

namespace Axelweb\AwDbCleaner\Command;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanDatabaseCommand extends Command
{
    protected static $defaultName = 'prestashop:awdbcleaner:clean';

    private const DEFAULT_TABLES = [
        'connections',
        'connections_source',
        'guest',
        'page_viewed',
        'pagenotfound',
    ];

    protected function configure(): void
    {
        $this
            ->setDescription('Truncate PrestaShop statistics tables (connections, guests, page views, 404 pages).')
            ->addOption('tables', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of tables to truncate (without prefix). Default: ' . implode(',', self::DEFAULT_TABLES))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show which tables would be truncated without executing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tables = $input->getOption('tables')
            ? array_map('trim', explode(',', $input->getOption('tables')))
            : self::DEFAULT_TABLES;

        $dryRun = (bool) $input->getOption('dry-run');
        $prefix = _DB_PREFIX_;

        if ($dryRun) {
            $output->writeln('<comment>Dry run — no tables will be truncated.</comment>');
        }

        try {
            foreach ($tables as $table) {
                $fullTable = $prefix . $table;

                if ($dryRun) {
                    $output->writeln("  Would truncate <info>{$fullTable}</info>");
                    continue;
                }

                Db::getInstance()->execute('TRUNCATE TABLE `' . bqSQL($fullTable) . '`');
                $output->writeln("  Truncated <info>{$fullTable}</info>");
            }

            $output->writeln($dryRun ? '<comment>Done (dry run).</comment>' : '<info>Done.</info>');

            return $this->exitSuccess();
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return $this->exitFailure();
        }
    }

    private function exitSuccess(): int
    {
        return \defined('\Symfony\Component\Console\Command\Command::SUCCESS') ? Command::SUCCESS : 0;
    }

    private function exitFailure(): int
    {
        return \defined('\Symfony\Component\Console\Command\Command::FAILURE') ? Command::FAILURE : 1;
    }
}
