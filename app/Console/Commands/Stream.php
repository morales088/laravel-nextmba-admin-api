<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ModuleStream;
use App\Models\ReplayVideo;
use DB;

class Stream extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transfer:stream';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'transfer stream details from module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $DBtransaction = DB::transaction(function() {
            // Modules
            $modules = DB::SELECT("select * from modules 
                                        where live_url IS NOT NULL 
                                        OR uid IS NOT NULL
                                        OR zoom_link IS NOT NULL");

            foreach ($modules as $key => $value) {
                $check = ModuleStream::where('module_id', $value->id)->get();
                // dd($check, empty($check), !$check->isEmpty());
                if(!$check->isEmpty()) continue;

                if(isset($value->live_url)){

                    $moduleStream = new ModuleStream;
                    $moduleStream->module_id = $value->id;
                    $moduleStream->name = "Youtube";
                    $moduleStream->key = $value->live_url;
                    $moduleStream->chat_link = $value->chat_url;
                    $moduleStream->type = 1;
                    // $moduleStream->broadcast_status = $value->broadcast_status;
                    $moduleStream->status = 4;

                    $moduleStream->save();
                }

                if(isset($value->uid)){
                    
                    $moduleStream = new ModuleStream;
                    $moduleStream->module_id = $value->id;
                    $moduleStream->name = "CloudFlare";
                    $moduleStream->key = $value->uid;
                    // $moduleStream->chat_link = null;
                    $moduleStream->type = 2;
                    // $moduleStream->broadcast_status = $value->broadcast_status;
                    $moduleStream->status = $value->broadcast_status;

                    $moduleStream->save();
                }

                if(isset($value->zoom_link)){
                    
                    $moduleStream = new ModuleStream;
                    $moduleStream->module_id = $value->id;
                    $moduleStream->name = "Zoom";
                    $moduleStream->key = $value->zoom_link;
                    // $moduleStream->chat_link = null;
                    $moduleStream->type = 2;
                    // $moduleStream->broadcast_status = $value->broadcast_status;
                    $moduleStream->status = 4;

                    $moduleStream->save();
                }

                $this->line("Module Id: $value->id");

            }

            // Topics
            $topics = DB::SELECT("select * from topics 
                                    where video_link IS NOT NULL 
                                    OR vimeo_url IS NOT NULL
                                    OR uid IS NOT NULL");
            

            foreach ($topics as $key => $value) {
                $check = ReplayVideo::where('topic_id', $value->id)->get();

                if(!$check->isEmpty()) continue;

                if(isset($value->video_link)){

                    $moduleStream = new ReplayVideo;
                    $moduleStream->topic_id = $value->id;
                    $moduleStream->name = "Youtube";
                    $moduleStream->stream_link = $value->video_link;
                    $moduleStream->type = 1;
                    $moduleStream->status =2;

                    $moduleStream->save();
                }

                if(isset($value->vimeo_url)){

                    $moduleStream = new ReplayVideo;
                    $moduleStream->topic_id = $value->id;
                    $moduleStream->name = "Vimeo";
                    $moduleStream->stream_link = $value->vimeo_url;
                    $moduleStream->type = 4;
                    $moduleStream->status =2;

                    $moduleStream->save();
                }

                if(isset($value->uid)){

                    $moduleStream = new ReplayVideo;
                    $moduleStream->topic_id = $value->id;
                    $moduleStream->name = "CloudFlare";
                    $moduleStream->stream_link = $value->uid;
                    $moduleStream->type = 2;
                    $moduleStream->status =2;

                    $moduleStream->save();
                }

                $this->line("Module Id: $value->id");

            }

        });

        $this->line("DONE.");
    }
}
