<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReMarkableAuthenticatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private string $authFileContents;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|PrivateChannel|array
    {
        return new PrivateChannel('channel-name');
    }
}
