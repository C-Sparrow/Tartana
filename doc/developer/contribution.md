# Tartana Contribution

Tartana uses TDD, means every single peace of functionality must be covered trough a unit test. That means we do not accept pull requests without test coverage and that the tests are not failing. The project is hosted on [Github](https://github.com/C-Sparrow/Tartana) where you can open issues and pull requests.

## Development workflow
Before you start, make sure your system meets the [requirements](../main/requirements.md). To develop Tartana run the following commands on your linux machine:

- `git clone https://github.com/C-Sparrow/Tartana.git`
- `cd Tartana`
- `wget https://getcomposer.org/composer.phar`
- `php composer.phar install`
- `vendor/bin/phpunit tests/unit`

If composer is installed globaly the wget command is not needed, but then you know anyway what to do.