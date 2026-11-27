<?php

namespace Iquesters\UserManagement\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Iquesters\Foundation\Database\Seeders\BaseSeeder;
use Iquesters\Foundation\Http\Middleware\EnsureAdmin;
use Iquesters\Foundation\Models\Module;
use Spatie\Permission\Models\Role;

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
                'icon' => 'fas fa-users',
                'label' => 'Users',
                'route' => 'ui.list',
                'table_schema' => [
                    'slug' => 'user-table',
                    'name' => 'Users',
                    'description' => 'Datatable schema for users',
                    'schema' => [
                        'entity' => 'users',
                        // List page Create button: opens user-details in create mode.
                        'create' => ['form' => 'user-details', 'label' => 'Create user'],
                        // The split view's list rows use the user card; the
                        // record itself shows user-details' view component.
                        'row_component' => self::USER_SUMMARY_COMPONENT,
                        'dt-options' => [
                            'columns' => [
                                ['data' => 'id', 'title' => 'ID', 'visible' => true],
                                [
                                    'data' => 'name',
                                    'title' => 'User Name',
                                    'visible' => true,
                                    'link' => true,
                                    'form-schema-uid' => 'user-details',
                                ],
                                [
                                    'data' => 'email',
                                    'title' => 'Email',
                                    'visible' => true,
                                ],
                                [
                                    'data' => 'meta.registered_at',
                                    'title' => 'Registered At',
                                    'visible' => true,
                                ],
                                [
                                    'data' => 'meta.login_ip_address',
                                    'title' => 'Login IP',
                                    'visible' => true,
                                ],
                            ],
                            'options' => [
                                'pageLength' => 10,
                                'order' => [[0, 'desc']],
                                'responsive' => true,
                            ],
                        ],
                        'default_view_mode' => 'inbox',
                    ],
                ],
            ],
            /*
            |-------------------------------------------------
            | Roles
            |-------------------------------------------------
            */
            [
                'icon' => 'fas fa-user-shield',
                'label' => 'Roles',
                'route' => 'ui.list',
                'table_schema' => [
                    'slug' => 'role-table',
                    'name' => 'Roles',
                    'description' => 'Datatable schema for roles',
                    'schema' => [
                        'entity' => 'roles',
                        'create' => ['form' => 'role-details', 'label' => 'Create role'],
                        'dt-options' => [
                            'columns' => [
                                ['data' => 'id', 'title' => 'ID', 'visible' => true],
                                [
                                    'data' => 'name',
                                    'title' => 'Role Name',
                                    'visible' => true,
                                    'link' => true,
                                    'form-schema-uid' => 'role-details',
                                ],
                            ],
                            'options' => [
                                'pageLength' => 10,
                                'order' => [[0, 'desc']],
                                'responsive' => true,
                            ],
                        ],
                        'default_view_mode' => 'inbox',
                    ],
                ],
            ],
            /*
            |-------------------------------------------------
            | Permissions
            |-------------------------------------------------
            */
            [
                'icon' => 'fas fa-shield-alt',
                'label' => 'Permissions',
                'route' => 'ui.list',
                'table_schema' => [
                    'slug' => 'permission-table',
                    'name' => 'Permissions',
                    'description' => 'Datatable schema for permissions',
                    'schema' => [
                        'entity' => 'permissions',
                        'dt-options' => [
                            'columns' => [
                                ['data' => 'id', 'title' => 'ID', 'visible' => true],
                                [
                                    'data' => 'name',
                                    'title' => 'Permission Name',
                                    'visible' => true,
                                    'link' => true,
                                    'form-schema-uid' => 'permission-details',
                                ],
                            ],
                            'options' => [
                                'pageLength' => 10,
                                'order' => [[0, 'desc']],
                                'responsive' => true,
                            ],
                        ],
                        'default_view_mode' => 'inbox',
                    ],
                ],
            ],
        ],
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
        $this->seedProfileFormSchemas();
        // Before the admin forms: user-details uses it when it is there.
        $this->seedUserAccountBusinessEntity();
        $this->seedAdminFormSchemas();
        $this->addCreateButtonsToTables();
        $this->restrictModuleToAdminRoles();
    }

    /** Whether the "user-account" business entity is set up (user-details then edits roles too). */
    protected bool $userAccountReady = false;

    /**
     * Users screen: create and edit a user with their roles.
     *
     * database/business-entities/user-account.json is a foundation business
     * entity: users + roles through Spatie's model_has_roles, "API only" (no
     * generated screens). The hand-made "user-details" form (Users table:
     * Create and rows) saves through its API, so a user and their roles are
     * saved together. It changes no tables. Skipped without foundation's
     * business entities (older foundation); user-details then keeps the
     * generic entity API, without roles.
     */
    protected function seedUserAccountBusinessEntity(): void
    {
        $service = 'Iquesters\\Foundation\\Services\\BusinessEntity\\BusinessEntityService';

        if (! class_exists($service) || ! Schema::hasTable('business_entities') || ! Schema::hasTable('table_schemas')) {
            return;
        }

        try {
            $file = json_decode((string) file_get_contents(__DIR__.'/../business-entities/user-account.json'), true, 512, JSON_THROW_ON_ERROR);
            app($service)->upsertFromFile($file);
            $this->userAccountReady = true;
        } catch (\Throwable $e) {
            // The rest of the module works without it: log and keep seeding.
            Log::warning('User account business entity not seeded: '.$e->getMessage());
        }
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
                'created_by' => $existing->created_by ?? null,
                'updated_by' => null,
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
                \Iquesters\UserManagement\Support\Terms::field(),
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

        // Agree to the Terms / Privacy policy (after Google or OTP sign-up, or a new terms_version).
        $this->upsertFormSchema('accept-terms', 'Accept terms', 'Agree to the Terms and conditions and the Privacy policy', [
            'endpoint' => '/accept-terms',
            'method' => 'POST',
            'allowCancel' => false,
            'modes' => ['edit' => ['allowCancel' => false]],
            'defaultFieldSize' => 12,
            'submitButtonLabel' => 'Agree and continue',
            'fields' => [\Iquesters\UserManagement\Support\Terms::field()],
        ]);

        // First-run application setup (/setup). Multipart because of the logo upload;
        // SetupController::store answers JSON with redirect_url for this form.
        $this->upsertFormSchema('setup-form', 'Application Setup', 'First-run setup: application name, logo and description', [
            'endpoint' => '/setup',
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'allowCancel' => false,
            'modes' => [
                'edit' => ['allowCancel' => false],
            ],
            'defaultFieldSize' => 12,
            'submitButtonLabel' => 'Continue',
            'fields' => [
                [
                    'id' => 'app_name',
                    'label' => 'Application Name',
                    'type' => 'text',
                    'required' => true,
                    'maxLength' => 255,
                    'autofocus' => true,
                ],
                [
                    'id' => 'logo',
                    'label' => 'Logo',
                    'type' => 'file',
                    'required' => true,
                    'accept' => 'image/png,image/jpeg,image/gif,image/webp',
                ],
                [
                    'id' => 'favicon',
                    'label' => 'Browser tab icon (optional; the logo is used if empty)',
                    'type' => 'file',
                    'required' => false,
                    'accept' => 'image/png,image/x-icon,image/vnd.microsoft.icon,image/jpeg,image/webp',
                ],
                [
                    'id' => 'description',
                    'label' => 'Description',
                    'type' => 'textarea',
                    'rows' => 3,
                    'required' => false,
                    'maxLength' => 500,
                ],
            ],
            'actions' => [
                [
                    'type' => 'submit',
                    'route' => '#',
                    'text' => 'Continue',
                    // Same look as the classic setup form: small outline-info, right-aligned.
                    'element' => ['type' => 'button', 'color' => 'info', 'variant' => 'outline', 'size' => 'sm'],
                    'align' => 'end',
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

        // The Terms / Privacy agreement on the first step (when the app requires one).
        $termsField = \Iquesters\UserManagement\Support\Terms::required() ? [\Iquesters\UserManagement\Support\Terms::field()] : [];

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
                // The agreement, asked up front: recorded when the account is made (or a returning
                // person signs in with a code).
                ...$termsField,
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
                ...$termsField,
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
            'submitButtonLabel' => 'Continue',
            'disableSnackbar' => true,
            'fields' => [
                ['id' => 'flow_token', 'type' => 'hidden', 'required' => true],
                ['id' => 'identifier_type', 'type' => 'hidden', 'required' => true],
                ['id' => 'identifier', 'type' => 'hidden', 'required' => true],
                ['id' => 'delivery_channel', 'type' => 'hidden', 'required' => true],
                [
                    'id' => 'otp',
                    'label' => 'Code',
                    'type' => 'text',
                    'required' => true,
                    // Exactly the code sent: its length (setting otp_length), digits only.
                    'minLength' => $otpLength = \Iquesters\UserManagement\Http\Requests\Auth\VerifyIdentifierOtpRequest::otpLength(),
                    'maxLength' => $otpLength,
                    'pattern' => '[0-9]{'.$otpLength.'}',
                    'digitsOnly' => true,
                    'inputmode' => 'numeric',
                    'placeholder' => $otpLength.'-digit code',
                    'helperText' => 'The '.$otpLength.'-digit code we sent you.',
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
     * Forms of the signed-in user's own pages (My Profile, Settings).
     * "user-profile" has two modes: view on My Profile, edit on Settings.
     * The controllers answer these fetch() submissions with JSON + redirect_url.
     */
    protected function seedProfileFormSchemas(): void
    {
        if (! Schema::hasTable('form_schemas')) {
            return;
        }

        $submit = fn (string $text, string $color = 'primary') => [[
            'type' => 'submit',
            'route' => '#',
            'text' => $text,
            'element' => ['type' => 'button', 'color' => $color, 'variant' => 'outline', 'size' => 'sm'],
            'align' => 'end',
            'row' => 0,
        ]];

        $this->upsertFormSchema('user-profile', 'Profile', 'Signed-in user profile: view on My Profile, edit on Settings', [
            'endpoint' => '/profile/update',
            'method' => 'PATCH',
            'allowCancel' => false,
            'modes' => [
                // My Profile: the whole page; the Edit icon opens Settings.
                'view' => [
                    'header' => ['title' => 'My profile'],
                    'allowEdit' => true,
                    'allowDelete' => false,
                    'editUrl' => '/settings',
                ],
                // Cancel goes back (to My Profile).
                'edit' => ['allowCancel' => true],
            ],
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Save',
            'fields' => [
                // View only: picture with Change / Remove (each a form schema in a modal).
                [
                    'id' => 'picture',
                    'label' => 'Profile picture',
                    'type' => 'image',
                    'showIn' => ['view'],
                    'maxWidth' => '200px',
                    'actions' => [
                        ['label' => 'Change picture', 'icon' => 'fas fa-upload', 'form' => 'user-profile-picture', 'title' => 'Change profile picture', 'submitLabel' => 'Upload'],
                        ['label' => 'Remove picture', 'icon' => 'fas fa-trash', 'form' => 'user-profile-picture-remove', 'title' => 'Remove profile picture?', 'submitLabel' => 'Remove', 'danger' => true],
                    ],
                ],
                ['id' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxLength' => 255, 'autocomplete' => 'name'],
                [
                    'id' => 'status',
                    'label' => 'Status',
                    'type' => 'select',
                    // Same badge as <x-userinterface::status> (text-bg-{status}).
                    'display' => 'status',
                    'showIn' => ['view'],
                    'options' => [
                        ['value' => 'active', 'label' => 'Active'],
                        ['value' => 'inactive', 'label' => 'Inactive'],
                        ['value' => 'unknown', 'label' => 'Unknown'],
                    ],
                ],
                ['id' => 'email', 'label' => 'Email', 'type' => 'email', 'readonly' => true],
                ['id' => 'email_status', 'label' => 'Email verification', 'type' => 'text', 'display' => 'badge', 'showIn' => ['view']],
                ['id' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'readonly' => true],
                [
                    'id' => 'theme',
                    'label' => 'Theme',
                    'type' => 'select',
                    'optionsFrom' => ['master_data' => 'theme', 'exclude' => ['current_theme'], 'empty' => 'Application default'],
                ],
                ['id' => 'roles', 'label' => 'Roles', 'type' => 'text', 'display' => 'badge', 'showIn' => ['view']],
                ['id' => 'member_since', 'label' => 'Member since', 'type' => 'text', 'showIn' => ['view']],
                ['id' => 'previous_login', 'label' => 'Previous sign-in', 'type' => 'text', 'showIn' => ['view']],
            ],
            'actions' => $submit('Save'),
        ]);

        // Opened in a modal from the picture's "Change picture" action.
        $this->upsertFormSchema('user-profile-picture', 'Change Profile Picture', 'Upload a new profile picture (cropped to a square on the server)', [
            'endpoint' => '/profile/picture',
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'allowCancel' => true,
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Upload',
            'info' => ['innerHTML' => 'Choose a JPG, PNG, WebP or GIF up to 5 MB, then pick the square area to use.'],
            'fields' => [
                [
                    'id' => 'picture',
                    'label' => 'Picture',
                    'type' => 'file',
                    'required' => true,
                    'accept' => 'image/png,image/jpeg,image/webp,image/gif',
                    // Square crop in the browser (image-crop-field.js); the server keeps it square too.
                    'crop' => ['aspectRatio' => 1, 'maxSize' => 1024],
                ],
            ],
        ]);

        // Opened in a modal from the picture's "Remove picture" action.
        $this->upsertFormSchema('user-profile-picture-remove', 'Remove Profile Picture', 'Confirm removing the profile picture', [
            'endpoint' => '/remove-profile-picture',
            'method' => 'POST',
            'allowCancel' => true,
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Remove',
            'info' => ['innerHTML' => 'Your uploaded picture will be removed and the default picture shown instead.'],
            'fields' => [
                ['id' => 'confirm', 'type' => 'hidden', 'default' => '1'],
            ],
        ]);

        $passwordFields = [
            [
                'id' => 'password',
                'label' => 'New password',
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
                'label' => 'Confirm new password',
                'type' => 'password',
                'required' => true,
                'minLength' => 8,
                'autocomplete' => 'new-password',
                'confirms' => 'password',
            ],
        ];
        $passwordCommon = [
            'endpoint' => '/password',
            'method' => 'PUT',
            'allowCancel' => false,
            'modes' => ['edit' => ['allowCancel' => false]],
            'defaultFieldSize' => 'auto',
        ];

        $this->upsertFormSchema('user-password-update', 'Change Password', 'Change the signed-in user\'s password', $passwordCommon + [
            'submitButtonLabel' => 'Update password',
            'fields' => array_merge([
                ['id' => 'current_password', 'label' => 'Current password', 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password'],
                // Ends the row, so New + Confirm sit together on the next one.
                ['id' => 'current_password_spacer', 'type' => 'spacer', 'size' => ['xs' => 12, 'md' => 6]],
            ], $passwordFields),
            'actions' => $submit('Update password'),
        ]);

        // Users who signed up with Google or an OTP have no password yet.
        $this->upsertFormSchema('user-password-set', 'Set Password', 'Set a first password for a user who signed up without one', $passwordCommon + [
            'submitButtonLabel' => 'Set password',
            'fields' => $passwordFields,
            'actions' => $submit('Set password'),
        ]);

        // Sent as POST + _method=DELETE: the JSON client sends no body with DELETE.
        $deleteCommon = [
            'endpoint' => '/profile',
            'method' => 'POST',
            'allowCancel' => false,
            'modes' => ['edit' => ['allowCancel' => false]],
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Delete my account',
            'actions' => $submit('Delete my account', 'danger'),
        ];
        $methodField = ['id' => '_method', 'type' => 'hidden', 'default' => 'DELETE'];

        $this->upsertFormSchema('user-delete-account', 'Delete Account', 'Delete the signed-in user\'s account (confirm with password)', $deleteCommon + [
            'fields' => [
                $methodField,
                ['id' => 'password', 'label' => 'Enter your password to confirm', 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password'],
            ],
        ]);

        $this->upsertFormSchema('user-delete-account-email', 'Delete Account', 'Delete the account of a user without a password (confirm with email)', $deleteCommon + [
            'fields' => [
                $methodField,
                ['id' => 'confirm_email', 'label' => 'Type your email address to confirm', 'type' => 'email', 'required' => true, 'autocomplete' => 'off'],
            ],
        ]);
    }

    /** Record components of the Users table (registered by UserManagementServiceProvider). */
    public const USER_SUMMARY_COMPONENT = 'user.summary';

    public const USER_DETAILS_COMPONENT = 'user.details';

    /** Values seeded before the keys existed: replaced by the keys, unlike an admin's own choice. */
    private const OLD_COMPONENT_VALUES = [
        'summary_component' => 'userinterface::components.lab-user-summary',
        'details_component' => 'userinterface::components.lab-user-details',
    ];

    /**
     * Existing installs: table schemas are only created once (admins may edit
     * them), so add these keys where they are missing, without touching
     * anything else in the schema: the list page "create" entry, and the
     * Users table's summary (left) and details (right) components (the older
     * view-path values are upgraded to the component keys).
     */
    protected function addCreateButtonsToTables(): void
    {
        if (! Schema::hasTable('table_schemas')) {
            return;
        }

        $this->moveUserTableComponents();

        $additions = [
            'user-table' => [
                'create' => ['form' => 'user-details', 'label' => 'Create user'],
                'row_component' => self::USER_SUMMARY_COMPONENT,
            ],
            'role-table' => [
                'create' => ['form' => 'role-details', 'label' => 'Create role'],
            ],
        ];

        foreach ($additions as $slug => $keys) {
            $row = DB::table('table_schemas')->where('slug', $slug)->first(['id', 'schema']);
            $schema = $row ? json_decode((string) $row->schema, true) : null;
            if (! is_array($schema)) {
                continue;
            }

            $changed = false;
            foreach ($keys as $key => $value) {
                $old = self::OLD_COMPONENT_VALUES[$key] ?? null;

                if (! array_key_exists($key, $schema) || ($old !== null && $schema[$key] === $old)) {
                    $schema[$key] = $value;
                    $changed = true;
                }
            }

            if (! $changed) {
                continue;
            }

            DB::table('table_schemas')->where('id', $row->id)->update([
                'schema' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Existing installs, Users table: "summary_component" is now
     * "row_component" (an admin's own value is kept), and the record's
     * component moved to the user-details form (modes.view.component), so the
     * table's "details_component" is dropped when it is the seeded one.
     */
    protected function moveUserTableComponents(): void
    {
        $row = DB::table('table_schemas')->where('slug', 'user-table')->first(['id', 'schema']);
        $schema = $row ? json_decode((string) $row->schema, true) : null;
        if (! is_array($schema)) {
            return;
        }

        $changed = false;

        if (array_key_exists('summary_component', $schema)) {
            $value = $schema['summary_component'];
            $schema['row_component'] ??= $value === self::OLD_COMPONENT_VALUES['summary_component'] ? self::USER_SUMMARY_COMPONENT : $value;
            unset($schema['summary_component']);
            $changed = true;
        }

        $seededDetails = [self::USER_DETAILS_COMPONENT, self::OLD_COMPONENT_VALUES['details_component']];
        if (array_key_exists('details_component', $schema) && in_array($schema['details_component'], $seededDetails, true)) {
            unset($schema['details_component']);
            $changed = true;
        }

        if ($changed) {
            DB::table('table_schemas')->where('id', $row->id)->update([
                'schema' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Only admin roles (foundation setting admin_roles: super-admin, admin) see
     * this module. Its pages and data already require an admin role
     * (ensure.admin, entity permissions); this makes the navigation match.
     */
    protected function restrictModuleToAdminRoles(): void
    {
        $module = Module::where('name', $this->moduleName)->first();
        if (! $module) {
            return;
        }

        $roleIds = collect(EnsureAdmin::roles())
            ->map(fn (string $name) => Role::findOrCreate($name, $this->guardName)->id)
            ->unique()
            ->values()
            ->all();

        $module->assignRoles($roleIds);
    }

    /**
     * Detail forms of the Users / Roles / Permissions tables (the tables link
     * rows to these slugs via "form-schema-uid"). View mode first; Edit and
     * Cancel switch modes. Endpoints are relative so the schema works on any host.
     */
    protected function seedAdminFormSchemas(): void
    {
        if (! Schema::hasTable('form_schemas')) {
            return;
        }

        $api = '/api/user-interface/v1/entity';
        $save = [[
            'type' => 'submit',
            'route' => '#',
            'text' => 'Save',
            'element' => ['type' => 'button', 'color' => 'primary', 'variant' => 'outline', 'size' => 'sm'],
            'align' => 'end',
            'row' => 0,
        ]];

        // Users: a user with their roles, saved together through the
        // "user-account" business entity (admins only). Without it (older
        // foundation), the basic fields through the generic entity API, no roles.
        $withRoles = $this->userAccountReady;
        $base = $withRoles ? '/api/foundation/v1/business-entity' : $api;
        $resource = $withRoles ? 'user-account' : 'users';
        $id = fn (string $field) => $withRoles ? 'user__'.$field : $field;

        $this->upsertFormSchema('user-details', 'User', 'User details (view / edit) for the Users table', array_filter([
            'entity' => $withRoles ? 'business-entity/user-account' : 'users',
            'dataEndpoint' => $withRoles ? $base.'/show/user-account/{uid}' : null,
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Save',
            'modes' => [
                'view' => [
                    'method' => 'GET',
                    'endpoint' => $base."/show/{$resource}/{uid}",
                    'header' => ['title' => 'User'],
                    // The record shows as the user card (picture, roles…); Edit shows the fields.
                    'component' => self::USER_DETAILS_COMPONENT,
                    'allowEdit' => true,
                    'allowDelete' => false,
                ],
                'edit' => [
                    'method' => 'PUT',
                    'endpoint' => $base."/update/{$resource}/{uid}",
                    'header' => ['title' => 'Edit user'],
                    'allowCancel' => true,
                ],
                // "Create user" button on the Users table (table schema "create").
                'create' => [
                    'method' => 'POST',
                    'endpoint' => $base."/store/{$resource}",
                    'header' => ['title' => 'Create user'],
                    'allowCancel' => true,
                ],
            ],
            'fields' => array_values(array_filter([
                ['id' => $id('name'), 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxLength' => 255],
                ['id' => $id('email'), 'label' => 'Email', 'type' => 'email', 'maxLength' => 255],
                ['id' => $id('phone'), 'label' => 'Phone', 'type' => 'tel', 'maxLength' => 32],
                // Shown only: the entity API manages status (it never saved a change from here).
                [
                    'id' => 'status',
                    'label' => 'Status',
                    'type' => 'select',
                    'showIn' => ['view'],
                    'display' => 'status',
                    'options' => [
                        ['value' => 'active', 'label' => 'Active'],
                        ['value' => 'inactive', 'label' => 'Inactive'],
                        ['value' => 'unknown', 'label' => 'Unknown'],
                    ],
                ],
                // Roles: Spatie's model_has_roles, through the business entity (pick existing roles).
                $withRoles ? [
                    'id' => 'roles',
                    'label' => 'Roles',
                    'type' => 'checkbox',
                    'display' => 'badge',
                    'optionsFrom' => ['url' => $base.'/options/user-account/roles'],
                ] : null,
                // Not in view mode. Required when creating; when editing, blank keeps
                // the current password. The entity API hashes it and never returns it.
                [
                    'id' => $id('password'),
                    'label' => 'Password',
                    'type' => 'password',
                    'showIn' => ['create', 'edit'],
                    'requiredIn' => ['create'],
                    'omitIfEmpty' => true,
                    'minLength' => 8,
                    'autocomplete' => 'new-password',
                    'confirmed' => true,
                    'password_policy' => true,
                    'password_mixed_case' => true,
                    'password_numbers' => true,
                    'password_symbols' => true,
                    'helperText' => 'When editing, leave blank to keep the current password.',
                ],
                [
                    'id' => $id('password_confirmation'),
                    'label' => 'Confirm password',
                    'type' => 'password',
                    'showIn' => ['create', 'edit'],
                    'requiredIn' => ['create'],
                    'omitIfEmpty' => true,
                    'autocomplete' => 'new-password',
                    'confirms' => $id('password'),
                ],
            ])),
            'actions' => $save,
        ], fn ($value) => $value !== null));

        // Roles: name + permissions, through the admin role endpoints
        // (the generic entity API does not handle the role/permission links).
        $this->upsertFormSchema('role-details', 'Role', 'Role details with permissions (view / edit) for the Roles table', [
            'entity' => 'roles',
            'dataEndpoint' => '/admin/roles/{uid}/details',
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Save',
            'modes' => [
                'view' => [
                    'method' => 'GET',
                    'endpoint' => '/admin/roles/{uid}/details',
                    'header' => ['title' => 'Role'],
                    'allowEdit' => true,
                    'allowDelete' => false,
                ],
                'edit' => [
                    'method' => 'PUT',
                    'endpoint' => '/admin/roles/{uid}/details',
                    'header' => ['title' => 'Edit role'],
                    'allowCancel' => true,
                ],
                // "Create role" button on the Roles table.
                'create' => [
                    'method' => 'POST',
                    'endpoint' => '/admin/roles/details',
                    'header' => ['title' => 'Create role'],
                    'allowCancel' => true,
                ],
            ],
            'fields' => [
                ['id' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxLength' => 255],
                [
                    'id' => 'permissions',
                    'label' => 'Permissions',
                    'type' => 'checkbox',
                    'optionsFrom' => ['url' => '/admin/permission-options'],
                ],
            ],
            'actions' => $save,
        ]);

        // Permissions are view-only: code checks them by name, so renaming one
        // would silently change who can do what.
        $this->upsertFormSchema('permission-details', 'Permission', 'Permission details (view only) for the Permissions table', [
            'entity' => 'permissions',
            'defaultFieldSize' => 'auto',
            'modes' => [
                'view' => [
                    'method' => 'GET',
                    'endpoint' => $api.'/show/permissions/{uid}',
                    'header' => ['title' => 'Permission'],
                    'allowEdit' => false,
                    'allowDelete' => false,
                ],
            ],
            'fields' => [
                ['id' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['id' => 'guard_name', 'label' => 'Guard', 'type' => 'text'],
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
                'created_by' => $existing->created_by ?? null,
                'updated_by' => null,
                'created_at' => $existing->created_at ?? now(),
                'updated_at' => now(),
            ]
        );
    }
}
