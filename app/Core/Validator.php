<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use \Exception; 

class Validator
{
    private array $errors = [];
    private array $data = [];

    /**
     * Initializes the Validator with the data to be validated and sanitized.
     * @param array $data The input data (e.g., $_POST).
     */
    public function __construct(array $data)
    {
        // Sanitize all input data immediately by trimming whitespace.
        $this->data = array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $data);
    }

    /**
     * Defines and executes a set of validation rules against the data.
     * @param array $rules Associative array where key is the field name and value is the pipe-separated rules string.
     * @return self Allows method chaining.
     */
    public function validate(array $rules): self
    {
        $this->errors = []; // Reset errors

        foreach ($rules as $field => $ruleString) {
            // Get the field value (defaults to null if not present)
            $value = $this->data[$field] ?? null;
            $rulesList = explode('|', $ruleString);
            
            // If the field is missing and not required, skip all checks for it.
            if ($value === null && !str_contains($ruleString, 'required')) {
                 continue;
            }

            foreach ($rulesList as $rule) {
                // Stop checking a field once an error is found (to avoid message clutter)
                if (isset($this->errors[$field])) {
                    break;
                }
                $this->applyRule($field, $rule);
            }
        }

        return $this;
    }
    
    /**
     * Applies a specific validation rule to a field.
     */
    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;
        $params = [];

        // Check for parameters (e.g., min:8, in:1,2,3)
        if (str_contains($rule, ':')) {
            [$rule, $paramString] = explode(':', $rule, 2);
            $params = explode(',', $paramString);
        }
        
        // --- Core Rules ---
        switch ($rule) {
            case 'required':
                // Check for null, empty string, or empty array. Allows '0' or 0.
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
                break;
            
            case 'email':
                // Only validate if a value is present (required rule handles absence)
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a valid email address.");
                }
                break;
                
            case 'min':
                $minLength = (int)($params[0] ?? 0);
                if (isset($value) && is_string($value) && strlen($value) < $minLength) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$minLength} characters long.");
                }
                break;
                
            case 'numeric':
                 if (isset($value) && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a valid number.");
                }
                break;
            
            case 'int':
                if (isset($value) && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a whole number.");
                }
                break;
        }
    }
    
    private function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /**
     * Checks if validation failed.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Retrieves all validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * Retrieves a single, type-casted and cleaned data element.
     * @param string $field The field name.
     * @param string $type The desired cast type ('int', 'float', 'string', 'bool').
     * @param mixed $default The default value if the field is missing.
     * @return mixed The type-casted value.
     */
    public function get(string $field, string $type = 'string', mixed $default = null): mixed
    {
        $value = $this->data[$field] ?? $default;

        if ($value === null) {
            return $default;
        }

        // Safely cast the value
        return match ($type) {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($value === 1 || $value === '1'),
            default => (string)$value,
        };
    }
    
    /**
     * Returns the cleaned/validated data array.
     */
    public function all(): array
    {
        return $this->data;
    }
}