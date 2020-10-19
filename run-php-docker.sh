#!/bin/bash

docker run --rm -d --name cidaas-sdk-php -p 80 -v $(pwd):/var/www/work php:apache
PORT=`docker port cidaas-sdk-php | awk -F: {'print $2'}`

echo "Enter docker container using: 'docker exec -ti cidaas-sdk-php /bin/bash'"
echo "Access docker container web site using: http://localhost:${PORT}"
echo "Run 'apt update && apt install zip unzip' to be able to run composer"
echo "Run 'cd /var/www/work; ./docker/install-composer.sh' to install composer"
echo "Run './composer.phar i' to install dependencies"
echo "Run tests like './vendor/bin/phpunit tests/GetRequestIdTest.php'"

