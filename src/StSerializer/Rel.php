<?php

namespace App\StSerializer;

/**
 * Relation for StSerializer::patchDeep
 */
class Rel
{
    /**
     * @param Rel[] $children
     */
    public function __construct(
        public readonly string $name,
        public readonly array|null $children = null,
        /**
         * Remove any items not present in the data array.
         */
        public readonly bool $orphanRemoval = false,
        /**
         * Create new entities for items without id in the data array.
         */
        public readonly bool $createNew = false,
    ) {
    }
}
