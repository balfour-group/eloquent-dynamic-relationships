<?php

namespace Balfour\EloquentDynamicRelationships;

use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    use HasDynamicRelationships;
}
