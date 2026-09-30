<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShelterDocumentTemplate extends Model
{
    protected $fillable = ['name', 'kind', 'company_name', 'title', 'declaration', 'footer', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function printValues(): array
    {
        $values = $this->only(['name', 'kind', 'company_name', 'title', 'declaration', 'footer']);
        foreach (['company_name', 'title', 'declaration', 'footer'] as $field) {
            $values[$field] = replaceDocumentPlaceholders($values[$field] ?? '');
        }

        return $values;
    }
}
