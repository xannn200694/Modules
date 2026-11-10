<?php

namespace DummyNamespace\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class DummyBasenameModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'DummySnakePlural';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
    ];
}
