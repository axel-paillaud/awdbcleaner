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

use Axelweb\AwDbCleaner\Service\TableCleaner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compatible Symfony 4.4 (PrestaShop 8) and 6.4 (PrestaShop 9): the name is
 * set in configure() and in the service tag (lazy loading), not with the
 * static $defaultName (deprecated in Symfony 6.1) nor #[AsCommand] (Symfony 5.3+).
 */
class CleanDatabaseCommand extends Command
{
    public const NAME = 'prestashop:awdbcleaner:clean';

    // Command::SUCCESS / FAILURE only exist since Symfony 5.1
    private const SUCCESS_CODE = 0;
    private const FAILURE_CODE = 1;

    /**
     * @var TableCleaner
     */
    private $tableCleaner;

    public function __construct(TableCleaner $tableCleaner)
    {
        $this->tableCleaner = $tableCleaner;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Truncate PrestaShop statistics tables (connections, guests, page views, 404 pages).')
            ->addOption('tables', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of tables to truncate (without prefix). Default: ' . implode(',', TableCleaner::DEFAULT_TABLES))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show which tables would be truncated without executing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tables = $input->getOption('tables')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $input->getOption('tables')))))
            : TableCleaner::DEFAULT_TABLES;

        $dryRun = (bool) $input->getOption('dry-run');

        try {
            // Check everything before truncating anything
            $missing = $this->tableCleaner->getMissingTables($tables);
            if (!empty($missing)) {
                $output->writeln('<error>Unknown table(s): ' . $this->tableCleaner->getPrefix() . implode(', ' . $this->tableCleaner->getPrefix(), $missing) . '</error>');

                return self::FAILURE_CODE;
            }

            if ($dryRun) {
                $output->writeln('<comment>Dry run — no tables will be truncated.</comment>');
            }

            foreach ($tables as $table) {
                $fullTable = $this->tableCleaner->getPrefix() . $table;

                if ($dryRun) {
                    $rows = $this->tableCleaner->countRows($table);
                    $output->writeln(sprintf('  Would truncate <info>%s</info> (%s rows)', $fullTable, number_format($rows, 0, '.', ' ')));
                    continue;
                }

                $rows = $this->tableCleaner->truncate($table);
                $output->writeln(sprintf('  Truncated <info>%s</info> (%s rows)', $fullTable, number_format($rows, 0, '.', ' ')));
            }

            $output->writeln($dryRun ? '<comment>Done (dry run).</comment>' : '<info>Done.</info>');

            return self::SUCCESS_CODE;
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::FAILURE_CODE;
        }
    }
}
