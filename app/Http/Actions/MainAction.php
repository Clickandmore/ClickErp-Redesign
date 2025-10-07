<?php

namespace App\Http\Actions;

class MainAction
{
    protected $model;
    protected $fileFolder;

    protected $files;

    public function clearRequesrt($data)
    {
        foreach ($data as $key => $value) {
            // Debug: Log any array fields
            if (is_array($value)) {
                \Log::error("[MainAction] Array field found: $key => " . print_r($value, true));
            }

            // Handle arrays properly - don't try to trim them
            if (is_array($value)) {
                // If array is empty, remove it
                if (empty($value)) {
                    unset($data[$key]);
                    \Log::info("[MainAction] Removed empty array field: $key");
                } else {
                    // If array has values, keep it as is
                    \Log::info("[MainAction] Keeping array field: $key with values: " . implode(',', $value));
                }
                continue;
            }

            // Handle strings and other types
            $trimedValue = (gettype($value) == "string") ? trim($value) : $value;
            if ($trimedValue === '' || $trimedValue === null) {
                unset($data[$key]);
                continue;
            }
            $data[$key] = $trimedValue;
        }
        return $data;
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function first()
    {
        return $this->model->first();
    }

    public function delete($id)
    {
         return $this->model->destroy($id);
    }

    public function store($data)
    {
        $data = $this->clearRequesrt($data);
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $data = $this->clearRequesrt($data);
        return $this->model->where("id", $id)->update($data);
    }

    public function get()
    {
        return $this->model->get();
    }

    public function getWhere($where)
    {
        return $this->model->where($where)->get();
    }

    public function getWhereFirst($where)
    {
        return $this->model->where($where)->first();
    }

    public function getWhereIn($column, $where)
    {
        return $this->model->whereIn($column, $where)->get();
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


    public function UpdateOrStore($where, $data)
    {
        $data["created_by_id"] = auth('admin')->user()->id;
        return $this->model->updateOrCreate($where, $data);
    }

}
