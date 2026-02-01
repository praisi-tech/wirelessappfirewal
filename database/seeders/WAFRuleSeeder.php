<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WAFRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create SQL injection patterns file
        $sqlPatterns = [
            '/(union\s+select)/i',
            '/(select.*from)/i',
            '/(insert\s+into)/i',
            '/(update\s+.+\s+set)/i',
            '/(delete\s+from)/i',
            '/(drop\s+table)/i',
            '/(truncate\s+table)/i',
            '/(--|\#)/',
            '/(\/\*.*\*\/)/',
            '/(waitfor\s+delay)/i',
            '/(sleep\s*\([^)]*\))/i',
            '/(benchmark\s*\([^)]*\))/i',
            '/(load_file\s*\([^)]*\))/i',
            '/(into\s+outfile\s*)/i',
            '/(into\s+dfile\s*)/i',
            '/(exec\s*\([^)]*\))/i',
            '/(xp_cmdshell)/i',
            '/(@@version)/i',
            '/(char\s*\([^)]*\))/i',
            '/(concat\s*\([^)]*\))/i',
            '/(group_concat\s*\([^)]*\))/i',
            '/(information_schema)/i',
            '/(table_schema)/i',
            '/(table_name)/i',
            '/(column_name)/i',
            '/(pg_sleep)/i',
            '/(dbms_pipe)/i',
            '/(utl_http)/i',
            '/(or\s*1\s*=\s*1)/i',
            '/(and\s*1\s*=\s*1)/i',
            '/(or\s*\'\'=\')/i',
            '/(and\s*\'\'=\')/i',
            '/(or\s*\d+\s*=\s*\d+)/i',
            '/(and\s*\d+\s*=\s*\d+)/i',
            '/(\'\s+or\s+\')/i',
            '/(\'\s+and\s+\')/i',
            '/(\|\|)/',
            '/(&&)/',
            '/(;\s*--)/',
            '/(;\s*$)/',
            '/(\b(\'|"|`)\s*[\+\-]\s*[\d\w]+\s*\b)/i',
        ];

        // Create XSS patterns file
        $xssPatterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript\s*:/i',
            '/on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^>\s]+)/i',
            '/vbscript\s*:/i',
            '/expression\s*\([^)]*\)/i',
            '/url\s*\([^)]*\)/i',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/<frame\b[^>]*>(.*?)<\/frame>/is',
            '/<embed\b[^>]*>(.*?)<\/embed>/is',
            '/<object\b[^>]*>(.*?)<\/object>/is',
            '/<applet\b[^>]*>(.*?)<\/applet>/is',
            '/<meta\b[^>]*>/i',
            '/<link\b[^>]*>/i',
            '/<base\b[^>]*>/i',
            '/<form\b[^>]*>(.*?)<\/form>/is',
            '/<input\b[^>]*>/i',
            '/<button\b[^>]*>(.*?)<\/button>/is',
            '/<textarea\b[^>]*>(.*?)<\/textarea>/is',
            '/<select\b[^>]*>(.*?)<\/select>/is',
            '/<img\b[^>]*>/i',
            '/<svg\b[^>]*>(.*?)<\/svg>/is',
            '/<math\b[^>]*>(.*?)<\/math>/is',
            '/data\s*:/i',
            '/alert\s*\([^)]*\)/i',
            '/confirm\s*\([^)]*\)/i',
            '/prompt\s*\([^)]*\)/i',
            '/eval\s*\([^)]*\)/i',
            '/setTimeout\s*\([^)]*\)/i',
            '/setInterval\s*\([^)]*\)/i',
            '/Function\s*\([^)]*\)/i',
            '/<.*?\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^>]+)/i',
            '/&#x?[0-9a-f]+;/i',
            '/fromCharCode\s*\([^)]*\)/i',
            '/unescape\s*\([^)]*\)/i',
            '/escape\s*\([^)]*\)/i',
            '/document\./i',
            '/window\./i',
            '/location\./i',
            '/history\./i',
            '/navigator\./i',
            '/cookie\s*=/i',
            '/localStorage\./i',
            '/sessionStorage\./i',
            '/XMLHttpRequest/i',
            '/fetch\s*\([^)]*\)/i',
            '/\.src\s*=/i',
            '/\.href\s*=/i',
            '/\.action\s*=/i',
            '/\.innerHTML\s*=/i',
            '/\.outerHTML\s*=/i',
            '/\.write\s*\([^)]*\)/i',
            '/\.writeln\s*\([^)]*\)/i',
            '/<!\-\-.*?\-\->/s',
        ];

        // Ensure directories exist
        $wafDir = config_path('waf/rules');
        if (!file_exists($wafDir)) {
            mkdir($wafDir, 0755, true);
        }

        // Save patterns to files
        file_put_contents($wafDir . '/sql_patterns.json', json_encode($sqlPatterns, JSON_PRETTY_PRINT));
        file_put_contents($wafDir . '/xss_patterns.json', json_encode($xssPatterns, JSON_PRETTY_PRINT));

        $this->command->info('WAF rules have been seeded successfully.');
    }
}