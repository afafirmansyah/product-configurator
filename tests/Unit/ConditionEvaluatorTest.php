<?php

use App\Enums\RuleOperator;
use App\Services\Rules\ConditionEvaluator;

/**
 * Build a condition object matching the shape the evaluator reads.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeCondition(array $overrides = []): object
{
    return (object) array_merge([
        'attribute_id' => 1,
        'operator' => RuleOperator::Equals->value,
        'value' => '10',
        'logic' => 'AND',
        'group' => 0,
    ], $overrides);
}

beforeEach(function () {
    $this->evaluator = new ConditionEvaluator;
});

test('empty conditions always pass', function () {
    expect($this->evaluator->passes([], 'AND', []))->toBeTrue();
});

test('AND within a group requires every condition to be true', function () {
    $conditions = [
        makeCondition(['attribute_id' => 1, 'value' => '10', 'logic' => 'AND', 'group' => 0]),
        makeCondition(['attribute_id' => 2, 'value' => '20', 'logic' => 'AND', 'group' => 0]),
    ];

    expect($this->evaluator->passes($conditions, 'AND', [1 => '10', 2 => '20']))->toBeTrue();
    expect($this->evaluator->passes($conditions, 'AND', [1 => '10', 2 => '99']))->toBeFalse();
});

test('OR within a group passes when any condition is true', function () {
    $conditions = [
        makeCondition(['attribute_id' => 1, 'value' => '10', 'logic' => 'OR', 'group' => 0]),
        makeCondition(['attribute_id' => 2, 'value' => '20', 'logic' => 'OR', 'group' => 0]),
    ];

    expect($this->evaluator->passes($conditions, 'AND', [1 => '99', 2 => '20']))->toBeTrue();
    expect($this->evaluator->passes($conditions, 'AND', [1 => '99', 2 => '99']))->toBeFalse();
});

test('OR across groups passes when any group is true', function () {
    $conditions = [
        makeCondition(['attribute_id' => 1, 'value' => '10', 'logic' => 'AND', 'group' => 0]),
        makeCondition(['attribute_id' => 2, 'value' => '20', 'logic' => 'AND', 'group' => 1]),
    ];

    // Group 0 fails, group 1 passes -> OR across groups is true.
    expect($this->evaluator->passes($conditions, 'OR', [1 => '99', 2 => '20']))->toBeTrue();
    // Both groups fail.
    expect($this->evaluator->passes($conditions, 'OR', [1 => '99', 2 => '99']))->toBeFalse();
    // AND across groups fails because group 0 fails.
    expect($this->evaluator->passes($conditions, 'AND', [1 => '99', 2 => '20']))->toBeFalse();
});
