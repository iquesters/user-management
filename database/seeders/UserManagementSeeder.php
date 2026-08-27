<?php

namespace Iquesters\UserManagement\Database\Seeders;

use Iquesters\Foundation\Database\Seeders\BaseSeeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeder for the User Management module
 *
 * Extends BaseSeeder and provides:
 *   - Module configuration
 *   - Entity definitions with fields and metadata
 *   - Custom logic for test user creation
 */
class UserManagementSeeder extends BaseSeeder
{
    /**
     * Module basic information
     */
    protected string $moduleName = 'user-management';
    protected string $description = 'User Management Module';

    /**
     * Module metadata
     */
    protected array $metas = [
        'module_icon' => 'fas fa-user-gear',
        'module_sidebar_menu' => [
            /*
            |-------------------------------------------------
            | Users
            |-------------------------------------------------
            */
            [
                "icon" => "fas fa-users",
                "label" => "Users",
                "route" => "ui.list",
                "table_schema" => [
                    "slug" => "user-table",
                    "name" => "Users",
                    "description" => "Datatable schema for users",
                    "schema" => [
                        "entity" => "users",
                        "dt-options" => [
                            "columns" => [
                                ["data" => "id", "title" => "ID", "visible" => true],
                                [
                                    "data" => "name",
                                    "title" => "User Name",
                                    "visible" => true,
                                    "link" => true,
                                    "form-schema-uid" => "user-details"
                                ],
                                [
                                    "data" => "email",
                                    "title" => "Email",
                                    "visible" => true
                                ],
                                [
                                    "data" => "meta.registered_at",
                                    "title" => "Registered At",
                                    "visible" => true
                                ],
                                [
                                    "data" => "meta.login_ip_address",
                                    "title" => "Login IP",
                                    "visible" => true
                                ],
                            ],
                            "options" => [
                                "pageLength" => 10,
                                "order" => [[0, "desc"]],
                                "responsive" => true
                            ]
                        ],
                        "default_view_mode" => "inbox"
                    ]
                ]
            ],
            /*
            |-------------------------------------------------
            | Roles
            |-------------------------------------------------
            */
            [
                "icon" => "fas fa-user-shield",
                "label" => "Roles",
                "route" => "ui.list",
                "table_schema" => [
                    "slug" => "role-table",
                    "name" => "Roles",
                    "description" => "Datatable schema for roles",
                    "schema" => [
                        "entity" => "roles",
                        "dt-options" => [
                            "columns" => [
                                ["data" => "id", "title" => "ID", "visible" => true],
                                [
                                    "data" => "name",
                                    "title" => "Role Name",
                                    "visible" => true,
                                    "link" => true,
                                    "form-schema-uid" => "role-details"
                                ]
                            ],
                            "options" => [
                                "pageLength" => 10,
                                "order" => [[0, "desc"]],
                                "responsive" => true
                            ]
                        ],
                        "default_view_mode" => "inbox"
                    ]
                ]
            ],
            /*
            |-------------------------------------------------
            | Permissions
            |-------------------------------------------------
            */
            [
                "icon" => "fas fa-shield-alt",
                "label" => "Permissions",
                "route" => "ui.list",
                "table_schema" => [
                    "slug" => "permission-table",
                    "name" => "Permissions",
                    "description" => "Datatable schema for permissions",
                    "schema" => [
                        "entity" => "permissions",
                        "dt-options" => [
                            "columns" => [
                                ["data" => "id", "title" => "ID", "visible" => true],
                                [
                                    "data" => "name",
                                    "title" => "Permission Name",
                                    "visible" => true,
                                    "link" => true,
                                    "form-schema-uid" => "permission-details"
                                ]
                            ],
                            "options" => [
                                "pageLength" => 10,
                                "order" => [[0, "desc"]],
                                "responsive" => true
                            ]
                        ],
                        "default_view_mode" => "inbox"
                    ]
                ]
            ]
        ]
    ];

    /**
     * Module permissions
     */
    protected array $permissions = [
        'view-users',
        'create-users',
        'edit-users',
        'delete-users',
        'view-roles',
        'create-roles',
        'edit-roles',
        'delete-roles',
        'view-permissions',
        'create-permissions',
        'edit-permissions',
        'delete-permissions',
    ];

    /**
     * Guard name
     */
    protected string $guardName = 'web';

    /**
     * Entity definitions with fields and metadata
     */
    protected array $entities = [
        'users' => [
            'fields' => [],
            'meta_fields' => [
                'google_id' => [
                    'meta_key' => 'google_id',
                    'type' => 'string',
                    'label' => 'Google ID',
                    'required' => false,
                    'nullable' => true,
                ],
                'logo' => [
                    'meta_key' => 'logo',
                    'type' => 'string',
                    'label' => 'User Logo',
                    'required' => false,
                    'nullable' => true,
                    'input_type' => 'file',
                ],
                'registration_ip_address' => [
                    'meta_key' => 'registration_ip_address',
                    'type' => 'string',
                    'label' => 'Registration IP Address',
                    'required' => false,
                    'display' => false,
                ],
                'registration_user_agent' => [
                    'meta_key' => 'registration_user_agent',
                    'type' => 'string',
                    'label' => 'Registration User Agent',
                    'required' => false,
                    'display' => false,
                ],
                'registration_country' => [
                    'meta_key' => 'registration_country',
                    'type' => 'string',
                    'label' => 'Registration Country',
                    'required' => false,
                    'nullable' => true,
                ],
                'registration_locale' => [
                    'meta_key' => 'registration_locale',
                    'type' => 'string',
                    'label' => 'Registration Locale',
                    'required' => false,
                    'nullable' => true,
                ],
                'registration_timezone' => [
                    'meta_key' => 'registration_timezone',
                    'type' => 'string',
                    'label' => 'Registration Timezone',
                    'required' => false,
                    'nullable' => true,
                ],
                'registered_at' => [
                    'meta_key' => 'registered_at',
                    'type' => 'timestamp',
                    'label' => 'Registered At',
                    'required' => false,
                    'display' => false,
                ],
                'current_login_at' => [
                    'meta_key' => 'current_login_at',
                    'type' => 'timestamp',
                    'label' => 'Current Login At',
                    'required' => false,
                    'display' => false,
                ],
                'last_login_at' => [
                    'meta_key' => 'last_login_at',
                    'type' => 'timestamp',
                    'label' => 'Last Login At',
                    'required' => false,
                    'display' => false,
                ],
                'login_ip_address' => [
                    'meta_key' => 'login_ip_address',
                    'type' => 'string',
                    'label' => 'Login IP Address',
                    'required' => false,
                    'display' => false,
                ],
                'login_user_agent' => [
                    'meta_key' => 'login_user_agent',
                    'type' => 'string',
                    'label' => 'Login User Agent',
                    'required' => false,
                    'display' => false,
                ],
                'session_token' => [
                    'meta_key' => 'session_token',
                    'type' => 'string',
                    'label' => 'Session Token',
                    'required' => false,
                    'display' => false,
                ],
                'login_country' => [
                    'meta_key' => 'login_country',
                    'type' => 'string',
                    'label' => 'Login Country',
                    'required' => false,
                    'nullable' => true,
                ],
                'login_locale' => [
                    'meta_key' => 'login_locale',
                    'type' => 'string',
                    'label' => 'Login Locale',
                    'required' => false,
                    'nullable' => true,
                ],
                'login_timezone' => [
                    'meta_key' => 'login_timezone',
                    'type' => 'string',
                    'label' => 'Login Timezone',
                    'required' => false,
                    'nullable' => true,
                ],
            ],
            'metas' => [],
        ],
        'roles' => [
            'fields' => [],
            'meta_fields' => [],
            'metas' => [],
        ],
        'permissions' => [
            'fields' => [],
            'meta_fields' => [],
            'metas' => [],
        ],
    ];

     /**
     * Implement abstract method from BaseSeeder
     */
    protected function seedCustom(): void
    {
        $this->seedAuthFormSchemas();
    }

    /**
     * Seed FormSchema records that drive schema-rendered auth forms.
     * Guarded on form_schemas existing since user-management does not
     * require iquesters/user-interface.
     */
    protected function seedAuthFormSchemas(): void
    {
        if (! Schema::hasTable('form_schemas')) {
            return;
        }

        $existing = DB::table('form_schemas')->where('slug', 'login-with-password')->first();

        DB::table('form_schemas')->updateOrInsert(
            ['slug' => 'login-with-password'],
            [
                'uid' => $existing->uid ?? (string) Str::ulid(),
                'name' => 'Login',
                'description' => 'Classic email/password login form',
                'schema' => json_encode([
                    'endpoint' => '/login',
                    'method' => 'POST',
                    'allowCancel' => false,
                    // Top-level allowCancel is only the fallback default; the
                    // page always resolves to 'edit' mode (no /edit/ /view/
                    // /delete/ in the URL), and applyModeOverrides() in
                    // form.js hardcodes allowCancel=true for 'edit' mode
                    // unless overridden here per-mode.
                    'modes' => [
                        'edit' => ['allowCancel' => false],
                    ],
                    // Plain 12 (no breakpoint keys) resolves to a single
                    // col-12 class, i.e. full width at every screen size.
                    'defaultFieldSize' => 12,
                    'submitButtonLabel' => 'Log in',
                    'fields' => [
                        [
                            'id' => 'email',
                            'label' => 'Email',
                            'type' => 'email',
                            'required' => true,
                            'autocomplete' => 'username',
                            'autofocus' => true,
                        ],
                        [
                            'id' => 'password',
                            'label' => 'Password',
                            'type' => 'password',
                            'required' => true,
                            'autocomplete' => 'current-password',
                        ],
                    ],
                    // Defining 'actions' at all takes over the whole footer
                    // (form.js only auto-renders a submit button when
                    // 'actions' is absent/empty) — so the submit button has
                    // to be listed explicitly here alongside the links.
                    // Every action needs a truthy 'route' or addAction() in
                    // form.js renders nothing at all for it, submit included
                    // — '#' is a harmless no-op for the submit button itself.
                    'actions' => [
                        [
                            'type' => 'link',
                            'route' => '/forgot-password',
                            'text' => 'Forgot password?',
                            'element' => ['type' => 'a', 'variant' => 'link', 'color' => 'info'],
                            'row' => 0,
                        ],
                        [
                            'type' => 'link',
                            'route' => '/register',
                            'text' => 'Create a new account',
                            'element' => ['type' => 'a', 'variant' => 'link', 'color' => 'info'],
                            'row' => 1,
                        ],
                        [
                            'type' => 'submit',
                            'route' => '#',
                            'text' => 'Log in',
                            'element' => ['type' => 'button', 'color' => 'primary'],
                            'row' => 0,
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'extra_info' => $existing->extra_info ?? null,
                'status' => 'active',
                'created_by' => $existing->created_by ?? 0,
                'updated_by' => 0,
                'created_at' => $existing->created_at ?? now(),
                'updated_at' => now(),
            ]
        );

        $this->upsertFormSchema('register', 'Register', 'Classic name/email/password registration form', [
            'endpoint' => '/register',
            'method' => 'POST',
            'allowCancel' => false,
            'modes' => [
                'edit' => ['allowCancel' => false],
            ],
            'defaultFieldSize' => 12,
            'submitButtonLabel' => 'Register',
            'fields' => [
                [
                    'id' => 'name',
                    'label' => 'Name',
                    'type' => 'text',
                    'required' => true,
                    'maxLength' => 255,
                    'autocomplete' => 'name',
                    'autofocus' => true,
                ],
                [
                    'id' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                    'maxLength' => 255,
                    'autocomplete' => 'username',
                    'unique_table' => 'users',
                    'unique_column' => 'email',
                ],
                [
                    'id' => 'password',
                    'label' => 'Password',
                    'type' => 'password',
                    'required' => true,
                    'minLength' => 8,
                    'autocomplete' => 'new-password',
                    'confirmed' => true,
                    'password_policy' => true,
                    'password_mixed_case' => true,
                    'password_numbers' => true,
                    'password_symbols' => true,
                ],
                [
                    'id' => 'password_confirmation',
                    'label' => 'Confirm Password',
                    'type' => 'password',
                    'required' => true,
                    'minLength' => 8,
                    'autocomplete' => 'new-password',
                    'confirms' => 'password',
                ],
            ],
            'actions' => [
                [
                    'type' => 'link',
                    'route' => '/login',
                    'text' => 'Already registered?',
                    'element' => ['type' => 'a', 'variant' => 'link', 'color' => 'info'],
                    'row' => 0,
                ],
                [
                    'type' => 'submit',
                    'route' => '#',
                    'text' => 'Register',
                    'element' => ['type' => 'button', 'color' => 'primary'],
                    'row' => 0,
                ],
            ],
        ]);

        $this->upsertFormSchema('password_reset_link-form', 'Forgot Password', 'Request a password reset link by email', [
            'endpoint' => '/forgot-password',
            'method' => 'POST',
            'allowCancel' => false,
            'modes' => [
                'edit' => ['allowCancel' => false],
            ],
            'defaultFieldSize' => 12,
            'info' => [
                'innerHTML' => 'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.',
            ],
            'submitButtonLabel' => 'Send reset link',
            'fields' => [
                [
                    'id' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                    'autocomplete' => 'username',
                    'autofocus' => true,
                ],
            ],
        ]);

        $this->upsertFormSchema('password_reset-form', 'Reset Password', 'Set a new password using a reset token', [
            'endpoint' => '/reset-password',
            'method' => 'POST',
            'allowCancel' => false,
            'modes' => [
                'edit' => ['allowCancel' => false],
            ],
            'defaultFieldSize' => 12,
            'submitButtonLabel' => 'Reset Password',
            'fields' => [
                [
                    'id' => 'token',
                    'type' => 'hidden',
                    'required' => true,
                ],
                [
                    'id' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                    'autocomplete' => 'username',
                    'autofocus' => true,
                ],
                [
                    'id' => 'password',
                    'label' => 'Password',
                    'type' => 'password',
                    'required' => true,
                    'minLength' => 8,
                    'autocomplete' => 'new-password',
                    'confirmed' => true,
                    'password_policy' => true,
                    'password_mixed_case' => true,
                    'password_numbers' => true,
                    'password_symbols' => true,
                ],
                [
                    'id' => 'password_confirmation',
                    'label' => 'Confirm Password',
                    'type' => 'password',
                    'required' => true,
                    'minLength' => 8,
                    'autocomplete' => 'new-password',
                    'confirms' => 'password',
                ],
            ],
        ]);

        // Two tabs, two independent schemas — both render + submit through
        // the generic engine, both post to the same /auth/identify (the
        // controller doesn't need to guess email-vs-phone since the active
        // tab already tells it via which schema/fields were submitted).
        // The response still needs custom handling either way — it returns
        // flow state (existing vs new, available login methods) for the
        // page's JS to branch on (show password vs OTP panel), not a
        // redirect_url the engine could act on by itself — wired via the
        // lab-form:submitted event instead of the engine's defaults.
        $identifyCommon = [
            'endpoint' => '/auth/identify',
            'method' => 'POST',
            'allowCancel' => false,
            'modes' => [
                'edit' => ['allowCancel' => false],
            ],
            'defaultFieldSize' => 12,
            'submitButtonLabel' => 'Continue',
            // The page's own lab-form:submitted handler drives the next
            // panel — a generic "submitted successfully" snackbar on top of
            // that would just be redundant/confusing.
            'disableSnackbar' => true,
        ];

        $this->upsertFormSchema('unified-identify-email', 'Unified Sign-in — Email', 'Identifier-first login/registration entry step (email tab)', $identifyCommon + [
            'fields' => [
                [
                    'id' => 'identifier',
                    'label' => 'Email address',
                    'type' => 'email',
                    'required' => true,
                    'minLength' => 3,
                    'maxLength' => 255,
                    'placeholder' => 'name@example.com',
                    'autocomplete' => 'username',
                    'autofocus' => true,
                ],
            ],
        ]);

        // Small, static list (matches IdentifierResolverService::getCountryDialCodes())
        // — worth embedding directly rather than fetching separately, since
        // the list itself never changes, only the auto-detected default
        // selection does (synced in via JS after the IP-lookup resolves).
        $countryDialCodeOptions = [
            ['value' => '+1', 'label' => 'United States (+1)'],
            ['value' => '+1', 'label' => 'Canada (+1)'],
            ['value' => '+44', 'label' => 'United Kingdom (+44)'],
            ['value' => '+91', 'label' => 'India (+91)'],
            ['value' => '+61', 'label' => 'Australia (+61)'],
            ['value' => '+971', 'label' => 'United Arab Emirates (+971)'],
            ['value' => '+880', 'label' => 'Bangladesh (+880)'],
            ['value' => '+49', 'label' => 'Germany (+49)'],
            ['value' => '+33', 'label' => 'France (+33)'],
            ['value' => '+81', 'label' => 'Japan (+81)'],
            ['value' => '+254', 'label' => 'Kenya (+254)'],
            ['value' => '+234', 'label' => 'Nigeria (+234)'],
            ['value' => '+92', 'label' => 'Pakistan (+92)'],
            ['value' => '+65', 'label' => 'Singapore (+65)'],
            ['value' => '+27', 'label' => 'South Africa (+27)'],
        ];

        $this->upsertFormSchema('unified-identify-phone', 'Unified Sign-in — Phone', 'Identifier-first login/registration entry step (phone tab)', $identifyCommon + [
            'fields' => [
                [
                    'id' => 'country_dial_code',
                    'label' => 'Country code',
                    'type' => 'select',
                    'required' => false,
                    'options' => $countryDialCodeOptions,
                ],
                [
                    'id' => 'identifier',
                    'label' => 'Phone number',
                    'type' => 'tel',
                    'required' => true,
                    'minLength' => 3,
                    'maxLength' => 255,
                    'placeholder' => '9876543210',
                    'autocomplete' => 'tel',
                ],
            ],
        ]);

        // Verify-OTP is its own schema/card, deliberately separate from the
        // two identify tabs above — flow_token/identifier_type/identifier/
        // delivery_channel are carried as hidden fields, populated by JS
        // from the identify step's response (VerifyIdentifierOtpRequest
        // requires all four alongside otp). Send/Resend stay custom buttons
        // outside this form — their auto-trigger/cooldown behavior doesn't
        // map onto the generic submit-button engine.
        $this->upsertFormSchema('unified-verify-otp', 'Unified Sign-in — Verify OTP', 'Identifier-first login/registration OTP verification step', [
            'endpoint' => '/auth/otp/verify',
            'method' => 'POST',
            'allowCancel' => false,
            'modes' => [
                'edit' => ['allowCancel' => false],
            ],
            'defaultFieldSize' => 12,
            'submitButtonLabel' => 'Verify OTP',
            'disableSnackbar' => true,
            'fields' => [
                ['id' => 'flow_token', 'type' => 'hidden', 'required' => true],
                ['id' => 'identifier_type', 'type' => 'hidden', 'required' => true],
                ['id' => 'identifier', 'type' => 'hidden', 'required' => true],
                ['id' => 'delivery_channel', 'type' => 'hidden', 'required' => true],
                [
                    'id' => 'otp',
                    'label' => 'Verification code',
                    'type' => 'text',
                    'required' => true,
                    'minLength' => 4,
                    'maxLength' => 8,
                    'placeholder' => 'Enter OTP',
                    'autocomplete' => 'one-time-code',
                    'autofocus' => true,
                ],
            ],
        ]);

        // The unified flow's password step for an already-identified,
        // already-has-a-password account. Posts to the same /login as
        // classic — LoginRequest's rules always come from the
        // 'login-with-password' schema regardless of which rendered form
        // submitted the request, so this one only needs to render
        // correctly. email is hidden/pre-filled by JS from the identify
        // step's response (setSchemaFieldValue) rather than user-editable,
        // since the identifier is already locked in by that point.
        $this->upsertFormSchema('unified-login-with-password', 'Unified Sign-in — Password', 'Password login step for an already-identified unified sign-in flow', [
            'endpoint' => '/login',
            'method' => 'POST',
            'allowCancel' => false,
            'modes' => [
                'edit' => ['allowCancel' => false],
            ],
            'defaultFieldSize' => 12,
            'submitButtonLabel' => 'Log in',
            'disableSnackbar' => true,
            'fields' => [
                ['id' => 'email', 'type' => 'hidden', 'required' => true],
                [
                    'id' => 'password',
                    'label' => 'Password',
                    'type' => 'password',
                    'required' => true,
                    'autocomplete' => 'current-password',
                    'autofocus' => true,
                ],
            ],
            'actions' => [
                [
                    'type' => 'link',
                    'route' => '/forgot-password',
                    'text' => 'Forgot password?',
                    'element' => ['type' => 'a', 'variant' => 'link', 'color' => 'info'],
                    'row' => 0,
                ],
                [
                    'type' => 'submit',
                    'route' => '#',
                    'text' => 'Log in',
                    'element' => ['type' => 'button', 'color' => 'primary'],
                    'row' => 0,
                ],
            ],
        ]);
    }

    /**
     * Upsert a single form_schemas row by slug, preserving its uid/audit
     * fields across re-seeds.
     */
    protected function upsertFormSchema(string $slug, string $name, string $description, array $schema): void
    {
        $existing = DB::table('form_schemas')->where('slug', $slug)->first();

        DB::table('form_schemas')->updateOrInsert(
            ['slug' => $slug],
            [
                'uid' => $existing->uid ?? (string) Str::ulid(),
                'name' => $name,
                'description' => $description,
                'schema' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'extra_info' => $existing->extra_info ?? null,
                'status' => 'active',
                'created_by' => $existing->created_by ?? 0,
                'updated_by' => 0,
                'created_at' => $existing->created_at ?? now(),
                'updated_at' => now(),
            ]
        );
    }
}