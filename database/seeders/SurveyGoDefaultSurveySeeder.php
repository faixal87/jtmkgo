<?php

namespace Database\Seeders;

use App\Modules\SurveyGo\Models\Question;
use App\Modules\SurveyGo\Models\Survey;
use Illuminate\Database\Seeder;

class SurveyGoDefaultSurveySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSurvey(
            Survey::TYPE_BASELINE,
            'JTMK Go Baseline Survey',
            'Mohon lengkapkan Kajian Awal ini sebelum menggunakan JTMK Go.',
            [
                'I often have difficulty finding important links or documents shared through WhatsApp.',
                'Information related to department tasks is difficult to locate when needed.',
                'I frequently need to ask colleagues again for links or information that were previously shared.',
                'Department information management depends heavily on WhatsApp and informal communication.',
                'I spend too much time searching for department-related information.',
                'I am not always confident that the information I receive is the latest version.',
                'Academic management tasks such as subject preference or replacement class information are not fully centralized.',
                'Programme/activity records and budget-related information are difficult to monitor manually.',
                'Staff contact and profile information are not easy to find in one place.',
                'Official staff photos or documentation images are stored in multiple locations.',
                'Important portfolio links such as MQA, audit, examination, FYP, KPro or ABM links are difficult to manage centrally.',
                'Repeated communication is often needed to obtain the same information.',
                'Current manual or semi-manual processes reduce work efficiency.',
                'JTMK needs a centralized platform to manage departmental information and workflows.',
                'A digital system can improve department productivity and information accessibility.',
            ],
            [
                'Do you currently use WhatsApp as the main source to retrieve department links or information?',
                'Have you ever lost an important link shared through WhatsApp?',
                'Have you ever asked a colleague to resend a link or document?',
                'Do you personally save important links using your own method?',
                'Do you agree that JTMK needs a centralized platform for departmental information management?',
            ]
        );

        $this->seedSurvey(
            Survey::TYPE_IMPACT,
            'JTMK Go Impact Survey',
            'Mohon kongsikan maklum balas anda selepas menggunakan JTMK Go.',
            [
                'JTMK Go makes it easier for me to access important department information.',
                'JTMK Go reduces my dependency on WhatsApp for finding department links or documents.',
                'I can find department-related information faster using JTMK Go.',
                'LinkGo helps me manage and access important department links more effectively.',
                'Staff Directory helps me find staff information more easily.',
                'Photo Repository helps me access official staff photos or documentation images more efficiently.',
                'ProgramGo helps improve the management of programme/activity records and budget monitoring.',
                'SubjekGo helps make subject preference management more systematic.',
                'GantiGo helps improve replacement class record management.',
                'Information in JTMK Go is more organized and easier to understand.',
                'JTMK Go reduces repeated communication when looking for information.',
                'JTMK Go supports better collaboration among department staff.',
                'JTMK Go saves time in completing department-related tasks.',
                'I am satisfied with my experience using JTMK Go.',
                'I would recommend JTMK Go to other department staff.',
            ],
            [
                'Has JTMK Go helped you find information faster?',
                'Has your dependency on WhatsApp for retrieving department information reduced?',
                'Have you used LinkGo to access important links?',
                'Have you used Staff Directory or Photo Repository to find staff-related information?',
                'Would you continue using JTMK Go for department-related tasks?',
            ]
        );
    }

    /**
     * @param array<int, string> $likertQuestions
     * @param array<int, string> $yesNoQuestions
     */
    private function seedSurvey(string $type, string $title, string $description, array $likertQuestions, array $yesNoQuestions): void
    {
        $survey = Survey::query()->firstOrCreate(
            ['type' => $type, 'title' => $title],
            [
                'description' => $description,
                'status' => Survey::STATUS_DRAFT,
                'is_forced' => false,
                'is_notification_sent' => false,
            ]
        );

        $survey->fill(['description' => $description])->save();

        $sort = 1;

        foreach ($likertQuestions as $questionText) {
            $this->upsertQuestion($survey, $questionText, Question::TYPE_LIKERT, 'Adoption / Kajian Impak', $sort++);
        }

        foreach ($yesNoQuestions as $questionText) {
            $this->upsertQuestion($survey, $questionText, Question::TYPE_YES_NO, 'Usage Pattern', $sort++);
        }
    }

    private function upsertQuestion(Survey $survey, string $text, string $type, string $domain, int $sortOrder): void
    {
        $question = Question::query()->firstOrNew([
            'survey_id' => $survey->id,
            'question_text' => $text,
        ]);

        $question->fill([
            'question_type' => $type,
            'domain' => $domain,
            'is_required' => true,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ])->save();
    }
}
