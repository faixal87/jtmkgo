<?php

namespace App\Modules\SurveyGo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public const TYPE_LIKERT = 'likert';
    public const TYPE_YES_NO = 'yes_no';
    public const TYPE_SHORT_TEXT = 'short_text';

    protected $table = 'survey_go_questions';

    protected $fillable = [
        'survey_id',
        'question_text',
        'question_type',
        'domain',
        'is_required',
        'sort_order',
        'is_active',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_LIKERT => 'Likert',
            self::TYPE_YES_NO => 'Yes / No',
            self::TYPE_SHORT_TEXT => 'Short Text',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'question_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function typeLabel(): string
    {
        return self::types()[$this->question_type] ?? str($this->question_type)->replace('_', ' ')->title()->toString();
    }

    public function displayText(): string
    {
        return self::defaultMalayQuestions()[$this->question_text] ?? $this->question_text;
    }

    public function displayTypeLabel(): string
    {
        return match ($this->question_type) {
            self::TYPE_LIKERT => 'Skala Persetujuan',
            self::TYPE_YES_NO => 'Ya / Tidak',
            self::TYPE_SHORT_TEXT => 'Jawapan Ringkas',
            default => $this->typeLabel(),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function defaultMalayQuestions(): array
    {
        return [
            'I often have difficulty finding important links or documents shared through WhatsApp.' => 'Saya sering menghadapi kesukaran mencari pautan atau dokumen penting yang dikongsi melalui WhatsApp.',
            'Information related to department tasks is difficult to locate when needed.' => 'Maklumat berkaitan tugasan jabatan sukar dicari apabila diperlukan.',
            'I frequently need to ask colleagues again for links or information that were previously shared.' => 'Saya kerap perlu meminta semula pautan atau maklumat yang pernah dikongsi daripada rakan sekerja.',
            'Department information management depends heavily on WhatsApp and informal communication.' => 'Pengurusan maklumat jabatan terlalu bergantung kepada WhatsApp dan komunikasi tidak formal.',
            'I spend too much time searching for department-related information.' => 'Saya mengambil masa yang lama untuk mencari maklumat berkaitan jabatan.',
            'I am not always confident that the information I receive is the latest version.' => 'Saya tidak sentiasa yakin bahawa maklumat yang diterima adalah versi terkini.',
            'Academic management tasks such as subject preference or replacement class information are not fully centralized.' => 'Urusan akademik seperti pemilihan subjek atau maklumat kelas ganti masih belum dipusatkan sepenuhnya.',
            'Programme/activity records and budget-related information are difficult to monitor manually.' => 'Rekod program/aktiviti dan maklumat berkaitan bajet sukar dipantau secara manual.',
            'Staff contact and profile information are not easy to find in one place.' => 'Maklumat kontak dan profil staf tidak mudah dicari dalam satu tempat.',
            'Official staff photos or documentation images are stored in multiple locations.' => 'Gambar rasmi staf atau imej dokumentasi disimpan di pelbagai lokasi.',
            'Important portfolio links such as MQA, audit, examination, FYP, KPro or ABM links are difficult to manage centrally.' => 'Pautan portfolio penting seperti MQA, audit, peperiksaan, FYP, KPro atau ABM sukar diurus secara berpusat.',
            'Repeated communication is often needed to obtain the same information.' => 'Komunikasi berulang sering diperlukan untuk mendapatkan maklumat yang sama.',
            'Current manual or semi-manual processes reduce work efficiency.' => 'Proses manual atau separa manual semasa mengurangkan kecekapan kerja.',
            'JTMK needs a centralized platform to manage departmental information and workflows.' => 'JTMK memerlukan satu platform berpusat untuk mengurus maklumat dan aliran kerja jabatan.',
            'A digital system can improve department productivity and information accessibility.' => 'Sistem digital boleh meningkatkan produktiviti jabatan dan memudahkan akses kepada maklumat.',
            'Do you currently use WhatsApp as the main source to retrieve department links or information?' => 'Adakah anda kini menggunakan WhatsApp sebagai sumber utama untuk mendapatkan pautan atau maklumat jabatan?',
            'Have you ever lost an important link shared through WhatsApp?' => 'Pernahkah anda kehilangan pautan penting yang dikongsi melalui WhatsApp?',
            'Have you ever asked a colleague to resend a link or document?' => 'Pernahkah anda meminta rakan sekerja menghantar semula pautan atau dokumen?',
            'Do you personally save important links using your own method?' => 'Adakah anda menyimpan pautan penting menggunakan kaedah sendiri?',
            'Do you agree that JTMK needs a centralized platform for departmental information management?' => 'Adakah anda bersetuju bahawa JTMK memerlukan platform berpusat untuk pengurusan maklumat jabatan?',
            'JTMK Go makes it easier for me to access important department information.' => 'JTMK Go memudahkan saya mengakses maklumat penting jabatan.',
            'JTMK Go reduces my dependency on WhatsApp for finding department links or documents.' => 'JTMK Go mengurangkan kebergantungan saya kepada WhatsApp untuk mencari pautan atau dokumen jabatan.',
            'I can find department-related information faster using JTMK Go.' => 'Saya dapat mencari maklumat berkaitan jabatan dengan lebih cepat menggunakan JTMK Go.',
            'LinkGo helps me manage and access important department links more effectively.' => 'LinkGo membantu saya mengurus dan mengakses pautan penting jabatan dengan lebih berkesan.',
            'Staff Directory helps me find staff information more easily.' => 'Staff Directory membantu saya mencari maklumat staf dengan lebih mudah.',
            'Photo Repository helps me access official staff photos or documentation images more efficiently.' => 'Photo Repository membantu saya mengakses gambar rasmi staf atau imej dokumentasi dengan lebih cekap.',
            'ProgramGo helps improve the management of programme/activity records and budget monitoring.' => 'ProgramGo membantu menambah baik pengurusan rekod program/aktiviti dan pemantauan bajet.',
            'SubjekGo helps make subject preference management more systematic.' => 'SubjekGo membantu menjadikan pengurusan pemilihan subjek lebih sistematik.',
            'GantiGo helps improve replacement class record management.' => 'GantiGo membantu menambah baik pengurusan rekod kelas ganti.',
            'Information in JTMK Go is more organized and easier to understand.' => 'Maklumat dalam JTMK Go lebih tersusun dan mudah difahami.',
            'JTMK Go reduces repeated communication when looking for information.' => 'JTMK Go mengurangkan komunikasi berulang semasa mencari maklumat.',
            'JTMK Go supports better collaboration among department staff.' => 'JTMK Go menyokong kerjasama yang lebih baik dalam kalangan staf jabatan.',
            'JTMK Go saves time in completing department-related tasks.' => 'JTMK Go menjimatkan masa dalam menyelesaikan tugasan berkaitan jabatan.',
            'I am satisfied with my experience using JTMK Go.' => 'Saya berpuas hati dengan pengalaman menggunakan JTMK Go.',
            'I would recommend JTMK Go to other department staff.' => 'Saya akan mengesyorkan JTMK Go kepada staf jabatan yang lain.',
            'Has JTMK Go helped you find information faster?' => 'Adakah JTMK Go membantu anda mencari maklumat dengan lebih cepat?',
            'Has your dependency on WhatsApp for retrieving department information reduced?' => 'Adakah kebergantungan anda kepada WhatsApp untuk mendapatkan maklumat jabatan telah berkurang?',
            'Have you used LinkGo to access important links?' => 'Adakah anda telah menggunakan LinkGo untuk mengakses pautan penting?',
            'Have you used Staff Directory or Photo Repository to find staff-related information?' => 'Adakah anda telah menggunakan Staff Directory atau Photo Repository untuk mencari maklumat berkaitan staf?',
            'Would you continue using JTMK Go for department-related tasks?' => 'Adakah anda akan terus menggunakan JTMK Go untuk tugasan berkaitan jabatan?',
        ];
    }

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
