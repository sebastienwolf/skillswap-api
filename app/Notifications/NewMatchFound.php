<?php

namespace App\Notifications;

use App\Contracts\Exchangeable;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMatchFound extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Exchangeable&Model $exchangeable,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Une nouvelle annonce correspond à votre recherche')
            ->line(sprintf('%s vient d\'être publiée et pourrait vous intéresser.', ucfirst($this->exchangeable->displayLabel())))
            ->action('Voir l\'annonce', config('app.url'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_match_found',
            'exchangeable_type' => $this->exchangeable::class,
            'exchangeable_id' => $this->exchangeable->getKey(),
            'message' => sprintf('%s pourrait vous intéresser.', ucfirst($this->exchangeable->displayLabel())),
        ];
    }
}
