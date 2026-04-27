<?php

namespace Nami\Finance;

use Illuminate\Support\Facades\DB;

class MainService
{
    protected $model;
    protected $fileFolder;
    protected $files;

    public function clearRequest($data)
    {


        foreach ($data as $key => $value) {
            $trimmedValue = (gettype($value) == "string") ? trim($value) : $value;
            if (empty($trimmedValue) && $trimmedValue != 0) {
                unset($data[$key]);
                continue;
            }
            $data[$key] = $trimmedValue;
            if($this->files != null && in_array($key,$this->files)){
                $data[$key] = uploadFile($data[$key], $this->fileFolder);
            }
            if($key == "password"){
                $data[$key] = bcrypt($value);
            }
        }
        return $data;
    }

    public function count($where = [])
    {
        return DB::table($this->model->getTable())->select('id')->where($where)->count();
    }

    public function aggregate($operator,$column='',$where = [])
    {
        return DB::table($this->model->getTable())->select('id',$column)->where($where)->$operator($column);
    }

    public function getDataTable()
    {
        return $this->model->query()->latest();
    }
    public function getWithoutId($id)
    {
        return $this->model->where('id','!=',$id)->get();
    }


    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function first()
    {
        return $this->model->first();
    }

    public function latestFirst()
    {
        return $this->model->latest()->first();
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    public function store($data)
    {
        $data = $this->clearRequest($data);
//        $data['created_by_id'] = auth('admin')->user()->id;
        return $this->model->create($data);
    }

    public function insertMany($data)
    {
        foreach ($data as  $value) {
            $this->model->create($value);
        }
        return true;
    }

    public function update($id, $data)
    {
        $data = $this->clearRequest($data);
        return $this->model->find($id)->update($data);
    }

    public function get()
    {
        return $this->model->latest()->get();
    }

    public function getWhere($where)
    {
        return $this->model->where($where)->latest()->get();
    }

    public function getWhereFirst($where)
    {
        return $this->model->where($where)->first();
    }
    public function getWhereColumn($where)
    {
        return $this->model->where($where);
    }

    public function getWhereIn($column,$where)
    {
        return $this->model->whereIn($column,$where)->latest()->get();
    }

    public function getWhereNotIn($column,$where)
    {
        return $this->model->whereNotIn($column,$where)->latest()->get();
    }

    public function lastId()
    {
        $row = $this->model->orderBy('id', 'DESC')->first();
        return (isset($row->id)) ? ($row->id + 1) : 1;
    }


    public function deleteWithFile($id, $column)
    {
        $obj = $this->find($id);
        deleteFile($obj->{$column});
        $this->delete($id);
    }

    public function updateWhere($where, $data)
    {
        return $this->model->where($where)->update($data);
    }

    public function findToArray($id)
    {
        return $this->model->find($id)->toArray();
    }

    public function deleteWhere($where)
    {
        return $this->model->where($where)->delete();
    }

    public function UpdateOrStore($where,$data){
        return $this->model->updateOrCreate($where,$data);
    }

    public function getWhereNotNull($column)
    {
        return $this->model->whereNotNull($column)->latest()->get();
    }


    // MainController functions
    public function createData($mainRoute)
    {
        return [
            'storeRoute' => route($mainRoute.'.store'),
        ];
    }

    public function editData($id, $mainRoute)
    {
        return [
            'obj' => $this->find($id),
            'updateRoute' => route($mainRoute.'.update', $id)
        ];
    }

    public function showData($id)
    {
        return [
            'obj' => $this->find($id)
        ];
    }


    public function customStore($data)
    {
        $data = $this->clearRequest($data);
        return $this->model->create($data);
    }

    public function customUpdate($id, $data)
    {
        $data = $this->clearRequest($data);
        return $this->model->find($id)->update($data);
    }




}
