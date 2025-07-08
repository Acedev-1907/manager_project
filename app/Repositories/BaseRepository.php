<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    const LIMIT = 30;

    const OFFSET = 0;

    const OPERATOR_WHERE = 'where';

    const OPERATOR_WHERE_BETWEEN = 'between';

    const OPERATOR_WHERE_NOT_IN = 'where_not_in';

    const OPERATOR_WHERE_IN = 'where_in';

    protected $baseModel;
    protected $tableName;

    public function __construct(?array $params = [])
    {
        $this->baseModel = app()->make($this->getModel());
        $this->tableName = $this->baseModel->getTable();
        if (!empty($params)) {
            $this->baseModel = $this->baseQuery($params);
        }
    }

    public function __get($property)
    {
        if ($property === 'model') {
            return clone $this->baseModel;
        }

        return $this->$property;
    }
    abstract protected function getModel(): string;

    private function baseQuery($multi_conditions)
    {
        return $this->model->where(function ($query) use ($multi_conditions) {
            foreach ($multi_conditions as $method => $conditions) {
                foreach ($conditions as $condition) {
                    if (is_callable($condition)) {
                        $query->{$method}($condition);
                    } else {
                        $query->{$method}(...$condition);
                    }
                }
            }
        });
    }

    public function updateByPK(int|Model|null $model = null, array $update = [])
    {
        if ($model instanceof Model) {
            return $model->update($update);
        }

        return $this->model->find($model)->update($update);
    }
}
