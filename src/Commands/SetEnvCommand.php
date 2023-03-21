<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class SetEnvCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'souldoit:set-env {new_env_var?} {--E|env_file_path=.env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update/insert env variable';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $env_file_path = $this->option('env_file_path');

        $new_env_var_arr = [];

        $new_env_var = $this->argument('new_env_var');
        if(empty($new_env_var)){
            $new_env_var_arr[0] = $this->components->ask('Please insert the variable name');
            if(empty($new_env_var_arr[0])){
                $this->components->error('The variable name should not be empty.');
                return;
            }
            if(Str::of($new_env_var_arr[0])->isMatch('/\w+\s+\w+/')){
                $this->components->error('The variable name should not have whitespaces.');
                return;
            }

            $new_env_var_arr[1] = $this->components->ask('Please insert the value');
            if(empty($new_env_var_arr[1])){
                $this->components->error('The value should not be empty.');
                return;
            }
        }else{
            $new_env_var_arr = explode("=", $new_env_var);

            if(count($new_env_var_arr) !== 2){
                $this->components->error('Invalid argument format. Correct format should be {var}={value}.');
                return;
            }
        }

        $new_env_var_arr = Arr::map($new_env_var_arr, function ($value, $key) {
            return trim(($key === 0 ? strtoupper($value) : $value));
        });

        $new_env_var_arr[1] = "\"$new_env_var_arr[1]\"";

        $new_env_var_final = implode("=", $new_env_var_arr);

        if(!$this->confirmToProceed()){
            return;
        }

        $env_file = File::get($env_file_path);
        
        $is_already_exist = Str::of($env_file)->isMatch("/^$new_env_var_arr[0]=/m");
        
        if($is_already_exist){
            $replaced_env = Str::of($env_file)->replaceMatches("/^$new_env_var_arr[0]=.*$/m", $new_env_var_final);
            File::put($env_file_path, $replaced_env);
        }else{
            $is_last_env_have_newline = Str::of($env_file)->isMatch("/\n$/");
            if(!$is_last_env_have_newline) $new_env_var_final = "\n$new_env_var_final";

            File::append($env_file_path, $new_env_var_final);
        }

        $this->components->info('Success');
    }
}
