#!/bin/sh
set -eu
task_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
for task_php in /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/php /usr/bin/php; do
    if [ -x "$task_php" ] && "$task_php" -r 'exit(PHP_VERSION_ID >= 80200 && extension_loaded("pdo_mysql") ? 0 : 1);'; then
        exec "$task_php" "$task_root/bin/console" content:sync
    fi
done
echo 'No PHP 8.2+ CLI with PDO MySQL found. Import database/cpanel/content_release_2026_10_12.mysql.sql to synchronize the release.' >&2
exit 1
