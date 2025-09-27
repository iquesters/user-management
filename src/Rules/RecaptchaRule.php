<?php

namespace Iquesters\UserManagement\Rules;

use Iquesters\UserManagement\Services\RecaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RecaptchaRule implements ValidationRule
{
    protected $action;
    protected $minScore;

    public function __construct($action = null, $minScore = 0.5)
    {
        $this->action = $action;
        $this->minScore = $minScore;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $recaptcha = new RecaptchaService();
        $result = $recaptcha->verify($value, $this->action, $this->minScore);

        if (!$result['success']) {
            $errorMessage = match ($result['error']) {
                'Score too low' => "Security verification failed. Score: {$result['score']}, required: {$result['threshold']}",
                'Action mismatch' => 'Security verification failed. Invalid action.',
                'reCAPTCHA verification failed' => 'Security verification failed. Please try again.',
                default => 'Security verification failed. Please try again.'
            };

            $fail($errorMessage);
        }
    }
}