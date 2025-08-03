<?php

namespace App\Console\Commands;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Console\Command;

class FindCityUers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:findCity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find the code of the city and province';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $birthplace;
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->birthplace = json_decode(Settings::where('name','birthplace')->first()->value);

        $users = User::whereNull('info->birthplace')->whereNotNull('national_code')->limit(5000)->orderBy('id')->get();
        foreach ($users as $user){
            $info = json_decode($user->info??'{}');
            $national_code = substr($user->national_code,0,3);
            echo 'id user:'.$user->id.' | '.$national_code.PHP_EOL;
            try{
                $birthplace = $this->find($national_code);
                if(isset($birthplace) && isset($birthplace['city'])) {
                    $info->birthplace = $this->find($national_code);
                    $user->info = json_encode($info);
                    $user->save();
                }
            }catch (\Exception $e){
                //dd($e);
            }
        }
    }

    function find($national_code){
        foreach ($this->birthplace as $key=>$plases){
            foreach ($plases as $plase) {
                if (str_contains($plase[0], $national_code)) {
                    return ['province'=>$key,'city'=>$plase[1]];
                }
            }
        }
        return [];
    }
}
