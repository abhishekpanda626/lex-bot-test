<?php

namespace App\Filament\Pages;

use App\Models\ChatInteraction;
use App\Services\GeminiService;
use App\Services\LexService;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class ChatPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.chat-page';

    protected static ?string $title = 'Chat';

    public $conversation = [];
    public $currentIntent;
    public $sessionId;
    public $chatInteraction, $userInput, $intents, $responseOptions;
    public $lexResponse = ['message' => ''];

    protected $lexService;

    public static function getNavigationLabel(): string
    {
        return 'Chat ';
    }


    public function boot(LexService $lexService)
    {
        $this->lexService = $lexService;
    }

    public function mount()
    {
        $this->sessionId = 'user-' . Str::uuid();
        $this->intents = $this->lexService->getIntentQuestions();
        $this->startNewConversation();
    }

    public function startNewConversation()
    {
        $this->currentIntent = array_rand($this->intents);
        $intentQuestion = $this->getIntentQuestion($this->currentIntent);

        $this->chatInteraction = ChatInteraction::create([
            'session_id' => $this->sessionId,
            'intent_name' => $this->currentIntent,
            'appointment_id' => mt_rand()
        ]);

        // Send the intent question to Lex and get the response
        $this->lexResponse = $this->lexService->postText($intentQuestion, $this->sessionId);

        // Store only the Lex response
        $this->addBotMessage($this->lexResponse['message']);

        // Update the chat interaction with Lex response
        $this->chatInteraction->intent_state = $this->lexResponse['intentState'];
        $this->chatInteraction->save();

        $this->updateResponseOptions();
    }


    public function selectResponse($response)
    {
        // Add user's response to the conversation
        $this->addUserMessage($response);

        // Process the response with Lex
        $this->lexResponse = $this->lexService->postText($response, $this->sessionId);

        // Add Lex's response to the conversation
        $this->addBotMessage($this->lexResponse['message']);

        // Update chat interaction
        $this->chatInteraction->intent_name = $this->lexResponse['intentName'];
        $this->chatInteraction->intent_state = $this->lexResponse['intentState'];
        $this->chatInteraction->save();

        if ($this->lexService->isIntentComplete($this->lexResponse['intentState'])) {
            $this->moveToNextIntent();
        } else {
            $this->updateResponseOptions();
        }
    }
    private function moveToNextIntent()
    {
        $nextIntent = $this->lexService->getNextIntent($this->currentIntent);
        if ($nextIntent) {
            $this->currentIntent = $nextIntent;
            $intentQuestion = $this->getIntentQuestion($this->currentIntent);

            // Send the next intent question to Lex and get the response
            $this->lexResponse = $this->lexService->postText($intentQuestion, $this->sessionId);

            // Add only Lex's response to the conversation
            $this->addBotMessage($this->lexResponse['message']);

            // Update chat interaction
            $this->chatInteraction->intent_name = $this->currentIntent;
            $this->chatInteraction->intent_state = $this->lexResponse['intentState'];
            $this->chatInteraction->save();

            $this->updateResponseOptions();
        } else {
            // All intents completed
            $this->addBotMessage("Thanks for your response and time.");
            $this->currentIntent = null;
            $this->responseOptions = [];
            $this->analyzeEmotionalIndex(app(GeminiService::class));
        }
    }

    private function addUserMessage($message)
    {
        $this->chatInteraction->addToConversation($message, true);
        $this->conversation = $this->chatInteraction->conversation;
    }

    private function addBotMessage($message)
    {
        $this->chatInteraction->addToConversation($message, false);
        $this->conversation = $this->chatInteraction->conversation;
    }

    public function getIntentQuestion($intent)
    {
        return $this->lexService->getIntentQuestion($intent);
    }

    private function updateResponseOptions()
    {
        if ($this->currentIntent === 'followup_instructions') {
            $currentState = $this->chatInteraction->intent_state['state'] ?? '';
            if ($currentState === 'InProgress') {
                $this->responseOptions = ['Very clear', 'Clear', 'Neutral', 'Unclear', 'Very unclear'];
            } else {
                $this->responseOptions = ['Yes', 'No'];
            }
        } else {
            $this->responseOptions = $this->getResponseOptions($this->currentIntent);
        }
    }

    private function getResponseOptions($intent)
    {
        $options = [
            'office' => ['Very happy', 'Happy', 'Neutral', 'Unhappy', 'Very unhappy'],
            'doctor' => ['Excellent', 'Good', 'Average', 'Poor', 'Very poor'],
            'treatment' => ['Very comfortable', 'Comfortable', 'Neutral', 'Uncomfortable', 'Very uncomfortable'],
            // 'followup_instructions' => ['Very clear', 'Clear', 'Neutral', 'Unclear', 'Very unclear'],
            'staff_friendliness' => ['Excellent', 'Good', 'Average', 'Poor', 'Very poor'],
        ];

        return $options[$intent] ?? [];
    }


    private function analyzeEmotionalIndex(GeminiService $geminiService)
    {
        try {
            $emotionalIndex = $geminiService->getEmotionalIndex($this->conversation);

            // You can display the emotional index to the user if needed
            $this->addBotMessage("Thank you for your feedback. Here's a summary of the emotional index of our conversation: " . json_encode($emotionalIndex));
        } catch (\Exception $e) {
            // Handle any errors that occur during the analysis
            $this->addBotMessage("An error occurred while analyzing the conversation. " . $e->getMessage());
        }
    }
}
