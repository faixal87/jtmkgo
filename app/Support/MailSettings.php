<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MailSettings
{
    /**
     * Keys whose stored value is encrypted at rest.
     *
     * @var array<int, string>
     */
    private const ENCRYPTED_KEYS = ['smtp_password', 'ses_smtp_password'];

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return $this->defaults();
        }

        return SafeArrayCache::rememberForever('mail.settings', function (): array {
            $stored = Setting::query()
                ->whereIn('setting_key', array_keys($this->defaults()))
                ->pluck('setting_value', 'setting_key')
                ->all();

            $settings = array_replace($this->defaults(), $stored);

            $settings['mail_provider'] = in_array($settings['mail_provider'], ['smtp', 'ses'], true)
                ? $settings['mail_provider']
                : 'smtp';

            $settings['smtp_mailer'] = in_array($settings['smtp_mailer'], ['smtp', 'log', 'array'], true)
                ? $settings['smtp_mailer']
                : 'smtp';

            $settings['smtp_encryption'] = in_array($settings['smtp_encryption'], ['tls', 'ssl', ''], true)
                ? $settings['smtp_encryption']
                : '';

            return $settings;
        }, array_keys($this->defaults()));
    }

    public function get(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->all()['mail_notifications_enabled'] ?? false);
    }

    public function hasSecret(string $key): bool
    {
        return in_array($key, self::ENCRYPTED_KEYS, true) && filled($this->all()[$key] ?? null);
    }

    /**
     * Decrypt a stored secret value for internal use (applying runtime mail
     * config, sending a test email). Never expose the return value in a view.
     */
    public function decryptedSecret(string $key): ?string
    {
        $value = $this->all()[$key] ?? null;

        if (! $value || ! in_array($key, self::ENCRYPTED_KEYS, true)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, string|null>  $settings
     */
    public function update(array $settings): void
    {
        foreach (self::ENCRYPTED_KEYS as $secretKey) {
            if (! array_key_exists($secretKey, $settings) || $settings[$secretKey] === '' || $settings[$secretKey] === null) {
                unset($settings[$secretKey]);

                continue;
            }

            $settings[$secretKey] = Crypt::encryptString($settings[$secretKey]);
        }

        foreach ($settings as $key => $value) {
            if (! array_key_exists($key, $this->defaults())) {
                continue;
            }

            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        Cache::forget('mail.settings');
    }

    /**
     * Apply the stored mail settings on top of config/mail.php at runtime.
     * No-ops when the settings table doesn't exist yet, so a fresh install
     * behaves exactly like the .env-driven defaults.
     *
     * Must run BEFORE Mail::to()/Mail::send() — Laravel builds the transport
     * from config the moment the mailer is first resolved, so applying inside
     * a Mailable's build() is too late and mail silently follows .env instead.
     *
     * Amazon SES is delivered through SES's SMTP interface (not Laravel's
     * API-based "ses" driver), so this only ever configures the "smtp"
     * mailer — host/port/encryption are just derived differently depending
     * on which provider is active.
     */
    public function applyToRuntimeConfig(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = $this->all();

        if ($settings['mail_provider'] === 'ses') {
            $region = $settings['ses_region'] ?: 'us-east-1';

            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', "email-smtp.{$region}.amazonaws.com");
            Config::set('mail.mailers.smtp.port', 587);
            Config::set('mail.mailers.smtp.scheme', 'smtp');

            if (filled($settings['ses_smtp_username'])) {
                Config::set('mail.mailers.smtp.username', $settings['ses_smtp_username']);
            }

            if ($this->hasSecret('ses_smtp_password')) {
                Config::set('mail.mailers.smtp.password', $this->decryptedSecret('ses_smtp_password'));
            }
        } else {
            Config::set('mail.default', $settings['smtp_mailer']);

            if ($settings['smtp_mailer'] === 'smtp') {
                if (filled($settings['smtp_host'])) {
                    Config::set('mail.mailers.smtp.host', $settings['smtp_host']);
                }

                if (filled($settings['smtp_port'])) {
                    Config::set('mail.mailers.smtp.port', $settings['smtp_port']);
                }

                if (filled($settings['smtp_username'])) {
                    Config::set('mail.mailers.smtp.username', $settings['smtp_username']);
                }

                if ($this->hasSecret('smtp_password')) {
                    Config::set('mail.mailers.smtp.password', $this->decryptedSecret('smtp_password'));
                }

                // Symfony Mailer schemes: 'smtp' = STARTTLS (port 587), 'smtps' = implicit SSL (port 465).
                Config::set('mail.mailers.smtp.scheme', match ($settings['smtp_encryption']) {
                    'tls' => 'smtp',
                    'ssl' => 'smtps',
                    default => null,
                });
            }
        }

        if (filled($settings['mail_ehlo_domain'])) {
            Config::set('mail.mailers.smtp.local_domain', $settings['mail_ehlo_domain']);
        }

        if (filled($settings['mail_from_address'])) {
            Config::set('mail.from.address', $settings['mail_from_address']);
        }

        if (filled($settings['mail_from_name'])) {
            Config::set('mail.from.name', $settings['mail_from_name']);
        }

        // Drop cached mailer instances so the next Mail:: call rebuilds the
        // transport from the config applied above (also refreshes stale
        // transports in long-running queue workers after settings change).
        Mail::forgetMailers();
    }

    /**
     * @return array<string, string|null>
     */
    public function defaults(): array
    {
        return [
            'mail_provider' => 'smtp',
            'mail_notifications_enabled' => '1',

            'smtp_mailer' => 'smtp',
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_encryption' => 'tls',

            'ses_region' => 'ap-southeast-1',
            'ses_smtp_username' => null,
            'ses_smtp_password' => null,

            'mail_from_address' => null,
            'mail_from_name' => null,
            'mail_ehlo_domain' => null,
        ];
    }
}
