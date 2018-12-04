<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeTransformer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:transformer {transformerName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '快速创建Transformer文件';

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
        $name = $this->argument('transformerName');
        $fileName = app_path().'/Transformers/'.$name.".php";
        $content = <<<ETO
<?php

namespace App\Transformers;


use League\Fractal\TransformerAbstract;

class $name extends TransformerAbstract
{
    public function transform()
    {
        return [
            
        ];
    }
}
ETO;

        if(!file_exists($fileName)){
            file_put_contents($fileName,$content);
            $this->info('创建成功');
        }else{
            $this->error('文件已存在!');
        }

    }
}
