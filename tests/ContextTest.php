<?php

use Altra\Context\Tests\TestCase;
use Altra\Context\Tests\TestSupport\TestClass;
use Altra\Context\Tests\TestSupport\Resources\TestClassResource;
use Illuminate\Database\Eloquent\Collection;

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

  public function test_response_not_paginated()
  {
    $testClass = TestClass::tableContext()
      ->doNotPaginate()
      ->get();
    $this->assertInstanceOf(Collection::class, $testClass);
    $this->assertNotInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $testClass);
  }

  public function test_set_initial_query()
  {
    $initialQuery = TestClass::whereIn('id', [1, 2]);
    $testClass    = TestClass::tableContext()
      ->doNotPaginate()
      ->setInitialQuery($initialQuery)
      ->get();

    $this->assertCount(2, $testClass->toArray());
  }

  public function test_results_per_page()
  {
    $testClass = TestClass::tableContext()
      ->perPage(2)
      ->get();

    $this->assertLessThanOrEqual(2, $testClass->count());
  }

  public function test_current_page_definition()
  {
    $testClass = TestClass::tableContext()
      ->perPage(2)
      ->currentPage(2)
      ->get();
    $this->assertContains(3, $testClass->pluck('id')->toArray());
    $this->assertContains(4, $testClass->pluck('id')->toArray());

  }

  public function test_response_transformed_by_resource()
  {
    $testClass = TestClass::tableContext()
    ->withResource(TestClassResource::class)
    ->get();

    $this->assertInstanceOf(TestClassResource::class, $testClass->first());

  }

  // public function test_custom_filter_after_query()
  // {
  //   $testClass = TestClass::tableContext()
  //     ->doNotPaginate()
  //     ->get();

  //   $prueba = $testClass->withCustomFilter(function($test){
  //       dd($test);
  //   });

  //   dd($prueba);
  // }

  // public function test_sort_by_method()
  // {
  //   $testClass = TestClass::tableContext()
  //   ->setContext($this->generic_context)
  //   ->doNotPaginate()
  //   ->sortBy('-column_1')
  //   ->sortDescending(true)
  //   ->get();

  //  $laravelOrderBy = TestClass::orderBy('column_1')->pluck('column_1')->toArray();
  //  dd($laravelOrderBy,$testClass->pluck('column_1'));
  //  $this->assertEquals($laravelOrderBy,$testClass->toArray());

  // }

}
