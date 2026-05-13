# Loan Application API

In .env set DB credentials and HTTP port

```
POSTGRES_DB
POSTGRES_USER
POSTGRES_PASSWORD
HTTP_PORT
```

Launch docker compose
```
docker compose up -d --build
```

Run migrations
```
docker compose exec php php bin/console doctrine:migrations:migrate
```

Tests
```
docker compose exec php php bin/console doctrine:database:create --env=test
docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec php php bin/phpunit
```

Open the app
```
http://localhost:${port}
```

Open API docs 
```
http://localhost:${port}/api/doc
```
