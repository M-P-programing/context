<?php

use Altra\Context\Tests\TestCase;
use Altra\Context\Tests\TestSupport\TestClass;

class ContextTest extends TestCase
{
  public function setUp(): void
  {
    parent::setUp();

    $this->generic_context = json_decode('{"filter":{"all":""},"sortBy":"","sortDesc":false,"perPage":10,"currentPage":1}');
    $this->testClasses     = TestClass::factory()->count(5)->create();
  }

  public function test_query_result_without_context()
  {

    $testClass = TestClass::tableContext()->get();
    $this->assertInstanceOf(TestClass::class, $testClass->first());
  }

  public function test_own_entity_column_filter_matches()
  {
    $context   = json_decode('{"filter":{"column_1":"' . $this->testClasses->first()->column_1 . '"},"sortBy":"","sortDesc":true,"perPage":10,"currentPage":1}');
    $testClass = TestClass::tableContext()
      ->setContext($context)
      ->get();
    $this->assertContains($this->testClasses->first()->column_1, $testClass->pluck('column_1')->toArray());
  }

  public function test_query_result_comes_empty_when_no_match_found()
  {
    $context   = json_decode('{"filter":{"column_1":"text that I know it is not going to exist in any test class name"},"sortBy":"","sortDesc":true,"perPage":10,"currentPage":1}');
    $testClass = TestClass::tableContext()
      ->setContext($context)
      ->doNotPaginate()
      ->get();

    $this->assertEmpty($testClass->toArray());

  }

  // public function test_sort_by_method()
  // {
  //   $testClass = TestClass::tableContext()
  //   ->setContext($this->generic_context)
  //   ->doNotPaginate()
  //   ->sortBy('column_1')
  //   ->get();

  //   dd($testClass);


  // }

}
