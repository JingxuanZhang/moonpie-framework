<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AddUpLoadTable extends Migrator
{
    protected $uploadGroup = 'system_upload_group';
    protected $uploadFile = 'system_upload_file';

    public function up()
    {
        if(!$this->hasTable($this->uploadGroup)){
            $table = $this->table($this->uploadGroup,['engine'=>'InnoDB','comment'=>'文件库分组记录表']);
            $table->setId('group_id')
                ->addColumn(Column::string('group_type')->setComment('分组类型'))
                ->addColumn(Column::string('group_name')->setComment('分组名称'))
                ->addColumn(Column::integer('sort')->setComment('排序'))
                ->addColumn(Column::integer('create_at')->setComment('创建时间'))
                ->addColumn(Column::integer('update_at')->setComment('更新时间'))
                ->create();
        }

        if(!$this->hasTable($this->uploadFile)){
            $table = $this->table($this->uploadFile,['engine'=>'InnoDB','comment'=>'文件库记录']);
            $table->setId('file_id')
                ->addColumn(Column::string('storage',20)->setComment('存储方式'))
                ->addColumn(Column::integer('group_id')->setComment('文件分组id'))
                ->addColumn(Column::string('file_url')->setComment('存储域名'))
                ->addColumn(Column::string('file_name')->setComment('文件路径'))
                ->addColumn(Column::integer('file_size')->setComment('文件大小(字节)'))
                ->addColumn(Column::string('file_type',20)->setComment('文件类型'))
                ->addColumn(Column::string('extension',20)->setComment('文件扩展名'))
                ->addColumn(Column::boolean('is_user')->setComment('是否为c端用户上传'))
                ->addColumn(Column::boolean('is_delete')->setComment('软删除'))
                ->addColumn(Column::integer('create_at')->setComment('创建时间'))
                ->create();
        }


    }


    public function down()
    {
        if($this->hasTable($this->uploadGroup)){
            $this->dropTable($this->uploadGroup);
        }
        if($this->hasTable($this->uploadFile)){
            $this->dropTable($this->uploadFile);
        }
    }


}
