<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Service to interact with Google Gemini AI API.
 */
class GeminiService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $geminiApiKey,
    ) {}

    /**
     * Generates content using Gemini AI.
     *
     * @param string $prompt The text prompt to send
     * @return string The AI response text
     * @throws \RuntimeException if the API call fails
     */
    public function generateAnalysis(string $prompt): string
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                self::API_URL . '?key=' . $this->geminiApiKey,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ]
                    ],
                ]
            );

            $data = $response->toArray();
            
            // Extract the text from the response structure
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Désolé, l\'IA n\'a pas pu générer d\'analyse.';
            
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Erreur de connexion à Gemini API: ' . $e->getMessage());
        } catch (\Symfony\Component\HttpClient\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 429) {
                return 'RATE_LIMIT_EXCEEDED';
            }
            throw new \RuntimeException('Erreur client Gemini API: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Erreur Gemini API: ' . $e->getMessage());
        }
    }
}
