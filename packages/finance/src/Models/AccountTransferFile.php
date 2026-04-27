<?php

namespace Nami\Finance\Models;


class AccountTransferFile extends Finance
{
    protected $table = 'fn_account_transfer_files';

    protected $fillable = [
        'account_transfer_id',
        'file',
    ];


}
