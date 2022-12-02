<?php

namespace Context\Contracts;

use Context\PendingTableContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * This file is part of MP Programming,
 * Library that makes it possible to filter by context for HTTP request responses.
 *
 * @license MIT
 */
interface PendingTableContextContract
{
    /* Set initial context */
    public function setContext(mixed $context): PendingTableContext;

    /* Set initial context from request*/
    public function setContextFromRequest(string $key): PendingTableContext;

    /* Set initial query */
    public function setInitialQuery(Builder $query): PendingTableContext;

    /* Set sort direction for query */
    public function sortDescending(bool $descending): PendingTableContext;

    /* Sort By field */
    public function sortBy(string $sortBy): PendingTableContext;

    /* Set filters */
    public function withFilters($filters): PendingTableContext;

    /* Add filter */
    public function addFilter(string $key, mixed $value): PendingTableContext;

    /* Set items per page */
    public function perPage($perPage): PendingTableContext;

    /* Set current page */
    public function currentPage($currentPage): PendingTableContext;

    /* Do not paginate  */
    public function doNotPaginate(): PendingTableContext;

    /* Transform Filter as needed  */
    public function withCustomFilter($callback): PendingTableContext;

    /* Apply conditional to query */
    public function when($condition, $callback, $else): PendingTableContext;

    /* Return result with resource structure chosen */
    public function withResource(string $resource): PendingTableContext;

    /* Add relations to entity response */
    public function includeRelations(array | string $with): PendingTableContext;

    /* Execute action to return query result */
    public function get();
}
