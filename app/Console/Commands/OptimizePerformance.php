<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PerformanceOptimizationService;
use Illuminate\Support\Facades\Log;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bus-tracker:optimize-performance 
                            {--type=all : Type of optimization (all, database, realtime, memory, network)}
                            {--metrics : Show performance metrics only}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize bus tracker performance for 250-300+ concurrent users';

    protected PerformanceOptimizationService $optimizationService;

    public function __construct(PerformanceOptimizationService $optimizationService)
    {
        parent::__construct();
        $this->optimizationService = $optimizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚌 Bus Tracker Performance Optimization');
        $this->info('=====================================');

        if ($this->option('metrics')) {
            return $this->showMetrics();
        }

        $optimizationType = $this->option('type');
        $detailed = $this->option('detailed');

        $this->info("Starting {$optimizationType} optimization...");
        $this->newLine();

        $results = [];

        switch ($optimizationType) {
            case 'database':
                $results['database'] = $this->optimizeDatabase($detailed);
                break;
            case 'realtime':
                $results['realtime'] = $this->optimizeRealTime($detailed);
                break;
            case 'memory':
                $results['memory'] = $this->optimizeMemory($detailed);
                break;
            case 'network':
                $results['network'] = $this->optimizeNetwork($detailed);
                break;
            case 'all':
            default:
                $results['database'] = $this->optimizeDatabase($detailed);
                $results['realtime'] = $this->optimizeRealTime($detailed);
                $results['memory'] = $this->optimizeMemory($detailed);
                $results['network'] = $this->optimizeNetwork($detailed);
                break;
        }

        $this->displayResults($results);
        $this->showRecommendations();

        return Command::SUCCESS;
    }

    private function optimizeDatabase(bool $detailed): array
    {
        $this->info('🗄️  Optimizing database queries...');
        
        $progressBar = $this->output->createProgressBar(4);
        $progressBar->start();

        $result = $this->optimizationService->optimizeDatabaseQueries();
        
        $progressBar->advance();
        
        if ($detailed && $result['success']) {
            $this->newLine(2);
            $this->line('   ✅ Location queries optimized');
            $this->line('   ✅ Query caching implemented');
            $this->line('   ✅ Aggregation queries optimized');
            $this->line('   ✅ Connection pooling configured');
        }
        
        $progressBar->finish();
        $this->newLine();

        return $result;
    }

    private function optimizeRealTime(bool $detailed): array
    {
        $this->info('⚡ Optimizing real-time updates...');
        
        $progressBar = $this->output->createProgressBar(4);
        $progressBar->start();

        $result = $this->optimizationService->optimizeRealTimeUpdates();
        
        $progressBar->advance();
        
        if ($detailed && $result['success']) {
            $this->newLine(2);
            $this->line('   ✅ Batch processing optimized');
            $this->line('   ✅ WebSocket broadcasting optimized');
            $this->line('   ✅ Position caching implemented');
            $this->line('   ✅ Write operations optimized');
        }
        
        $progressBar->finish();
        $this->newLine();

        return $result;
    }

    private function optimizeMemory(bool $detailed): array
    {
        $this->info('🧠 Optimizing memory usage...');
        
        $progressBar = $this->output->createProgressBar(4);
        $progressBar->start();

        $result = $this->optimizationService->optimizeMemoryUsage();
        
        $progressBar->advance();
        
        if ($detailed && $result['success']) {
            $this->newLine(2);
            $this->line('   ✅ Data cleanup implemented');
            $this->line('   ✅ Object lifecycle optimized');
            $this->line('   ✅ Data structures optimized');
            $this->line('   ✅ Garbage collection optimized');
            
            if (isset($result['memory_usage'])) {
                $memory = $result['memory_usage'];
                $this->line("   📊 Memory: {$memory['initial_mb']}MB → {$memory['final_mb']}MB (Δ{$memory['difference_mb']}MB)");
            }
        }
        
        $progressBar->finish();
        $this->newLine();

        return $result;
    }

    private function optimizeNetwork(bool $detailed): array
    {
        $this->info('🌐 Optimizing network efficiency...');
        
        $progressBar = $this->output->createProgressBar(4);
        $progressBar->start();

        $result = $this->optimizationService->optimizeNetworkEfficiency();
        
        $progressBar->advance();
        
        if ($detailed && $result['success']) {
            $this->newLine(2);
            $this->line('   ✅ Response compression enabled');
            $this->line('   ✅ API payloads optimized');
            $this->line('   ✅ Data serialization optimized');
            $this->line('   ✅ CDN caching configured');
        }
        
        $progressBar->finish();
        $this->newLine();

        return $result;
    }

    private function showMetrics(): int
    {
        $this->info('📊 Performance Metrics');
        $this->info('=====================');

        $metrics = $this->optimizationService->getPerformanceMetrics();

        if (!$metrics['success']) {
            $this->error('Failed to retrieve performance metrics: ' . $metrics['error']);
            return Command::FAILURE;
        }

        $this->displayMetricsTable($metrics['metrics']);

        return Command::SUCCESS;
    }

    private function displayMetricsTable(array $metrics): void
    {
        // Database Metrics
        $this->info('🗄️  Database Performance');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Active Connections', $metrics['database']['active_connections']],
                ['Query Count', $metrics['database']['query_count']],
                ['Average Query Time', $metrics['database']['average_query_time']],
                ['Slow Queries', $metrics['database']['slow_queries']],
                ['Connection Pool Usage', $metrics['database']['connection_pool_usage']]
            ]
        );

        // Memory Metrics
        $this->info('🧠 Memory Usage');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Usage', $metrics['memory']['current_usage_mb'] . ' MB'],
                ['Peak Usage', $metrics['memory']['peak_usage_mb'] . ' MB'],
                ['Memory Limit', $metrics['memory']['memory_limit']],
                ['GC Enabled', $metrics['memory']['gc_enabled'] ? 'Yes' : 'No']
            ]
        );

        // Cache Metrics
        $this->info('💾 Cache Performance');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Driver', $metrics['cache']['cache_driver']],
                ['Estimated Hit Ratio', $metrics['cache']['estimated_hit_ratio']],
                ['Eviction Policy', $metrics['cache']['eviction_policy']],
                ['Real-time TTL', $metrics['cache']['ttl_configuration']['real_time_data']],
                ['Static TTL', $metrics['cache']['ttl_configuration']['static_data']]
            ]
        );

        // Network Metrics
        $this->info('🌐 Network Efficiency');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Average Response Size', $metrics['network']['average_response_size']],
                ['Compression Ratio', $metrics['network']['compression_ratio']],
                ['API Response Time', $metrics['network']['api_response_time']],
                ['Mobile Efficiency', $metrics['network']['mobile_efficiency']]
            ]
        );
    }

    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📋 Optimization Results');
        $this->info('======================');

        $totalOptimizations = 0;
        $successfulOptimizations = 0;

        foreach ($results as $type => $result) {
            $totalOptimizations++;
            
            if ($result['success']) {
                $successfulOptimizations++;
                $this->line("✅ {$type}: " . $result['message']);
            } else {
                $this->error("❌ {$type}: " . ($result['error'] ?? 'Optimization failed'));
            }
        }

        $this->newLine();
        $this->info("Completed: {$successfulOptimizations}/{$totalOptimizations} optimizations");

        if ($successfulOptimizations === $totalOptimizations) {
            $this->info('🎉 All optimizations completed successfully!');
        } elseif ($successfulOptimizations > 0) {
            $this->warn('⚠️  Some optimizations completed with issues.');
        } else {
            $this->error('❌ All optimizations failed. Check logs for details.');
        }
    }

    private function showRecommendations(): void
    {
        $this->newLine();
        $this->info('💡 Performance Recommendations');
        $this->info('==============================');

        $recommendations = [
            'Database' => [
                'Monitor query performance regularly',
                'Use database connection pooling',
                'Implement proper indexing strategy',
                'Consider read replicas for high load'
            ],
            'Real-time Updates' => [
                'Use WebSocket for primary communication',
                'Implement AJAX polling as fallback',
                'Batch location updates efficiently',
                'Monitor connection scaling'
            ],
            'Memory Management' => [
                'Implement automatic data cleanup',
                'Monitor memory usage patterns',
                'Use efficient data structures',
                'Configure garbage collection properly'
            ],
            'Network Optimization' => [
                'Enable response compression',
                'Optimize API payload sizes',
                'Use CDN for static assets',
                'Implement proper caching headers'
            ]
        ];

        foreach ($recommendations as $category => $items) {
            $this->line("📌 {$category}:");
            foreach ($items as $item) {
                $this->line("   • {$item}");
            }
            $this->newLine();
        }

        $this->info('🔍 For detailed performance analysis, run:');
        $this->line('   php artisan bus-tracker:optimize-performance --metrics');
        
        $this->newLine();
        $this->info('📈 Monitor performance continuously with:');
        $this->line('   php artisan bus-tracker:optimize-performance --type=all --detailed');
    }
}