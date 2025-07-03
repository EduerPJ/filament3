<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LlmSecurityMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llm:security-report {--period=today : Período del reporte (today, yesterday, week, month)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera reportes de seguridad y monitoreo para las APIs LLM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->option('period');
        $this->info("Generando reporte de seguridad LLM para el período: {$period}");

        $report = $this->generateSecurityReport($period);
        
        $this->displayReport($report);
        $this->saveReport($report, $period);
        
        $this->info('Reporte de seguridad LLM completado.');
    }

    private function generateSecurityReport(string $period): array
    {
        $dateRange = $this->getDateRange($period);
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            $this->warn('No se encontró el archivo de log.');
            return [];
        }

        $logContent = File::get($logPath);
        $lines = explode("\n", $logContent);
        
        $report = [
            'period' => $period,
            'date_range' => $dateRange,
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'injection_attempts' => 0,
            'rate_limit_hits' => 0,
            'suspicious_activity' => 0,
            'unique_users' => [],
            'user_activity' => [],
            'error_patterns' => [],
            'security_incidents' => [],
            'recommendations' => []
        ];

        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            $this->analyzeLlogLine($line, $dateRange, $report);
        }

        $report['unique_users'] = count($report['unique_users']);
        $report = $this->generateRecommendations($report);
        
        return $report;
    }

    private function analyzeLlogLine(string $line, array $dateRange, array &$report): void
    {
        // Extraer timestamp del log
        if (!preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return;
        }
        
        $logDate = Carbon::parse($matches[1]);
        
        if ($logDate->lt($dateRange['start']) || $logDate->gt($dateRange['end'])) {
            return;
        }

        // Analizar diferentes tipos de eventos LLM
        if (strpos($line, 'LLM') !== false) {
            $report['total_requests']++;
            
            // Extraer user_id si está presente
            if (preg_match('/user_id["\']?\s*:\s*["\']?(\d+)/', $line, $userMatches)) {
                $userId = $userMatches[1];
                $report['unique_users'][$userId] = true;
                
                if (!isset($report['user_activity'][$userId])) {
                    $report['user_activity'][$userId] = 0;
                }
                $report['user_activity'][$userId]++;
            }
        }

        // Detectar solicitudes exitosas
        if (strpos($line, 'LLM content generated successfully') !== false ||
            strpos($line, 'LLM analysis completed successfully') !== false ||
            strpos($line, 'Chat message processed successfully') !== false) {
            $report['successful_requests']++;
        }

        // Detectar fallos
        if (strpos($line, 'LLM generation failed') !== false ||
            strpos($line, 'LLM analysis failed') !== false ||
            strpos($line, 'Chat message failed') !== false) {
            $report['failed_requests']++;
            
            // Categorizar errores
            if (preg_match('/error["\']?\s*:\s*["\']?([^"\']+)/', $line, $errorMatches)) {
                $error = $errorMatches[1];
                if (!isset($report['error_patterns'][$error])) {
                    $report['error_patterns'][$error] = 0;
                }
                $report['error_patterns'][$error]++;
            }
        }

        // Detectar intentos de inyección
        if (strpos($line, 'Potential prompt injection') !== false) {
            $report['injection_attempts']++;
            $report['security_incidents'][] = [
                'type' => 'prompt_injection',
                'timestamp' => $logDate->toISOString(),
                'details' => $this->extractIncidentDetails($line)
            ];
        }

        // Detectar actividad sospechosa
        if (strpos($line, 'Suspicious user agent') !== false ||
            strpos($line, 'Malicious header detected') !== false ||
            strpos($line, 'CSRF token mismatch') !== false) {
            $report['suspicious_activity']++;
            $report['security_incidents'][] = [
                'type' => 'suspicious_activity',
                'timestamp' => $logDate->toISOString(),
                'details' => $this->extractIncidentDetails($line)
            ];
        }

        // Detectar rate limiting
        if (strpos($line, 'rate limit') !== false || strpos($line, 'throttle') !== false) {
            $report['rate_limit_hits']++;
        }
    }

    private function extractIncidentDetails(string $line): array
    {
        $details = [];
        
        // Extraer user_id
        if (preg_match('/user_id["\']?\s*:\s*["\']?(\d+)/', $line, $matches)) {
            $details['user_id'] = $matches[1];
        }
        
        // Extraer IP
        if (preg_match('/ip["\']?\s*:\s*["\']?([^"\']+)/', $line, $matches)) {
            $details['ip'] = $matches[1];
        }
        
        // Extraer user agent
        if (preg_match('/user_agent["\']?\s*:\s*["\']?([^"\']+)/', $line, $matches)) {
            $details['user_agent'] = $matches[1];
        }

        return $details;
    }

    private function getDateRange(string $period): array
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];
            case 'yesterday':
                return [
                    'start' => $now->copy()->subDay()->startOfDay(),
                    'end' => $now->copy()->subDay()->endOfDay()
                ];
            case 'week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
            default:
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];
        }
    }

    private function generateRecommendations(array $report): array
    {
        $recommendations = [];

        // Analizar tasa de éxito
        $successRate = $report['total_requests'] > 0 
            ? ($report['successful_requests'] / $report['total_requests']) * 100 
            : 0;

        if ($successRate < 90) {
            $recommendations[] = "Tasa de éxito baja ({$successRate}%). Revisar errores y optimizar el servicio.";
        }

        // Analizar intentos de inyección
        if ($report['injection_attempts'] > 5) {
            $recommendations[] = "Alto número de intentos de inyección ({$report['injection_attempts']}). Considerar endurecer las reglas de validación.";
        }

        // Analizar actividad sospechosa
        if ($report['suspicious_activity'] > 10) {
            $recommendations[] = "Actividad sospechosa elevada ({$report['suspicious_activity']}). Revisar logs y considerar bloqueos de IP.";
        }

        // Analizar usuarios muy activos
        $maxUserActivity = 0;
        foreach ($report['user_activity'] as $userId => $activity) {
            if ($activity > $maxUserActivity) {
                $maxUserActivity = $activity;
            }
            if ($activity > 100) {
                $recommendations[] = "Usuario {$userId} con actividad muy alta ({$activity} requests). Verificar si es uso legítimo.";
            }
        }

        // Analizar rate limiting
        if ($report['rate_limit_hits'] > $report['total_requests'] * 0.1) {
            $recommendations[] = "Alto número de hits de rate limiting. Considerar ajustar los límites o educar a los usuarios.";
        }

        $report['recommendations'] = $recommendations;
        return $report;
    }

    private function displayReport(array $report): void
    {
        $this->info("=== REPORTE DE SEGURIDAD LLM ===");
        $this->info("Período: {$report['period']}");
        $this->info("Rango: {$report['date_range']['start']->format('Y-m-d H:i')} - {$report['date_range']['end']->format('Y-m-d H:i')}");
        $this->newLine();

        $this->info("📊 ESTADÍSTICAS GENERALES:");
        $this->info("• Total de solicitudes: {$report['total_requests']}");
        $this->info("• Solicitudes exitosas: {$report['successful_requests']}");
        $this->info("• Solicitudes fallidas: {$report['failed_requests']}");
        $this->info("• Usuarios únicos: {$report['unique_users']}");
        
        $successRate = $report['total_requests'] > 0 
            ? round(($report['successful_requests'] / $report['total_requests']) * 100, 2)
            : 0;
        $this->info("• Tasa de éxito: {$successRate}%");
        $this->newLine();

        $this->warn("🔒 INCIDENTES DE SEGURIDAD:");
        $this->warn("• Intentos de inyección: {$report['injection_attempts']}");
        $this->warn("• Actividad sospechosa: {$report['suspicious_activity']}");
        $this->warn("• Hits de rate limiting: {$report['rate_limit_hits']}");
        $this->newLine();

        if (!empty($report['recommendations'])) {
            $this->error("⚠️  RECOMENDACIONES:");
            foreach ($report['recommendations'] as $recommendation) {
                $this->error("• {$recommendation}");
            }
            $this->newLine();
        }

        if (!empty($report['error_patterns'])) {
            $this->info("🐛 PATRONES DE ERROR MÁS COMUNES:");
            arsort($report['error_patterns']);
            $topErrors = array_slice($report['error_patterns'], 0, 5, true);
            foreach ($topErrors as $error => $count) {
                $this->info("• {$error}: {$count} veces");
            }
            $this->newLine();
        }
    }

    private function saveReport(array $report, string $period): void
    {
        $filename = "llm_security_report_{$period}_" . now()->format('Y_m_d_H_i_s') . ".json";
        $reportPath = storage_path("app/security_reports/{$filename}");
        
        // Crear directorio si no existe
        $directory = dirname($reportPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("Reporte guardado en: {$reportPath}");
    }
}