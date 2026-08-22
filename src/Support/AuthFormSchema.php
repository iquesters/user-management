<?php

namespace Iquesters\UserManagement\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bridges this package's auth forms to iquesters/user-interface's FormSchema
 * engine (form_schemas table + DynamicFormSchema::toRules()) when that
 * package is installed, so one seeded schema drives both the schema-rendered
 * form column and server-side validation. Falls back to null when
 * user-interface isn't installed or the schema hasn't been seeded, so every
 * caller can keep its original hardcoded rules as a fallback.
 */
class AuthFormSchema
{
    /**
     * Fetch a seeded FormSchema's raw schema array by slug.
     */
    public static function schema(string $slug): ?array
    {
        if (! Schema::hasTable('form_schemas')) {
            return null;
        }

        $raw = DB::table('form_schemas')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->value('schema');

        if (! $raw) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Build a Laravel Password rule from a field's own declarative flags,
     * rather than a single hardcoded policy shared by every password field.
     * Every flag is independently optional, so one form can require more
     * than another (e.g. login's password has none of these — it only needs
     * to match, not satisfy a policy):
     *   - 'minLength' => 10                 minimum length (defaults to 8,
     *                                        same field key client-side
     *                                        validation already uses)
     *   - 'password_mixed_case' => true     require upper AND lower case
     *                                        (Laravel's Password rule only
     *                                        supports requiring both together,
     *                                        not one independently of the other)
     *   - 'password_numbers' => true        require at least one digit
     *   - 'password_symbols' => true        require at least one symbol
     */
    protected static function buildPasswordRule(array $field): \Illuminate\Validation\Rules\Password
    {
        $rule = \Illuminate\Validation\Rules\Password::min($field['minLength'] ?? 8);

        if (! empty($field['password_mixed_case'])) {
            $rule->mixedCase();
        }

        if (! empty($field['password_numbers'])) {
            $rule->numbers();
        }

        if (! empty($field['password_symbols'])) {
            $rule->symbols();
        }

        return $rule;
    }

    /**
     * Build Laravel validation rules from a seeded FormSchema by slug.
     *
     * Supports field flags DynamicFormSchema::toRules() doesn't know about,
     * kept local here rather than in the shared engine:
     *   - 'confirmed' => true            adds the 'confirmed' rule
     *   - 'unique_table' => 'users'      adds 'unique:users,<unique_column|id>'
     *     'unique_column' => 'email'
     *   - 'unique_except_id' => true     appends ',{id}' to the unique rule,
     *     reading the ignored id from $context['id'] (for update forms)
     *   - 'password_policy' => true      builds a Password rule from this
     *     field's own minLength/password_mixed_case/password_numbers/
     *     password_symbols flags (see buildPasswordRule()) — a schema omits
     *     this for a field where complexity rules don't apply.
     *
     * @param array<string, mixed> $context values available to unique_except_id
     */
    public static function rules(string $slug, array $context = []): ?array
    {
        if (! class_exists(\Iquesters\UserInterface\Support\DynamicFormSchema::class)) {
            return null;
        }

        $schema = static::schema($slug);

        if ($schema === null) {
            return null;
        }

        $rules = \Iquesters\UserInterface\Support\DynamicFormSchema::toRules($schema);

        foreach ($schema['fields'] ?? [] as $field) {
            $fieldId = $field['id'] ?? null;

            if (! $fieldId || ! isset($rules[$fieldId])) {
                continue;
            }

            if (! empty($field['confirmed'])) {
                $rules[$fieldId][] = 'confirmed';
            }

            if (! empty($field['unique_table'])) {
                $column = $field['unique_column'] ?? $fieldId;
                $uniqueRule = "unique:{$field['unique_table']},{$column}";

                if (! empty($field['unique_except_id']) && ! empty($context['id'])) {
                    $uniqueRule .= ',' . $context['id'];
                }

                $rules[$fieldId][] = $uniqueRule;
            }

            if (! empty($field['password_policy'])) {
                $rules[$fieldId][] = static::buildPasswordRule($field);
            }
        }

        return $rules;
    }

    /**
     * Build Laravel validation messages from a seeded FormSchema by slug.
     */
    public static function messages(string $slug): array
    {
        if (! class_exists(\Iquesters\UserInterface\Support\DynamicFormSchema::class)) {
            return [];
        }

        $schema = static::schema($slug);

        return $schema === null
            ? []
            : \Iquesters\UserInterface\Support\DynamicFormSchema::toMessages($schema);
    }
}
