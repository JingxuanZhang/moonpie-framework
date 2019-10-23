<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/5/24
 * Time: 15:05
 */

namespace app\common\service\log;


use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use think\Db;

class ThinkDbHandler extends AbstractProcessingHandler
{
    protected $tableName;
    /**
     * @var \think\db\Query
     */
    protected $db;
    public function __construct($dbName, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->tableName = $dbName;
    }

    protected function write(array $record)
    {
        $this->initialize();
        /** @var \DateTimeImmutable $record['datetime']*/
        $this->db->insert([
            'channel' => $record['channel'], 'level' => $record['level'],
            'message' => $record['formatted'], 'create_at' => $record['datetime']->getTimestamp(),
        ]);
    }
    protected function initialize()
    {
        if(is_null($this->db)){
            $this->db = Db::name($this->tableName);
        }
        return $this->db;
    }
    protected function getDefaultFormatter()
    {
        return new JsonFormatter();
    }
}