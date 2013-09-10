<?php
/**
 * ----- mediawatch ----
 * User: Iwan Sapoetra
 * Date: 9/11/13
 * Time: 12:15 AM
 * 
 */

namespace System\Web\Security;


interface IWebFilterInput {

    public function filterInput($value);
}