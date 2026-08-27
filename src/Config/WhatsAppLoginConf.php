<?php

namespace Iquesters\UserManagement\Config;

use Iquesters\Foundation\Support\BaseConf;

class WhatsAppLoginConf extends BaseConf
{
    protected ?string $identifier = 'whatsapp_login';

    protected bool $enabled;
    protected string $delivery_provider;
    protected string $graph_version;
    protected ?string $phone_number_id;
    protected ?string $access_token;
    protected ?string $verify_template_name;
    protected string $template_language_code;
    protected int $otp_length;
    protected int $otp_ttl_minutes;
    protected int $max_attempts;
    protected int $resend_cooldown_seconds;
    protected int $max_send_per_hour;
    protected int $max_verify_failures_per_window;
    protected int $max_global_sends_per_hour;

    protected function prepareDefault(BaseConf $default_values)
    {
        $default_values->enabled = false;
        $default_values->delivery_provider = 'meta';
        $default_values->graph_version = 'v23.0';
        $default_values->phone_number_id = '';
        $default_values->access_token = '';
        $default_values->verify_template_name = 'login_verification';
        $default_values->template_language_code = 'en_IN';
        $default_values->otp_length = 6;
        $default_values->otp_ttl_minutes = 10;
        $default_values->max_attempts = 5;
        $default_values->resend_cooldown_seconds = 60;
        $default_values->max_send_per_hour = 5;
        $default_values->max_verify_failures_per_window = 10;
        $default_values->max_global_sends_per_hour = 250;
    }
}