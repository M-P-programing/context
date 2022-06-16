<?php

namespace Altra\Context\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;

class HasOneRelation extends Model
{
  protected $guarded = false;
  protected $table = 'test_class_has_one_relation';
}
