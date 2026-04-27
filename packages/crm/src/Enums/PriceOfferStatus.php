<?php
namespace Nami\Crm\Enums;


enum PriceOfferStatus : string
{
    case New = "new";
    case Sent = "sent";
    case Contracted = 'contracted';
    case NotContracted = 'not_contracted';




    public function lang()
    {
        return __(trans("crm.{$this->value}"));
    }
}
