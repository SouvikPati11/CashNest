<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;

/**
 * Rule-based input validator.
 *
 * Validates an associative array against a map of `field => "rule1|rule2:arg"`.
 * Designed to be dependency-free and predictable. On failure it can either
 * return the error bag or throw a ValidationException that the global handler
 * renders into the standard error envelope.
 *
 * Supported rules: required, nullable, string, integer, numeric, boolean,
 * array, email, url, min, max, between, in, not_in, regex, digits, same,
 * different, confirmed, alpha_num.
 */
final class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed>  $data  Input to validate.
     * @param array<string, string> $rules field => pipe-delimited rules.
     * @param array<string, string> $messages Optional custom "field.rule" messages.
     */
    public function __construct(
        private array $data,
        private array $rules,
        private array $messages = []
    ) {
    }

    /**
     * Convenience factory + immediate validation.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        $validator = new self($data, $rules, $messages);
        $validator->validate();

        return $validator;
    }

    /**
     * Run all rules, collecting errors.
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules    = explode('|', $ruleString);
            $value    = $this->data[$field] ?? null;
            $present  = array_key_exists($field, $this->data);
            $nullable = in_array('nullable', $rules, true);

            // Skip optional, absent/empty fields (unless required).
            if (!in_array('required', $rules, true) && ($value === null || $value === '')) {
                if ($nullable || !$present) {
                    continue;
                }
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable' || $rule === '') {
                    continue;
                }

                [$name, $arg] = $this->parseRule($rule);
                $this->applyRule($field, $value, $name, $arg);
            }
        }

        return $this->errors === [];
    }

    /**
     * Validate and throw on failure.
     *
     * @throws ValidationException
     */
    public function validateOrFail(): void
    {
        if (!$this->validate()) {
            throw new ValidationException($this->errors);
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Return only the validated (rule-covered) fields present in the input.
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        $result = [];

        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $result[$field] = $this->data[$field];
            }
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $arg] = explode(':', $rule, 2);
            return [$name, $arg];
        }

        return [$rule, null];
    }

    /**
     * Apply a single rule to a field value.
     */
    private function applyRule(string $field, mixed $value, string $rule, ?string $arg): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                    $this->addError($field, $rule, sprintf('The %s field is required.', $field));
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, $rule, sprintf('The %s must be a string.', $field));
                }
                break;

            case 'integer':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, $rule, sprintf('The %s must be an integer.', $field));
                }
                break;

            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->addError($field, $rule, sprintf('The %s must be numeric.', $field));
                }
                break;

            case 'boolean':
                if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
                    $this->addError($field, $rule, sprintf('The %s must be true or false.', $field));
                }
                break;

            case 'array':
                if ($value !== null && !is_array($value)) {
                    $this->addError($field, $rule, sprintf('The %s must be an array.', $field));
                }
                break;

            case 'email':
                if ($value !== null && filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addError($field, $rule, sprintf('The %s must be a valid email address.', $field));
                }
                break;

            case 'url':
                if ($value !== null && filter_var((string) $value, FILTER_VALIDATE_URL) === false) {
                    $this->addError($field, $rule, sprintf('The %s must be a valid URL.', $field));
                }
                break;

            case 'min':
                if ($value !== null && $this->size($value) < (float) $arg) {
                    $this->addError($field, $rule, sprintf('The %s must be at least %s.', $field, $arg));
                }
                break;

            case 'max':
                if ($value !== null && $this->size($value) > (float) $arg) {
                    $this->addError($field, $rule, sprintf('The %s may not be greater than %s.', $field, $arg));
                }
                break;

            case 'between':
                $bounds = explode(',', (string) $arg);
                $size   = $this->size($value);
                $outOfRange = count($bounds) === 2
                    && ($size < (float) $bounds[0] || $size > (float) $bounds[1]);
                if ($value !== null && $outOfRange) {
                    $this->addError($field, $rule, sprintf('The %s must be between %s.', $field, $arg));
                }
                break;

            case 'in':
                $options = explode(',', (string) $arg);
                if ($value !== null && !in_array((string) $value, $options, true)) {
                    $this->addError($field, $rule, sprintf('The selected %s is invalid.', $field));
                }
                break;

            case 'not_in':
                $options = explode(',', (string) $arg);
                if ($value !== null && in_array((string) $value, $options, true)) {
                    $this->addError($field, $rule, sprintf('The selected %s is invalid.', $field));
                }
                break;

            case 'regex':
                if ($value !== null && $arg !== null && @preg_match($arg, (string) $value) !== 1) {
                    $this->addError($field, $rule, sprintf('The %s format is invalid.', $field));
                }
                break;

            case 'digits':
                if ($value !== null && !preg_match('/^\d{' . (int) $arg . '}$/', (string) $value)) {
                    $this->addError($field, $rule, sprintf('The %s must be %s digits.', $field, $arg));
                }
                break;

            case 'alpha_num':
                if ($value !== null && !ctype_alnum((string) $value)) {
                    $this->addError($field, $rule, sprintf('The %s may only contain letters and numbers.', $field));
                }
                break;

            case 'same':
                if ($arg !== null && ($this->data[$arg] ?? null) !== $value) {
                    $this->addError($field, $rule, sprintf('The %s and %s must match.', $field, $arg));
                }
                break;

            case 'different':
                if ($arg !== null && ($this->data[$arg] ?? null) === $value) {
                    $this->addError($field, $rule, sprintf('The %s and %s must differ.', $field, $arg));
                }
                break;

            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    $this->addError($field, $rule, sprintf('The %s confirmation does not match.', $field));
                }
                break;

            default:
                // Unknown rule: ignore silently to stay forward-compatible.
                break;
        }
    }

    /**
     * Measure a value for min/max/between: string length, number value, or count.
     */
    private function size(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_array($value)) {
            return (float) count($value);
        }

        return (float) mb_strlen((string) $value);
    }

    /**
     * Record an error, honoring a custom message when provided.
     */
    private function addError(string $field, string $rule, string $default): void
    {
        $custom = $this->messages[$field . '.' . $rule] ?? null;
        $this->errors[$field][] = $custom ?? $default;
    }
}
