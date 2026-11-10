#affiliate-tracker-symfony/README.md

### INSTALL SYMFONY 7.4 to app folder:  
```
sudo docker run --rm -v $(pwd)/backend:/app -w /app composer:latest \
     create-project symfony/skeleton:"7.4.*" .
```

Этот пакет связывает Symfony Runtime с RoadRunner через PSR-7. Он поддерживает Symfony 5.4–8.x и PHP от 8.0.5. 
packagist.org
```
sudo docker compose build app

sudo docker compose run --rm --no-deps \
  --entrypoint php app -m | grep -E '^(amqp|sockets)$'

sudo docker compose run --rm --no-deps \
  --entrypoint composer app \
  require runtime/roadrunner-symfony-nyholm

sudo docker compose up --build -d
sudo docker compose logs -f app
```

### Изменения с RoadRunner
```
sudo docker compose exec app php bin/console cache:clear
sudo docker compose restart app
```

RoadRunner — долгоживущий PHP-процесс. В отличие от PHP-FPM, он держит PHP workers запущенными между запросами.

Когда ты меняешь код:
- PHP-файлы на диске обновляются благодаря Docker bind mount.
- Но уже запущенные workers могут продолжать использовать старое состояние приложения.
- Symfony также может использовать кэш маршрутов и контейнера.

```
sudo docker compose exec app rr --version
```

```
sudo docker compose restart app
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
### Скачай архив и посмотри:  
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


[26 10 2026 21:58] Affilate Symfony Project: ############ CONSOLE WORK (CURL, DB, DOCKER) ############################3

---АДМИН---

### CRATE ADMIN CUSTOM METHOD
```
sudo docker compose exec php php bin/console app:user:create-admin "msxpahub@gmail.com" "12351235admin"
```


### Login ADMIN

```
ADMIN_TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"msxpahub@gmail.com","password":"12351235admin"}' | jq -r .token)

echo "ADMIN_TOKEN length: ${#ADMIN_TOKEN}"
```


### Получить один оффер (АДМИН)
```
curl http://localhost/api/admin/offers/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq
```


### Список офферов (АДМИН)
```
curl http://localhost/api/admin/offers \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq
```



### Создать оффер (АДМИН)
```
curl -X POST http://localhost/api/admin/offers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{
    "name": "Test Offer",
    "slug": "test-offer",
    "destinationUrl": "https://example.com/offer",
    "payout": "10.00",
    "revenue": "15.00",
    "allowedCountries": ["RU", "KZ"]
  }' | jq
```



### Взять ОФФЕР
```
curl -X POST http://localhost/api/affiliate/offers/{id}/take -H "Authorization: Bearer $ADMIN_TOKEN"
```

  
  
### Активировать веба (админ)
### Веб создаётся со status: pending. Чтобы он мог брать офферы — нужен status: active.
### Обнови статус через psql (пока нет API-метода для этого):
```
sudo docker compose exec database psql -U tracker -d tracker -c "
UPDATE affiliates SET status = 'active' WHERE web_id = '$WEB_ID';
"
```


### POSTBACK
```
curl -i "http://localhost/postback?web_id=$ADMIN_TOKEN&click_id=$CLICK_ID&payout=10.00&status=approved&secret=$SECRET"
```


### Проверь токен (АДМИН)
```

curl http://localhost/api/auth/me \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq
```



### Обновить оффер (АДМИН)
```
curl -X PATCH http://localhost/api/admin/offers/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"payout": "20.00", "active": false}' | jq
```


---ВЕБ---
### Создать веба (регистрация)
```
curl -s -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"web@test.com","password":"password123"}' | jq
```



### Логин веба — получить токен
```
WEB_TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"web@test.com","password":"password123"}' | jq -r .token)
```


### MAKE WEB ACTIVE
```
sudo docker compose exec database psql -U tracker -d tracker -c "
UPDATE affiliates SET status = 'active' WHERE web_id = '$WEB_ID';
"
```


### Проверка токена:
```
curl -s http://localhost/api/auth/me \
  -H "Authorization: Bearer $WEB_TOKEN" | jq

echo "WEB_TOKEN length: ${#WEB_TOKEN}"
```



### Список Офферов
```
curl http://localhost/api/affiliate/offers \
  -H "Authorization: Bearer $WEB_TOKEN" | jq
```



### Создать оффер (админ)
```
curl -s -X POST http://localhost/api/admin/offers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $WEB_TOKEN" \
  -d '{
    "name": "Test Offer",
    "slug": "test-offer",
    "destinationUrl": "https://example.com/offer",
    "payout": "10.00",
    "revenue": "15.00",
    "allowedCountries": ["RU", "KZ"]
  }' | jq
```



### Взять ОФФЕР
```
curl -X POST http://localhost/api/affiliate/offers/{id}/take -H "Authorization: Bearer $WEB_TOKEN"
```



### Проверка прав — веб НЕ может создавать офферы
```
WEB_TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"web@test.com","password":"password123"}' | jq -r .token)

echo "WEB_TOKEN=$WEB_TOKEN"

curl -X POST http://localhost/api/admin/offers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $WEB_TOKEN" \
  -d '{"name":"Hack","slug":"hack","destinationUrl":"https://hack.com","payout":"0","revenue":"0"}' | jq
[26 10 2026 21:58] Affilate Symfony Project: ### CLICK
CLICK_ID=$(cat /proc/sys/kernel/random/uuid)
echo "CLICK_ID=$CLICK_ID"
curl -i "http://localhost/click?offer=1&web_id=$WEB_ID&clickid=$CLICK_ID"
sleep 3;
```



### POSTBACK
```
curl -i "http://localhost/postback?web_id=$WEB_ID&click_id=$CLICK_ID&payout=10.00&status=approved&secret=$SECRET"
```



### AFFILIATE by WEB_ID
```
SELECT * FROM affiliates WHERE web_id = '$WEB_ID';
```


### AFFILIATE FIND CONVERSION
```
docker exec -it tacker_database -c "
SELECT * FROM conversions c JOIN affiliates a ON a.id = c.affiliate_id WHERE a.web_id = '$WEB_ID';
```

"

### UPDATE PRICE
```
docker exec -it tacker_database -c "
update affiliates SET balance ='10.00' where web_id='$WEB_ID';
"
```





<!-- 
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
``` -->

<!-- ### ⚠️ Про AMQP: Если хочешь избежать проблем с C-расширением, установи php-amqplib:
```
sudo docker run --rm -u $(id -u):$(id -g) -v $(pwd)/backend:/app -w /app composer:latest require php-amqplib/php-amqplib
``` -->

### Проверить, что Symfony видит БД
```
sudo docker exec tracker_php php bin/console about
sudo docker exec tracker_php php bin/console doctrine:query:sql "SELECT 1"
```

### .env.local:
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
sudo docker exec tracker_php php bin/console doctrine:query:sql "SELECT version()"

# Messenger знает про транспорт
sudo docker exec tracker_php php bin/console debug:config framework messenger

# Расширения PHP
sudo docker exec tracker_php php -m | grep -E "redis|pgsql"
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
sudo docker exec tracker_php php -m | grep -E "sockets|amqp|redis|bcmath|pgsql"
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
sudo docker compose logs worker | tail -20
sudo docker compose logs rabbitmq | tail -20
```

### Чек коннекта с Rabbit MQ через php
```
sudo docker compose exec php php -r '
try {
    $c = new AMQPConnection([
        "host" => "rabbitmq",
        "port" => 5672,
        "login" => "tracker",
        "password" => "secret",
        "vhost" => "/",
    ]);
    $c->connect();
    echo "Connected OK\n";
    $c->disconnect();
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
'
```



```
sudo docker compose ps
sudo docker compose exec php php bin/console --version
sudo docker compose exec php php bin/console doctrine:query:sql "SELECT version()"
ls backend/config/jwt/

sudo docker compose exec -u $(id -u):$(id -g) php php bin/console lexik:jwt:generate-keypair
ls -la backend/config/jwt/
```


### Вариант 1: psql напрямую
```
sudo docker compose exec database psql -U tracker -d tracker -c "SELECT version();"
```

### Вариант 2: dbal:run-sql
```
sudo docker compose exec php php bin/console dbal:run-sql "SELECT version()"
```

### Вариант 3: посмотреть все доступные команды doctrine
```
sudo docker compose exec php php bin/console list doctrine
sudo docker compose exec php php bin/console list dbal
```





### 1. Создать папку (если её нет)
```
mkdir -p backend/config/jwt
```

### 2. Сгенерировать ключи от своего UID (чтобы файлы принадлежали тебе)
```
sudo docker compose exec -u $(id -u):$(id -g) php php bin/console lexik:jwt:generate-keypair
```

### 3. Проверить
```
ls -la backend/config/jwt/
```


### Проверь, что Doctrine видит сущности:
```
sudo docker compose exec php php bin/console doctrine:mapping:info
```

Должен вывести список: User, Affiliate, Offer, Click, Conversion.

### Сгенерируй миграцию:
```
sudo docker compose exec php php bin/console make:migration
```


### Проверь синтаксис
```
sudo docker compose exec php php -l /var/www/html/src/Entity/Click.php
```

### 2. Doctrine видит сущность
```
sudo docker compose exec php php bin/console doctrine:mapping:info | grep Click
```

### 3. Схема совпадает с Entity
```
sudo docker compose exec php php bin/console doctrine:schema:validate
```

### Список всех app: команд
```
sudo docker compose exec php php bin/console list app
```

### Или найти конкретную
```
sudo docker compose exec php php bin/console list | grep validate
```

### Validate schema Custom
```
sudo docker compose exec php php bin/console app:db:validate-schema
```

### Проверь роуты
```
sudo docker compose exec php php bin/console debug:router | grep -iE "api|login"
```

api_auth_register   POST    /api/auth/register
api_auth_me         GET     /api/auth/me
api_login_check     POST    /api/auth/login


### Тест регистрации
```
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"web@test.com","password":"password123"}'
```

Ожидаемо 201 Created:
```
{
  "user": {"id":1,"email":"web@test.com","roles":["ROLE_AFFILIATE","ROLE_USER"]},
  "affiliate": {"id":1,"webId":"web_...","status":"pending"}
}
```

### Тест логина
```
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"web@test.com","password":"password123"}'
```

Ожидаемо 200 OK:
```
{"token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."}
```

### Тест /api/auth/me с токеном
```
TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"web@test.com","password":"password123"}' | jq -r .token)

curl http://localhost/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

Ожидаемо:
```
{
  "user": {"id":1,"email":"web@test.com","roles":["ROLE_AFFILIATE","ROLE_USER"]},
  "affiliate": {"id":1,"webId":"web_...","status":"pending"}
}
```

Сгенерировать CRUD для Offer
```
sudo docker compose exec php php bin/console make:admin:crud
```