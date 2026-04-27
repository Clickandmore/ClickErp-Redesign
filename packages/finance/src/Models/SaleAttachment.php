<?php

namespace Nami\Finance\Models;

use Nami\Finance\Models\Sale;

class SaleAttachment extends Finance
{
    protected $table ='sales_attachments';
    protected $fillable =['sale_id' , 'file'];


    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
