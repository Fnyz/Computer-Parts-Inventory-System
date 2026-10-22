<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small rule-based validator.
 *
 *   $v = Validator::make($data, ['name' => 'required|max:150', 'price' => 'required|money|min:0']);
 *   if ($v->fails()) { ... $v->errors() ... }
 *
 * Rules: required, max:N / minlen:N (characters), min:N (number), int, money, email, in:a,b,c, regex:/…/
 */
final class Validator
{
    private array $errors = [];

    private function __construct(private readonly array $data, private readonly array $labels)
    {
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        $validator = new self($data, $labels);
        foreach ($rules as $field => $fieldRules) {
            $validator->check($field, is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules));
        }

        return $validator;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> field => message */
    public function errors(): array
    {
        return $this->errors;
    }

    private function check(string $field, array $rules): void
    {
        $value = $this->data[$field] ?? null;
        $value = is_string($value) ? trim($value) : $value;
        $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
        $empty = $value === null || $value === '' || $value === [];

        foreach ($rules as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            if ($name === 'required') {
                if ($empty) {
                    $this->errors[$field] = "Enter the {$this->lower($label)}.";

                    return;
                }
                continue;
            }
            if ($empty) {
                continue; // optional and empty: nothing else to check
            }
            $message = match ($name) {
                'max' => mb_strlen((string) $value) > (int) $arg ? "{$label} can be at most {$arg} characters." : null,
                'minlen' => mb_strlen((string) $value) < (int) $arg ? "{$label} must be at least {$arg} characters." : null,
                'min' => !is_numeric(str_replace(',', '', (string) $value)) || (float) str_replace(',', '', (string) $value) < (float) $arg
                    ? "{$label} must be {$arg} or more." : null,
                'int' => filter_var($value, FILTER_VALIDATE_INT) === false ? "{$label} must be a whole number." : null,
                'money' => to_cents($value) === null ? "{$label} must be an amount like 1250 or 1,250.50." : null,
                'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false ? 'Enter a valid email address.' : null,
                'in' => !in_array((string) $value, explode(',', (string) $arg), true) ? "Choose a valid {$this->lower($label)}." : null,
                'regex' => !preg_match((string) $arg, (string) $value) ? "{$label} has an invalid format." : null,
                default => throw new \InvalidArgumentException("Unknown rule {$name}"),
            };
            if ($message !== null) {
                $this->errors[$field] = $message;

                return;
            }
        }
    }

    private function lower(string $label): string
    {
        return mb_strtolower(mb_substr($label, 0, 1)) . mb_substr($label, 1);
    }
}
