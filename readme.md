To publish online
===
- Pushes any changes on the Git
- SSH to the server `ssh dev@venteshirdl.com`
- From the home directory, run `./deploy.sh` (see end of this README for file content)

Live reinstall (from a backup file)
===
- Clone the git files and correctly setup folder rights (especially storage/ folder must be writable by web server)
- `composer install`
- Copy .env.example to .env and update
- Import the database
- `bower install`
- Run as www-data: `php artisan config:cache`
- Run as www-data: `php artisan route:cache`
- `sudo supervisorctl restart laravel-worker:*`
- Add the Laravel scheduler to the www-data crontab (see Laravel documentation)

Sample API request
===
POST http://hirdlpos.xfdev/api/1.0/hirdl/deviceData

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

cd /var/www/html/venteshirdl.com
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