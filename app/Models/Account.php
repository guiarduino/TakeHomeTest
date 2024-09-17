<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Account extends Model
{

    // Construtor da classe
    public function __construct($id, $balance)
    {
        $this->id = $id;
        $this->balance = $balance;
    }
    
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'id' => 0,
        'balance' => 0,
    ];
}