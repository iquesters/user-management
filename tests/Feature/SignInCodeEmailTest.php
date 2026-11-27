<?php

namespace Iquesters\UserManagement\Tests\Feature;

use Iquesters\UserManagement\Support\SignInCodeEmail;
use Iquesters\UserManagement\Tests\TestCase;

/** The sign-in code email (built in, without the notifications module): the app's name, the code, how long it lasts. */
class SignInCodeEmailTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.from', ['address' => 'noreply@example.test', 'name' => 'Mailer']);
    }

    public function test_the_code_email_names_the_app_and_shows_the_code(): void
    {
        // Sent right after the page answers: in a test, "after" is at once.
        app(\Illuminate\Contracts\Bus\Dispatcher::class)->withoutDispatchingAfterResponses();
        SignInCodeEmail::send('new.person@example.test', '482913');

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages[0]->getOriginalMessage();
        // The code in the subject (seen in the phone's notification), from the app.
        $this->assertStringStartsWith('482913 is your ', $email->getSubject());
        $html = $email->getHtmlBody();
        $this->assertStringContainsString('482913', $html);
        $this->assertStringContainsString('for the next <strong>'.SignInCodeEmail::minutes().' minutes</strong>', $html);
        $this->assertSame('new.person@example.test', $email->getTo()[0]->getAddress());
    }
}
