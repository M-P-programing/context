<?php

namespace Altra\Context\Actions;

use Altra\Context\PendingTableContext;
use Altra\Context\Traits\QueryModifiers;

/**
 * This file is part of Altra,
 * Library that makes it possible to filter by context for HTTP request responses.
 *
 * @license MIT
 * @package Altra\Context
 */
class SearchByTableContext
{

  use QueryModifiers;

  /** @var PendingTableContext $context */
  protected $context;

  /**
   * Executes the query from the table context
   *
   * @param \Altra\Context\PendingTableContext $context
   *
   * @return
   */
  public function execute(PendingTableContext $context)
  {
    $this->context = $context;
    //For pagination to work we merge the context current page
    request()->merge(['page' => $context->currentPage]);

    $this->context->query->with($this->context->with);
    $this->orderQuery();
    $this->addFiltersToQuery();
    return $this->queryResultWithPaginationConditional();

  }
}
