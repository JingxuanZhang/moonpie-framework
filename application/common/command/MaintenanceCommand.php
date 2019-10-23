<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/10
 * Time: 14:28
 */

namespace app\common\command;


use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class MaintenanceCommand extends Command
{
    protected function configure()
    {
        $this->setName('maintenance')
            ->addArgument('action', Argument::REQUIRED, 'maintenance direction')
            ->addOption('message', 'm', Option::VALUE_OPTIONAL, 'Invalid Message', '系统正在维护')
            ->addOption('retry', 'r', Option::VALUE_OPTIONAL, 'retry left times(unit:second)')
            ->setDescription('manager app maintenance mode')
            ;
    }
    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        if(!in_array($action, ['up', 'down'])){
            $output->error('unknown action.');
            return;
        }
        $dir = RUNTIME_PATH . 'framework' . DS;
        $path = $dir . 'down';
        switch ($action){
            case 'up':
                if(file_exists($path)){
                    $result = unlink($path);
                    if(!$result){
                        $output->error('sorry, cannot delete maintenance file: '. $path);
                        return;
                    }
                }
                $output->writeln('<info>has been normal mode.</info>');
                break;
            case 'down':
                //进入维护模式
                if(!is_dir($dir)){
                    $result = mkdir($dir, 0755, true);
                    if(!$result){
                        $output->error('sorry, init maintenance dir failed.');
                        return;
                    }
                }
                $message = $input->getOption('message');

                $data = [
                    'time' => time(),
                    'retry' => $input->getOption('retry'),
                    'message' => $message
                ];
                $str = json_encode($data, JSON_UNESCAPED_UNICODE);
                if(json_last_error() !== 0) {
                    $output->error('sorry json encode failed with error: '. json_last_error_msg());
                    return;
                }
                $result = false !== file_put_contents($path, $str);
                if(!$result){
                    $output->error('sorry, save maintenance data failed');
                    return;
                }
                $output->writeln('<info>has been maintenance mode.</info>');
                break;
        }
    }
}