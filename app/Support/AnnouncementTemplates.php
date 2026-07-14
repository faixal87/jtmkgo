<?php

namespace App\Support;

class AnnouncementTemplates
{
    /**
     * Built-in announcement email templates. Every template shares the same
     * premium dark-and-amber shell so all system emails look consistent.
     *
     * Tokens:
     * - `[LABEL]`  — admin-fillable blanks, auto-converted to form fields on
     *   the announcement page (long fields: MESEJ/KANDUNGAN/CATATAN/UCAPAN).
     * - `{name}`   — replaced with each recipient's name at send time.
     * - `{photo}`  — replaced with the recipient's embedded profile photo.
     *
     * @return array<int, array{key: string, label: string, subject: string, html: string}>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'maintenance',
                'label' => 'Penyelenggaraan Sistem (Maintenance)',
                'subject' => 'Notis Penyelenggaraan Sistem - JTMK Go!',
                'html' => self::shell(
                    badge: 'Penyelenggaraan Berjadual',
                    heading: 'Sistem JTMK Go! akan menjalani penyelenggaraan',
                    body: <<<'HTML'
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Assalamualaikum / Salam sejahtera {name},</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Dimaklumkan bahawa Sistem JTMK Go! akan menjalani penyelenggaraan berjadual pada:</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:14px;">
                            <tr>
                                <td style="padding:10px 0;font-size:13px;font-weight:700;color:#8A7460;width:38%;border-bottom:1px solid #F0E4D4;">Tarikh</td>
                                <td style="padding:10px 0;font-size:14px;font-weight:700;color:#29231F;border-bottom:1px solid #F0E4D4;">[TARIKH]</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;font-size:13px;font-weight:700;color:#8A7460;">Waktu</td>
                                <td style="padding:10px 0;font-size:14px;font-weight:700;color:#29231F;">[MASA MULA] hingga [MASA TAMAT]</td>
                            </tr>
                        </table>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Sepanjang tempoh ini, sistem tidak akan dapat diakses. Sila pastikan sebarang kerja yang belum disimpan diselesaikan sebelum waktu penyelenggaraan bermula.</p>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#5F554D;">Kami memohon maaf di atas sebarang kesulitan yang dialami.</p>
                        HTML,
                ),
            ],
            [
                'key' => 'downtime',
                'label' => 'Sistem Tidak Boleh Diakses (Downtime)',
                'subject' => 'Notis: Sistem JTMK Go! Tidak Boleh Diakses',
                'html' => self::shell(
                    badge: 'Gangguan Sistem',
                    heading: 'Sistem JTMK Go! tidak dapat diakses buat sementara waktu',
                    body: <<<'HTML'
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Assalamualaikum / Salam sejahtera {name},</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Dimaklumkan bahawa Sistem JTMK Go! sedang mengalami gangguan teknikal dan tidak dapat diakses buat sementara waktu bermula [TARIKH/MASA].</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Pasukan teknikal sedang berusaha menyelesaikan isu ini secepat mungkin. Notis lanjut akan dihantar sebaik sistem kembali beroperasi seperti biasa.</p>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#5F554D;">Kami memohon maaf di atas sebarang kesulitan yang dialami.</p>
                        HTML,
                ),
            ],
            [
                'key' => 'peringatan',
                'label' => 'Peringatan Tugasan / Tarikh Akhir',
                'subject' => 'Peringatan: [PERKARA] - JTMK Go!',
                'html' => self::shell(
                    badge: 'Peringatan',
                    heading: 'Peringatan: [PERKARA]',
                    body: <<<'HTML'
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Assalamualaikum / Salam sejahtera {name},</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Ini adalah peringatan mesra mengenai perkara berikut:</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:14px;">
                            <tr>
                                <td style="padding:10px 0;font-size:13px;font-weight:700;color:#8A7460;width:38%;border-bottom:1px solid #F0E4D4;">Perkara</td>
                                <td style="padding:10px 0;font-size:14px;font-weight:700;color:#29231F;border-bottom:1px solid #F0E4D4;">[PERKARA]</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;font-size:13px;font-weight:700;color:#8A7460;">Tarikh Akhir</td>
                                <td style="padding:10px 0;font-size:14px;font-weight:700;color:#29231F;">[TARIKH AKHIR]</td>
                            </tr>
                        </table>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;white-space:pre-line;">[MESEJ]</p>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#5F554D;">Sila ambil tindakan sewajarnya melalui sistem JTMK Go!. Terima kasih.</p>
                        HTML,
                ),
            ],
            [
                'key' => 'profil',
                'label' => 'Kemas Kini Maklumat Profil',
                'subject' => 'Sila Kemas Kini Maklumat Profil Anda - JTMK Go!',
                'html' => self::shell(
                    badge: 'Tindakan Diperlukan',
                    heading: 'Sila kemas kini maklumat profil anda',
                    body: <<<'HTML'
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Assalamualaikum / Salam sejahtera {name},</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Semakan mendapati maklumat profil anda dalam Sistem JTMK Go! belum lengkap atau perlu dikemas kini (contohnya nombor telefon, gambar profil, atau maklumat jawatan).</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;white-space:pre-line;">[CATATAN]</p>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#5F554D;">Sila log masuk ke JTMK Go! dan kemas kini profil anda sebelum <strong style="color:#29231F;">[TARIKH AKHIR]</strong>. Terima kasih atas kerjasama anda.</p>
                        HTML,
                ),
            ],
            [
                'key' => 'hari_jadi',
                'label' => 'Ucapan Hari Jadi (dengan gambar staf)',
                'subject' => 'Selamat Hari Jadi, {name}!',
                'html' => self::shell(
                    badge: 'Ucapan Hari Jadi',
                    heading: 'Selamat Hari Jadi, {name}!',
                    body: <<<'HTML'
                        {photo}
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;text-align:center;">Seluruh warga JTMK POLIMAS mengucapkan <strong style="color:#B45309;">Selamat Hari Jadi</strong> kepada anda. Semoga panjang umur, sihat sejahtera dan terus cemerlang dalam kerjaya.</p>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#5F554D;text-align:center;font-style:italic;white-space:pre-line;">[UCAPAN]</p>
                        HTML,
                ),
            ],
            [
                'key' => 'hari_raya',
                'label' => 'Salam Hari Raya Aidilfitri',
                'subject' => 'Salam Aidilfitri daripada Warga JTMK',
                'html' => self::shell(
                    badge: 'Salam Perayaan',
                    heading: 'Selamat Hari Raya Aidilfitri [TAHUN]',
                    body: <<<'HTML'
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Assalamualaikum {name},</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Seluruh warga JTMK POLIMAS mengucapkan <strong style="color:#B45309;">Selamat Hari Raya Aidilfitri, Maaf Zahir dan Batin</strong>.</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;font-style:italic;white-space:pre-line;">[UCAPAN]</p>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#5F554D;">Semoga Syawal ini membawa keberkatan dan mengeratkan lagi silaturahim antara kita semua.</p>
                        HTML,
                ),
            ],
            [
                'key' => 'cuti_umum',
                'label' => 'Notis Cuti Umum / Cuti Am',
                'subject' => 'Notis Cuti Umum - JTMK Go!',
                'html' => self::shell(
                    badge: 'Notis Cuti',
                    heading: 'Notis Cuti: [NAMA CUTI]',
                    body: <<<'HTML'
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Assalamualaikum / Salam sejahtera {name},</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Dimaklumkan jabatan akan bercuti sempena [NAMA CUTI] seperti berikut:</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:14px;">
                            <tr>
                                <td style="padding:10px 0;font-size:13px;font-weight:700;color:#8A7460;width:38%;border-bottom:1px solid #F0E4D4;">Tarikh Cuti</td>
                                <td style="padding:10px 0;font-size:14px;font-weight:700;color:#29231F;border-bottom:1px solid #F0E4D4;">[TARIKH MULA] hingga [TARIKH TAMAT]</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;font-size:13px;font-weight:700;color:#8A7460;">Beroperasi Semula</td>
                                <td style="padding:10px 0;font-size:14px;font-weight:700;color:#29231F;">[TARIKH BEROPERASI SEMULA]</td>
                            </tr>
                        </table>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#5F554D;">Sebarang urusan melalui sistem akan diproses selepas jabatan beroperasi semula. Kami memohon maaf di atas sebarang kesulitan.</p>
                        HTML,
                ),
            ],
            [
                'key' => 'custom',
                'label' => 'Custom / Kosong',
                'subject' => '[TAJUK] - JTMK Go!',
                'html' => self::shell(
                    badge: 'Pengumuman',
                    heading: '[TAJUK]',
                    body: <<<'HTML'
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#5F554D;">Assalamualaikum / Salam sejahtera {name},</p>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#5F554D;white-space:pre-line;">[MESEJ]</p>
                        HTML,
                ),
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        foreach (self::all() as $template) {
            if ($template['key'] === $key) {
                return $template;
            }
        }

        return null;
    }

    public static function personalize(string $text, string $recipientName): string
    {
        return str_replace('{name}', $recipientName, $text);
    }

    /**
     * The single premium shell every announcement email uses: dark JTMK Go branding
     * on white with serif display headings, so all system emails share one
     * consistent identity.
     */
    private static function shell(string $badge, string $heading, string $body): string
    {
        $brand = '#29231F';
        $brandAccent = '#F59E0B';
        $brandSoft = '#FFF7ED';
        $ink = '#29231F';
        $cream = '#F7F4EF';

        return <<<HTML
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0;padding:0;background:{$cream};font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:{$ink};">
                <tr>
                    <td align="center" style="padding:32px 14px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #F0E4D4;">
                            <tr>
                                <td style="background:{$brand};padding:26px 30px;color:{$brandAccent};border-bottom:4px solid {$brandAccent};">
                                    <div style="font-family:Georgia,'Times New Roman',serif;font-size:24px;font-weight:700;letter-spacing:1.5px;">JTMK GO!</div>
                                    <div style="margin-top:5px;font-size:12.5px;letter-spacing:.6px;color:#FDE68A;">Jabatan Teknologi Maklumat &amp; Komunikasi &middot; POLIMAS</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:30px;">
                                    <div style="display:inline-block;margin-bottom:16px;border-radius:999px;background:{$brandSoft};color:#B45309;font-size:11.5px;font-weight:800;letter-spacing:.8px;padding:8px 14px;text-transform:uppercase;">{$badge}</div>
                                    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:23px;line-height:1.35;color:{$ink};font-weight:700;">{$heading}</h1>
                                    {$body}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:18px 30px;background:#FAF7F2;border-top:1px solid #F0E4D4;font-size:12px;line-height:1.6;color:#7C6F64;">
                                    E-mel ini dijana secara automatik oleh Sistem JTMK Go!. Sila jangan balas e-mel ini. Untuk sebarang pertanyaan, sila hubungi pentadbir sistem JTMK.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            HTML;
    }
}
