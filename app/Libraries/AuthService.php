<?php

namespace App\Libraries;

class AuthService
{
    private $user;

    public function setUser($user){
        $this->user=$user;
    }

    public function getUser(){
        return $this->user;
    }
}
