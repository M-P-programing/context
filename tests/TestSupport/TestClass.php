<?php

namespace Altra\Context\Tests\TestSupport;

use Altra\Context\Contracts\Contextable;
use Altra\Context\Traits\UseTableContext;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestClass extends Model implements Contextable, TranslatableContract
{

  use UseTableContext, HasFactory, Translatable;

  protected $guarded              = false;
  protected $translatedAttributes = ['translation_1', 'translation_2'];
  protected $table_context_fields = [
    'column_1',
    'column_2',
  ];
  protected $table_context_translatable_fields = [
    'translation_1',
    'translation_1',
  ];
  protected $table_context_relation_fields = [
    'has_one_relation',
    'has_many_relation',
  ];

  public function has_one_relation()
  {
    return $this->hasOne(HasOneRelation::class);
  }

  public function has_many_relation()
  {
    return $this->hasMany(HasManyRelation::class);
  }

  /**
   * Create a new factory instance for the model.
   *
   * @return \Illuminate\Database\Eloquent\Factories\Factory
   */
  protected static function newFactory()
  {
    return \Altra\Context\Database\Factories\TestClassFactory::new ();
  }
}
