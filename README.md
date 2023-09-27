# AsGoodAsNew

`git clone https://gitea.verklagmichdo.ch/slurp/asgoodasnew.git`
```dotenv
# .env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/asgoodasnew"
```
```dotenv
# .env.test
KERNEL_CLASS='App\Kernel'
```
`composer install`

# Testing
- `php bin/console --env test doctrine:database:create`
- `php bin/console --env test doctrine:migrations:migrate`
- `php bin/console --env test doctrine:fixtures:load`
- `php ./vendor/phpunit/phpunit/phpunit --configuration /var/www/asgoodasnew/phpunit.xml.dist`

# Postman
- `php bin/console doctrine:database:create`
- `php bin/console doctrine:migrations:migrate`
- `php bin/console doctrine:fixtures:load`

The Collection-File is here:  `docs/AsGoodAsNew.postman_collection.json`
