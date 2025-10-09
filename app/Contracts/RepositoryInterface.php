<?php

namespace App\Contracts;

/**
 * Repository Interface
 * 
 * Định nghĩa các phương thức chuẩn cho tất cả repositories
 * Tuân thủ Interface Segregation Principle (SOLID)
 */
interface RepositoryInterface
{
    /**
     * Tạo mới một bản ghi
     * 
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Tìm bản ghi theo ID
     * 
     * @param int $id
     * @param array $relations
     * @return mixed
     */
    public function find(int $id, array $relations = []);

    /**
     * Cập nhật bản ghi theo ID
     * 
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateById(int $id, array $data);

    /**
     * Xóa bản ghi theo ID
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Lấy tất cả bản ghi
     * 
     * @param array $relations
     * @return mixed
     */
    public function all(array $relations = []);

    /**
     * Lấy bản ghi với điều kiện
     * 
     * @param array $conditions
     * @param array $relations
     * @return mixed
     */
    public function findWhere(array $conditions, array $relations = []);

    /**
     * Kiểm tra bản ghi tồn tại theo ID
     * 
     * @param int $id
     * @return bool
     */
    public function existsById(int $id): bool;
}

