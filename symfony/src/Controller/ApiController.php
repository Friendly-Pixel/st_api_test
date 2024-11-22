<?php

namespace App\Controller;

use App\Entity\Post;
use App\StSerializer\Rel;
use App\StSerializer\StSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/post/{id}', name: 'api_post_show', methods: ['GET'])]
    public function show(Post $post, StSerializer $serializer): Response
    {
        $json = $serializer->normalize(
            $post,
            relations: ['tags' => ['createdBy']],
            groups: ['default', 'administrator'],
        );

        dd(json_encode($json, JSON_PRETTY_PRINT));

        return new JsonResponse($json);
    }

    #[Route('/post/{id}/update', name: 'api_post_update', methods: ['GET'])]
    public function update(
        Post $post,
        Request $request,
        StSerializer $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SerializerInterface $symfonySerializer,
    ) {
        // Normally data would come from $request ofcourse
        $data = [
            'title' => 'Updated post title',
            'tags' => [
                [
                    'id' => 1,
                    'name' => 'Updated tag name',
                ],
                [
                    'name' => 'New tag name',
                ],
            ],
        ];

        $serializer->patchDeep(
            $post,
            $data,
            relations: [new Rel('tags', orphanRemoval: true, createNew: true)],
        );

        dump($post);

        $errors = $validator->validate($post);
        if (count($errors) > 0) {
            return JsonResponse::fromJsonString(
                $symfonySerializer->serialize($errors, 'json'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        die();

        $em->flush();

        return new JsonResponse('ok');
    }
}
