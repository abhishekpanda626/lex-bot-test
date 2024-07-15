<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppointmentQuestionnaire;
use App\Services\GeminiService;

class MockConversationController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function handleConversation(Request $request)
    {
        $appointmentId = $request->input('appointment_id') ?? mt_rand(100000, 999999); // Generate random integer appointment_id if not provided
        $this->startConversation($appointmentId);
    }

    private function startConversation($appointmentId)
    {
        $lexResponses = [
            "start" => "Hello! How can I help you today?",
            "question_1" => "What type of treatment are you looking for?",
            "question_2" => "Do you have any previous medical history related to fertility?",
            "end" => "Thank you for your responses. Have a great day!"
        ];

        $artisanResponses = [
            "response_1" => "I am looking for information on IVF.",
            "response_2" => "Yes, I have undergone treatment before."
        ];

        // Initialize conversation data
        $conversationData = [];

        // Simulate Lex and Artisan conversation
        $conversationData[] = ['type' => 'lex', 'message' => $lexResponses['start']];
        $conversationData[] = ['type' => 'artisan', 'message' => $artisanResponses['response_1']];
        $conversationData[] = ['type' => 'lex', 'message' => $lexResponses['question_1']];
        $conversationData[] = ['type' => 'artisan', 'message' => $artisanResponses['response_2']];
        $conversationData[] = ['type' => 'lex', 'message' => $lexResponses['question_2']];
        $conversationData[] = ['type' => 'lex', 'message' => $lexResponses['end']];

        // Save conversation data
        $questionnaire = AppointmentQuestionnaire::firstOrNew(['appointment_id' => $appointmentId]);
        $questionnaire->questionnaire = $conversationData;
        $questionnaire->save();

        // Send data to Anthropic API and get emotional index
        $emotionalIndex = $this->geminiService->getEmotionalIndex($conversationData);

        // Send emotional index to Artisan
        // $this->sendEmotionalIndexToArtisan($appointmentId, $emotionalIndex);

        return response()->json([
            'message' => 'Conversation saved successfully',
            'appointment_id' => $appointmentId,
            'emotional_index' => $emotionalIndex
        ]);
    }

    private function sendEmotionalIndexToArtisan($appointmentId, $emotionalIndex)
    {
        $client = new \GuzzleHttp\Client();
        $client->post('https://artisan-api-url.com/endpoint', [
            'json' => [
                'appointment_id' => $appointmentId,
                'emotional_index' => $emotionalIndex,
            ]
        ]);
    }
}
