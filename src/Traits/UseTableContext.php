<?php

namespace Altra\Context\Traits;

use Altra\Context\PendingTableContext;

/**
 * This file is part of Altra,
 * Library that makes it possible to filter by context for HTTP request responses.
 *
 * @license MIT
 * @package Altra\Context
 */
trait UseTableContext
{
  /**
   *
   * @return \Altra\Context\PendingTableContext
   */
  public static function tableContext(): PendingTableContext
  {
    return new PendingTableContext(static::class);
  }
  public static function getTableContextFields(): array
  {
    $self   = new static;
    $fields = $self->table_context_fields ?? [];
    return array_values(collect($fields)->filter(function ($item) {
      $exploded    = explode('.', $item);
      $check_multi = explode('|', $item);
      if (isset($exploded[1]) || isset($check_multi[1])) {
        return false;
      }

      return true;
    })->toArray());

  }

  public static function getTableContextTranslatableFields(): array
  {
    $self = new static;
    return $self->table_context_translatable_fields ?? [];
  }

  public static function getTableContextMultipleFields(): array
  {
    $self   = new static;
    $fields = $self->table_context_fields ?? [];
    return array_values(collect($fields)->filter(function ($item) {
      $exploded = explode('|', $item);
      if (isset($exploded[1])) {
        return true;
      }
      return false;
    })->toArray());
  }

  public static function getTableContextRelationFields(): array
  {
    $self   = new static;
    $fields = $self->table_context_fields ?? [];
    return array_values(collect($fields)->filter(function ($item) {
      $check_multi = explode('|', $item);
      if (!isset($check_multi[1])) {
        $exploded = explode('.', $item);
        if (isset($exploded[1])) {
          return true;
        }
      }
      return false;
    })->toArray());
  }

  public static function getTableContextSortKeyOrder(): string|null
  {
    $self = new static;
    return $self->table_context_sort_key_order ?? null;
  }

  public static function getTableContextExactFields(): array
  {
    $self = new static;
    return $self->table_context_exact_fields ?? [];
  }
}
