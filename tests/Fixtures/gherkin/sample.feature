Feature: Sample feature

  Scenario: First scenario
    Given something
    When it happens
    Then a result

  Scenario: Second scenario
    Given another thing
    Then another result

  Scenario Outline: Parameterised scenario
    Given <input>
    Then <output>

    Examples:
      | input | output |
      | a     | b      |
      | c     | d      |
