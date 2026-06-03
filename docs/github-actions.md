# Using Pyra in GitHub Actions

Run `pyra diff` on every pull request and have the missing tests show up as inline
annotations on the changed files.

```yaml
name: Test pyramid

on:
    pull_request:

jobs:
    pyra:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4
              with:
                  fetch-depth: 0   # Pyra needs history to diff against the base branch

            - uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.3'

            - run: composer install --no-interaction --prefer-dist

            # `--format=github` turns violations into inline PR annotations.
            # Drop `--strict` if you want a warning that does not fail the build.
            - name: Check the test pyramid for this PR
              run: |
                  vendor/bin/pyra diff \
                    --base "origin/${{ github.base_ref }}" \
                    --format=github \
                    --strict
```

## With coverage

To get a real "changed lines are X% covered" verdict, generate a coverage report first
and pass it in:

```yaml
            - uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.3'
                  coverage: pcov

            - run: composer install --no-interaction --prefer-dist

            - run: vendor/bin/phpunit --coverage-clover build/clover.xml

            - name: Check the test pyramid for this PR
              run: |
                  vendor/bin/pyra diff \
                    --base "origin/${{ github.base_ref }}" \
                    --coverage build/clover.xml \
                    --format=github
```

`--format=json` is available too, for piping into other tooling.
