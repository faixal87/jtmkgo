@php
    $selectClass = 'mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]';

    $awsRegions = [
        'ap-southeast-1' => 'Asia Pacific (Singapore) - ap-southeast-1',
        'ap-southeast-2' => 'Asia Pacific (Sydney) - ap-southeast-2',
        'ap-south-1' => 'Asia Pacific (Mumbai) - ap-south-1',
        'us-east-1' => 'US East (N. Virginia) - us-east-1',
        'us-west-2' => 'US West (Oregon) - us-west-2',
        'eu-west-1' => 'Europe (Ireland) - eu-west-1',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Email</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Configure SMTP or Amazon SES so dashboard notifications are also delivered by email.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="{
                mailProvider: @js(old('mail_provider', $settings['mail_provider'] ?? 'smtp')),
            }"
        >
            <x-toast />

            @include('super-admin.settings.partials.mail-tabs', ['activeTab' => 'settings'])

            <div class="enterprise-card rounded-2xl border p-6">
                <div class="flex items-center gap-2">
                    <span class="text-lg">&#128231;</span>
                    <h3 class="text-lg font-semibold text-[var(--color-text)]">Email Delivery</h3>
                </div>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Choose an email delivery provider. Both sets of settings are stored separately, so you can switch anytime without retyping.</p>

                <form method="POST" action="{{ route('super-admin.settings.mail.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="max-w-md">
                        <x-input-label for="mail_provider" value="Mail Provider" />
                        <select id="mail_provider" name="mail_provider" x-model="mailProvider" class="{{ $selectClass }}">
                            <option value="smtp" @selected(old('mail_provider', $settings['mail_provider'] ?? 'smtp') === 'smtp')>SMTP Biasa (Gmail / cPanel / lain-lain)</option>
                            <option value="ses" @selected(old('mail_provider', $settings['mail_provider'] ?? 'smtp') === 'ses')>Amazon SES</option>
                        </select>
                        <x-form-helper>
                            <span x-show="mailProvider === 'ses'">Amazon SES uses AWS's official SMTP endpoint — host, port (587), and TLS are set automatically based on region.</span>
                            <span x-show="mailProvider === 'smtp'">A standard SMTP server such as Gmail, Outlook, or your hosting provider's cPanel mail.</span>
                        </x-form-helper>
                        <x-input-error :messages="$errors->get('mail_provider')" class="mt-2" />
                    </div>

                    <div x-show="mailProvider === 'ses'" x-cloak class="space-y-5">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            <p class="font-semibold">Amazon SES Requirements</p>
                            <p class="mt-1 leading-6">The From Email below must be an identity verified in SES. While the account is still in the SES sandbox, the recipient address must also be verified. Use the SMTP credentials generated in the SES Console — not your regular AWS access key.</p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-3">
                            <div>
                                <x-input-label for="ses_region" value="SES Region" />
                                <select id="ses_region" name="ses_region" class="{{ $selectClass }}">
                                    @foreach ($awsRegions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('ses_region', $settings['ses_region'] ?? 'ap-southeast-1') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-form-helper>Region of your SES account. Endpoint: email-smtp.&lt;region&gt;.amazonaws.com:587</x-form-helper>
                                <x-input-error :messages="$errors->get('ses_region')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="ses_smtp_username" value="SES SMTP Username" />
                                <x-text-input id="ses_smtp_username" name="ses_smtp_username" class="mt-1 block w-full" :value="old('ses_smtp_username', $settings['ses_smtp_username'] ?? '')" autocomplete="off" />
                                <x-form-helper>The SMTP username generated in SES Console &rarr; SMTP settings.</x-form-helper>
                                <x-input-error :messages="$errors->get('ses_smtp_username')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="ses_smtp_password" value="SES SMTP Password" />
                                <x-text-input id="ses_smtp_password" name="ses_smtp_password" type="password" class="mt-1 block w-full" value="" autocomplete="new-password" placeholder="{{ $hasSesPassword ? 'Leave blank to keep existing password' : 'Not set' }}" />
                                <x-form-helper>Stored encrypted. Leave blank to keep the currently saved password.</x-form-helper>
                                <x-input-error :messages="$errors->get('ses_smtp_password')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div x-show="mailProvider === 'smtp'" x-cloak class="space-y-5">
                        <div class="grid gap-5 md:grid-cols-3">
                            <div>
                                <x-input-label for="smtp_mailer" value="Mailer" />
                                <select id="smtp_mailer" name="smtp_mailer" class="{{ $selectClass }}">
                                    <option value="smtp" @selected(old('smtp_mailer', $settings['smtp_mailer'] ?? 'smtp') === 'smtp')>SMTP</option>
                                    <option value="log" @selected(old('smtp_mailer', $settings['smtp_mailer'] ?? 'smtp') === 'log')>Log (local file only)</option>
                                    <option value="array" @selected(old('smtp_mailer', $settings['smtp_mailer'] ?? 'smtp') === 'array')>Array (testing only)</option>
                                </select>
                                <x-form-helper>Selects how outgoing emails are delivered. Log/Array modes write to local logs only — no real email leaves the server.</x-form-helper>
                                <x-input-error :messages="$errors->get('smtp_mailer')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="smtp_host" value="SMTP Host" />
                                <x-text-input id="smtp_host" name="smtp_host" class="mt-1 block w-full" :value="old('smtp_host', $settings['smtp_host'] ?? '')" placeholder="e.g. smtp.gmail.com" />
                                <x-form-helper>Only used when Mailer is set to SMTP.</x-form-helper>
                                <x-input-error :messages="$errors->get('smtp_host')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="smtp_port" value="SMTP Port" />
                                <x-text-input id="smtp_port" name="smtp_port" type="number" class="mt-1 block w-full" :value="old('smtp_port', $settings['smtp_port'] ?? '')" placeholder="e.g. 587" />
                                <x-form-helper>Common values: 587 (TLS/STARTTLS) or 465 (SSL).</x-form-helper>
                                <x-input-error :messages="$errors->get('smtp_port')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="smtp_username" value="SMTP Username" />
                                <x-text-input id="smtp_username" name="smtp_username" class="mt-1 block w-full" :value="old('smtp_username', $settings['smtp_username'] ?? '')" autocomplete="off" />
                                <x-form-helper>Login username for your SMTP mail account.</x-form-helper>
                                <x-input-error :messages="$errors->get('smtp_username')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="smtp_password" value="SMTP Password" />
                                <x-text-input id="smtp_password" name="smtp_password" type="password" class="mt-1 block w-full" value="" autocomplete="new-password" placeholder="{{ $hasSmtpPassword ? 'Leave blank to keep existing password' : 'Not set' }}" />
                                <x-form-helper>Stored encrypted in the database. Leave blank to keep the current saved password unchanged.</x-form-helper>
                                <x-input-error :messages="$errors->get('smtp_password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="smtp_encryption" value="Encryption / Scheme" />
                                <select id="smtp_encryption" name="smtp_encryption" class="{{ $selectClass }}">
                                    <option value="tls" @selected(old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') === 'tls')>TLS / STARTTLS (smtp)</option>
                                    <option value="ssl" @selected(old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') === 'ssl')>SSL (smtps)</option>
                                    <option value="" @selected(old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') === '')>None</option>
                                </select>
                                <x-form-helper>Use TLS/STARTTLS for port 587, SSL for port 465. Wrong scheme is the most common cause of SMTP test failures.</x-form-helper>
                                <x-input-error :messages="$errors->get('smtp_encryption')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[var(--color-border)] pt-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Sender Identity (both providers)</p>

                        <div class="mt-4 grid gap-5 md:grid-cols-3">
                            <div>
                                <x-input-label for="mail_from_address" value="From Email" />
                                <x-text-input id="mail_from_address" name="mail_from_address" type="email" class="mt-1 block w-full" :value="old('mail_from_address', $settings['mail_from_address'] ?? '')" placeholder="e.g. noreply@jtmkpolimas.com" required />
                                <x-form-helper>Sender address shown on all outgoing system emails. For SES, this address must be a verified identity.</x-form-helper>
                                <x-input-error :messages="$errors->get('mail_from_address')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="mail_from_name" value="From Name" />
                                <x-text-input id="mail_from_name" name="mail_from_name" class="mt-1 block w-full" :value="old('mail_from_name', $settings['mail_from_name'] ?? 'JTMK Go!')" placeholder="e.g. JTMK Go!" required />
                                <x-form-helper>Sender display name shown alongside the From Email above.</x-form-helper>
                                <x-input-error :messages="$errors->get('mail_from_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="mail_ehlo_domain" value="EHLO Domain" />
                                <x-text-input id="mail_ehlo_domain" name="mail_ehlo_domain" class="mt-1 block w-full" :value="old('mail_ehlo_domain', $settings['mail_ehlo_domain'] ?? '')" placeholder="e.g. jtmkpolimas.com" />
                                <x-form-helper>Optional. Some SMTP providers require this to match your sending domain.</x-form-helper>
                                <x-input-error :messages="$errors->get('mail_ehlo_domain')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[var(--color-border)] pt-5">
                        <label class="enterprise-card flex cursor-pointer items-center gap-3 rounded-xl border p-4">
                            <input type="checkbox" name="mail_notifications_enabled" value="1" class="h-5 w-5 rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(old('mail_notifications_enabled', $settings['mail_notifications_enabled'] ?? '1') == '1')>
                            <span>
                                <span class="block text-sm font-semibold text-[var(--color-text)]">Enable email notifications</span>
                                <span class="mt-1 block text-xs leading-5 text-[var(--color-muted)]">When on, every notification sent to the dashboard bell icon is also emailed to the recipient.</span>
                            </span>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-[var(--color-border)] pt-5">
                        <button type="submit" class="theme-button-primary inline-flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold shadow-sm">
                            <span>&#128190;</span> Save Config
                        </button>
                        <p class="text-xs text-[var(--color-muted)]">Saving stores both SMTP and SES settings together — switching providers later won't lose the other's saved values.</p>
                    </div>
                </form>
            </div>

            <div class="enterprise-card rounded-2xl border p-6">
                <div class="flex items-center gap-2">
                    <span class="text-lg">&#9992;&#65039;</span>
                    <h3 class="text-lg font-semibold text-[var(--color-text)]">Test SMTP</h3>
                </div>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Send a test email using the saved mail config. Save the config first before testing.</p>

                <form method="POST" action="{{ route('super-admin.settings.mail.test') }}" class="mt-4 flex flex-col gap-3 md:flex-row md:items-end">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="test_email" value="Send Test Email To" />
                        <x-text-input id="test_email" name="test_email" type="email" class="mt-1 block w-full" value="{{ old('test_email', auth()->user()->email) }}" required />
                    </div>
                    <button type="submit" class="theme-button-primary inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold shadow-sm">
                        <span>&#9992;&#65039;</span> Test SMTP
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
