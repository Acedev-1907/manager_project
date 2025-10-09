<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository
 * 
 * Repository pattern cơ bản cho tất cả repositories
 * Implements RepositoryInterface và tuân thủ SOLID principles
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Khởi tạo model instance
     * 
     * @return Model
     */
    protected function makeModel(): Model
    {
        $modelClass = $this->getModel();
        return new $modelClass;
    }

    /**
     * Định nghĩa model class
     * 
     * @return string
     */
    abstract protected function getModel(): string;

    /**
     * Tạo mới một bản ghi
     * 
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Tìm bản ghi theo ID với relations
     * 
     * @param int $id
     * @param array $relations
     * @return mixed
     */
    public function find(int $id, array $relations = [])
    {
        $query = $this->model->newQuery();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->find($id);
    }

    /**
     * Cập nhật bản ghi theo ID
     * 
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateById(int $id, array $data)
    {
        $model = $this->find($id);
        
        if (!$model) {
            return null;
        }
        
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Xóa bản ghi theo ID
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);
        
        if (!$model) {
            return false;
        }
        
        return $model->delete();
    }

    /**
     * Lấy tất cả bản ghi
     * 
     * @param array $relations
     * @return mixed
     */
    public function all(array $relations = [])
    {
        $query = $this->model->newQuery();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->get();
    }

    /**
     * Lấy bản ghi với điều kiện
     * 
     * @param array $conditions
     * @param array $relations
     * @return mixed
     */
    public function findWhere(array $conditions, array $relations = [])
    {
        $query = $this->model->newQuery();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->get();
    }

    /**
     * Đếm số lượng bản ghi
     * 
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->count();
    }

    /**
     * Kiểm tra bản ghi tồn tại theo ID
     * 
     * @param int $id
     * @return bool
     */
    public function existsById(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }
}
