<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.google.api_key');
        $this->apiUrl = config('services.gemini.api_url');
    }

    public function getEmotionalIndex($responses)
    {
        $prompt = "
        Based on this above conversation, please generate a JSON object with the following fields. Ensure that each field contains a value between 0.0 and 1.0, never zero, and do not include any extra text or markdown formatting backticks. Only return the values for the specified fields in json format:
              - Happy (float): A value between 0.0 and 1.0 representing the patient's level of happiness
              - Neutral (float): A value between 0.0 and 1.0 representing the patient's level of neutrality
              - Sad (float): A value between 0.0 and 1.0 representing the patient's level of sadness
              - Anxious (float): A value between 0.0 and 1.0 representing the patient's level of anxiety
              - Angry (float): A value between 0.0 and 1.0 representing the patient's level of anger
        ";
        $text = '';
        foreach ($responses as $response) {
            $text .= "{$response['type']}: {$response['message']}\n";
        }

        $message = $text . "\n\n" . $prompt;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $message],
                    ],
                ],
            ],
        ]);

        if ($response->successful()) {
            $result = $response->json();
            $emotionalIndex = json_decode($result['candidates'][0]['content']['parts'][0]['text'], true);
            return $emotionalIndex;
        } else {
            throw new \Exception("Error invoking Gemini API: " . $response->body());
        }
    }
}
