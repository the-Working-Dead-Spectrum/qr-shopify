<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Job d'alerte admin après échec définitif d'un autre Job.
 *
 * ⚠️ N'hérite PAS de BaseJob volontairement :
 *  - Pas de retry : si l'admin n'est pas notifié, ce n'est pas grave
 *  - Pas de failed() chaîné → pas de boucle infinie en cas de panne SMTP
 *  - Log garanti même si l'email échoue (filet de sécurité ultime)
 *
 * Dispatché par : BaseJob::failed() de tout autre Job.
 *
 * Email envoyé à : ADMIN_EMAIL (env)
 * Contenu : nom du Job fautif + message d'exception + contexte métier.
 */
final class NotifyAdminOnErrorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Une seule tentative — pas de retry pour une notification.
     */
    public int $tries = 1;

    /**
     * Timeout court — l'admin préfère une absence de mail à un worker bloqué.
     */
    public int $timeout = 30;

    public function __construct(
        public readonly string $jobClass,
        public readonly string $errorMessage,
        /** @var array<string, mixed> */
        public readonly array $context = [],
    ) {}

    public function handle(): void
    {
        $adminEmail = config('mail.admin_email')
            ?: env('ADMIN_EMAIL')
            ?: null;

        if (empty($adminEmail)) {
            // Pas d'admin configuré → fallback log uniquement.
            Log::critical('[notify] no_admin_email_configured', [
                'job_class' => $this->jobClass,
                'error' => $this->errorMessage,
                'context' => $this->context,
            ]);

            return;
        }

        $subject = sprintf(
            '[%s] Échec Job : %s',
            config('app.name'),
            class_basename($this->jobClass),
        );

        try {
            // On construit l'email inline (pas de Mailable dédié) :
            // c'est un email technique, pas besoin d'un template Blade.
            Mail::raw(
                $this->buildBody(),
                function (Message $message) use ($adminEmail, $subject): void {
                    $message->to($adminEmail)
                        ->subject($subject)
                        ->from(
                            config('mail.from.address'),
                            config('mail.from.name', config('app.name')),
                        );
                },
            );

            Log::info('[notify] admin_alerted', [
                'job_class' => $this->jobClass,
                'to' => $this->adminEmailRedacted($adminEmail),
            ]);
        } catch (Throwable $e) {
            // L'email admin a échoué : on log en CRITICAL pour Sentry/Datadog.
            // C'est notre dernière ligne de défense — à superviser en monitoring.
            Log::critical('[notify] admin_email_send_failed', [
                'original_job_class' => $this->jobClass,
                'original_error' => $this->errorMessage,
                'smtp_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Construit le corps de l'email d'alerte.
     * Format texte brut — lisible dans n'importe quel client email,
     * pas de HTML pour limiter les soucis de rendu.
     */
    private function buildBody(): string
    {
        $contextJson = json_encode($this->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<TEXT
        Alerte automatique — Échec définitif d'un Job
        
        Job en échec : {$this->jobClass}
        Date          : {$this->nowFormatted()}
        Environnement : {$this->env()}
        
        Message d'erreur :
        {$this->errorMessage}
        
        Contexte métier :
        {$contextJson}
        
        ---
        Cette alerte est générée automatiquement par le système de monitoring.
        Vérifier les logs Laravel pour le stack trace complet :
        storage/logs/laravel-{$this->today()}.log
        TEXT;
    }

    private function nowFormatted(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    private function today(): string
    {
        return now()->format('Y-m-d');
    }

    private function env(): string
    {
        return (string) config('app.env');
    }

    /**
     * Masque partiellement l'email admin pour les logs (RGPD).
     */
    private function adminEmailRedacted(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + ['', ''];

        if ($local === '') {
            return '***';
        }

        return mb_substr($local, 0, 2).'***@'.$domain;
    }
}
