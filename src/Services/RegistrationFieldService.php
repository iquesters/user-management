<?php

namespace Iquesters\UserManagement\Services;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\UserManagement\Config\RegistrationFieldConf;

class RegistrationFieldService
{
    /**
     * @return array<int, RegistrationFieldConf>
     */
    public function enabledFields(): array
    {
        $fields = ConfProvider::from(Module::USER_MGMT)->registration_fields->fields ?? [];

        return array_values(array_filter($fields, function ($field) {
            // Read into plain locals first — isset()/empty() on a magic
            // property (via __get() with no matching __isset()) always
            // report "not set" regardless of the real value, so check the
            // copied value instead of the property expression directly.
            $enabled = $field->enabled ?? false;
            $identifier = $field->identifier;

            return $enabled && !empty($identifier);
        }));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->enabledFields() as $field) {
            $ruleSet = [$field->required ? 'required' : 'nullable'];
            $fieldType = strtolower((string) ($field->field_type ?? 'text'));

            switch ($fieldType) {
                case 'email':
                    $ruleSet[] = 'string';
                    $ruleSet[] = 'email';
                    $ruleSet[] = 'max:255';
                    break;
                case 'date':
                    $ruleSet[] = 'date';
                    break;
                case 'number':
                    $ruleSet[] = 'numeric';
                    break;
                default:
                    $ruleSet[] = 'string';
                    $ruleSet[] = 'max:255';
                    break;
            }

            $rules['fields.' . $field->identifier] = $ruleSet;
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $submittedFields
     * @return array<string, string>
     */
    public function metaPayload(array $submittedFields): array
    {
        $meta = [];

        foreach ($this->enabledFields() as $field) {
            $identifier = (string) $field->identifier;
            $value = $submittedFields[$identifier] ?? $field->default_value ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $meta[$identifier] = (string) $value;
        }

        return $meta;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function presentableFields(): array
    {
        return array_map(function ($field) {
            return [
                'identifier' => $field->identifier,
                'label' => $field->label ?: ucfirst(str_replace('_', ' ', (string) $field->identifier)),
                'required' => (bool) ($field->required ?? false),
                'field_type' => $field->field_type ?? 'text',
                'default_value' => $field->default_value,
            ];
        }, $this->enabledFields());
    }
}
