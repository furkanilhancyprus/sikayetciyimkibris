<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly OrganizationInvitation $invitation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Şikayetçiyim Kıbrıs kurum hesabı daveti')
            ->greeting('Merhaba,')
            ->line($this->invitation->entity->name.' adına kurum hesabı oluşturmanız için güvenli bir davet bağlantısı oluşturuldu.')
            ->action('Kurum Hesabını Oluştur', route('organization-invitations.show', $this->invitation->token))
            ->line('Bu bağlantı '.optional($this->invitation->expires_at)->format('d.m.Y H:i').' tarihine kadar geçerlidir.')
            ->line('Bu daveti siz talep etmediyseniz bu e-postayı yok sayabilirsiniz.');
    }
}
