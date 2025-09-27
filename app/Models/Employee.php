<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $connection = 'employee_db';

    protected $table = 'vEmployee';

    protected $primaryKey = 'emp_no';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'emp_no',
        'Fullname',
        'PosDesc',
        'DeptDesc',
        'DivDesc',
    ];
}
