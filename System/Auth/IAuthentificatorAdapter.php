<?php
namespace System\Auth;

interface IAuthentificatorAdapter
{
    function setIdentify($value);

    function setCredential($value);

    function SetLogonMethod($value);

    function setRemember($value);

    /**
     *
     * Enter description here ...
     * @return AuthResult
     */
    function authenticate();

}
