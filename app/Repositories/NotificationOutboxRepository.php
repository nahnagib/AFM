<?php

namespace App\Repositories;

use App\Models\NotificationOutbox;

class NotificationOutboxRepository
{
    public function create(array $data): NotificationOutbox
    {
        return NotificationOutbox::create($data);
    }

    public function getPendingToSend()
    {
        return NotificationOutbox::pending()->get();
    }
}
