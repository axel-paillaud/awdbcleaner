# DB Cleaner (awdbcleaner)

A PrestaShop 8 and 9 module that truncates statistics tables, from the back office or with a Symfony CLI command, by [Axelweb](https://axelweb.fr).

Over time, tables like `ps_connections`, `ps_guest` and `ps_page_viewed` can grow to millions of rows, slowing down the database. This module empties them from its back office page (one button per table, or all at once) or with a console command.

## Requirements

- PrestaShop 8.0 to 9.x (the same code runs on both)
- PHP supported by your PrestaShop version (7.2+ on PS 8, 8.1+ on PS 9)
- Composer

## Installation

1. Copy the module to `modules/awdbcleaner/`.
2. Run `composer install` in the module directory.
3. Install the module from the PrestaShop back-office.

## Back office

*Modules > Module Manager > DB Cleaner > Configure* shows each table with its row count and size, an *Empty* button per table and an *Empty all tables* button (with a confirmation). It also lists the CLI commands below, with the shop root path.

Access follows the permissions of the hidden `AdminAwDbCleaner` tab (*Advanced Parameters > Team > Permissions*): *read* to see the page, *edit* to empty tables.

## CLI usage

Use `-e prod`: the default `dev` environment needs dev dependencies that a production install does not ship.

```bash
# Truncate all default tables
php bin/console -e prod prestashop:awdbcleaner:clean

# Dry run — see what would be truncated without executing
php bin/console -e prod prestashop:awdbcleaner:clean --dry-run

# Truncate only specific tables (without prefix, must exist)
php bin/console -e prod prestashop:awdbcleaner:clean --tables=connections,guest

# Combine options
php bin/console -e prod prestashop:awdbcleaner:clean --tables=connections,connections_source --dry-run
```

### Default tables

| Table | Description |
|---|---|
| `connections` | Visitor connection records |
| `connections_source` | Traffic source for each connection |
| `guest` | Guest visitor tracking |
| `page_viewed` | Page view statistics |
| `pagenotfound` | 404 error logs |

## Cron setup

Schedule the command to run monthly (e.g. 1st of each month at 3:00 AM):

```cron
0 3 1 * * cd /var/www/html && php bin/console -e prod prestashop:awdbcleaner:clean >> /var/log/awdbcleaner.log 2>&1
```

Or with a specific PHP version:

```cron
0 3 1 * * cd /var/www/html && /usr/bin/php8.2 bin/console prestashop:awdbcleaner:clean >> /var/log/awdbcleaner.log 2>&1
```

Adjust `/var/www/html` to your PrestaShop root directory.

## License

[Academic Free License 3.0 (AFL-3.0)](https://opensource.org/licenses/AFL-3.0)
