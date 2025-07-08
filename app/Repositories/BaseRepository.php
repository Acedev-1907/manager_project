<?php

namespace App\Repositories;


abstract class BaseRepository
{
    protected $model;

    public function __construct()
    {
        $modelClass = $this->getModel();
        $this->model = new $modelClass;
    }

    abstract protected function getModel(): string;

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function find($id, $relations = [])
    {
        return $this->model->with($relations)->find($id);
    }

    public function updateById($id, array $data)
    {
        $model = $this->model->find($id);
        if ($model) {
            $model->update($data);
            return $model;
        }
        return null;
    }

    public function delete($id)
    {
        $model = $this->model->find($id);
        if ($model) {
            return $model->delete();
        }
        return false;
    }
}
