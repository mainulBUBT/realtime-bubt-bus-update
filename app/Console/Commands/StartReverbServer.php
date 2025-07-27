<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class StartReverbServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:start 
                            {--host=0.0.0.0 : The host to bind the server to}
                            {--port=8080 : The port to bind the server to}
                            {--hostname= : The hostname for the server}
                            {--debug : Enable debug mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Laravel Reverb WebSocket server for bus tracking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $hostname = $this->option('hostname') ?: config('app.url');
        $debug = $this->option('debug');

        $this->info('Starting Laravel Reverb WebSocket server...');
        $this->info("Host: {$host}");
        $this->info("Port: {$port}");
        $this->info("Hostname: {$hostname}");

        if ($debug) {
            $this->info('Debug mode enabled');
        }

        // Build the command
        $command = [
            'php',
            'artisan',
            'reverb:start',
            "--host={$host}",
            "--port={$port}",
            "--hostname={$hostname}"
        ];

        if ($debug) {
            $command[] = '--debug';
        }

        // Check if port is available
        if ($this->isPortInUse($host, $port)) {
            $this->error("Port {$port} is already in use on {$host}");
            return 1;
        }

        $this->info('WebSocket server starting...');
        $this->info('Press Ctrl+C to stop the server');
        $this->newLine();

        // Start the server
        $process = Process::start($command);
        
        // Handle the process output
        while ($process->running()) {
            $output = $process->getIncrementalOutput();
            if ($output) {
                $this->line($output);
            }
            
            $errorOutput = $process->getIncrementalErrorOutput();
            if ($errorOutput) {
                $this->error($errorOutput);
            }
            
            usleep(100000); // 0.1 second
        }

        $exitCode = $process->wait();
        
        if ($exitCode === 0) {
            $this->info('WebSocket server stopped gracefully');
        } else {
            $this->error("WebSocket server stopped with exit code: {$exitCode}");
        }

        return $exitCode;
    }

    /**
     * Check if a port is in use
     *
     * @param string $host
     * @param int $port
     * @return bool
     */
    private function isPortInUse(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 1);
        
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        
        return false;
    }
}