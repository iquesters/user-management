<?php

namespace Iquesters\UserManagement\Http\Requests\Auth;

use Iquesters\UserManagement\Rules\RecaptchaRule;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->baseRulesFromSchema() ?? [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];

        $recaptcha = ConfProvider::from(Module::USER_MGMT)->recaptcha;
        $recaptchaEnabled = $recaptcha->enabled;
        
        if ($recaptchaEnabled) {
            $rules['recaptcha_token'] = ['required', new RecaptchaRule('login', 0.5)];
        }

        return $rules;
    }

    /**
     * Derive email/password rules from the login-with-password FormSchema so
     * the schema-rendered form and this request validate identically. Returns
     * null (falling back to the hardcoded rules above) if user-interface
     * isn't installed or the schema hasn't been seeded yet.
     */
    protected function baseRulesFromSchema(): ?array
    {
        if (! class_exists(\Iquesters\UserInterface\Models\FormSchema::class)
            || ! class_exists(\Iquesters\UserInterface\Support\DynamicFormSchema::class)) {
            return null;
        }

        $formSchema = \Iquesters\UserInterface\Models\FormSchema::where('slug', 'login-with-password')->first();

        if (! $formSchema || empty($formSchema->schema['fields'])) {
            return null;
        }

        return \Iquesters\UserInterface\Support\DynamicFormSchema::toRules($formSchema->schema);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}