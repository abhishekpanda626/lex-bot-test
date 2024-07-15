<?php
namespace App\Services;

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class AnthropicService
{
    protected $client;

    public function __construct()
    {
        $this->client = new BedrockRuntimeClient([
            'region' => config('aws.aws_default_region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('aws.aws_access_key'),
                'secret' => config('aws.aws_secret_access_key'),
            ],
        ]);
    }

    public function getEmotionalIndex($responses)
    {
        $prompt = "Analyze the following conversation and provide the emotional index:";
        $text = '';
        foreach ($responses as $response) {
            $text .= "{$response['type']}: {$response['message']}\n";
        }

        $message = $text . "\n\n" . $prompt;
        $modelId = 'anthropic.claude-v2';

        try {
            $response = $this->client->invokeModel([
                'modelId' => $modelId,
                'body' => json_encode([
                    'anthropic_version' => 'bedrock-2023-05-31',
                    'max_tokens' => 2048,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [['type' => 'text', 'text' => $message]],
                        ],
                    ],
                ]),
            ]);

            $result = json_decode($response['body'], true);
            $content = $result['content'];
            $text = '';
            foreach ($content as $item) {
                if ($item['type'] === 'text') {
                    $text .= $item['text'] . "\n";
                }
            }

            return $text;
        } catch (AwsException $e) {
            $errorCode = $e->getAwsErrorCode();
            $errorMessage = $e->getAwsErrorMessage();
            Log::error("Couldn't invoke Claude 3 Sonnet. Here's why: {$errorCode}: {$errorMessage}");
            throw $e;
        }
    }
}
