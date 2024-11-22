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

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/post/{id}', name: 'api_post_show', methods: ['GET'])]
    public function show(Post $post, StSerializer $serializer): Response
    {
        $json = $serializer->normalize($post, relations: ['tags' => ['createdBy']]);

        dd(json_encode($json, JSON_PRETTY_PRINT));

        return new JsonResponse($json);
    }

    #[Route('/post/{id}/update', name: 'api_post_update', methods: ['GET'])]
    public function update(
        Post $post,
        Request $request,
        StSerializer $serializer,
        EntityManagerInterface $em,
    ) {
        // Normally data would come from $request ofcourse
        $data = [
            'id' => 1,
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

        dd($post);

        $em->flush();

        return new JsonResponse('ok');
    }
}
