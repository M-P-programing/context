<?php

namespace Altra\Context\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * This file is part of Altra,
 * Library that makes it possible to filter by context for HTTP request responses.
 *
 * @license MIT
 * @package Altra\Context
 */
trait QueryModifiers
{
  /**
   * We order the results by what comes in the context
   *
   * @return void
   */
  private function orderQuery(): void
  {
    $this->context->query
      ->when($this->context->sortBy != null, function ($query) {
        foreach (explode(', ', $this->context->sortBy) as $value) {
          $sortBy          = $this->context->sortBy;
          $sortDir         = $this->context->sortDir;
          $exploded        = explode(' ', $value);
          $explodedSortBy  = $exploded[0];
          $explodedSortDir = $exploded[1] ?? null;
          if ($explodedSortDir) {
            $sortBy  = $explodedSortBy;
            $sortDir = $explodedSortDir;
          }

          //Check if field is translatable or not to apply order by on those fields as well
          $query->when(in_array($sortBy, $this->context->translatableFields), function ($query) use ($sortBy, $sortDir) {
            $query->orderByTranslation($sortBy, $sortDir);
          }, function ($query) use ($sortBy, $sortDir) {
            if (in_array($sortBy, $this->context->fields)) {
              $query->orderBy($sortBy, $sortDir);

              if ($this->context->sortKeyOrder && ($sortBy == 'sort_key')) {
                $query->orderBy($this->context->sortKeyOrder);
              }
            }
          });
        }
      });
  }

  /**
   * We start building the query with the filters that como on the context
   *
   * @return void
   */
  private function addFiltersToQuery(): void
  {
    $this->context->query->when($this->context->filter->isNotEmpty(), function ($query) {
      //Iterate all possible fields besides all
      $this->applyFilters($this->context->query);

      //Generic filter all
      $this->applyGenericFilterAll($this->context->query);
    });
  }

  /**
   * We paginate the query in case needed and return the query result after all the filters
   *
   * @return mixed
   */
  private function queryResultWithPaginationConditional()
  {
    if ($this->context->customFilter == null) {
      if ($this->context->paginate) {
        if ($this->context->resource != null) {
          return $this->resourcePaginated($this->context->query->paginate($this->context->perPage), $this->context->resource);
        }
        return $this->context->query->paginate($this->context->perPage);
      }
      if ($this->context->resource != null) {
        return $this->context->resource::collection($this->context->query->get());
      }

      return $this->context->query->get();
    } else {
      return $this->context->query->get()->when(is_callable($this->context->customFilter), function ($collection) {
        $data = ($this->context->customFilter)($collection);
        if ($this->context->currentPage == -1 || !$this->context->paginate) {
          if ($this->context->resource != null) {
            return $this->context->resource::collection($data);
          }
          return $data;
        }

        $paginatedData = new LengthAwarePaginator($data->forPage($this->context->currentPage, $this->context->perPage), $data->count(), $this->context->perPage, $this->context->currentPage, []);

        if ($this->context->resource != null) {
          $paginatedData = $this->resourcePaginated($paginatedData, $this->context->resource);
        }
        return $paginatedData;
      });
    }
  }

  #########################################################################################################################
  ########################################## Own trait functions ##########################################################
  #########################################################################################################################

  /**
   * Apply custom filters
   *
   * @param Builder $query
   *
   * @return void
   */
  private function applyFilters($query)
  {
    foreach ($this->context->filter->except('all') as $key => $value) {
      //Check if field is translatable or not to apply whereTranslationLike
      $query
        ->when(in_array($key, $this->context->translatableFields) && !in_array($key, $this->context->relationFields), function ($query) use ($key, $value) {
          $query->whereTranslationLike($key, "%" . $value . "%");
        }, function ($query) use ($key, $value) {
          $key = $this->checkColumnDuplicationOnTranslation($key);
          // We filter by multiple fields from a direct relation or a field from the own entity
          $query->when(in_array($key, $this->context->multipleFields), function ($query) use ($key, $value) {
            $query->where(function ($query) use ($key, $value) {
              $fields = explode('|', $key);
              foreach ($fields as $multi_field_key => $field) {
                $exploded_field = explode('.', $field);
                if (isset($exploded_field[1])) {
                  $query->orWhereRelation($exploded_field[0], $exploded_field[1], 'LIKE', '%' . $value . '%');
                } else {
                  $query->orWhere($field, 'LIKE', '%' . $value . '%');
                }
              }
            });
          });
          //Field is not a relation
          if (in_array($key, $this->context->fields)) {
            // When is an object filters by range from -> to
            if (is_object($value)) {
              $query->whereBetween($key, [$value->from, $value->to]);
            } else {
              $this->whereOrLikeFilter($key, $value, $query);
            }
          }

          //Field is a relation
          if (in_array($key, $this->context->relationFields)) {
            $query->when(isset(explode(',', $value)[1]), function ($query) use ($key, $value) {
              // Has many relation
              foreach (explode(',', $value) as $explodedValue) {
                $query->whereHas(explode('.', $key)[0], function ($query) use ($key, $explodedValue) {
                  $query->when(in_array($key, $this->context->translatableFields), function ($q) use ($key, $explodedValue) {
                    $q->whereTranslationLike(explode('.', $key)[1], "%" . $explodedValue . "%");
                  }, function ($q) use ($key, $explodedValue) {
                    $q->where(explode('.', $key)[1], "LIKE", "%" . $explodedValue . "%");
                  });
                });
              }
            }, function ($query) use ($key, $value) {
              // Belongs to relation
              $this->deepRelationQuery($query, $key, $value, $key);
            });
          }
        });
    }
  }

  /**
   * Apply generic filter all
   *
   * @param Builder $query
   *
   * @return void
   */
  private function applyGenericFilterAll($query)
  {
    $query->when(isset($this->context->filter->toArray()['all']), function ($query) {
      $value = $this->context->filter->toArray()['all'];
      $query->where(function ($query) use ($value) {
        foreach ($this->context->fields as $field) {
          $query->orWhere($field, "LIKE", "%" . $value . "%");
        }
        foreach ($this->context->translatableFields as $field) {
          if (!in_array($field, $this->context->relationFields)) {
            $query->orWhereTranslationLike($field, "%" . $value . "%");
          }
        }
        foreach ($this->context->relationFields as $field) {
          $query->when(isset(explode(',', $value)[1]), function ($query) use ($field, $value) {
            // Has many
            foreach (explode(',', $value) as $explodedValue) {
              $query->orWhereHas(explode('.', $field)[0], function ($query) use ($field, $explodedValue) {
                $query->when(in_array($field, $this->context->translatableFields), function ($q) use ($field, $explodedValue) {
                  $q->whereTranslationLike(explode('.', $field)[1], "%" . $explodedValue . "%");
                }, function ($q) use ($field, $explodedValue) {
                  $q->where(explode('.', $field)[1], "LIKE", "%" . $explodedValue . "%");
                });
              });
            }
          }, function ($query) use ($field, $value) {
            // Belongs to
            $this->orDeepRelationQuery($query, $field, $value, $field);
          });
        }
      });
    });
  }
  /**
   * We make sure that filters created_at and updated_at are searched on main entity
   * and not on translations
   *
   * @param string $item
   *
   * @return string $item
   */
  private function checkColumnDuplicationOnTranslation(string $item)
  {
    $item_lower = strtolower($item);
    if ($item_lower == 'created_at' || $item_lower == 'updated_at') {
      return $this->context->tableName . '.' . $item;
    }

    return $item;
  }

  /**
   * Recursive relation where filter
   *
   * @param Builder $query
   * @param string $key
   * @param string $value
   * @param string $field
   *
   * @return void
   *
   */
  private function deepRelationQuery($query, $key, $value, $field)
  {
    $levels = collect(explode('.', $key));
    if ($levels->count() == 1) {
      $query->when(in_array($key, $this->context->translatableFields), function ($q) use ($levels, $value) {
        $q->whereTranslationLike($levels->first(), "%" . $value . "%");
      }, function ($q) use ($levels, $value, $field) {
        $this->whereOrLikeFilter($levels, $value, $q, $field);
      });
    } else {
      $query->whereHas($levels->first(), function ($q) use ($levels, $value, $field) {
        $levels->shift();
        $this->deepRelationQuery($q, $levels->implode('.'), $value, $field);
      });
    }
  }

  /**
   * Recursive relation OrWhere filter
   *
   * @param Builder $query
   * @param string $key
   * @param string $value
   * @param string $field
   *
   * @return void
   *
   */
  private function orDeepRelationQuery($query, $key, $value, $field)
  {
    $levels = collect(explode('.', $key));

    if ($levels->count() == 1) {
      $query->when(in_array($key, $this->translatableFields), function ($q) use ($levels, $value) {
        $q->whereTranslationLike($levels->first(), "%" . $value . "%");
      }, function ($q) use ($levels, $value, $field) {
        $this->whereOrLikeFilter($levels, $value, $q, $field);
      });
    } else {
      $query->orWhereHas($levels->first(), function ($q) use ($levels, $value, $field) {
        $levels->shift();
        $this->deepRelationQuery($q, $levels->implode('.'), $value, $field);
      });
    }
  }

  /**
   * Match exact filter value if necessary
   *
   * @param string $key
   * @param string $value
   * @param Builder $query
   * @param string|null $field
   *
   * @return Builder
   */
  private function whereOrLikeFilter($key, $value, $query, $field = null)
  {
    $field = $field == null ? $key : $field;
    if ($key instanceof Collection) {
      return in_array($field, $this->context->exactFields) ? $query->where($key->first(), $value) : $query->where($key->first(), "LIKE", "%" . $value . "%");
    }

    return in_array($field, $this->context->exactFields) ? $query->where($key, $value) : $query->where($key, "LIKE", "%" . $value . "%");
  }

  /**
   * Use Resource class for request response and customize path if needed
   *
   * @param Builder $queryResult
   * @param string $class
   *
   * @return mixed $queryResult
   */
  public static function resourcePaginated($queryResult, string $class)
  {
    if ($queryResult instanceof Collection) {

      $queryResult->transform(function ($quote) use ($class) {
        return new $class($quote);
      });

      return $queryResult;
    }

    // Generate custom path for pagination
    $uri          = request()->path();
    $gateway_path = str_replace(['api/public/v', 'api/v'], [env('MS_NAME') . '.pv', env('MS_NAME') . '.v'], $uri);
    $path         = rtrim(env('GATEWAY_URL', ''), '/') . '/' . $gateway_path;
    $queryResult->withPath($path);

    $queryResult->getCollection()
      ->transform(function ($quote) use ($class) {
        return new $class($quote);
      });

    return $queryResult;
  }
}
