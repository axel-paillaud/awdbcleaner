<?php
/**
 * @author    Axelweb <contact@axelweb.fr>
 * @copyright 2026 Axelweb
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Axelweb
 */
declare(strict_types=1);

namespace Axelweb\AwDbCleaner\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Axelweb\AwDbCleaner\Command\CleanDatabaseCommand;
use Axelweb\AwDbCleaner\Service\TableCleaner;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Module page: manual cleaning of the tables and CLI reminder.
 * Uses PrestaShop's _token parameter for CSRF protection.
 */
class AdminConfigurationController extends FrameworkBundleAdminController
{
    private const ROUTE = 'awdbcleaner_configuration';

    /**
     * Must match the tab class_name (PrestaShop creates it from routes.yml).
     */
    private const LEGACY_CONTROLLER = 'AdminAwDbCleaner';

    public function index(Request $request): Response
    {
        if (!$this->isGranted('read', self::LEGACY_CONTROLLER)) {
            return $this->accessDenied();
        }

        $cleaner = new TableCleaner();

        return $this->render('@Modules/awdbcleaner/views/templates/admin/configuration.html.twig', [
            'layoutTitle' => $this->translator()->trans('DB Cleaner', [], 'Modules.Awdbcleaner.Admin'),
            'enableSidebar' => false,
            'stats' => $cleaner->getStats(TableCleaner::DEFAULT_TABLES),
            'canClean' => $this->isGranted('update', self::LEGACY_CONTROLLER),
            'command' => CleanDatabaseCommand::NAME,
            'defaultTables' => implode(',', TableCleaner::DEFAULT_TABLES),
            'psRootDir' => rtrim(_PS_ROOT_DIR_, '/'),
        ]);
    }

    /**
     * POST "table": one of the default tables, or "all".
     */
    public function clean(Request $request): RedirectResponse
    {
        if (!$this->isGranted('update', self::LEGACY_CONTROLLER)) {
            return $this->accessDenied();
        }

        $table = (string) $request->request->get('table', '');

        if ('all' === $table) {
            $tables = TableCleaner::DEFAULT_TABLES;
        } elseif (in_array($table, TableCleaner::DEFAULT_TABLES, true)) {
            $tables = [$table];
        } else {
            $this->addFlash('error', $this->translator()->trans('Unknown table.', [], 'Modules.Awdbcleaner.Admin'));

            return $this->redirectToRoute(self::ROUTE);
        }

        $cleaner = new TableCleaner();
        $emptied = [];
        $rows = 0;

        foreach ($tables as $name) {
            try {
                $rows += $cleaner->truncate($name);
                $emptied[] = $cleaner->getPrefix() . $name;
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        if (!empty($emptied)) {
            $this->addFlash('success', $this->translator()->trans('Emptied: %tables% (%rows% rows deleted).', [
                '%tables%' => implode(', ', $emptied),
                '%rows%' => number_format($rows, 0, ',', ' '),
            ], 'Modules.Awdbcleaner.Admin'));
        }

        return $this->redirectToRoute(self::ROUTE);
    }

    private function accessDenied(): RedirectResponse
    {
        $this->addFlash('error', $this->translator()->trans('Access denied.', [], 'Admin.Notifications.Error'));

        return $this->redirectToRoute('admin_module_manage');
    }

    /**
     * Domains must stay literal in the ->trans() calls: the PrestaShop
     * extractor (BO translations, xlf export) ignores variables and constants.
     *
     * @return \Symfony\Contracts\Translation\TranslatorInterface
     */
    private function translator()
    {
        return \Context::getContext()->getTranslator();
    }
}
