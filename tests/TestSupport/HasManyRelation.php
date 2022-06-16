<?php

namespace Altra\Context\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;

class HasManyRelation extends Model
{
  protected $guarded = false;
  protected $table   = 'test_class_has_many_relations';
}
