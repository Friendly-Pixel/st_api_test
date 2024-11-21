<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Serializer\ArrayByIdDenormalizer;
use App\Serializer\MyNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Debug\TraceableSerializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/post/{id}', name: 'api_post_show', methods: ['GET'])]
    public function show(Post $post, PostRepository $postRepository): Response
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $serializer = new Serializer(
            [new MyNormalizer($classMetadataFactory), new DateTimeNormalizer()],
            [new JsonEncoder()],
        );

        // // dd($serializer);
        // if (!$serializer instanceof TraceableSerializer) {
        //     throw new \Exception('Serializer is not an instance of Serializer');
        // }

        $o = new ObjectNormalizer();
        // $o->normalize();

        $json = $serializer->normalize($post, 'json', [
            ObjectNormalizer::GROUPS => ['default'],
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
            // ObjectNormalizer::
            // ObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES => true,
            ObjectNormalizer::ATTRIBUTES => [
                'tags' => ['createdBy'],
            ],
        ]);

        dd($json);

        return JsonResponse::fromJsonString($json);
    }

    #[Route('/post/{id}/update', name: 'api_post_update', methods: ['GET'])]
    public function update(
        Post $post,
        Request $request,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager,
    ) {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $serializer = new Serializer(
            [
                // new ArrayByIdDenormalizer(),
                new ArrayDenormalizer(),
                new MyNormalizer($classMetadataFactory),
                new DateTimeNormalizer(),
            ],
            [new JsonEncoder()],
        );

        /**
         * This doesn't work. Symfony serializer cannot handle nested data updates easily,
         * so we'll leave this experiment as is, and go with a different approach, where we only
         * use serializer voor primitive values, and write our own helper for handling relations.
         */

        $data = json_encode([
            'title' => 'Updated Title',
            'tags' => [
                [
                    'name' => 'Updated Tag',
                ],
            ],
        ]);

        $post->getTags()->toArray();

        $serializer->deserialize($data, Post::class, 'json', [
            ObjectNormalizer::GROUPS => ['default'],
            ObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
            ObjectNormalizer::OBJECT_TO_POPULATE => $post,
            ObjectNormalizer::ATTRIBUTES => ['title', 'tags' => ['createdBy']],
        ]);

        dd($post, $post->getTags()[0], $post->getTags()[1]);
    }
}
