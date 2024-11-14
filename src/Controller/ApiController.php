<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
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
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/post/{id}', name: 'api_post_show', methods: ['GET'])]
    public function show(int $id, PostRepository $postRepository): Response
    {
        $post = $postRepository->find($id);
        
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $serializer = new Serializer(
            [new MyNormalizer($classMetadataFactory), new DateTimeNormalizer()], 
            [new JsonEncoder()]
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
            ObjectNormalizer::ATTRIBUTES => ['tags'],
        ]);
        
        dd($json);
        
        return JsonResponse::fromJsonString($json);
    }

}
