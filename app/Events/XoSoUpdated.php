<?php
namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class XoSoUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;
    /**
     * Tên giải (giai_7, giai_6, ..., giai_dac_biet)
     */
    public string $prize_name;
    /**
     * Giá trị kết quả (string hoặc array)
     */
    public $value;
    /**
     * Vị trí trong nhóm giải (0-based index cho các giải có nhiều số)
     */
    public int $index;
    public function __construct(string $prize_name, $value, int $index = 0)
    {
        $this->prize_name = $prize_name;
        $this->value = $value;
        $this->index = $index;
    }
    /**
     * Kênh broadcast công khai — tất cả client đều nhận được.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('xsmb-channel'),
        ];
    }
    /**
     * Tên sự kiện phía client sẽ lắng nghe: .xsmb.update
     */
    public function broadcastAs(): string
    {
        return 'xsmb.update';
    }
    /**
     * Dữ liệu gửi kèm event.
     */
    public function broadcastWith(): array
    {
        return [
            'prize_name' => $this->prize_name,
            'value'      => $this->value,
            'index'      => $this->index,
        ];
    }
}