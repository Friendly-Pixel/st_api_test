<?php

namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ArrayByIdDenormalizer extends MyNormalizer
{
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        // dump($data, $type, $format);
        return false;

        // Check if the type expects an array with IDs
        return $type === 'array' && isset($data['id']);
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        dd($data, $type, $format, $context);

        // Assuming $data['id'] exists, search array elements by ID
        $items = $context['items'] ?? [];

        foreach ($items as $item) {
            if ($item['id'] === $data['id']) {
                return $item; // Return the found item by ID
            }
        }

        throw new \Exception("Item with ID {$data['id']} not found");
    }
}
