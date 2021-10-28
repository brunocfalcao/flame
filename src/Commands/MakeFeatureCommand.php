<?php

namespace Brunocfalcao\Flame\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeFeatureCommand extends Command
{
    protected $headersConfirmation = ['Parameter', 'Value', 'Comments'];

    protected $headersRouteExample = ['Name', 'Value'];

    /**
     * Flame configuration group.
     *
     * @var string
     */
    protected $group;

    /**
     * Feature to be created.
     *
     * @var string
     */
    protected $feature;

    /**
     * Base path for the feature folder.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Controller action, optional.
     *
     * @var string
     */
    protected $action;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:feature';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Flame feature, so you can build something awesome!';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('');
        $this->info('*** Waygou Flame - Create Feature ***');
        $this->info('');

        if (! file_exists(config_path('flame.php'))) {
            return $this->error('flame.php configuration file not found. Please publish it first via php artisan vendor:publish --tag=flame-configuration');
        }

        $namespaceGroups = collect(array_keys(config('flame.groups')));

        $namespaceGroups->transform(function ($item) {
            return $item.' ('.config("flame.groups.{$item}.namespace").')';
        });

        $hint = $this->choice('What is the Flame namespace group you want to use?', $namespaceGroups->toArray());
        // Compute $hint string.
        $hint = explode(' ', $hint)[0];

        $out = false;
        while (! $out) {
            $this->feature = $this->ask("What is your Feature name (can be like 'Welcome' or 'Services/Welcome' in case you want to create a directory) ?");
            $this->featureNamespace = str_replace('/', '\\', $this->feature);

            $out = $this->validateClassName($this->feature);

            if (! $out) {
                $this->error('Feature name is blank or invalid (e.g. cannot start with a integer, or a symbol)! Try again.');
            }
        }

        $out = false;
        while (! $out) {
            $this->action = $this->ask('What is your Action name?');

            $out = $this->validateClassName($this->action);

            if (! $out) {
                $this->error('Action name is blank or invalid (e.g. cannot start with a integer, or a symbol)! Try again.');
            }
        }

        // Create parameters, except default action.
        $this->group = $hint;
        $this->feature = studly_case($this->feature);

        // Calculate namespace file path or throw error.
        if (is_null(config("flame.groups.{$this->group}.path"))) {
            $this->error('Your feature namespace path cannot be null. Please check your flame.php configuration file.');
        }

        $this->basePath = group_absolute_path($this->group);
        $this->fullPath = $this->basePath.'/'.str_replace('/', '\\', $this->feature);

        $this->action = camel_case($this->action);
        $this->controllerNamespace = config("flame.groups.{$this->group}.namespace").
                                     '\\'.
                                     $this->feature.
                                     "\\Controllers\\{$this->feature}Controller";

        // Show table to confirm configuration data.
        $this->table($this->headersConfirmation, [['Namespace Group', $this->group, 'Your Flame namespace group, as in your flame.php configuration.'],
            ['Feature Name', $this->feature, 'Your feature name (directory), in studly case.'],
            ['Base path', $this->basePath, 'File path where your new feature will be created.'],
            ['Default action', $this->action, 'The action method name that will be created inside your feature.'], ]);

        if ($this->confirm('Do you wish to continue?', true)) {
            // Check feature directory.
            if (File::exists("{$this->basePath}/{$this->feature}")) {
                if ($this->confirm('Feature directory already exists. IT WILL BE DELETED! Do you want to continue?')) {
                    // Delete directory prior start.
                    File::deleteDirectory($this->basePath, true);
                } else {
                    return;
                }
            }

            // Compute scaffolds.
            $this->makeFeatureDirectories();
            $this->info('Directories creation -- OK.');

            $this->parseFiles();
            $this->info('Files creation -- OK.');

            $this->info('');
            $this->info('');

            $this->info('Feature created! Here is a possible route example:');

            $featureKebab = kebab_case($this->feature);
            $actionKebab = kebab_case($this->action);
            $this->table($this->headersRouteExample, [['Route', "Route::get('{$featureKebab}', '\\{$this->controllerNamespace}@{$this->action}')->name('{$featureKebab}.{$actionKebab}');"]]);

            $this->info('');
            $this->info('Build something amazing!');
        }
    }

    /**
     * Iterate the base path (invokable, or string?).
     *
     * @param  string  $configPath  The flame configuration path.
     * @return string The path.
     */
    protected function iteratePath($configPath)
    {
        if (is_null($configPath)) {
            return;
        }

        if (gettype($configPath) == 'object') {
            return app($configPath)();
        } else {
            return config("flame.groups.{$this->group}.path");
        }
    }

    protected function validateClassName($name)
    {
        if (preg_match("/\A[A-Za-z][A-Za-z0-9]+[A-Za-z]/", $name) == 1) {
            return true;
        }

        return false;
    }

    /**
     * Create the following file structure:
     * Twinkles (dir)
     *   welcome.blade.php
     * Controllers (dir)
     *   FeatureController.php
     * Panels (dir)
     *   (action).blade.php (if action don't exist then default.blade.php).
     *
     * Parse also each file with the respective text replacement given the
     * arguments.
     *
     * @return void
     */
    protected function parseFiles($root = 'Blank')
    {
        /*
         * Create the following file structure:
         * Twinkles (dir)
         *   welcome.blade.php
         * Controllers (dir)
         *   FeatureController.php
         * Panels (dir)
         *   (action).blade.php (if action don't exist then default.blade.php)
         *
         * Parse also each file with the respective text replacement given the
         * arguments.
         */

        // Welcome Twinkle.
        $this->parseFile(
            __DIR__."/../../resources/scaffolding/{$root}/Feature/Twinkles/welcome.blade.php.stub",
            $this->fullPath.'/Twinkles/welcome.blade.php',
            ['{{feature}}'],
            [$this->feature]
        );

        // Feature Controller.
        $this->parseFile(
            __DIR__."/../../resources/scaffolding/{$root}/Feature/Controllers/FeatureController.php.stub",
            $this->fullPath."/Controllers/{$this->feature}Controller.php",
            ['{{namespace}}', '{{controller_name}}', '{{action}}'],
            [config("flame.groups.{$this->group}.namespace").'\\'.$this->feature.'\\Controllers', "{$this->feature}Controller", $this->action]
        );

        // Twinkle Controller.
        $this->parseFile(
            __DIR__."/../../resources/scaffolding/{$root}/Feature/Controllers/WelcomeController.php.stub",
            $this->fullPath.'/Controllers/WelcomeController.php',
            ['{{namespace}}', '{{action}}'],
            [config("flame.groups.{$this->group}.namespace").'\\'.$this->feature.'\\Controllers', $this->action]
        );

        // Panel.
        $this->parseFile(
            __DIR__."/../../resources/scaffolding/{$root}/Feature/Panels/default.blade.php.stub",
            $this->fullPath."/Panels/{$this->action}.blade.php"
        );
    }

    /**
     * Parses the original file with the respective replacement pairs, and
     * creates the destination file with everything replaced.
     *
     * @param  string  $origin  The original file path.
     * @param  string  $destination  The destination file path.
     * @param  array  $translations  The keys to translate: {{example}}.
     * @param  array  $conversions  The the converted values.
     * @return void
     */
    protected function parseFile(string $origin, string $destination, array $translations = [], array $conversions = [])
    {
        $data = str_replace($translations, $conversions, file_get_contents($origin));
        file_put_contents($destination, $data);
    }

    /**
     * Creates the Feature directory and sub-directories.
     *
     * @return void
     */
    protected function makeFeatureDirectories()
    {
        File::makeDirectories([$this->basePath.'/'.$this->feature,
            $this->basePath.'/'.$this->feature.'/Twinkles',
            $this->basePath.'/'.$this->feature.'/Panels',
            $this->basePath.'/'.$this->feature.'/Controllers', ]);
    }
}
