# DB Cleaner (awdbcleaner)

A PrestaShop 8 module that truncates statistics tables via a Symfony CLI command, by [Axelweb](https://axelweb.fr).

Over time, tables like `ps_connections`, `ps_guest` and `ps_page_viewed` can grow to millions of rows, slowing down the database. This module provides a single console command to clean them up.

## Requirements

- PrestaShop 8.0+
- PHP 8.1+
- Composer

## Installation

1. Copy the module to `modules/awdbcleaner/`.
2. Run `composer install` in the module directory.
3. Install the module from the PrestaShop back-office.

## CLI usage

```bash
# Truncate all default tables
php bin/console prestashop:awdbcleaner:clean

# Dry run — see what would be truncated without executing
php bin/console prestashop:awdbcleaner:clean --dry-run

# Truncate only specific tables (without prefix)
php bin/console prestashop:awdbcleaner:clean --tables=connections,guest

# Combine options
php bin/console prestashop:awdbcleaner:clean --tables=connections,connections_source --dry-run
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
0 3 1 * * cd /var/www/html && php bin/console prestashop:awdbcleaner:clean >> /var/log/awdbcleaner.log 2>&1
```

Or with a specific PHP version:

```cron
0 3 1 * * cd /var/www/html && /usr/bin/php8.2 bin/console prestashop:awdbcleaner:clean >> /var/log/awdbcleaner.log 2>&1
```

Adjust `/var/www/html` to your PrestaShop root directory.

## License

[Academic Free License 3.0 (AFL-3.0)](https://opensource.org/licenses/AFL-3.0)
