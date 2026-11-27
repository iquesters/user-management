<?php

namespace Iquesters\UserManagement;

use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\System\Providers\BaseServiceProvider;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\UserInterface\Config\UserInterfaceConf;
use Iquesters\UserManagement\Contracts\WhatsAppOtpSender;
use Iquesters\UserManagement\Install\InstallSteps;
use Iquesters\UserManagement\Services\MetaWhatsAppOtpSender;
use Iquesters\UserManagement\Support\Assets;

/**
 * Migrations, views (usermanagement::), routes/web.php, the
 * user-management:seed command, middleware aliases and the conf are loaded by
 * BaseServiceProvider via Package\UserManagementPackageInfo. This class only
 * adds what is specific to the module.
 */
class UserManagementServiceProvider extends BaseServiceProvider
{
    use Loggable;

    public function register(): void
    {
        parent::register();

        // Meta is the only supported WhatsApp OTP delivery provider. Fail loudly on
        // anything else (blank/misconfigured included) instead of silently faking sends.
        $this->app->bind(WhatsAppOtpSender::class, function ($app) {
            $provider = strtolower(trim((string) (ConfProvider::from(Module::USER_MGMT)->whatsapp_login->delivery_provider ?? '')));

            if ($provider !== 'meta') {
                throw new \RuntimeException("Unsupported WhatsApp delivery_provider: '{$provider}'. Only 'meta' is supported.");
            }

            return $app->make(MetaWhatsAppOtpSender::class);
        });
    }

    public function boot(): void
    {
        parent::boot();

        // The sign-in code email, as an editable app message when notifications is installed.
        \Iquesters\UserManagement\Support\SignInCodeEmail::register();

        Assets::registerRoutes();

        // Layout the auth views extend: user-interface's when installed, else ours.
        $this->app->instance('auth.layout', $this->resolveAuthLayout());

        $this->registerRecordComponents();

        // Signed-in people agree to the current Terms / Privacy policy once (Terms::required()).
        $this->app['router']->pushMiddlewareToGroup('web', \Iquesters\UserManagement\Http\Middleware\EnsureTermsAccepted::class);

        $this->publishes([
            __DIR__.'/../resources/views/layouts/package.blade.php' => resource_path('views/vendor/usermanagement/layouts/package.blade.php'),
        ], 'user-management-config');

        // 'laravel-assets' is re-published by the app after every `composer update`.
        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/usermanagement'),
        ], ['laravel-assets', 'user-management-assets']);

        // composer install/update: publish Spatie/Sanctum files, patch the user model.
        $this->onPackageDiscover(fn () => InstallSteps::run());
    }

    /**
     * The Users table's record components (resources/views/records/user/):
     * "user.summary" (list rows) and "user.details" (right panel). Only with
     * iquesters/user-interface, which renders them.
     */
    private function registerRecordComponents(): void
    {
        $registry = 'Iquesters\\UserInterface\\Support\\RecordComponents';

        if (! class_exists($registry)) {
            return;
        }

        $components = $this->app->make($registry);
        $components->register('user.summary', 'usermanagement::records.user.summary', 'summary', 'User (avatar, name, email)');
        $components->register('user.details', 'usermanagement::records.user.details', 'details', 'User details with roles');
    }

    private function resolveAuthLayout(): string
    {
        if (class_exists(UserInterfaceConf::class)) {
            try {
                return ConfProvider::from(Module::USER_INFE)->auth_layout;
            } catch (\Throwable $e) {
                static::logWarning('Falling back to the user-management auth layout: '.$e->getMessage());
            }
        }

        return ConfProvider::from(Module::USER_MGMT)->auth_layout;
    }

    /* Asset URLs, kept here because views call them on the provider. */

    public static function getLogoUrl(): string
    {
        return Assets::logoUrl();
    }

    public static function getCssUrl(string $file = 'css/app.css'): string
    {
        return Assets::url($file);
    }

    public static function getJsUrl(string $file = 'js/app.js'): string
    {
        return Assets::url($file);
    }
}
