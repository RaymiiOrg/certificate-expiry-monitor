# Certificate Expiry Monitor

## About

Certificate Expiry Monitor is an open source monitoring tool for certificates. It monitors websites and emails you when the certificates are about to expire.

See the example site: https://certificatemonitor.org/

## Requirements

- PHP 5.6+
- OpenSSL
- PHP must allow remote fopen.

## Installation

Unpack, change some variables, setup a cronjob and go!

First get the code and unpack it to your webroot:

    cd /var/www/html/
    git clone https://github.com/RaymiiOrg/certificate-expiry-monitor.git

Create the database files, outside of your webroot. If you create these inside your webroot, everybody can read them.

    echo '{}' > /var/www/certificate-expiry-monitor-db/pre_checks.json
    echo '{}' > /var/www/certificate-expiry-monitor-db/checks.json
    echo '{}' > /var/www/certificate-expiry-monitor-db/deleted_checks.json
    chown -R $wwwuser /var/www/certificate-expiry-monitor-db/*.json

These files are used by the tool as database for checks.


Change the location of these files in `variables.php`:


    // set this to a location outside of your webroot so that it cannot be accessed via the internets.

    $pre_check_file = '/var/www/html/certificate-expiry-monitor/pre_checks.json';
    $check_file = '/var/www/html/certificate-expiry-monitor/checks.json';
    $deleted_check_file = '/var/www/html/certificate-expiry-monitor/deleted_checks.json';

Also change the `$current_domain` variable, it is used in all the email addresses.

    $current_domain = "certificatemonitor.org";

And `$current_link`, which may or may not be the same. It is used in the confirm and unsubscribe links, and depends on your webserver configuration. `example.com/subdir` here means your unsubscribe links will start `https://example.com/subdir/unsubscribe.php`.

    $current_link = "certificatemonitor.org";

Set up the cronjob to run once a day:

    # /etc/cron.d/certificate-expiry-monitor
    1 1 * * * $wwwuser /path/to/php /var/www/html/certificate-expiry-monitor/cron.php >> /var/log/certificate-expiry-monitor.log 2>&1


The default timeout for checks is 2 seconds. If this is too fast for your internal services, this can be raised in the `variables.php` file.

