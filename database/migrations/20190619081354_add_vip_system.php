<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AddVipSystem extends Migrator
{
    protected $tablePrice = 'acl_role_price';
    protected $tableCredit = 'acl_role_transfer_credit';

    public function up()
    {
        $this->addPriceTable();
        $this->addRoleTransferCreditTable();
    }

    public function down()
    {
        $this->dropRoleTransferCreditTable();
        $this->dropPriceTable();
    }

    //凭证部分
    protected function addRoleTransferCreditTable()
    {
        if (!$this->hasTable($this->tableCredit)) {
            $table = $this->table($this->tableCredit, ['engine' => 'InnoDB', 'comment' => '角色转换凭证记录表']);
            $table->addColumn(Column::integer('user_id')->setComment('用户ID'))
                ->addColumn(Column::integer('order_id')->setComment('关联的订单'))
                ->addColumn(Column::integer('config_id')->setComment('关联的配置'))
                ->addColumn(Column::integer('valid_seconds')->setComment('关联的配置的有效时长0表示无期限'))
                ->addColumn(Column::dateTime('deadline')->setComment('关联的配置的截止日期'))
                ->addColumn(Column::integer('create_at')->setComment('记录添加时间'))
                ->addColumn(Column::integer('update_at')->setComment('记录修改时间'))
                ->addIndex(['user_id', 'order_id'], ['name' => 'unique_record', 'unique' => true])
                ->create();
        }
    }

    protected function dropRoleTransferCreditTable()
    {
        if ($this->hasTable($this->tableCredit)) {
            $this->dropTable($this->tableCredit);
        }
    }

    //商品部分
    protected function addPriceTable()
    {
        if (!$this->hasTable($this->tablePrice)) {
            $table = $this->table($this->tablePrice, ['engine' => 'InnoDB', 'comment' => '角色转换价格配置']);
            $table->addColumn(Column::integer('from_role')->setComment('来源角色'))
                ->addColumn(Column::integer('to_role')->setComment('目标角色'))
                ->addColumn(Column::decimal('price', 8, 2)->setComment('购买价格'))
                ->addColumn(Column::integer('valid_seconds')->setComment('有效时长,单位秒')->setUnsigned())
                ->addColumn(Column::dateTime('deadline')->setComment('截止日期'))
                ->addColumn(Column::boolean('valid')->setComment('是否启用')->setUnsigned())
                ->addColumn(Column::integer('create_at')->setComment('记录添加时间'))
                ->addColumn(Column::integer('update_at')->setComment('记录修改时间'))
                ->addIndex(['from_role', 'to_role'], ['name' => 'unique_config', 'unique' => true])
                ->create();
        }
    }

    protected function dropPriceTable()
    {
        if ($this->hasTable($this->tablePrice)) {
            $this->dropTable($this->tablePrice);
        }
    }
}
