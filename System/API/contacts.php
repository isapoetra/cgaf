<?php
/**
 * User: isapoetra
 * Date: 8/13/13
 * Time: 9:35 AM
 */

namespace System\API;


class Contacts extends PublicApi
{
    function phone($configs)
    {
        return '<a href="tel:' . $configs->id . '"><i class="icon-phone"></i></a>';
    }

    function email($configs)
    {
        return '<a href="mailto:' . $configs->id . '"><i class="icon-envelope"></i></a>';
    }
}