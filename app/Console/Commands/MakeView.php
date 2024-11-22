<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:view {name : The name of the view} {--resource : Create resource views (index, create, edit, show)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view file or resource views';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $isResource = $this->option('resource');
        $viewPath = resource_path('views/' . str_replace('.', '/', $name));

        if ($isResource) {
            $files = ['index', 'create', 'edit', 'show'];
            foreach ($files as $file) {
                $filePath = "{$viewPath}/{$file}.blade.php";
                $this->createView($filePath);
            }
            $this->info('Resource views created successfully!');
        } else {
            $filePath = "{$viewPath}.blade.php";
            $this->createView($filePath);
            $this->info("View {$name}.blade.php created successfully!");
        }

        return 0;
    }

    private function createView($path)
    {
        $directory = dirname($path);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (!File::exists($path)) {
            File::put($path, '');
        } else {
            $this->warn("View {$path} already exists!");
        }
    }
}
