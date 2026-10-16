<?php

namespace App\Services\Bom;

use App\Exceptions\InvalidQuantityFormulaException;

/**
 * Safe arithmetic evaluator for BOM quantity formulas such as "{length} / 2 + 1".
 *
 * The formula is tokenized and parsed by a small recursive-descent parser into an
 * expression tree; nothing is ever handed to eval() or the PHP engine. Grammar:
 *
 *   expr   := term (('+' | '-') term)*
 *   term   := factor (('*' | '/') factor)*
 *   factor := ('+' | '-') factor | number | '{' code '}' | '(' expr ')'
 *
 * number matches \d+(\.\d+)?, code matches [A-Za-z0-9_-]+ and refers to an
 * attribute code. Whitespace is ignored; any other character is rejected with an
 * InvalidQuantityFormulaException.
 *
 * @phpstan-type FormulaNode array{0: 'num', 1: float}|array{0: 'var', 1: string}|array{0: 'neg', 1: mixed}|array{0: 'bin', 1: string, 2: mixed, 3: mixed}
 * @phpstan-type FormulaToken array{type: 'number'|'variable'|'operator'|'open'|'close', value: string, position: int}
 */
class QuantityFormula
{
    /**
     * List the attribute codes referenced by a formula, in order of first use.
     *
     * @return list<string>
     *
     * @throws InvalidQuantityFormulaException When the formula is not valid syntax.
     */
    public function variables(string $formula): array
    {
        $codes = [];

        $this->collectVariables($this->parse($formula), $codes);

        return array_values(array_unique($codes));
    }

    /**
     * Check a formula's syntax and that it only references the allowed codes.
     *
     * @param  list<string>  $allowedCodes
     * @return list<string> Human-readable error messages; empty when valid.
     */
    public function validate(string $formula, array $allowedCodes): array
    {
        try {
            $codes = $this->variables($formula);
        } catch (InvalidQuantityFormulaException $exception) {
            return [$exception->getMessage()];
        }

        $errors = [];

        foreach ($codes as $code) {
            if (! in_array($code, $allowedCodes, true)) {
                $errors[] = "Unknown attribute code {{$code}}.";
            }
        }

        return $errors;
    }

    /**
     * Evaluate a formula with the given numeric variable values keyed by code.
     *
     * @param  array<string, float|int|string>  $variables
     *
     * @throws InvalidQuantityFormulaException On syntax errors, missing or
     *                                         non-numeric variables, division by zero or a non-finite result.
     */
    public function evaluate(string $formula, array $variables): float
    {
        $result = $this->evaluateNode($this->parse($formula), $variables);

        if (! is_finite($result)) {
            throw new InvalidQuantityFormulaException('Formula result is not a finite number.');
        }

        return $result;
    }

    /**
     * @return FormulaNode
     */
    private function parse(string $formula): array
    {
        $tokens = $this->tokenize($formula);

        if ($tokens === []) {
            throw new InvalidQuantityFormulaException('Formula is empty.');
        }

        $position = 0;
        $node = $this->parseExpression($tokens, $position);

        if ($position < count($tokens)) {
            $token = $tokens[$position];

            throw new InvalidQuantityFormulaException("Unexpected '{$token['value']}' at position {$token['position']}.");
        }

        return $node;
    }

    /**
     * @return list<FormulaToken>
     */
    private function tokenize(string $formula): array
    {
        $tokens = [];
        $length = strlen($formula);
        $offset = 0;

        while ($offset < $length) {
            $character = $formula[$offset];
            $position = $offset + 1;

            if (ctype_space($character)) {
                $offset++;

                continue;
            }

            if (preg_match('/\G\d+(?:\.\d+)?/', $formula, $match, 0, $offset) === 1) {
                $tokens[] = ['type' => 'number', 'value' => $match[0], 'position' => $position];
                $offset += strlen($match[0]);

                continue;
            }

            if ($character === '{') {
                if (preg_match('/\G\{([A-Za-z0-9_\-]+)\}/', $formula, $match, 0, $offset) !== 1) {
                    throw new InvalidQuantityFormulaException("Invalid attribute reference at position {$position}; use {code}.");
                }

                $tokens[] = ['type' => 'variable', 'value' => $match[1], 'position' => $position];
                $offset += strlen($match[0]);

                continue;
            }

            $type = match ($character) {
                '+', '-', '*', '/' => 'operator',
                '(' => 'open',
                ')' => 'close',
                default => throw new InvalidQuantityFormulaException("Unexpected character '{$character}' at position {$position}."),
            };

            $tokens[] = ['type' => $type, 'value' => $character, 'position' => $position];
            $offset++;
        }

        return $tokens;
    }

    /**
     * @param  list<FormulaToken>  $tokens
     * @return FormulaNode
     */
    private function parseExpression(array $tokens, int &$position): array
    {
        $node = $this->parseTerm($tokens, $position);

        while ($this->peekOperator($tokens, $position, ['+', '-'])) {
            $operator = $tokens[$position++]['value'];
            $node = ['bin', $operator, $node, $this->parseTerm($tokens, $position)];
        }

        return $node;
    }

    /**
     * @param  list<FormulaToken>  $tokens
     * @return FormulaNode
     */
    private function parseTerm(array $tokens, int &$position): array
    {
        $node = $this->parseFactor($tokens, $position);

        while ($this->peekOperator($tokens, $position, ['*', '/'])) {
            $operator = $tokens[$position++]['value'];
            $node = ['bin', $operator, $node, $this->parseFactor($tokens, $position)];
        }

        return $node;
    }

    /**
     * @param  list<FormulaToken>  $tokens
     * @return FormulaNode
     */
    private function parseFactor(array $tokens, int &$position): array
    {
        $token = $tokens[$position] ?? null;

        if ($token === null) {
            throw new InvalidQuantityFormulaException('Formula ends unexpectedly.');
        }

        $position++;

        if ($token['type'] === 'operator' && in_array($token['value'], ['+', '-'], true)) {
            $operand = $this->parseFactor($tokens, $position);

            return $token['value'] === '-' ? ['neg', $operand] : $operand;
        }

        if ($token['type'] === 'number') {
            return ['num', (float) $token['value']];
        }

        if ($token['type'] === 'variable') {
            return ['var', $token['value']];
        }

        if ($token['type'] === 'open') {
            $node = $this->parseExpression($tokens, $position);
            $closing = $tokens[$position] ?? null;

            if ($closing === null || $closing['type'] !== 'close') {
                throw new InvalidQuantityFormulaException("Missing closing parenthesis for '(' at position {$token['position']}.");
            }

            $position++;

            return $node;
        }

        throw new InvalidQuantityFormulaException("Unexpected '{$token['value']}' at position {$token['position']}.");
    }

    /**
     * @param  list<FormulaToken>  $tokens
     * @param  list<string>  $operators
     */
    private function peekOperator(array $tokens, int $position, array $operators): bool
    {
        $token = $tokens[$position] ?? null;

        return $token !== null && $token['type'] === 'operator' && in_array($token['value'], $operators, true);
    }

    /**
     * @param  FormulaNode  $node
     * @param  list<string>  $codes
     */
    private function collectVariables(array $node, array &$codes): void
    {
        match ($node[0]) {
            'var' => $codes[] = $node[1],
            'neg' => $this->collectVariables($node[1], $codes),
            'bin' => [$this->collectVariables($node[2], $codes), $this->collectVariables($node[3], $codes)],
            default => null,
        };
    }

    /**
     * @param  FormulaNode  $node
     * @param  array<string, float|int|string>  $variables
     */
    private function evaluateNode(array $node, array $variables): float
    {
        return match ($node[0]) {
            'num' => $node[1],
            'var' => $this->variableValue($node[1], $variables),
            'neg' => -$this->evaluateNode($node[1], $variables),
            'bin' => $this->applyOperator(
                $node[1],
                $this->evaluateNode($node[2], $variables),
                $this->evaluateNode($node[3], $variables),
            ),
        };
    }

    /**
     * @param  array<string, float|int|string>  $variables
     */
    private function variableValue(string $code, array $variables): float
    {
        if (! array_key_exists($code, $variables)) {
            throw new InvalidQuantityFormulaException("Missing value for {{$code}}.");
        }

        if (! is_numeric($variables[$code])) {
            throw new InvalidQuantityFormulaException("Value for {{$code}} is not numeric.");
        }

        return (float) $variables[$code];
    }

    private function applyOperator(string $operator, float $left, float $right): float
    {
        if ($operator === '/' && $right == 0.0) {
            throw new InvalidQuantityFormulaException('Division by zero');
        }

        return match ($operator) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $left / $right,
        };
    }
}
