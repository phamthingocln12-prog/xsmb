<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DrawResultUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $drawData;

    public function __construct($drawData)
    {
        $this->drawData = $drawData;
    }

    public function broadcastOn(): array
    {
        // Kênh để giao diện frontend lắng nghe
        return [
            new Channel('xsmb-channel'),
        ];
    }

    // Đặt tên sự kiện để frontend dễ bắt
    public function broadcastAs()
    {
        return 'new-result';
    }
}