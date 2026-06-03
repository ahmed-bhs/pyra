# Configuration

Pyra is configured with a single YAML file, `pyra.yaml`, at the root of your project.
Everything lives under a top-level `pyra:` key. Paths are resolved relative to the file.

```yaml
pyra:
    enforce_ordering: true
    levels:        # required
        ...
    diff:          # optional, only used by `pyra diff`
        ...
```

## `levels`

A map of level name to its definition. The level names are fixed: `unit`,
`integration`, `e2e`. You only declare the ones you have.

```yaml
pyra:
    levels:
        unit:
            paths: [tests/Unit, tests/Component]
            counter: phpunit
            min_percentage: 60
            max_percentage: null
            forbidden_dependencies:
                - Doctrine\ORM\EntityManagerInterface
```

| Key | Type | Default | Meaning |
|-----|------|---------|---------|
| `paths` | list of paths | `[]` | Directories scanned for this level. Non-existent paths are skipped silently. |
| `counter` | `phpunit` \| `gherkin` | `phpunit` | How tests are counted in those paths (see below). |
| `min_percentage` | number | none | Fail/warn if this level is **below** this share of its counting unit. |
| `max_percentage` | number | none | Fail/warn if this level is **above** this share. |
| `forbidden_dependencies` | list of FQCN | `[]` | Symbols a test at this level must not depend on. Matches a class or a namespace prefix. |

### Counters

- `phpunit` — counts public methods named `test*` or marked `#[Test]`, in `*.php` files.
  Also extracts the classes each test depends on (used for `forbidden_dependencies` and,
  in diff mode, for matching tests to changed classes).
- `gherkin` — counts `Scenario:` / `Scenario Outline:` in `*.feature` files. Gherkin
  files declare no PHP classes, so dependency-based checks do not apply to them.

### `forbidden_dependencies` — the "impure test" check

A unit test that depends on an integration-only symbol (an `EntityManager`, a
`KernelTestCase`, a database-reset trait) is really an integration test in the wrong
folder. List those symbols under `unit` and Pyra reports any unit test that pulls one in.

Matching is exact-class or namespace-prefix: `Doctrine\ORM` matches
`Doctrine\ORM\EntityManagerInterface`. Imports are resolved, so the style (`use`, grouped
`use`, alias, inline FQCN) does not matter.

## `enforce_ordering`

```yaml
pyra:
    enforce_ordering: true   # default
```

When true, Pyra checks the pyramid is not inverted: `unit >= integration >= e2e`. Levels
counted in different units (PHPUnit methods vs Gherkin scenarios) are **not** compared —
they are not the same kind of number.

## Percentages and counting units

`min_percentage` / `max_percentage` are a share **within a single counting unit**. PHPUnit
methods and Gherkin scenarios are never summed into one denominator, so a `unit` level
(PHPUnit) and an `e2e` level (Gherkin) each get their own 100%.

## `diff` (used by `pyra diff`)

```yaml
pyra:
    diff:
        base: origin/main
        sources:
            - path: src/Domain
              expect: [unit]
            - path: src/Application
              expect: [unit, integration]
        ignore:
            - migrations
            - config
```

| Key | Type | Default | Meaning |
|-----|------|---------|---------|
| `base` | git ref | `HEAD~1` | Ref to diff against. Overridable with `--base`. |
| `sources` | list | `[]` | Production areas and the test levels they should have. |
| `sources[].path` | path prefix | — | Matches changed files under this directory. |
| `sources[].expect` | list of levels | — | Levels a changed class in this area should be tested at. |
| `ignore` | list of path prefixes | `[]` | Changes here never require tests (migrations, config, generated code). |

For each changed class under a `sources` path, Pyra checks the whole test suite for a
test referencing it at each expected level. A missing level is a gate violation. With
`--coverage <clover|cobertura.xml>` it also reports how much of the changed lines are
executed.

## Full examples

### Symfony

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
                - Symfony\Bundle\FrameworkBundle\Test\WebTestCase
                - Zenstruck\Foundry\Test\ResetDatabase
        integration:
            paths: [tests/Integration]
            max_percentage: 35
        e2e:
            paths: [features]
            counter: gherkin
            max_percentage: 15
    diff:
        base: origin/main
        sources:
            - path: src/Domain
              expect: [unit]
            - path: src/Application
              expect: [unit, integration]
        ignore:
            - migrations
            - config
```

### Laravel (PHPUnit)

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
            - path: app/Http
              expect: [integration]
```

> Laravel projects whose tests are written in Pest are not yet counted — Pyra currently
> understands PHPUnit and Gherkin only.

## A note on test layout

Pyra classifies tests by **directory**: you map folders to levels. A single folder that
mixes unit and integration tests cannot be split — projects organised by component rather
than by level are only as precise as the paths you give. Per-test level markers may be
added later.
