<?php

use Context\Tests\TestCase;
use Context\Tests\TestSupport\HasOneRelation;
use Context\Tests\TestSupport\Resources\TestClassResource;
use Context\Tests\TestSupport\TestClass;
use Illuminate\Database\Eloquent\Collection;

class ContextTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->generic_context = json_decode('{"filter":{"all":""},"sortBy":"","sortDesc":false,"perPage":10,"currentPage":1}');
        $this->testClasses = TestClass::factory()->count(5)->create();
        $this->one_relation = HasOneRelation::create([
            'test_class_id' => $this->testClasses->first()->id,
            'column_1' => 'Prueba',
            'column_2' => 'Relación',
        ]);
    }

    public function test_query_result_without_context()
    {
        $testClass = TestClass::tableContext()->get();
        $this->assertInstanceOf(TestClass::class, $testClass->first());
        $this->assertEquals(TestClass::count(), $testClass->count());
    }

    public function test_own_entity_column_filter_matches()
    {
        $context = json_decode('{"filter":{"column_1":"'.$this->testClasses->first()->column_1.'"},"sortBy":"","sortDesc":true,"perPage":10,"currentPage":1}');
        $testClass = TestClass::tableContext()
      ->setContext($context)
      ->get();
        $this->assertContains($this->testClasses->first()->column_1, $testClass->pluck('column_1')->toArray());
    }

    public function test_can_get_context_from_request()
    {
        request()->merge(['context' => '{"filter":{"column_1":"'.$this->testClasses->first()->column_1.'"},"sortBy":"","sortDesc":true,"perPage":10,"currentPage":1}']);
        $testClass = TestClass::tableContext()
      ->setContextFromRequest()
      ->get();
        $this->assertContains($this->testClasses->first()->column_1, $testClass->pluck('column_1')->toArray());
    }

    public function test_can_get_context_from_request_with_custom_key()
    {
        request()->merge(['tableContext' => '{"filter":{"column_1":"'.$this->testClasses->first()->column_1.'"},"sortBy":"","sortDesc":true,"perPage":10,"currentPage":1}']);
        $testClass = TestClass::tableContext()
      ->setContextFromRequest('tableContext')
      ->get();
        $this->assertContains($this->testClasses->first()->column_1, $testClass->pluck('column_1')->toArray());
    }

    public function test_response_when_context_is_missing_on_request()
    {
        $testClass = TestClass::tableContext()->setContextFromRequest()->get();
        $this->assertInstanceOf(TestClass::class, $testClass->first());
        $this->assertEquals(TestClass::count(), $testClass->count());
    }

    public function test_query_result_comes_empty_when_no_match_found()
    {
        $context = json_decode('{"filter":{"column_1":"text that I know it is not going to exist in any test class name"},"sortBy":"","sortDesc":true,"perPage":10,"currentPage":1}');
        $testClass = TestClass::tableContext()
      ->setContext($context)
      ->doNotPaginate()
      ->get();

        $this->assertEmpty($testClass['data']->toArray());
    }

    public function test_response_not_paginated()
    {
        $testClass = TestClass::tableContext()
      ->doNotPaginate()
      ->get();
        $this->assertInstanceOf(Collection::class, $testClass['data']);
        $this->assertNotInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $testClass);
    }

    public function test_set_initial_query()
    {
        $initialQuery = TestClass::whereIn('id', [1, 2]);
        $testClass = TestClass::tableContext()
      ->doNotPaginate()
      ->setInitialQuery($initialQuery)
      ->get();

        $this->assertCount(2, $testClass['data']);
    }

    public function test_results_per_page()
    {
        $testClass = TestClass::tableContext()
      ->perPage(2)
      ->get();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $testClass);
        $this->assertEquals(2, $testClass->perPage());
        $this->assertEquals(TestClass::count(), $testClass->total());
    }

    public function test_current_page_definition()
    {
        $testClass = TestClass::tableContext()
      ->currentPage(2)
      ->get();

        $this->assertEquals(2, $testClass->currentPage());
        $this->assertEquals(TestClass::count(), $testClass->total());
    }

    public function test_response_transformed_by_resource()
    {
        $testClass = TestClass::tableContext()
      ->withResource(TestClassResource::class)
      ->get();

        $this->assertInstanceOf(TestClassResource::class, $testClass->first());
    }

    public function test_response_transformed_by_resource_with_context_set_from_request()
    {
        $testClass = TestClass::tableContext()
      ->setContextFromRequest()
      ->withResource(TestClassResource::class)
      ->get();

        $this->assertInstanceOf(TestClassResource::class, $testClass->first());
    }

    public function test_relations_on_response()
    {
        $testClass = TestClass::tableContext()
      ->includeRelations(['has_one_relation'])
      ->get();
        $this->assertTrue($testClass->first()->relationLoaded('has_one_relation'));
    }

    public function test_custom_filter_after_query()
    {
        $testClass = TestClass::tableContext()
      ->doNotPaginate()
      ->withCustomFilter(function ($collection) {
          return $collection->where('id', 1);
      })
      ->get();

        $this->assertEquals(1, $testClass['data']->first()->id);
        $this->assertCount(1, $testClass);
    }

    public function test_sort_by_method()
    {
        $testClass = TestClass::tableContext()
      ->setContext($this->generic_context)
      ->doNotPaginate()
      ->sortBy('column_1')
      ->sortDescending(true)
      ->get();

        $laravelOrderBy = TestClass::orderByDesc('column_1')->pluck('column_1')->toArray();
        $this->assertEquals($laravelOrderBy, $testClass['data']->pluck('column_1')->toArray());
    }

    public function test_when_conditional_false()
    {
        $context = [
            'resource' => null,
        ];
        $testClass = TestClass::tableContext()
      ->doNotPaginate()
      ->when($context['resource'] != null, function ($pendingContext) use ($context) {
          return $pendingContext->withResource($context['resource']);
      })
      ->get();

        $this->assertInstanceOf(TestClass::class, $testClass['data']->first());
    }

    public function test_when_conditional_true()
    {
        $context = [
            'resource' => TestClassResource::class,
        ];
        $testClass = TestClass::tableContext()
      ->doNotPaginate()
      ->when($context['resource'] != null, function ($pendingContext) use ($context) {
          return $pendingContext->withResource($context['resource']);
      })
      ->get();

        $this->assertInstanceOf(TestClassResource::class, $testClass['data']->first());
    }

    public function test_custom_filter_without_pagination_and_without_resource()
    {
        $testClass = TestClass::tableContext()
      ->doNotPaginate()
      ->withCustomFilter(function ($collection) {
          return $collection->where('id', 1);
      })
      ->get();

        $this->assertCount(1, $testClass['data']);
    }

    public function test_custom_filter_without_pagination_and_with_resource()
    {
        $testClass = TestClass::tableContext()
      ->doNotPaginate()
      ->withResource(TestClassResource::class)
      ->withCustomFilter(function ($collection) {
          return $collection->where('id', 1);
      })
      ->get();

        $this->assertInstanceOf(TestClassResource::class, $testClass['data']->first());
        $this->assertCount(1, $testClass['data']);
    }

    public function test_add_filters()
    {
        $pendingContext = TestClass::tableContext()
          ->addFilter('column_1', $this->testClasses->first()->column_1);
        $this->assertEquals(['all' => '', 'column_1' => $this->testClasses->first()->column_1], $pendingContext->filter->toArray());

        $testClass = $pendingContext->get();
        $this->assertContains($this->testClasses->first()->column_1, $testClass->pluck('column_1')->toArray());
    }
}
