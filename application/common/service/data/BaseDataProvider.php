<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\data;


use app\common\service\base\BaseObject;
use think\Config;
use think\Paginator;

/**
 * BaseDataProvider provides a base class that implements the [[DataProviderInterface]].
 *
 * For more details and usage information on BaseDataProvider, see the [guide article on data providers](guide:output-data-providers).
 *
 * @property int $count The number of data models in the current page. This property is read-only.
 * @property array $keys The list of key values corresponding to [[models]]. Each data model in [[models]] is
 * uniquely identified by the corresponding key value in this array.
 * @property array $models The list of data models in the current page.
 * @property Paginator|false $pagination The pagination object. If this is false, it means the pagination is
 * disabled. Note that the type of this property differs in getter and setter. See [[getPagination()]] and
 * [[setPagination()]] for details.
 * @property Sort|bool $sort The sorting object. If this is false, it means the sorting is disabled. Note that
 * the type of this property differs in getter and setter. See [[getSort()]] and [[setSort()]] for details.
 * @property int $totalCount Total number of possible data models.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class BaseDataProvider extends BaseObject implements DataProviderInterface
{
    /**
     * @var int Number of data providers on the current page. Used to generate unique IDs.
     */
    private static $counter = 0;
    /**
     * @var string an ID that uniquely identifies the data provider among all data providers.
     * Generated automatically the following way in case it is not set:
     *
     * - First data provider ID is empty.
     * - Second and all subsequent data provider IDs are: "dp-1", "dp-2", etc.
     */
    public $id;
    /**
     * @var array 分页相关配置
     */
    public $paginationConfig = [];

    /**
     * @var callable 当需要额外处理pagination时使用
     */
    public $paginationCallback;

    private $_sort;
    private $_pagination;
    private $_keys;
    private $_models;
    private $_totalCount;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->id === null) {
            if (self::$counter > 0) {
                $this->id = 'dp-' . self::$counter;
            }
            self::$counter++;
        }
    }

    /**
     * Prepares the data models that will be made available in the current page.
     * @return array the available data models
     */
    abstract protected function prepareModels();

    /**
     * Prepares the keys associated with the currently available data models.
     * @param array $models the available data models
     * @return array the keys
     */
    abstract protected function prepareKeys($models);

    /**
     * Returns a value indicating the total number of data models in this data provider.
     * @return int total number of data models in this data provider.
     */
    abstract protected function prepareTotalCount();

    /**
     * Prepares the data models and keys.
     *
     * This method will prepare the data models and keys that can be retrieved via
     * [[getModels()]] and [[getKeys()]].
     *
     * This method will be implicitly called by [[getModels()]] and [[getKeys()]] if it has not been called before.
     *
     * @param bool $forcePrepare whether to force data preparation even if it has been done before.
     */
    public function prepare($forcePrepare = false)
    {
        if ($forcePrepare || $this->_models === null) {
            $this->_models = $this->prepareModels();
        }
        if ($forcePrepare || $this->_keys === null) {
            $this->_keys = $this->prepareKeys($this->_models);
        }
    }

    /**
     * Returns the data models in the current page.
     * @return array the list of data models in the current page.
     */
    public function getModels()
    {
        $this->prepare();

        return $this->_models;
    }

    /**
     * Sets the data models in the current page.
     * @param array $models the models in the current page
     */
    public function setModels($models)
    {
        $this->_models = $models;
    }

    /**
     * Returns the key values associated with the data models.
     * @return array the list of key values corresponding to [[models]]. Each data model in [[models]]
     * is uniquely identified by the corresponding key value in this array.
     */
    public function getKeys()
    {
        $this->prepare();

        return $this->_keys;
    }

    /**
     * Sets the key values associated with the data models.
     * @param array $keys the list of key values corresponding to [[models]].
     */
    public function setKeys($keys)
    {
        $this->_keys = $keys;
    }

    /**
     * Returns the number of data models in the current page.
     * @return int the number of data models in the current page.
     */
    public function getCount()
    {
        return count($this->getModels());
    }

    /**
     * Returns the total number of data models.
     * When [[pagination]] is false, this returns the same value as [[count]].
     * Otherwise, it will call [[prepareTotalCount()]] to get the count.
     * @return int total number of possible data models.
     */
    public function getTotalCount()
    {
        if ($this->getPagination() === false) {
            return $this->getCount();
        } elseif ($this->_totalCount === null) {
            $this->_totalCount = $this->prepareTotalCount();
        }

        return $this->_totalCount;
    }

    /**
     * Sets the total number of data models.
     * @param int $value the total number of data models.
     */
    public function setTotalCount($value)
    {
        $this->_totalCount = $value;
    }

    /**
     * Returns the pagination object used by this data provider.
     * Note that you should call [[prepare()]] or [[getModels()]] first to get correct values
     * of [[Pagination::totalCount]] and [[Pagination::pageCount]].
     * @return Paginator|false the pagination object. If this is false, it means the pagination is disabled.
     */
    public function getPagination()
    {
        if ($this->_pagination === null) {
            $this->setPagination(false);
        }

        return $this->_pagination;
    }


    public function setPagination($value, $config = [])
    {
        $simple = isset($config['simple']) ? $config['simple'] : false;
        $listRows = isset($config['listRows']) ? $config['listRows'] : null;
        $options = isset($config['options']) ? $config['options'] : [];
        if (is_array($value)) {
            if (is_int($simple)) {
                $total = $simple;
                $simple = false;
            }
            if (is_array($listRows)) {
                $options = array_merge(Config::get('paginate'), $listRows);
                $listRows = $options['list_rows'];
            } else {
                $options = array_merge(Config::get('paginate') , $options);

                $listRows = $listRows ?: $options['list_rows'];
            }

            /** @var Paginator $class */
            $class = false !== strpos($options['type'], '\\') ? $options['type'] : '\\think\\paginator\\driver\\' . ucwords($options['type']);
            $page = isset($options['page']) ? (int)$options['page'] : call_user_func([
                $class,
                'getCurrentPage',
            ], $options['var_page']);

            $page = $page < 1 ? 1 : $page;

            $options['path'] = isset($options['path']) ? $options['path'] : call_user_func([$class, 'getCurrentPath']);
            if (!isset($total) && !$simple) {
                $total = $this->prepareTotalCount();
            } elseif ($simple) {
                $total = null;
            }
            $this->_pagination = call_user_func([$class, 'make'], $value, $listRows, $page, $total, $simple, $options);
        } elseif ($value instanceof Paginator || $value === false) {
            $this->_pagination = $value;
        } else {
            throw new \InvalidArgumentException('Only Pagination instance, configuration array or false is allowed.');
        }
        if(is_callable($this->paginationCallback) && $this->_pagination instanceof Paginator) {
            call_user_func($this->paginationCallback, $this->_pagination);
        }
        return $this->_pagination;
    }

    /**
     * Returns the sorting object used by this data provider.
     * @return Sort|bool the sorting object. If this is false, it means the sorting is disabled.
     */
    public function getSort()
    {
        if ($this->_sort === null) {
            $this->setSort(false);
        }

        return $this->_sort;
    }

    /**
     * Sets the sort definition for this data provider.
     * @param array|Sort|bool $value the sort definition to be used by this data provider.
     * This can be one of the following:
     *
     * - a configuration array for creating the sort definition object. The "class" element defaults
     *   to 'yii\data\Sort'
     * - an instance of [[Sort]] or its subclass
     * - false, if sorting needs to be disabled.
     *
     * @throws \InvalidArgumentException
     */
    public function setSort($value)
    {
        if (is_array($value)) {
            $config = [];
            if ($this->id !== null) {
                $config['sortParam'] = $this->id . '-sort';
            }
            $this->_sort = new Sort(array_merge($config, $value));
        } elseif ($value instanceof Sort || $value === false) {
            $this->_sort = $value;
        } else {
            throw new \InvalidArgumentException('Only Sort instance, configuration array or false is allowed.');
        }
    }

    /**
     * Refreshes the data provider.
     * After calling this method, if [[getModels()]], [[getKeys()]] or [[getTotalCount()]] is called again,
     * they will re-execute the query and return the latest data available.
     */
    public function refresh()
    {
        $this->_totalCount = null;
        $this->_models = null;
        $this->_keys = null;
    }
}
