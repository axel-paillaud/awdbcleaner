<?php
/**
 * @author    Axelweb <contact@axelweb.fr>
 * @copyright 2026 Axelweb
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Axelweb
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class AwDbCleaner extends Module
{
    public function __construct()
    {
        $this->name = 'awdbcleaner';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Axelweb';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans('DB Cleaner', [], 'Modules.Awdbcleaner.Admin');
        $this->description = $this->trans('Periodically truncates statistics tables (connections, guests, page views, 404 pages) via a Symfony CLI command.', [], 'Modules.Awdbcleaner.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module?', [], 'Modules.Awdbcleaner.Admin');

        $this->ps_versions_compliancy = [
            'min' => '8.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function install(): bool
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $installed = parent::install();

        // Prevent 'Unable to generate a URL for the named route [...]' error,
        // clear Symfony cache
        if ($installed) {
            Tools::clearSf2Cache();
        }

        return $installed;
    }

    public function uninstall(): bool
    {
        return parent::uninstall();
    }

    /**
     * Redirect to the module symfony configuration page
     *
     * @return void
     */
    public function getContent(): void
    {
        $route = $this->get('router')->generate('awdbcleaner_form_configuration');
        Tools::redirectAdmin($route);
    }
}
