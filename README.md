#affiliate-tracker-symfony/README.md

### INSTALL SYMFONY 7.4 to app folder:  
```
sudo docker run --rm -v $(pwd)/backend:/app -w /app composer:latest \
     create-project symfony/skeleton:"7.4.*" .
```

### Установка webapp pack через контейнер composer:  
```
sudo docker run --rm \
  -u $(id -u):$(id -g) \
  -v $(pwd)/backend:/app \
  -w /app \
  composer:latest \
  require webapp
```

### COMPOSER COMMANDS:  
```
sudo docker run --rm \
  -u $(id -u):$(id -g) \
  -v $(pwd)/backend:/app \
  -w /app \
  composer:latest \<КОМАНДА>
```

### Как проверить имя папки  
#### Скачай архив и посмотри:  
```
curl -L -o /tmp/amqp.tar.gz "https://github.com/php-amqp/php-amqp/archive/refs/tags/v2.2.0.tar.gz"
tar tzf /tmp/amqp.tar.gz | head -3
```

```
# Сначала подними PHP-контейнер (без остальных)
sudo docker compose up -d tracker_php

# Потом запускай composer внутри него
sudo docker exec -u $(id -u):$(id -g) tracker_php composer update
```

### Доустановить специфичные для трекера пакеты:
```
# Messenger + AMQP транспорт
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require symfony/messenger

# Redis (cache + sessions + lock)
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require symfony/cache predis/predis

# JWT для API
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require lexik/jwt-authentication-bundle

# CORS для Next.js
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require nelmio/cors-bundle

# UUID
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require symfony/uid

# Rate limiter (для /click)
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require symfony/rate-limiter

# Doctrine Migrations
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require doctrine/doctrine-migrations-bundle

# PHPStan для качества (dev)
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require --dev phpstan/phpstan

# Symfony CLI (если нужен)
# уже установлен в образе

#php-amqplib/php-amqplib (рекомендую)
sudo docker run --rm \
  -u $(id -u):$(id -g) \
  -v $(pwd)/backend:/app \
  -w /app \
  composer:latest \
  require php-amqplib/php-amqplib

#cat backend/composer.json | grep -iE "amqp|amqplib"
#"php-amqplib/php-amqplib": "^3.7",
#"checkthiscloud/phpamqplib-messenger": "^1.0"
sudo docker run --rm \
  -u $(id -u):$(id -g) \
  -v $(pwd)/backend:/app \
  -w /app \
  composer:latest \
  require php-amqplib/php-amqplib checkthiscloud/phpamqplib-messenger
```

### ⚠️ Про AMQP: Если хочешь избежать проблем с C-расширением, установи php-amqplib:
```
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require php-amqplib/php-amqplib
```

### Проверить, что Symfony видит БД
```
sudo docker exec tracker_php php bin/console about
sudo docker exec tracker_php php bin/console doctrine:query:sql "SELECT 1"
```

.env.local:
```
# backend/.env.local
APP_ENV=dev
APP_SECRET=change_me_to_random_string

DATABASE_URL="postgresql://tracker:secret@database:5432/tracker?serverVersion=15&charset=utf8"

REDIS_URL="redis://redis:6379"
MESSENGER_TRANSPORT_DSN="amqp://tracker:secret@rabbitmq:5672/%2f"

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=change_me
```

### Создать JWT-ключи:
```
sudo docker exec tracker_php php bin/console lexik:jwt:generate-keypair
```

### Проверить, что Messenger работает
```
sudo docker exec tracker_php php bin/console debug:config framework messenger
sudo docker exec tracker_php php bin/console messenger:stats
```



### Проверки
```
# Symfony видит БД
sudo docker  exec tracker_php php bin/console doctrine:query:sql "SELECT version()"

# Messenger знает про транспорт
sudo docker  exec tracker_php php bin/console debug:config framework messenger

# Расширения PHP
sudo docker  exec tracker_php php -m | grep -E "redis|pgsql"
```


### RabbitMQ — cookie file
```
sudo docker compose down
sudo docker volume rm affiliate-tracker-symfony_rabbitmq_data
# или
sudo docker volume ls | grep rabbitmq
sudo docker volume rm <имя>
sudo docker compose up -d rabbitmq
```
Или проще — через UI Docker Desktop или командой:
```
sudo docker volume prune -f
```


### Проверь, что расширения на месте
```
sudo docker  exec tracker_php php -m | grep -E "sockets|amqp|redis|bcmath|pgsql"
```


www-data@6ca167960be1:~/html$ ```php -m | grep -E "sockets|amqp|redis|bcmath|pgsql"```  
amqp  
bcmath  
pdo_pgsql  
pgsql  
redis  
sockets  
www-data@6ca167960be1:~/html$ ```php bin/console --version```    
Symfony v7.4.20 (env: dev, debug: true)  
www-data@6ca167960be1:~/html$   



```
sudo docker ps
sudo docker logs worker | tail -20
sudo docker logs rabbitmq | tail -10
```