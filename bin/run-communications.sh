#!/bin/sh
# Configure once, then process the shared queue. Credentials stay in .env.
set -eu
task_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
for task_php in /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/php /usr/bin/php; do
    if [ -x "$task_php" ] && "$task_php" -r 'exit(PHP_VERSION_ID >= 80200 && extension_loaded("pdo_mysql") && extension_loaded("curl") ? 0 : 1);'; then
        task_marker="$task_root/storage/data/telegram-webhook-configured"
        if [ ! -f "$task_marker" ]; then
            "$task_php" "$task_root/bin/console" communications:secret telegram
            "$task_php" "$task_root/bin/console" communications:setup telegram
            "$task_php" "$task_root/bin/console" communications:check telegram
            touch "$task_marker"
        fi
        exec "$task_php" "$task_root/bin/console" communications:work
    fi
done
echo 'PHP 8.2+ with PDO MySQL and cURL is required.' >&2
exit 1
