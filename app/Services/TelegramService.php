<?php

namespace App\Services;

use App\Models\TelegramBot;
use App\Models\AttendanceSession;
use App\Exports\AttendanceExport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class TelegramService
{
    /**
     * Send attendance report to active Telegram bot.
     *
     * @param int $sessionId
     * @return bool
     */
    public function sendAttendanceReport($sessionId)
    {
        $session = AttendanceSession::with(['classRoom.subject', 'attendanceRecords.student'])->findOrFail($sessionId);

        // Conditions
        if ($session->telegram_sent) {
            return false;
        }

        $bot = TelegramBot::where('is_active', true)->first();
        if (!$bot || !$bot->chat_id) {
            Log::warning("Telegram report skipped for session {$sessionId}. No active bot or chat_id found.");
            return false;
        }

        try {
            // 1. Prepare Summary
            $total = $session->classRoom->group_id ? \App\Models\Student::where('group_id', $session->classRoom->group_id)->count() : $session->attendanceRecords->count();
            $present = $session->attendanceRecords->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count();
            $absent = max(0, $total - $present);
            
            $subjectName = $session->classRoom->subject->name ?? 'Unknown Subject';
            $subjectCode = $session->classRoom->subject->code ?? 'N/A';
            $className = $session->classRoom->group->name ?? 'Unknown Class';
            $room = $session->classRoom->room_number ?? 'TBD';
            $teacher = $session->classRoom->teacher->user->name ?? 'Unknown';
            $yearLevel = $session->classRoom->group->year_level ?? 'N/A';
            $date = $session->start_time;

            $message = "📊 *Attendance Report Ready*\n\n"
                     . "📖 *Subject:* {$subjectName} ({$subjectCode})\n"
                     . "🏫 *Class:* {$className} (Year {$yearLevel})\n"
                     . "📍 *Room:* {$room}\n"
                     . "👨‍🏫 *Instructor:* {$teacher}\n"
                     . "📅 *Date:* {$date}\n\n"
                     . "👥 *Total Class Size:* {$total}\n"
                     . "✅ *Marked Present:* {$present}\n"
                     . "❌ *Total Missing:* {$absent}\n\n"
                     . "Please find the detailed Excel report attached below.";

            // 2. Send Text Summary
            $this->sendMessage($bot, $message);

            // 3. Generate and Send Excel
            $fileName = "attendance_report_{$sessionId}_" . date('Ymd_His') . ".xlsx";
            $filePath = "temp/{$fileName}";
            
            Excel::store(new AttendanceExport($sessionId), $filePath, 'local');
            $fullPath = Storage::disk('local')->path($filePath);

            $this->sendDocument($bot, $fullPath, $fileName);

            // Cleanup
            Storage::disk('local')->delete($filePath);

            // Mark as sent
            $session->update(['telegram_sent' => true]);

            return true;

        } catch (\Exception $e) {
            Log::error("Telegram Service Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send global system attendance summary to Telegram.
     */
    public function sendSystemSummaryReport($academicYear, $semester, $type = 'full')
    {
        $bot = TelegramBot::where('is_active', true)->first();
        if (!$bot || !$bot->chat_id) return false;

        $fileName = "system_summary_{$type}_" . str_replace('/', '-', $academicYear) . ".xlsx";
        $filePath = "temp/{$fileName}";

        // Use standard disk (local)
        Excel::store(new \App\Exports\SystemSummaryExport($academicYear, $semester, $type), $filePath, 'local');
        $fullPath = Storage::disk('local')->path($filePath);

        $typeName = ($type === 'half') ? 'MID-TERM' : 'FULL SEMESTER';
        $message = "📑 *System Attendance Summary*\n\n"
                 . "📅 *Year:* {$academicYear}\n"
                 . "🔢 *Semester:* {$semester}\n"
                 . "🎯 *Scope:* {$typeName}\n\n"
                 . "Attached is the comprehensive school-wide attendance report.";

        $this->sendMessage($bot, $message);
        $this->sendDocument($bot, $fullPath, $fileName);

        // Cleanup
        Storage::disk('local')->delete($filePath);
        return true;
    }

    protected function sendMessage($bot, $text)
    {
        return Http::post("https://api.telegram.org/bot{$bot->bot_token}/sendMessage", [
            'chat_id' => $bot->chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);
    }

    protected function sendDocument($bot, $filePath, $fileName)
    {
        return Http::attach(
            'document', file_get_contents($filePath), $fileName
        )->post("https://api.telegram.org/bot{$bot->bot_token}/sendDocument", [
            'chat_id' => $bot->chat_id,
            'caption' => 'Detailed Attendance Excel Report'
        ]);
    }
}
