<?php

namespace Altra\Context\Database\Factories;

use Altra\Context\Tests\TestSupport\TestClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestClassFactory extends Factory
{
    protected $model = TestClass::class;

    public function definition()
    {
        return [
            'column_1' => $this->faker->name(),
            'column_2' => $this->faker->text(),
        ];
    }
}
