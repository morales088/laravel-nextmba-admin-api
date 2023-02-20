<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VideoLibrary;
use DB;

class libraryTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classify:videoLibraries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'classifying video libraries';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $libraries = VideoLibrary::all();
        
        foreach ($libraries as $key => $value) {
            $library = VideoLibrary::find($value->id);
            // dd($library);
            if($value->category == null){
                $library->type = 1;
            }else{
                $library->type = 2;
            }

            $library->save();
            $this->line('ID: '.$value->id);
        }

        $this->line('DONE');
    }
}
