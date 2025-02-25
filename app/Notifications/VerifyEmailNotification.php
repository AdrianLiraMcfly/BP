<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class VerifyEmailNotification extends Notification
{
    public function __construct(protected $user) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Genera una URL firmada con vencimiento de 60 minutos
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify', // La ruta que manejará la verificación
            now()->addMinutes(60),
            ['user' => $notifiable->id]
        );

        return (new MailMessage)
            ->subject('Verifica tu dirección de correo')
            ->greeting("Hola, {$notifiable->name}!")
            ->line('Por favor, haz clic en el botón de abajo para verificar tu correo electrónico.')
            ->action('Verificar correo', $verificationUrl)
            ->line('Si no creaste esta cuenta, ignora este correo.');
    }
}
