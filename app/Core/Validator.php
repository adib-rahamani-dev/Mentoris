<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                [$name, $parameters] = $this->parseRule($rule);
                if ($name !== 'required' && ($value === null || $value === '')) {
                    continue;
                }
                if (!$this->passes($name, $value, $parameters, $data)) {
                    $this->errors[$field][] = $this->message($field, $name, $parameters);
                }
            }
        }

        return $this->errors === [];
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }

    private function parseRule(string|callable $rule): array
    {
        if (is_callable($rule)) {
            return [$rule, []];
        }
        [$name, $parameterString] = array_pad(explode(':', $rule, 2), 2, '');
        return [$name, $parameterString === '' ? [] : explode(',', $parameterString)];
    }

    private function passes(string|callable $rule, mixed $value, array $parameters, array $data): bool
    {
        if (is_callable($rule)) {
            return (bool) $rule($value, $data);
        }

        return match ($rule) {
            'required' => $value !== null && $value !== '' && (!is_array($value) || $value !== []),
            'string' => is_string($value),
            'array' => is_array($value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric' => is_numeric($value),
            'boolean' => in_array($value, [true, false, 0, 1, '0', '1'], true),
            'min' => $this->size($value) >= (int) ($parameters[0] ?? 0),
            'max' => $this->size($value) <= (int) ($parameters[0] ?? PHP_INT_MAX),
            'between' => $this->size($value) >= (int) ($parameters[0] ?? 0) && $this->size($value) <= (int) ($parameters[1] ?? PHP_INT_MAX),
            'same' => $value === ($data[$parameters[0] ?? ''] ?? null),
            'in' => in_array((string) $value, $parameters, true),
            'regex' => isset($parameters[0]) && preg_match($parameters[0], (string) $value) === 1,
            default => throw new \InvalidArgumentException("Unknown validation rule: {$rule}"),
        };
    }

    private function size(mixed $value): int|float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_array($value)) {
            return count($value);
        }
        return mb_strlen((string) $value);
    }

    private function message(string $field, string|callable $rule, array $parameters): string
    {
        if (is_callable($rule)) {
            return "The {$field} field is invalid.";
        }
        $limit = $parameters[0] ?? '';
        return match ($rule) {
            'required' => "The {$field} field is required.",
            'email' => "The {$field} field must be a valid email address.",
            'min' => "The {$field} field must be at least {$limit}.",
            'max' => "The {$field} field may not be greater than {$limit}.",
            'same' => "The {$field} field must match {$limit}.",
            default => "The {$field} field is invalid ({$rule}).",
        };
    }
}
