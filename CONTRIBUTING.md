# Contributing

Features and bug fixes are welcome! First-time contributors can jump in with the issues tagged [good first issue](https://github.com/jhedstrom/DrupalDriver/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22).

## Testing

Testing is performed automatically in Github Actions when a PR is submitted. To execute tests locally before submitting a PR, you'll need [Docker and Docker Compose](https://docs.docker.com/engine/install/).

Configure your test environment:
```
export PHP_VERSION=8.1
export DRUPAL_VERSION=10
export DOCKER_USER_ID=${UID}
```

Prepare environment for testing:
```
docker-compose up -d
docker-compose exec -T php composer self-update
docker-compose exec -u ${DOCKER_USER_ID} -T php composer require --no-interaction --dev --no-update drupal/core:^${DRUPAL_VERSION}
docker-compose exec -T php composer install
```

Execute all tests:
```
docker-compose exec -T php composer test
```

Execute specific tests, eg just PHPUnit's Drupal7FieldHandlerTest:
```
docker-compose exec -T php phpunit --filter Drupal7FieldHandlerTest
```

- Check the changes from `composer require` are not included in your submitted PR.
- Before testing another PHP or Drupal version, remove `composer.lock` and `vendor/`
