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

            // Use HTML for better reliability with names containing special chars
            $message = "📊 <b>Attendance Report Ready</b>\n\n"
                     . "📖 <b>Subject:</b> " . e($subjectName) . " (" . e($subjectCode) . ")\n"
                     . "🏫 <b>Class:</b> " . e($className) . " (Year " . e($yearLevel) . ")\n"
                     . "📍 <b>Room:</b> " . e($room) . "\n"
                     . "👨‍🏫 <b>Instructor:</b> " . e($teacher) . "\n"
                     . "📅 <b>Date:</b> " . e($date) . "\n\n"
                     . "👥 <b>Total Class Size:</b> {$total}\n"
                     . "✅ <b>Marked Present:</b> {$present}\n"
                     . "❌ <b>Total Missing:</b> {$absent}\n\n"
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
        $message = "📑 <b>System Attendance Summary</b>\n\n"
                 . "📅 <b>Year:</b> " . e($academicYear) . "\n"
                 . "🔢 <b>Semester:</b> " . e($semester) . "\n"
                 . "🎯 <b>Scope:</b> " . e($typeName) . "\n\n"
                 . "Attached is the comprehensive school-wide attendance report.";

        $this->sendMessage($bot, $message);
        $this->sendDocument($bot, $fullPath, $fileName);

        // Cleanup
        Storage::disk('local')->delete($filePath);
        return true;
    }

    public function sendMessage($botOrChatId, $text)
    {
        $botToken = null;
        $chatId = null;

        if ($botOrChatId instanceof TelegramBot) {
            $botToken = $botOrChatId->bot_token;
            $chatId = $botOrChatId->chat_id;
        } else {
            // If it's a string/int (chat_id), we need the active bot token
            $activeBot = TelegramBot::where('is_active', true)->first();
            if (!$activeBot) return false;
            $botToken = $activeBot->bot_token;
            $chatId = $botOrChatId;
        }

        if (!$botToken || !$chatId) return false;

        return Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
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

    /**
     * Check if any student in the class has hit 10 or 20 absences and notify Telegram.
     */
    public function checkAbsenceThresholds($classId)
    {
        try {
            $class = \App\Models\ClassRoom::with(['students.user', 'subject'])->find($classId);
            if (!$class) return;

            $completedSessionIds = AttendanceSession::where('class_id', $classId)
                ->where('status', 'completed')
                ->pluck('id');
            
            $sessionCount = $completedSessionIds->count();
            if ($sessionCount < 10) return;

            foreach ($class->students as $student) {
                // Check 1: Per-Subject Absence (Alert at 10)
                $attendedCount = \App\Models\Attendance::where('student_id', $student->id)
                    ->whereIn('session_id', $completedSessionIds)
                    ->whereIn('status', ['present', 'late', 'excused', 'PRESENT', 'LATE', 'EXCUSED'])
                    ->count();
                    
                $absentCount = max(0, $sessionCount - $attendedCount);

                if ($absentCount == 10) {
                    $bot = TelegramBot::where('is_active', true)->first();
                    if ($bot && $bot->chat_id) {
                        $message = "⚠️ <b>PER-SUBJECT ABSENCE ALERT</b> ⚠️\n\n"
                                 . "👤 <b>Student:</b> " . e($student->user->name) . " (" . e($student->student_code) . ")\n"
                                 . "📖 <b>Subject:</b> " . e($class->subject->name) . "\n"
                                 . "❌ <b>Total Absences in this Subject:</b> <b>" . $absentCount . "</b> sessions\n\n"
                                 . "This student has reached the threshold for this subject.";
                        $this->sendMessage($bot, $message);
                    }
                }

                // Check 2: Global Absence (Alert at 20 across all subjects)
                // We sum up all absences in all COMPLETED sessions of all classes for this student
                $allCompletedSessions = AttendanceSession::where('status', 'completed')->get();
                $allCompletedSessionIds = $allCompletedSessions->pluck('id');
                
                // Sessions for classes the student is actually enrolled in (if we can filter by group/class)
                // But generally, we can just check sessions of classes where they were supposed to be.
                // Assuming students are in multiple classes.
                
                // Let's get all classes for this student
                $studentClasses = \App\Models\ClassRoom::where('group_id', $student->group_id)->pluck('id');
                $relevantSessionIds = AttendanceSession::whereIn('class_id', $studentClasses)
                    ->where('status', 'completed')
                    ->pluck('id');
                
                $totalPossibleSessions = $relevantSessionIds->count();
                $totalAttended = \App\Models\Attendance::where('student_id', $student->id)
                    ->whereIn('session_id', $relevantSessionIds)
                    ->whereIn('status', ['present', 'late', 'excused', 'PRESENT', 'LATE', 'EXCUSED'])
                    ->count();
                
                $totalAbsents = max(0, $totalPossibleSessions - $totalAttended);

                if ($totalAbsents == 20) {
                    $bot = TelegramBot::where('is_active', true)->first();
                    if ($bot && $bot->chat_id) {
                        $message = "🚨 <b>SYSTEM-WIDE ABSENCE ALERT</b> 🚨\n\n"
                                 . "👤 <b>Student:</b> " . e($student->user->name) . " (" . e($student->student_code) . ")\n"
                                 . "📊 <b>Total Absences (All Subjects):</b> <b style='color:red'>" . $totalAbsents . "</b> sessions\n\n"
                                 . "⚠️ This student has reached the <b>CRITICAL</b> system-wide absence limit.";
                        $this->sendMessage($bot, $message);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Absence alert check failed: " . $e->getMessage());
        }
    }
}
