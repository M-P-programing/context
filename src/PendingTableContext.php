<?php

namespace Altra\Context;

use Altra\Context\Actions\SearchByTableContext;
use Altra\Context\Contracts\PendingTableContextContract;
use Illuminate\Database\Eloquent\Builder;

/**
 * This file is part of Altra,
 * Library that makes it possible to filter by context for HTTP request responses.
 *
 * @license MIT
 * @package Altra\Context
 */
class PendingTableContext implements PendingTableContextContract
{
  /** @var string $class */
  public $class;

  /** @var string $sortDir */
  public $sortDir = 'desc';

  /** @var \Illuminate\Support\Collection $filter*/
  public $filter;

  /** @var string $sortBy */
  public $sortBy;

  /** @var int $perPage */
  public $perPage = 15;

  /** @var int $perPage */
  public $currentPage = 1;

  /** @var bool $paginate */
  public $paginate = true;

  /** @var Builder $query */
  public $query;

  /** @var string $tableName */
  public $tableName;

  /** @var array $fields */
  public $fields;

  /** @var array $translatableFields */
  public $translatableFields;

  /** @var array $relationFields */
  public $relationFields;

  /** @var array $multipleFields */
  public $multipleFields;

  /** @var string $sortKeyOrder */
  public $sortKeyOrder;

  /** @var array $exactFields */
  public $exactFields;

  /** @var callable|null $customFilter */
  public $customFilter;

  /** @var string $resource */
  public $resource;

  /** @var array $with */
  public $with = [];

  public function __construct(string $class)
  {

    $this->class              = $class;
    $this->filter             = collect(["all" => ""]);
    $this->tableName          = (new $class)->getTable();
    $this->query              = $class::query();
    $this->fields             = $class::getTableContextFields();
    $this->translatableFields = $class::getTableContextTranslatableFields();
    $this->relationFields     = $class::getTableContextRelationFields();
    $this->multipleFields     = $class::getTableContextMultipleFields();
    $this->sortKeyOrder       = $class::getTableContextSortKeyOrder();
    $this->exactFields        = $class::getTableContextExactFields();
    $this->loadFields();

  }

  public function setContext(mixed $context): PendingTableContext
  {
    return $this
      ->sortDescending($context?->sortDesc)
      ->sortBy($context?->sortBy)
      ->withFilters($context?->filter)
      ->perPage($context?->perPage)
      ->currentPage($context?->currentPage)
    ;
  }

  public function setContextFromRequest(string $key = 'context'): PendingTableContext
  {
    if (request()->filled($key)) {
      $context = json_decode(request($key));
      $this->setContext($context);
    }

    return $this;
  }

  public function setInitialQuery(Builder $query): PendingTableContext
  {
    $this->query = $query;
    return $this;
  }

  public function sortDescending(bool $descending): PendingTableContext
  {
    $this->sortDir = $descending ? 'desc' : 'asc';
    return $this;
  }

  public function sortBy(string $sortBy): PendingTableContext
  {
    $this->sortBy = $sortBy;

    if (strpos($this->sortBy, ',') !== false) {
      $explodedSortBy = explode(',', $this->sortBy);
      foreach ($explodedSortBy as $key => $value) {
        $field = $value;
        if ($field == 'created_at' || $field == 'updated_at') {
          $field = $this->tableName . '.' . $field;
        }
        if (strpos($field, '-') !== false) {
          $explodedSortBy[$key] = ltrim($field, '-') . " ASC";
        } else {
          $explodedSortBy[$key] = $field . " DESC";
        }
      }

      $this->sortBy = collect($explodedSortBy)->join(', ');
    }
    return $this;
  }

  public function withFilters($filters): PendingTableContext
  {
    $this->filter = collect($filters)->filter(function ($item) {
      return $item !== "" && $item !== null;
    });

    if ($this->filter->isEmpty()) {
      $this->filter = collect(["all" => ""]);
    }

    return $this;
  }

  public function perPage($perPage): PendingTableContext
  {
    $this->perPage = $perPage;
    return $this;
  }

  public function currentPage($currentPage): PendingTableContext
  {
    $this->currentPage = $currentPage;
    $this->paginate    = ($this->currentPage == -1) ? false : $this->paginate;
    return $this;
  }

  public function doNotPaginate(): PendingTableContext
  {
    $this->paginate = false;
    return $this;
  }

  /**
   * Force search method to use custom filter (after Eloquent is done) before paginate
   * $callback param receives the eloquent collection as param
   *
   * @param $callback
   *
   * @return PendingTableContext
   */
  public function withCustomFilter($callback): PendingTableContext
  {
    $this->customFilter = $callback;

    return $this;
  }

  /**
   * Execute function conditionally
   *
   * @param bool $condition
   * @param callable $callback
   * @param callable|null $else
   *
   * @return PendingTableContext
   */
  public function when($condition, $callback, $else = null): PendingTableContext
  {
    if ($condition) {
      return $callback($this);
    } elseif (is_callable($else)) {
      return $else($this);
    } else {
      return $this;
    }
  }

  public function withResource(string $resource): PendingTableContext
  {

    $this->resource = $resource;

    return $this;
  }

  public function includeRelations(array | string $with): PendingTableContext
  {
    $this->with = $with;

    return $this;
  }

  private function loadFields()
  {
    foreach ($this->fields as $key => $value) {
      if ($value == 'created_at' || $value == 'updated_at') {
        $this->fields[$key] = $this->tableName . '.' . $value;
      }
    }
  }

  public function get()
  {
    return (new SearchByTableContext())->execute($this);
  }

}
