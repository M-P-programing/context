<?php

namespace Altra\Context\Contracts;

/**
 * This file is part of Altra,
 * Library that makes it possible to filter by context for HTTP request responses.
 *
 * @license MIT
 */
interface Contextable
{
    /**
     * Get fields from model that we want to be available to work with context
     *
     * @return array
     */
    public static function getTableContextFields(): array;

    /**
     * Get translatable fields from model that we want to be available to work with context
     *
     * @return array
     */
    public static function getTableContextTranslatableFields(): array;

    /**
     * Get multiple fields from model that we want to be available to work with context
     *
     * @return array
     */
    public static function getTableContextMultipleFields(): array;

    /**
     * Get relations from model that we want to be available to work with context
     *
     * @return array
     */
    public static function getTableContextRelationFields(): array;

    /**
     * Get custom sort key from model
     *
     * @return string
     */
    public static function getTableContextSortKeyOrder(): string|null;

    /**
     * Get fields to search exact match
     *
     * @return array
     */
    public static function getTableContextExactFields(): array;
}
