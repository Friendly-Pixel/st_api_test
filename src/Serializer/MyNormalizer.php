<?php
namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;

class MyNormalizer extends ObjectNormalizer
{
    /**
     * Copied from ObjectNormalizer. Only changed `parent::getAllowedAttributes` to `self::_getAllowedAttributes`.
     */
    protected function getAllowedAttributes(
        string|object $classOrObject,
        array $context,
        bool $attributesAsString = false,
    ): array|bool {
        if (
            false ===
            ($allowedAttributes = self::_getAllowedAttributes(
                $classOrObject,
                $context,
                $attributesAsString,
            ))
        ) {
            return false;
        }

        if (null !== $this->classDiscriminatorResolver) {
            $class = \is_object($classOrObject) ? $classOrObject::class : $classOrObject;
            if (
                null !==
                ($discriminatorMapping = $this->classDiscriminatorResolver->getMappingForMappedObject(
                    $classOrObject,
                ))
            ) {
                $allowedAttributes[] = $attributesAsString
                    ? $discriminatorMapping->getTypeProperty()
                    : new AttributeMetadata($discriminatorMapping->getTypeProperty());
            }

            if (
                null !==
                ($discriminatorMapping = $this->classDiscriminatorResolver->getMappingForClass(
                    $class,
                ))
            ) {
                $attributes = [];
                foreach ($discriminatorMapping->getTypesMapping() as $mappedClass) {
                    $attributes[] = self::_getAllowedAttributes(
                        $mappedClass,
                        $context,
                        $attributesAsString,
                    );
                }
                $allowedAttributes = array_merge($allowedAttributes, ...$attributes);
            }
        }

        return $allowedAttributes;
    }

    /**
     * Simon: Changed the allowed attributes to return ($inGroup OR $inAttributes) instead of AND as
     * is the default. Copied and changed from vendor/symfony/serializer/Normalizer/AbstractNormalizer.php
     *
     * @param bool $attributesAsString If false, return an array of {@link AttributeMetadataInterface}
     * @return string[]|AttributeMetadataInterface[]|bool
     * @throws LogicException if the 'allow_extra_attributes' context variable is false and no class metadata factory is provided
     */
    protected function _getAllowedAttributes(
        string|object $classOrObject,
        array $context,
        bool $attributesAsString = false,
    ): array|bool {
        $allowExtraAttributes =
            $context[self::ALLOW_EXTRA_ATTRIBUTES] ??
            $this->defaultContext[self::ALLOW_EXTRA_ATTRIBUTES];
        if (!$this->classMetadataFactory) {
            if (!$allowExtraAttributes) {
                throw new LogicException(
                    sprintf(
                        'A class metadata factory must be provided in the constructor when setting "%s" to false.',
                        self::ALLOW_EXTRA_ATTRIBUTES,
                    ),
                );
            }

            return false;
        }

        $groups = $this->getGroups($context);

        $allowedAttributes = [];
        foreach (
            $this->classMetadataFactory->getMetadataFor($classOrObject)->getAttributesMetadata()
            as $attributeMetadata
        ) {
            $ignore = $attributeMetadata->isIgnored();

            // If you update this check, update accordingly the one in Symfony\Component\PropertyInfo\Extractor\SerializerExtractor::getProperties()

            $name = $attributeMetadata->getName();

            $inGroup =
                [] === $groups ||
                array_intersect(array_merge($attributeMetadata->getGroups(), ['*']), $groups);

            $ignoredAttributes =
                $context[self::IGNORED_ATTRIBUTES] ??
                ($this->defaultContext[self::IGNORED_ATTRIBUTES] ?? []);
            $isIgnored = \in_array($name, $ignoredAttributes);

            $attributes =
                $context[self::ATTRIBUTES] ?? ($this->defaultContext[self::ATTRIBUTES] ?? []);
            $inAttributes = in_array($name, $attributes) || isset($attributes[$name]);

            // dump($classOrObject, $attributeMetadata->getName(), $context);
            // dump("inGroup: $inGroup, inAttributes: $inAttributes, allowed: $allowed");
            if (!$ignore && !$isIgnored && ($inGroup || $inAttributes)) {
                $allowedAttributes[] = $attributesAsString ? $name : $attributeMetadata;
            }
        }

        return $allowedAttributes;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        dump($data, $type, $format, $context);
        return parent::denormalize($data, $type, $format, $context);
    }
}
