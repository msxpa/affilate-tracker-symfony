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





┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ grep -n "function login" backend/src/Controller/Api/AuthController.php
47:    public function login(): JsonResponse
                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ sudo docker compose exec php php bin/console cache:clear

 // Clearing the cache for the dev environment with debug true                                                          

                                                                                                                        
 [OK] Cache for the "dev" environment (debug=true) was successfully cleared.                                            
                                                                                                                        

                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ sudo docker compose exec php php bin/console debug:router | grep -iE "api|login"
  api_auth_login             POST     /api/auth/login                    
  api_auth_register          POST     /api/auth/register                 
  api_auth_me                GET      /api/auth/me                       
                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"web@test.com","password":"password123"}'
{"user":{"id":1,"email":"web@test.com","roles":["ROLE_AFFILIATE","ROLE_USER"]},"affiliate":{"id":1,"webId":"web_1ZDVYMA6EP8CTV6MEVHE8AR7FC","status":"pending"}}                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ 















┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ sudo docker compose exec php php bin/console cache:clear

 // Clearing the cache for the dev environment with debug true                                                          

                                                                                                                        
 [OK] Cache for the "dev" environment (debug=true) was successfully cleared.                                            
                                                                                                                        

                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ sudo docker compose exec php php bin/console app:db:validate-schema
Schema is not in sync (excluding clicks):
  ALTER TABLE conversions ALTER created_at TYPE TIMESTAMPTZ
  ALTER TABLE conversions ALTER created_at SET DEFAULT 'now()'
  ALTER TABLE affiliates ALTER created_at TYPE TIMESTAMPTZ
  ALTER TABLE affiliates ALTER created_at SET DEFAULT 'now()'
  ALTER TABLE affiliates ALTER updated_at TYPE TIMESTAMPTZ
  ALTER TABLE offers ALTER created_at TYPE TIMESTAMPTZ
  ALTER TABLE offers ALTER created_at SET DEFAULT 'now()'
  ALTER TABLE offers ALTER updated_at TYPE TIMESTAMPTZ
  ALTER TABLE users ALTER created_at TYPE TIMESTAMPTZ
  ALTER TABLE users ALTER created_at SET DEFAULT 'now()'
  ALTER TABLE users ALTER updated_at TYPE TIMESTAMPTZ
                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ 

                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ sudo docker compose exec php php bin/console doctrine:schema:validate

Mapping
-------

                                                                                                                        
 [OK] The mapping files are correct.                                                                                    
                                                                                                                        

Database
--------

                                                                                                                        
 [ERROR] The database schema is not in sync with the current mapping file.                                              
                                                                                                                        

                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ sudo docker compose exec php php bin/console doctrine:schema:update --dump-sql
CREATE SEQUENCE clicks_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE TABLE conversions (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, click_id VARCHAR(36) NOT NULL, payout NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, meta JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, offer_id INT NOT NULL, affiliate_id INT NOT NULL, PRIMARY KEY (id));
CREATE INDEX idx_conversions_click_id ON conversions (click_id);
CREATE INDEX idx_conversions_created_at ON conversions (created_at);
CREATE INDEX IDX_6A02DBA553C674EE ON conversions (offer_id);
CREATE INDEX IDX_6A02DBA59F12C49A ON conversions (affiliate_id);
CREATE TABLE affiliates (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, web_id VARCHAR(64) NOT NULL, status VARCHAR(20) NOT NULL, postback_url VARCHAR(512) DEFAULT NULL, secret VARCHAR(64) NOT NULL, balance NUMERIC(12, 2) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id INT NOT NULL, PRIMARY KEY (id));
CREATE UNIQUE INDEX UNIQ_108C6A8FA76ED395 ON affiliates (user_id);
CREATE UNIQUE INDEX uniq_affiliates_web_id ON affiliates (web_id);
CREATE TABLE affiliate_offers (affiliate_id INT NOT NULL, offer_id INT NOT NULL, PRIMARY KEY (affiliate_id, offer_id));
CREATE INDEX IDX_559634DD9F12C49A ON affiliate_offers (affiliate_id);
CREATE INDEX IDX_559634DD53C674EE ON affiliate_offers (offer_id);
CREATE TABLE offers (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL, destination_url VARCHAR(1024) NOT NULL, payout NUMERIC(10, 2) NOT NULL, revenue NUMERIC(10, 2) NOT NULL, active BOOLEAN NOT NULL, allowed_countries JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id));
CREATE UNIQUE INDEX uniq_offers_slug ON offers (slug);
CREATE TABLE clicks (id BIGINT NOT NULL, click_id UUID NOT NULL, ip INET DEFAULT NULL, country CHAR(2) DEFAULT NULL, user_agent VARCHAR(512) DEFAULT NULL, referer VARCHAR(512) DEFAULT NULL, params JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT 'now()' NOT NULL, offer_id INT NOT NULL, affiliate_id INT NOT NULL, PRIMARY KEY (id));
CREATE INDEX IDX_20DA190153C674EE ON clicks (offer_id);
CREATE INDEX IDX_20DA19019F12C49A ON clicks (affiliate_id);
CREATE TABLE users (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id));
CREATE UNIQUE INDEX uniq_users_email ON users (email);
CREATE TABLE messenger_messages (id BIGINT GENERATED BY DEFAULT AS IDENTITY NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id));
CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id);
ALTER TABLE conversions ADD CONSTRAINT FK_6A02DBA553C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE RESTRICT NOT DEFERRABLE;
ALTER TABLE conversions ADD CONSTRAINT FK_6A02DBA59F12C49A FOREIGN KEY (affiliate_id) REFERENCES affiliates (id) ON DELETE RESTRICT NOT DEFERRABLE;
ALTER TABLE affiliates ADD CONSTRAINT FK_108C6A8FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE;
ALTER TABLE affiliate_offers ADD CONSTRAINT FK_559634DD9F12C49A FOREIGN KEY (affiliate_id) REFERENCES affiliates (id) ON DELETE CASCADE;
ALTER TABLE affiliate_offers ADD CONSTRAINT FK_559634DD53C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE;
ALTER TABLE clicks ADD CONSTRAINT FK_20DA190153C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE RESTRICT NOT DEFERRABLE;
ALTER TABLE clicks ADD CONSTRAINT FK_20DA19019F12C49A FOREIGN KEY (affiliate_id) REFERENCES affiliates (id) ON DELETE RESTRICT NOT DEFERRABLE;
                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ 



┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ sudo docker compose exec php php bin/console doctrine:migrations:status
+----------------------------------------------------------------------------------------------------------------------+
| Configuration                                                                                                        |
+----------------------+----------------------+------------------------------------------------------------------------+
| Storage              | Type                 | Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration |
|                      | Table Name           | doctrine_migration_versions                                            |
|                      | Column Name          | version                                                                |
|----------------------------------------------------------------------------------------------------------------------|
| Database             | Driver               | Symfony\Bridge\Doctrine\Middleware\IdleConnection\Driver               |
|                      | Name                 | tracker                                                                |
|----------------------------------------------------------------------------------------------------------------------|
| Versions             | Previous             | 0                                                                      |
|                      | Current              | 0                                                                      |
|                      | Next                 | DoctrineMigrations\Version20261013142727                               |
|                      | Latest               | DoctrineMigrations\Version20261013142727                               |
|----------------------------------------------------------------------------------------------------------------------|
| Migrations           | Executed             | 0                                                                      |
|                      | Executed Unavailable | 0                                                                      |
|                      | Available            | 1                                                                      |
|                      | New                  | 1                                                                      |
|----------------------------------------------------------------------------------------------------------------------|
| Migration Namespaces | DoctrineMigrations   | /var/www/html/migrations                                               |
+----------------------+----------------------+------------------------------------------------------------------------+
                                                                                                                                           
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ 

┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ sudo docker compose exec php php bin/console doctrine:migrations:migrate

 WARNING! You are about to execute a migration in database "tracker" that could result in schema changes and data loss. Are you sure you wish to continue? (yes/no) [yes]:
 > yes

[notice] Migrating up to DoctrineMigrations\Version20261013142727
[notice] finished in 245.7ms, used 24M memory, 1 migrations executed, 33 sql queries
                                                                                                                        
 [OK] Successfully migrated to version: DoctrineMigrations\Version20261013142727                                        
                                                                                                                                                                                                                                                            
┌──(comp㉿comp)-[~/Desktop/affilate-tracker/affiliate-tracker-symfony]
└─$ 


 Важно: schema:validate всегда будет ERROR для clicks
Запомни на будущее:

text
[ERROR] The database schema is not in sync with the current mapping file.
Это не проблема проекта, это ограничение Doctrine. Doctrine не знает про PARTITION BY RANGE, поэтому видит расхождение:

Entity: PK = (id), TIMESTAMP WITHOUT TIME ZONE

БД: PK = (id, created_at), TIMESTAMPTZ, PARTITION BY RANGE

Что делать: использовать --skip-sync, чтобы проверять только mapping:

bash
sudo docker compose exec php php bin/console doctrine:schema:validate --skip-sync


Что меняется (сводка)
Файл	Поле	Было	Стало
User	$roles	#[ORM\Column]	type: Types::JSON, options: ['jsonb' => true]
User	$createdAt	DATETIME_IMMUTABLE	+ options: ['default' => 'now()']
User	$updatedAt	DATETIME_IMMUTABLE, nullable: true	(без изменений)
Offer	$allowedCountries	type: Types::JSON	+ options: ['jsonb' => true, 'default' => '[]']
Offer	$createdAt	DATETIME_IMMUTABLE	+ options: ['default' => 'now()']
Affiliate	$balance	DECIMAL, precision: 12, scale: 2	+ options: ['default' => '0.00']
Affiliate	$createdAt	DATETIME_IMMUTABLE	+ options: ['default' => 'now()']
Conversion	$clickId	length: 36	type: Types::GUID
Conversion	$meta	type: Types::JSON	+ options: ['jsonb' => true, 'default' => '{}']
Conversion	$createdAt	DATETIME_IMMUTABLE	+ options: ['default' => 'now()']
Никаких изменений в логике методов — только маппинги.