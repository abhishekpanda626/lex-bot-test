<?php

namespace App\Services;

use Aws\LexRuntimeV2\LexRuntimeV2Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LexService
{
    protected $client;
    protected $botId;
    protected $botAliasId;

    public function __construct()
    {
        $this->client = new LexRuntimeV2Client([
            'region' => config('aws.region', 'us-east-1'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('aws.access_key'),
                'secret' => config('aws.secret_access_key'),
            ],
        ]);

        $this->botId = config('aws.bot_id');
        $this->botAliasId = config('aws.bot_alias_id');
    }

    public function postText($inputText, $sessionId)
    {
        try {
            $result = $this->client->recognizeText([
                'botId' => $this->botId,
                'botAliasId' => $this->botAliasId,
                'localeId' => 'en_US',
                'sessionId' => $sessionId,
                'text' => $inputText,
            ]);

            $response = [
                'message' => $result['messages'][0]['content'] ?? '',
                'intentName' => $result['sessionState']['intent']['name'] ?? '',
                'intentState' => $result['sessionState']['intent']['state'] ?? '',
                'slots' => $result['sessionState']['intent']['slots'] ?? [],
                'sessionId' => $sessionId,
            ];

            Log::info('Lex Response', $response);

            return $response;

        } catch (AwsException $e) {
            Log::error('Lex Error: ' . $e->getMessage());
            return [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId,
            ];
        }
    }

    public function getIntentQuestion($intent)
    {
        $intents = [
            'office' => 'Office Visit Satisfaction',
            'doctor' => 'Doctor Communication ',
            'treatment' => 'Treatment Comfort',
            // 'followup_instructions' => 'Follow Up Instructions',
            'staff_friendliness' => 'Staff Friendliness',
        ];

        return $intents[$intent] ?? '';
    }

    public function isIntentComplete($intentState)
    {
        // This is a placeholder. You'll need to implement the logic to determine
        // if the current intent is complete based on the bot's response.
        // For example, you might check if all required slots are filled:
        return $intentState === 'Fulfilled';
    }

    public function getNextIntent($currentIntent)
    {
        $intents = array_keys($this->getIntentQuestions());
        $currentIndex = array_search($currentIntent, $intents);
        
        if ($currentIndex !== false && $currentIndex < count($intents) - 1) {
            return $intents[$currentIndex + 1];
        }
        
        return null; // No more intents
    }

    public function getIntentQuestions()
    {
        return [
            'office' => 'How was your office visit today?',
            'doctor' => 'Did the doctor answer your questions and communicate well?',
            'treatment' => 'Are you comfortable with your treatment plan?',
            // 'followup_instructions' => 'Were your follow-up instructions clear and do you have any questions?',
            'staff_friendliness' => 'Were the office staff friendly and professional today?',
        ];
    }
}