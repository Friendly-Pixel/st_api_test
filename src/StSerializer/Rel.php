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
         * Remove any items not present in the data array from the parent relation.
         * (i.e. `$parent->removeTag($child)`)
         */
        public readonly bool $orphanRemoval = false,

        /**
         * Delete any items not present in the data.
         * (i.e. `$em->remove($child)`)
         */
        public readonly bool $orphanDelete = false,

        /**
         * Create new entities for items without id in the data array.
         */
        public readonly bool $createNew = false,
    ) {
    }

    public function shouldHandleOrphans(): bool
    {
        return $this->orphanRemoval || $this->orphanDelete;
    }
}
