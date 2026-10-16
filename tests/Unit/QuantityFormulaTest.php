<?php

use App\Exceptions\InvalidQuantityFormulaException;
use App\Services\Bom\QuantityFormula;

beforeEach(function () {
    $this->formula = new QuantityFormula;
});

test('multiplication and division bind tighter than addition', function () {
    expect($this->formula->evaluate('{length} / 2 + 1', ['length' => 10.0]))->toBe(6.0);
    expect($this->formula->evaluate('1 + 2 * 3 - 4 / 2', []))->toBe(5.0);
});

test('parentheses override precedence', function () {
    expect($this->formula->evaluate('({length} + 2) / 4', ['length' => 10.0]))->toBe(3.0);
    expect($this->formula->evaluate('2 * (3 + (4 - 1))', []))->toBe(12.0);
});

test('unary minus and plus apply to the following factor', function () {
    expect($this->formula->evaluate('-{a} + 5', ['a' => 2.0]))->toBe(3.0);
    expect($this->formula->evaluate('10 - -2', []))->toBe(12.0);
    expect($this->formula->evaluate('+1.5 * 2', []))->toBe(3.0);
});

test('codes may contain letters, digits, underscores and hyphens', function () {
    expect($this->formula->variables('{panel_width} * {rows-2} + {panel_width}'))->toBe(['panel_width', 'rows-2']);
    expect($this->formula->evaluate('{panel_width} * {rows-2}', ['panel_width' => 1.5, 'rows-2' => 4]))->toBe(6.0);
});

test('anything outside the grammar is rejected', function (string $formula) {
    expect(fn () => $this->formula->variables($formula))->toThrow(InvalidQuantityFormulaException::class);
    expect($this->formula->validate($formula, ['length']))->not->toBe([]);
})->with([
    'function call' => "system('x')",
    'power operator' => '2 ** 3',
    'statement separator' => '1; 2',
    'php variable' => '$a',
    'dangling operator' => '{length} +* 2',
    'unbalanced parenthesis' => '({length} + 1',
    'empty braces' => '{} + 1',
    'empty formula' => '   ',
    'trailing dot' => '2.',
]);

test('validate reports codes that are not allowed', function () {
    expect($this->formula->validate('{length} * {width}', ['length', 'height']))
        ->toBe(['Unknown attribute code {width}.']);
    expect($this->formula->validate('{length} * 2', ['length']))->toBe([]);
});

test('division by zero throws', function () {
    $this->formula->evaluate('{a} / {b}', ['a' => 1.0, 'b' => 0.0]);
})->throws(InvalidQuantityFormulaException::class, 'Division by zero');

test('a missing or non-numeric variable throws', function () {
    expect(fn () => $this->formula->evaluate('{a} + 1', []))->toThrow(InvalidQuantityFormulaException::class);
    expect(fn () => $this->formula->evaluate('{a} + 1', ['a' => 'abc']))->toThrow(InvalidQuantityFormulaException::class);
});
