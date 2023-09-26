<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Company;

class UniqueRucRule implements Rule
{
    //public $user_id;
    public $company_id;

     public function __construct($company_id = null)
    {
        $this->company_id = $company_id;
    }

//atribute es ruc  value es por ejemplo 20447393302  de acuerdo al valor que se pone en el parametro
    public function passes($attribute, $value)
    {
        $company = Company::where('ruc', $value)
            ->where('user_id', Auth()->id())
            ->when($this->company_id, function($query, $company_id){
                $query->where('id','!=',$company_id);
            })
            ->first();

        return !$company;

    }


    public function message()
    {
       return 'ruc ya existe';
    }


}
