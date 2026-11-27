<?php

namespace Iquesters\UserManagement\Support;

use Illuminate\Support\Facades\Mail;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\Support\ConfProvider;

/**
 * The email with a sign-in code. With iquesters/notifications installed it is
 * the app message "Sign-in code" (wording editable by super-admins in
 * Notifications → Templates, the app's logo and name, logged without the code);
 * without it, a built-in email in the same spirit. Sent at once, never later.
 */
class SignInCodeEmail
{
    public const TYPE = 'auth.sign_in_code';

    private const TYPES = 'Iquesters\Notifications\Support\NotificationTypes';

    private const SENDER = 'Iquesters\Notifications\Notifications';

    public static function viaNotifications(): bool
    {
        return class_exists(self::TYPES) && class_exists(self::SENDER) && app()->bound(self::TYPES);
    }

    /** Declare the message to the notifications module (its service provider's boot). */
    public static function register(): void
    {
        if (! self::viaNotifications()) {
            return;
        }
        app(self::TYPES)->register(self::TYPE, [
            'label' => 'Sign-in code',
            'description' => 'The code someone types to sign in or create their account. Always sent; the code itself is never kept in Sent messages.',
            'module' => 'user-management',
            // The app's message, not an organisation's; can't be switched off; the code isn't logged.
            'platform' => true,
            'required' => true,
            'secret' => ['code'],
            'variables' => ['code' => 'The code', 'minutes' => 'How long it is valid (minutes)', 'app' => 'The app\'s name'],
            'sample' => ['code' => '482913', 'minutes' => '10', 'app' => 'TTV Alumni'],
            'defaults' => ['email' => [
                'subject' => '{{code}} is your {{app}} sign-in code',
                'body' => "Hi there,\n\nHere's your code to sign in to **{{app}}**:\n\n## {{code}}\n\nJust enter it on the sign-in page. It works for the next **{{minutes}} minutes** and only once.\n\n**Didn't try to sign in?** No need to worry: simply ignore this email. Nobody can get into your account without this code.\n\nSee you soon,  \nThe {{app}} team",
            ]],
        ]);
    }

    /**
     * Send the code right after the page has answered: the sign-in page moves on
     * to "Check your email" at once instead of waiting for the mail server (a few
     * seconds with Gmail). A failure is logged (and in Sent messages); the person
     * presses Resend.
     */
    public static function send(string $email, string $code, ?int $userId = null): void
    {
        dispatch(function () use ($email, $code, $userId) {
            try {
                self::sendNow($email, $code, $userId);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Sign-in code email not sent: '.$e->getMessage());
            }
        })->afterResponse();
    }

    /**
     * Send the code now and wait for the mail server.
     *
     * @throws \Throwable when it can't be sent
     */
    public static function sendNow(string $email, string $code, ?int $userId = null): void
    {
        $values = ['code' => $code, 'minutes' => (string) self::minutes(), 'app' => self::appName()];

        if (self::viaNotifications()) {
            (self::SENDER)::sendNow(self::TYPE, null, ['email' => $email, 'user_id' => $userId], $values);

            return;
        }

        $subject = $code.' is your '.$values['app'].' sign-in code';
        Mail::html(view('usermanagement::emails.sign-in-code', $values + ['logoUrl' => self::appLogoUrl(), 'subject' => $subject])->render(), function ($message) use ($email, $subject, $values) {
            $message->to($email)->subject($subject)->from(config('mail.from.address'), $values['app']);
        });
    }

    /** How long a code is valid (the OTP setting). */
    public static function minutes(): int
    {
        try {
            $minutes = (int) ConfProvider::from(Module::USER_MGMT)->whatsapp_login->otp_ttl_minutes;
        } catch (\Throwable $e) {
            $minutes = 0;
        }

        return $minutes > 0 ? $minutes : 10;
    }

    private static function appName(): string
    {
        $assets = 'Iquesters\UserInterface\Support\Assets';
        try {
            $name = class_exists($assets) ? trim((string) $assets::appName()) : '';
        } catch (\Throwable $e) {
            $name = '';
        }

        return $name !== '' ? $name : (string) config('app.name');
    }

    private static function appLogoUrl(): ?string
    {
        $assets = 'Iquesters\UserInterface\Support\Assets';
        try {
            $url = class_exists($assets) ? trim((string) $assets::logoUrl()) : '';
        } catch (\Throwable $e) {
            $url = '';
        }
        if ($url === '') {
            return null;
        }

        $url = preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'data:') ? $url : url($url);

        // An address only this computer knows (localhost) shows as a broken image in the inbox: left out.
        return preg_match('#^https?://(localhost|127\.0\.0\.1|\[::1\])([:/]|$)#i', $url) ? null : $url;
    }
}
