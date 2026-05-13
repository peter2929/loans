<?php

namespace App\Controller;

use App\Entity\Client;
use App\Controller\LoanController;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\{Request, Response, JsonResponse, Exception\JsonException};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

class ClientController extends LoanController
{
    #[Route('/clients', name: 'create_client', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create client',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['firstName', 'lastName', 'email', 'phoneNumber'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'email', type: 'string', example: 'john.doe@mail.com'),
                    new OA\Property(property: 'phoneNumber', type: 'string', example: '+37101234567'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Client created'),
            new OA\Response(response: 400, description: 'Invalid JSON'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function createClient(EntityManagerInterface $entityManager, 
                                 ValidatorInterface $validator, 
                                 Request $request): JsonResponse
    {
        try {
            $requestData = $request->toArray();
        }
        catch(JsonException $e) {
            return $this->json(['errors' => ['Invalid JSON request data']], Response::HTTP_BAD_REQUEST);
        }

        $client = new Client();
        $client->updateFields($requestData);
        $violations = $validator->validate($client);
        if(count($violations) > 0) {
            return $this->getValidationErrors($violations);
        }

        $entityManager->persist($client);
        $entityManager->flush();
        return $this->json($client->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/clients/{id}', name: 'delete_client', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete client',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Client deleted'),
            new OA\Response(response: 404, description: 'Client not found'),
        ]
    )]
    public function deleteClient(?Client $client, EntityManagerInterface $entityManager): JsonResponse
    {
        return $this->delete($client, $entityManager);
    }

    #[Route('/clients/{id}', name: 'update_client', methods: ['PATCH'])]
    #[OA\Patch(
        summary: 'Update client',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jane'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane.doe@mail.com'),
                    new OA\Property(property: 'phoneNumber', type: 'string', example: '+37101234568'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Client updated'),
            new OA\Response(response: 400, description: 'Invalid JSON or unwritable fields'),
            new OA\Response(response: 404, description: 'Client not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateClient(?Client $client, 
                                  EntityManagerInterface $entityManager, 
                                  ValidatorInterface $validator, 
                                  Request $request): JsonResponse
    {
        if($client === null) {
            return $this->getNotFound();
        }

        try {
            $requestData = $request->toArray();
        }
        catch(JsonException $e) {
            return $this->json(['errors' => ['Invalid JSON request data']], Response::HTTP_BAD_REQUEST);
        }

        $unWritableFields = array_diff(array_keys($requestData), Client::WRITABLE);
        if(!empty($unWritableFields)) {
            return $this->json([
                'errors' => [
                    'fields' => array_values($unWritableFields),
                    'message' => 'These fields do not exist or you are not allowed to update them'
                    ]
                ], 
                Response::HTTP_BAD_REQUEST
            );
        }

        $client->updateFields($requestData);
        $violations = $validator->validate($client);
        if(count($violations) > 0) {
            return $this->getValidationErrors($violations);
        }

        $entityManager->flush();
        return $this->json($client->toArray());
    }

    #[Route('/clients', name: 'get_clients', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get paginated list of clients',
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, example: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Number of items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10, example: 10, minimum: 1, maximum: 100)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of clients')
        ]
    )]
    public function getClients(ClientRepository $repo, Request $request): JsonResponse
    {
        return $this->getAll($repo, $request);
    }

    #[Route('/clients/{id}', name: 'get_one_client', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get client by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Client found'),
            new OA\Response(response: 404, description: 'Client not found'),
        ]
    )]
    public function getOneClient(?Client $client): JsonResponse
    {
        return $this->getOne($client);
    }
}
