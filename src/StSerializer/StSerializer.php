<?php

namespace App\StSerializer;

use Doctrine\ORM\Mapping\MappingAttribute;
use Doctrine\ORM\Mapping\OneToMany;
use FriendlyPixel\Ar\Ar;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer as Symf;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * A thin helper class for (un)serializing json. Uses symfony serializer for simple/primitive values
 * but we handle relations (manyToMany etc) ourselves.
 */
class StSerializer
{
    public function __construct(
        private Symf\SerializerInterface $symfonySerializer,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    public function normalize($object, $relations = [], $groups = ['default'], $attributes = null)
    {
        /** @var Symf\Serializer */
        $symfonySerializer = $this->symfonySerializer;

        // dump($object, $relations);

        $json = $symfonySerializer->normalize(
            $object,
            'json',
            $this->symfSettings($groups, $attributes),
        );

        if ($relations) {
            foreach ($relations as $key => $value) {
                if (is_string($value)) {
                    $key = $value;
                }

                $children = $this->propertyAccessor->getValue($object, $key);
                $normalize = fn($child) => $this->normalize(
                    object: $child,
                    relations: $relations[$key] ?? null,
                    groups: $groups,
                    attributes: $attributes[$key] ?? null,
                );

                if (is_iterable($children)) {
                    $json[$key] = Ar::map($children, $normalize);
                } elseif ($children) {
                    $json[$key] = $normalize($children);
                } else {
                    throw new \Exception(
                        "Invalid relation type for $key: found " . gettype($children),
                    );
                }
            }
        }

        return $json;
    }

    private function symfSettings($groups = null, $attributes = null)
    {
        return [
            ObjectNormalizer::GROUPS => $groups,
            ObjectNormalizer::ATTRIBUTES => $attributes,
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
        ];
    }

    /**
     * @param Rel[] $relations
     */
    public function patchDeep(
        $object,
        $data,
        $relations = [],
        $groups = ['default'],
        $attributes = null,
    ) {
        /** @var Symf\Serializer */
        $symfonySerializer = $this->symfonySerializer;

        $symfonySerializer->denormalize($data, $object::class, 'json', [
            ObjectNormalizer::GROUPS => $groups,
            // ObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
            ObjectNormalizer::OBJECT_TO_POPULATE => $object,
            ObjectNormalizer::ATTRIBUTES => $attributes,
        ]);

        if ($relations) {
            foreach ($relations as $relation) {
                $children = $this->propertyAccessor->getValue($object, $relation->name);
                if (!$children) {
                    throw new \Exception("Relation $relation->name not found on object");
                }

                $patchDeep = fn($child, $data) => $this->patchDeep(
                    object: $child,
                    data: $data,
                    relations: $relation->children ?? null,
                    groups: $groups,
                    attributes: $attributes[$relation->name] ?? null,
                );

                if (is_iterable($children)) {
                    // dump($data, $relation->name, $children);

                    foreach ($data[$relation->name] as $childData) {
                        if ($childData['id'] ?? null) {
                            // Find by id, update

                            $child = Ar::first(
                                $children,
                                fn($child) => $child->getId() === $childData['id'],
                            );

                            if ($child) {
                                $patchDeep($child, $childData);
                            } else {
                                throw new \Exception(
                                    "Child not found in $relation->name: " . $childData['id'],
                                );
                            }
                        } else {
                            // Create new
                            if ($relation->createNew) {
                                // Find class via introspection of doctrine OneToMany etc attributes
                                $reflClass = new \ReflectionClass($object);
                                $property = $reflClass->getProperty($relation->name);
                                $attributes = $property->getAttributes(OneToMany::class);
                                if (!$attributes) {
                                    throw new \Exception(
                                        'No Mapping attribute found on ' .
                                            $object::class .
                                            '::' .
                                            $relation->name,
                                    );
                                }
                                $targetEntity = $attributes[0]->getArguments()['targetEntity'];

                                $child = new $targetEntity();
                                $patchDeep($child, $childData);

                                $adder = 'add' . ucfirst(rtrim($relation->name, 's'));
                                $object->$adder($child);
                            } else {
                                throw new \Exception(
                                    "Found new entity without id on $relation->name, but Rel::createNew is false",
                                );
                            }
                        }
                    }

                    if ($relation->orphanRemoval) {
                        // Remove any entities missing from the data array
                        $missing = Ar::filter(
                            $children,
                            fn($child) => !Ar::search(
                                $data[$relation->name],
                                fn($c) => ($c['id'] ?? null) === $child->getId(),
                            ),
                        );

                        foreach ($missing as $child) {
                            $remover = 'remove' . ucfirst(rtrim($relation->name, 's'));
                            $object->$remover($child);
                        }
                    }
                } else {
                    // Single entity
                    if ($children->getId() === $data[$relation->name]['id']) {
                        $patchDeep($children, $data[$relation->name]);
                    } else {
                        throw new \Exception(
                            "Entity id mismatch for $relation->name: " .
                                $data[$relation->name]['id'],
                        );
                    }
                }
            }
        }
    }
}
