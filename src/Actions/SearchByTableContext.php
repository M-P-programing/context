<?php

namespace Context\Actions;

use Context\PendingTableContext;
use Context\Traits\QueryModifiers;

/**
 * This file is part of MP Programming
 * Library that makes it possible to filter by context for HTTP request responses.
 *
 * @license MIT
 */
class SearchByTableContext
{
    use QueryModifiers;

    /** @var PendingTableContext */
    protected $context;

    /**
     * Executes the query from the table context
     *
     * @param  \Context\PendingTableContext  $context
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
