<?php
use Dotenv\Dotenv;

$ENVIRONMENT = getenv('APP_ENV') ?: 'development'; //TODO: Change to production
$envFile = ".env.{$ENVIRONMENT}";
$baseDir = __DIR__ . '/../';

if (!file_exists($baseDir . $envFile)) {
   $envFile = '.env';
}

$DOTENV = Dotenv::createImmutable($baseDir, $envFile);
$DOTENV->safeLoad();

$ENVCONFIG = [
   'APP' => [
      'HOST' => $_ENV['APP_HOST'],
      'PORT' => $_ENV['APP_PORT'],
      'ALLOWS_CORS' => $_ENV['ALLOWS_CORS']
   ],
   'DB' => [
      'HOST' => $_ENV['DB_HOST'],
      'PORT' => $_ENV['DB_PORT'],
      'NAME' => $_ENV['DB_NAME'],
      'USERNAME' => $_ENV['DB_USERNAME'],
      'PASSWORD' => $_ENV['DB_PASSWORD'],
   ],
   'JWT' => [
      'SECRET' => $_ENV['JWT_SECRET'],
      'EXPIRES_IN' => $_ENV['JWT_EXPIRES_IN'],
      'ACCESS_TOKEN' => [
         'SECRET' => $_ENV['ACCESS_TOKEN_SECRET'],
         'EXPIRES_IN' => $_ENV['ACCESS_TOKEN_EXPIRES_IN'],
      ],
      'REFRESH_TOKEN' => [
         'SECRET' => $_ENV['REFRESH_TOKEN_SECRET'],
         'EXPIRES_IN' => $_ENV['REFRESH_TOKEN_EXPIRES_IN'],
      ],
   ],
   'S3' => [
      'REGION' => $_ENV['S3_REGION'],
      'ACCESS_KEY' => $_ENV['S3_ACCESS_KEY'],
      'SECRET_KEY' => $_ENV['S3_SECRET_KEY'],
      'BUCKET' => $_ENV['S3_BUCKET'],
      'ENDPOINT' => $_ENV['S3_ENDPOINT'] ?? null,
   ]
];

return $ENVCONFIG;
