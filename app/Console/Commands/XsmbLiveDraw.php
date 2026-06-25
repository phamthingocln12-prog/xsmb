<?php
namespace App\Console\Commands;
use App\Services\LiveDrawService;
use Illuminate\Console\Command;
class XsmbLiveDraw extends Command
{
    /**
     * Tên lệnh artisan.
     */
    protected $signature = 'xsmb:live {--source=random : Nguồn dữ liệu: random hoặc today}';
    /**
     * Mô tả lệnh.
     */
    protected $description = 'Mô phỏng quay số XSMB trực tiếp qua WebSocket (giống chương trình TV)';
    /**
     * Thực thi lệnh.
     */   
    public function handle(LiveDrawService $service): int
    {
        $source = $this->option('source');
        if (!in_array($source, ['random', 'today'])) {
            $this->error("Source không hợp lệ. Sử dụng: random hoặc today");
            return self::FAILURE;
        }
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║    🎰 XSMB - QUAY SỐ TRỰC TIẾP 🎰      ║');
        $this->info('║    Nguồn: ' . str_pad(strtoupper($source), 31) . '║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();
        $service->runLiveDraw($source, function (string $message) {
            $this->line($message);
        });
        $this->newLine();
        $this->info('✨ Phiên quay số đã kết thúc thành công!');
        return self::SUCCESS;
    }
}