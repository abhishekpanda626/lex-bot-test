<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\LexRuntimeService\LexRuntimeServiceClient;
use GuzzleHttp\Client;
use App\Models\AppointmentQuestionnaire;

class AppointmentController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $appointmentId = $request->input('appointment_id');
        $this->triggerLexBot($appointmentId);
    }

    private function triggerLexBot($appointmentId)
    {
        $lexClient = new LexRuntimeServiceClient([
            'region'  => 'us-east-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ]
        ]);

        $userId = 'user_' . $appointmentId;

        // Start conversation with Lex bot
        $result = $lexClient->postText([
            'botAlias' => 'YOUR_BOT_ALIAS',
            'botName' => 'YOUR_BOT_NAME',
            'inputText' => 'Start conversation',
            'userId' => $userId,
        ]);

        $this->handleLexResponse($result, $appointmentId, $lexClient, []);
    }

    private function handleLexResponse($result, $appointmentId, $lexClient, $sessionAttributes)
    {
        // Retrieve existing conversation data or create a new record
        $questionnaire = AppointmentQuestionnaire::firstOrNew(['appointment_id' => $appointmentId]);
        $conversationData = $questionnaire->questionnaire ?? [];

        $lexMessage = $result->get('message');

        // Add Lex response to conversation data
        $conversationData[] = ['type' => 'lex', 'message' => $lexMessage];

        // Simulate sending Lex response to Artisan and getting a reply
        $artisanResponse = $this->sendToArtisan($lexMessage);

        // Add Artisan response to conversation data
        $conversationData[] = ['type' => 'artisan', 'message' => $artisanResponse];

        // Save updated conversation data
        $questionnaire->questionnaire = $conversationData;
        $questionnaire->save();

        if ($result->get('dialogState') !== 'Fulfilled') {
            // Continue conversation with Lex
            $newResult = $lexClient->postText([
                'botAlias' => $result->get('botAlias'),
                'botName' => $result->get('botName'),
                'inputText' => 'Next question',
                'userId' => 'user_' . $appointmentId,
                'sessionAttributes' => $sessionAttributes,
            ]);

            $this->handleLexResponse($newResult, $appointmentId, $lexClient, $sessionAttributes);
        } else {
            // End conversation
            $this->endConversation($appointmentId);
        }
    }

    private function sendToArtisan($response)
    {
        // Simulate sending the response to Artisan and getting a reply
        $client = new Client();
        $res = $client->post('https://artisan-api-url.com/endpoint', [
            'json' => ['response' => $response]
        ]);

        return json_decode($res->getBody(), true)['answer'];
    }

    private function endConversation($appointmentId)
    {
        // Retrieve the stored conversation data
        $questionnaire = AppointmentQuestionnaire::where('appointment_id', $appointmentId)->first();
        $responses = $questionnaire->questionnaire;

        // Send responses to Anthropics AI
        $emotionalIndex = $this->sendToAnthropicsAI($responses);

        // Send emotional index to Artisan
        $this->sendEmotionalIndexToArtisan($appointmentId, $emotionalIndex);
    }

    private function sendToAnthropicsAI($responses)
    {
        $client = new Client();
        $res = $client->post('https://anthropics-api-url.com/endpoint', [
            'json' => ['responses' => $responses]
        ]);

        return json_decode($res->getBody(), true)['emotional_index'];
    }

    private function sendEmotionalIndexToArtisan($appointmentId, $emotionalIndex)
    {
        $client = new Client();
        $client->post('https://artisan-api-url.com/endpoint', [
            'json' => [
                'appointment_id' => $appointmentId,
                'emotional_index' => $emotionalIndex,
            ]
        ]);
    }
}
