<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected ?string $openaiKey;
    protected bool $useAi;

    public function __construct()
    {
        $this->openaiKey = config('services.openai.api_key');
        $this->useAi = !empty($this->openaiKey);
    }

    public function isAiEnabled(): bool
    {
        return $this->useAi;
    }

    public function generateResponse(string $message, ?array $userContext = null): array
    {
        $userContext = $userContext ?? [];

        $persona = $this->getDocSecurPersona();
        
        $systemPrompt = <<<EOT
Tu es DocSecur, l'assistant virtuel de la plateforme DocSecur au Sénégal.
DocSecur est une plateforme numérique pour les dossiers médicaux sécurisés.

MISSION:
- Aider les patients à comprendre leur santé
- Expliquer les prescriptions et traitements
- Rappeler les rendez-vous
- Répondre aux questions sur les résultats d'analyses
- Orienter vers les bons services

INFORMATIONS CLÉS:
- Langues: français, wolof, anglais
- Accessible 24h/24
- Données médicales sécurisées et chiffrées
- Mode hors ligne disponible

RESPONSE STYLE:
- claire et empathique
- utilise des exemples quand pertinent
- reste concise (2-3 phrases max)
- propose toujours une action si utiles

EOT;

        if ($this->useAi && config('app.env') === 'production') {
            return $this->generateWithAi($message, $persona, $systemPrompt, $userContext);
        }

        return $this->generateRuleBased($message, $userContext);
    }

    protected function generateWithAi(string $message, string $persona, string $systemPrompt, array $userContext): array
    {
        try {
            $history = array_slice($userContext['history'] ?? [], -4);
            
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            foreach ($history as $msg) {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }

            $messages[] = ['role' => 'user', 'content' => $message];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiKey,
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content');
                return [
                    'reply' => $reply,
                    'source' => 'ai',
                ];
            }
        } catch (\Exception $e) {
            Log::error('[CHATBOT] AI error: ' . $e->getMessage());
        }

        return $this->generateRuleBased($message, $userContext);
    }

    protected function generateRuleBased(string $message, array $userContext): array
    {
        $message = mb_strtolower(trim($message));
        $reponses = $this->getRuleBasedResponses();

        foreach ($reponses as $pattern => $response) {
            if (str_contains($message, $pattern)) {
                return [
                    'reply' => $response[array_rand($response)],
                    'source' => 'rule',
                ];
            }
        }

        return [
            'reply' => "Je suis là pour vous aider avec DocSecur. Posez-moi une question sur vos rendez-vous, prescriptions ou résultats d'analyses.",
            'source' => 'fallback',
        ];
    }

    protected function getDocSecurPersona(): string
    {
        return "Tu es DocSecur, assistant virtuel médical au Sénégal.";
    }

    protected function getRuleBasedResponses(): array
    {
        return [
            'bonjour' => [
                "Bonjour! Je suis DocSecur, votre assistant santé. Comment puis-je vous aider?",
                "Hello! Je suis disponible pour répondre à vos questions sur DocSecur.",
            ],
            'rendez-vous' => [
                "Pour voir vos rendez-vous, allez dans l'onglet Rendez-vous. Vous pouvez aussi prendre ou annuler un RDV.",
                "Vos prochains rendez-vous apparaissent sur votre tableau de bord. Besoin de programmer un nouveau RDV?",
            ],
            'ordonnance' => [
                "Vos ordonnances se trouvent dans l'onglet Prescriptions. Votre pharmacien peut les recevoir directement.",
                " Consultez vos ordonnances actives dans l'application. Elles sont envoyées automatiquement à votre pharmacie.",
            ],
            'résultat' => [
                "Vos résultats d'analyses sont dans l'onglet Résultats. Vous recevez une notification quand ils sont disponibles.",
                "Les résultats d'analyses apparaissent dans votre espace patient. N'hésitez pas à les partager avec votre médecin.",
            ],
            'vaccin' => [
                "Votre carnet de vaccination est dans l'onglet Correspondant. keeps track of vos vaccine history.",
                "Vous pouvez voir vos vaccins dans la section Carnet de vaccination.",
            ],
            'téléconsultation' => [
                "Pour une téléconsultation, allez dans l'onglet对应的. Vous aurez besoin d'une connexion internet.",
                "Les téléconsultations se font via vidéo sécurisée avec Jitsi. Vérifiez votre connexion.",
            ],
            'constantes' => [
                "Vous pouvez suivre vos constantes vitales (tension, glycémie) dans l'onglet correspondant.",
                "Mesurez régulièrement votre tension et glycémie. Les objets connectés peuvent se synchroniser automatiquement.",
            ],
            'mot de passe' => [
                "Pour réinitialiser votre mot de passe, utilisez l'option 'Mot de passe oublié' sur la page de connexion.",
                "Si vous avez oublié votre mot de passe, cliquez sur la page de connexion pour le réinitialiser.",
            ],
            'numéro dossier' => [
                "Votre numéro de dossier est dans votre dossier médical, dans l'onglet informations.",
                "Vous trouvez votre numéro de dossier (format DS-XXXXXX) dans la section informations médicales.",
            ],
            'aide' => [
                "Je peux vous aider avec: rendez-vous, ordonnances, résultats, vaccinations, téléconsultations. Que souhaitez-vous savoir?",
                "Posez-moi une question sur DocSecur: prescriptions, rendez-vous, résultats ou vaccinations.",
            ],
            'merci' => [
                "De rien! Je suis là pour vous aider. N'hésitez pas si vous avez d'autres questions.",
                "Avec plaisir! Besoin d'autre chose?",
            ],
        ];
    }
}