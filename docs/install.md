To publish online
===
- Pushes any changes on the Git
- SSH to the server 
- From the home directory, run `./deploy.sh` (see end of this README for file content)

Server requirements
===
- Tested with PHP 7.1.10
- MySQL "Ver 14.14 Distrib 5.7.33"
- Extensions:
  - bcmath
  - xml
  - gd
  - mbstring
  - intl
  - zip

Live reinstall (from a backup file)
===
- Note that the server works with PHP 7.1.10 and mysql "Ver 14.14 Distrib 5.7.33"
- Clone the git files and correctly setup folder rights (especially storage/ and bootstrap/cache folders must be writable by web server)
- Make sure to install all required PHP extensions (bcmath, xml, gd, mbstring, maybe some others...)
- `composer install`
- Copy .env.example to .env and update
- Import the database
- `bower install`
- Run as www-data: `php artisan config:cache`
- Run as www-data: `php artisan route:cache`
- Use 'supervisor' to run the laravel queue worker:
    - https://www.coderomeos.org/laravel-queue-worker-and-supervisor-process-monitor
    - See below for config file for the task
    - `sudo supervisorctl restart laravel-worker:*`
- Add the Laravel scheduler to the www-data crontab (see Laravel documentation)

Troubleshooting
===
- If you have `SQLSTATE[42000]: Syntax error or access violation: 1231 Variable 'sql_mode' can't be set to the value of 'NO_AUTO_CREATE_USER'`, your MySQL server might be too new compared to the Laravel version used. [See here](https://stackoverflow.com/questions/50068663/laravel-5-5-with-mysql-8-0-11-sql-mode-cant-be-set-to-the-value-of-no-auto)
- If you want to ignore deprecation warnings on your development server, I fixed it by temporarily adding `error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);` in the `boot()` method of the AppServiceProvider :( . Don't commit it if you do it!

Sample API request
===
POST /api/1.0/hirdl/deviceData

{
	"token": "eNrAw6JYXlpGeNiLNejbmz7mIVnDwfSq",
	"dataVersion": "10"
}

To manage (add/edit) products
===
Go to /manage-products. Note that this section doesn't do too much validation and there is no revert (if you delete a product, it cannot be brought back -- note that this doesn't delete the sales of this product, it will still show up in sale statistics, it is just not available for new sales) so it is better that only the developer has access to it.

deploy.sh
===
Script to automate the deployment from git source (ex: install in home folder). Update accordingly.

```
#!/bin/bash

deploy_log() {
	echo -e "\033[1;33m$1\033[0m"
}

cd /var/www/html/
deploy_log "git pull..."
git pull
deploy_log "composer install..."
composer install
deploy_log "bower install..."
bower install
deploy_log "php artisan migrate..."
php artisan migrate --force
deploy_log "rebuilding artisan cached files..."
sudo -u www-data bash -c "php artisan config:cache"
sudo -u www-data bash -c "php artisan route:cache"
deploy_log "php artisan queue:restart..."
php artisan queue:restart
deploy_log "Making sure supervisor is running..."
sudo supervisorctl restart laravel-worker:*
```

supervisor laraver-worker config file
===
Once 'supervisor' is installed, create the following configuration file:
/etc/supervisor/conf.d/laravel-worker.conf
```
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work database --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
```
