<?php

namespace App\Controller;

use App\Entity\Application;
use App\Controller\LoanController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ApplicationRepository;
use App\Repository\ClientRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\{Request, Response, JsonResponse, Exception\JsonException};
use OpenApi\Attributes as OA;

class ApplicationController extends LoanController
{
    #[Route('/applications', name: 'create_application', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create loan application',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['clientId', 'term', 'amount', 'currency'],
                properties: [
                    new OA\Property(
                        property: 'clientId',
                        type: 'integer',
                        example: 1,
                        description: 'ID of an existing client'
                    ),
                    new OA\Property(
                        property: 'term',
                        type: 'integer',
                        minimum: 10,
                        maximum: 30,
                        example: 30,
                        description: 'Loan term in days'
                    ),
                    new OA\Property(
                        property: 'amount',
                        type: 'number',
                        format: 'float',
                        minimum: 100,
                        maximum: 5000,
                        example: 3000.00,
                        description: 'Loan amount'
                    ),
                    new OA\Property(
                        property: 'currency',
                        type: 'string',
                        enum: ['EUR'],
                        example: 'EUR',
                        description: 'Only EUR is allowed'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Application created'),
            new OA\Response(response: 400, description: 'Invalid JSON'),
            new OA\Response(response: 422, description: 'Invalid clientId or validation error'),
        ]
    )]
    public function createApplication(EntityManagerInterface $entityManager,
                                      ClientRepository $clientRepo, 
                                      ValidatorInterface $validator, 
                                      Request $request): JsonResponse
    {
        try {
            $requestData = $request->toArray();
        }
        catch(JsonException $e) {
            return $this->json(['errors' => ['Invalid JSON request data']], Response::HTTP_BAD_REQUEST);
        }

        if(empty($requestData['clientId'])) {
            return $this->json(['errors' => ['Invalid clientId']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client = $clientRepo->find($requestData['clientId']);
        if($client === null) {
            return $this->json(['errors' => ['Invalid clientId']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $requestData['client'] = $client;

        $application = new Application();
        $application->updateFields($requestData);
        $violations = $validator->validate($application);
        if(count($violations) > 0) {
            return $this->getValidationErrors($violations);
        }

        $entityManager->persist($application);
        $entityManager->flush();
        return $this->json($application->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/applications/{id}', name: 'delete_application', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete loan application',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Application ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Application deleted'),
            new OA\Response(response: 404, description: 'Application not found'),
        ]
    )]
    public function deleteApplication(?Application $application, EntityManagerInterface $entityManager): JsonResponse
    {
        return $this->delete($application, $entityManager);
    }

    #[Route('/applications/{id}', name: 'update_application', methods: ['PATCH'])]
    #[OA\Patch(
        summary: 'Update loan application',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Application ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'clientId',
                        type: 'integer',
                        example: 1,
                        description: 'ID of an existing client'
                    ),
                    new OA\Property(
                        property: 'term',
                        type: 'integer',
                        minimum: 10,
                        maximum: 30,
                        example: 20
                    ),
                    new OA\Property(
                        property: 'amount',
                        type: 'number',
                        format: 'float',
                        minimum: 100,
                        maximum: 5000,
                        example: 1500.00
                    ),
                    new OA\Property(
                        property: 'currency',
                        type: 'string',
                        enum: ['EUR'],
                        example: 'EUR'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Application updated'),
            new OA\Response(response: 400, description: 'Invalid JSON or unwritable fields'),
            new OA\Response(response: 404, description: 'Application not found'),
            new OA\Response(response: 422, description: 'Invalid clientId or validation error'),
        ]
    )]
    public function updateApplication(?Application $application,
                                       ClientRepository $clientRepo, 
                                       EntityManagerInterface $entityManager, 
                                       ValidatorInterface $validator, 
                                       Request $request): JsonResponse
    {
        if($application === null) {
            return $this->getNotFound();
        }

        try {
            $requestData = $request->toArray();
        }
        catch(JsonException $e) {
            return $this->json(['errors' => ['Invalid JSON request data']], Response::HTTP_BAD_REQUEST);
        }

        if(!empty($requestData['clientId'])) {
            $client = $clientRepo->find($requestData['clientId']);
            if($client === null) {
                return $this->json(['errors' => ['Invalid clientId']], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $requestData['client'] = $client;
            unset($requestData['clientId']);
        }

        $unWritableFields = array_diff(array_keys($requestData), Application::WRITABLE);
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

        $application->updateFields($requestData);
        $violations = $validator->validate($application);
        if(count($violations) > 0) {
            return $this->getValidationErrors($violations);
        }

        $entityManager->flush();
        return $this->json($application->toArray());
    }

    #[Route('/applications', name: 'get_applications', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get paginated list of loan applications',
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
            new OA\Response(response: 200, description: 'List of loan applications')
        ]
    )]
    public function getApplications(ApplicationRepository $repo, Request $request): JsonResponse
    {
        return $this->getAll($repo, $request);
    }

    #[Route('/applications/{id}', name: 'get_one_application', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get loan application by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Application ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Application found'),
            new OA\Response(response: 404, description: 'Application not found'),
        ]
    )]
    public function getOneApplication(?Application $application): JsonResponse
    {
        return $this->getOne($application);
    }
}
