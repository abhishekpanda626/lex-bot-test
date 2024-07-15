<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Aws\LexRuntimeService\LexRuntimeServiceClient;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class SimulateConversation extends Command
{
    protected $signature = 'simulate:conversation {appointmentId}';
    protected $description = 'Simulate a conversation with Lex bot and Artisan app';

    public function handle()
    {
        $appointmentId = $this->argument('appointmentId');

        $this->info('Starting conversation simulation for appointment ID: ' . $appointmentId);

        $lexClient = new LexRuntimeServiceClient([
            'region'  => 'us-east-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ]
        ]);

        $userId = 'simulated_user_' . $appointmentId;
        $sessionAttributes = [];

        // Start conversation with Lex bot
        $result = $lexClient->postText([
            'botAlias' => 'YOUR_BOT_ALIAS',
            'botName' => 'YOUR_BOT_NAME',
            'inputText' => 'Start conversation',
            'userId' => $userId,
        ]);

        $this->handleLexResponse($result, $appointmentId, $lexClient, $sessionAttributes);
    }

    private function handleLexResponse($result, $appointmentId, $lexClient, &$sessionAttributes)
    {
        $this->info('Lex Bot Response: ' . $result->get('message'));

        // Simulate sending Lex response to Artisan and getting a reply
        $response = $result->get('message');
        $artisanResponse = $this->sendToArtisan($response);

        // Save artisan response in DB
        DB::table('appointment_responses')->insert([
            'appointment_id' => $appointmentId,
            'response' => $artisanResponse,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($result->get('dialogState') !== 'Fulfilled') {
            // Continue conversation with Lex
            $newResult = $lexClient->postText([
                'botAlias' => $result->get('botAlias'),
                'botName' => $result->get('botName'),
                'inputText' => 'Next question',
                'userId' => 'simulated_user_' . $appointmentId,
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
        $this->info('Ending conversation for appointment ID: ' . $appointmentId);

        // Get all responses for the appointment
        $responses = DB::table('appointment_responses')
            ->where('appointment_id', $appointmentId)
            ->pluck('response');

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
