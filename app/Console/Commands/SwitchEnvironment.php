<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SwitchEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:switch {environment? : The environment to switch to (local|production)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch between local and production environment configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $environment = $this->argument('environment');

        if (!$environment) {
            $environment = $this->choice(
                'Select environment configuration:',
                ['local', 'production'],
                0
            );
        }

        $sourceFile = base_path(".env.{$environment}");
        $targetFile = base_path('.env');

        if (!File::exists($sourceFile)) {
            $this->error("Environment file {$sourceFile} does not exist!");
            return 1;
        }

        try {
            File::copy($sourceFile, $targetFile);

            $this->info("✓ Environment switched to: {$environment}");

            if ($environment === 'local') {
                $this->line('');
                $this->info('Local development configuration applied:');
                $this->line('• APP_URL: http://192.168.2.202:3000');
                $this->line('• SESSION_SECURE_COOKIE: false');
                $this->line('• APP_DEBUG: true');
                $this->line('• SESSION_SAME_SITE: none');
                $this->line('');
                $this->comment('You can now run:');
                $this->line('php artisan serve --host=0.0.0.0 --port=3000');
            } else {
                $this->line('');
                $this->info('Production configuration applied:');
                $this->line('• APP_URL: https://turnero.huv.gov.co');
                $this->line('• SESSION_SECURE_COOKIE: true');
                $this->line('• APP_DEBUG: false');
                $this->line('• SESSION_SAME_SITE: lax');
                $this->line('');
                $this->comment('Remember to update database credentials if needed.');
            }

            // Clear config cache
            $this->call('config:clear');
            $this->info('✓ Configuration cache cleared');

            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to switch environment: {$e->getMessage()}");
            return 1;
        }
    }
}
