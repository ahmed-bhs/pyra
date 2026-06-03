# Pyra

```
              /\
             /  \        E2E          fewer  ·  costly  ·  slow
            / E2E\       test the whole app through the UI
           /------\
          /        \     Integration
         / Integr.  \    test that units work together
        /------------\
       /              \  Unit         more  ·  cheap  ·  fast
      /     Unit       \ test units in isolation
     /------------------\
```

Pyra is a standalone CLI that checks the **shape of your test pyramid** and tells you,
on a pull request, whether a change is backed by the test levels you expect.

## Background

The Testing Pyramid, devised by Mike Cohn, is a guide for the types of automated tests
to favour in a project's test suite. It points at the unreliable, slow and costly nature
of end-to-end tests by placing them at the top, smaller section of the pyramid, and the
quick, cheap and isolated unit tests at the bottom, largest section.

The Testing Pyramid is only a guide. As the [Practical Test Pyramid](https://martinfowler.com/articles/practical-test-pyramid.html)
article sums up:

> Still, due to its simplicity the essence of the test pyramid serves as a good rule of
> thumb when it comes to establishing your own test suite. Your best bet is to remember
> two things from Cohn's original test pyramid:
>
> - Write tests with different granularity
> - The more high-level you get the fewer tests you should have

Pyra enforces exactly those two rules: that tests exist at different granularities for a
change, and that the higher levels stay smaller.

It is framework-agnostic: it reads files and a YAML config, it does not boot your
application. Works on Symfony, Laravel, or plain PHP projects.

Two modes:

- `pyra check` — global: counts tests per level (unit / integration / e2e), checks
  ratios + ordering, and flags a unit test that **behaves like an integration test**
  (depends on a forbidden I/O symbol such as an `EntityManager`).
- `pyra diff` — per pull request: for each **changed class**, reports whether the test
  levels you expect exist, and (when given coverage XML) how much of the changed lines
  are covered.

## Install

```bash
composer require --dev ahmed-bhs/pyra
```

## Configuration — `pyra.yaml`

```yaml
pyra:
    enforce_ordering: true

    levels:
        unit:
            paths: [tests/Unit]
            min_percentage: 60
            forbidden_dependencies:
                - Doctrine\ORM\EntityManagerInterface
                - Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
                - Zenstruck\Foundry\Test\ResetDatabase
        integration:
            paths: [tests/Integration]
            max_percentage: 35
        e2e:
            paths: [features]
            counter: gherkin        # count Gherkin scenarios instead of PHPUnit methods
            max_percentage: 15

    diff:
        base: origin/main
        sources:                    # which production areas expect which test levels
            - path: src/Domain
              expect: [unit]
            - path: src/Application
              expect: [unit, integration]
        ignore:
            - migrations
            - config
```

### Laravel example

```yaml
pyra:
    levels:
        unit:
            paths: [tests/Unit]
            forbidden_dependencies:
                - Illuminate\Foundation\Testing\RefreshDatabase
                - Illuminate\Foundation\Testing\DatabaseTransactions
        integration:
            paths: [tests/Feature]
    diff:
        base: origin/main
        sources:
            - path: app/Domain
              expect: [unit]
```

## Usage

```bash
# Global pyramid check
vendor/bin/pyra check --config pyra.yaml --strict

# Per-PR check against a base ref
vendor/bin/pyra diff --base origin/main --strict

# With real coverage (the only way "coverage" is reported)
vendor/bin/pyra diff --base origin/main --coverage build/clover.xml
```

Exit codes: violations + `--strict` → `1`; otherwise `0` (violations still printed).

## What it detects

- A changed class with no test at an expected level (**missing test** gate).
- A unit test depending on an integration-only symbol (**impure test**).
- An inverted pyramid (more integration than unit), within a single counting unit.
- With coverage XML: changed lines that are not executed by any test.

The search for tests covering a changed class spans the **whole** suite, not only the
files in the diff — so an existing, unchanged test that already covers the change does
**not** raise a false "missing test".

## Honest limitations

- **Static analysis answers presence, not coverage.** Without `--coverage`, Pyra says a
  test *appears* missing — never that coverage is insufficient. The word "coverage" only
  appears when a clover/cobertura XML is supplied.
- **No "feature" concept.** Granularity is the changed class, aggregated per PR.
- **Name mapping is heuristic.** A class referenced in a test but not exercised is a false
  positive; a class exercised transitively (via a collaborator, reflection, container
  wiring, or data providers) without being named is a false negative. Use `--coverage`
  to close the gap.
- **E2E / Gherkin cannot be name-mapped** (a `.feature` declares no PHP class). Use the
  path heuristic or coverage for e2e.
- **Per-level coverage needs per-suite coverage runs.** A single merged coverage file
  cannot say which level covered a line.
- **Supported test styles:** PHPUnit (methods `test*` / `#[Test]`) and Gherkin scenarios.
  Pest closures are not yet counted.

## License

MIT
