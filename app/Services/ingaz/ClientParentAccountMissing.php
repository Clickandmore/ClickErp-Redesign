<?php

namespace App\Services\ingaz;

/** لا يوجد حساب «العملاء» الأب في دليل الحسابات، فلا يمكن إنشاء الحساب المالي للعميل. */
class ClientParentAccountMissing extends \DomainException
{
}
