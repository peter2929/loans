<?php

namespace App\Controller;

use App\Entity\LoanEntity;
use App\Controller\LoanController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\{Request, Response, JsonResponse};
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class LoanController extends AbstractController
{
    private const DEFAULT_LIMIT_PER_PAGE = 10;
    private const MAX_LIMIT_PER_PAGE = 100;

    #[Route('/', name: 'home')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Loan application API',
            'documentation' => '/api/doc'
        ]);
    }

    protected function getOne(?LoanEntity $loanEntity): JsonResponse
    {
        if($loanEntity === null) {
            return $this->getNotFound();
        }
    
        return $this->json($loanEntity->toArray(), Response::HTTP_OK);
    }

    protected function getAll(ServiceEntityRepository $repo, Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);
        $total = $repo->count([]);
        $totalPages = max( 1, (int)ceil($total / max($limit, 1)) );
        $page = $this->getPage($request, $totalPages);
        $offset = ($page - 1) * $limit;

        $query = $repo->createQueryBuilder('c')
                        ->orderBy('c.id', 'ASC')
                        ->setFirstResult($offset)
                        ->setMaxResults($limit)
                        ->getQuery();
        $paginator = new Paginator($query);

        $clients = [];
        foreach($paginator as $client) {
            $clients[] = $client->toArray();
        }

        return $this->json([
            'data' => $clients, 
            'pagination' => [
                'page' => $page,
                'pages' => $totalPages,
                'limit' => $limit, 
                'total' => $total
            ]
        ]);
    }

    protected function delete(?LoanEntity $loanEntity, EntityManagerInterface $entityManager): JsonResponse
    {
        if($loanEntity === null) {
            return $this->getNotFound();
        }

        $entityManager->remove($loanEntity);
        $entityManager->flush();
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function getValidationErrors(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach($violations as $violation) {
            $errors[] = ['field' => $violation->getPropertyPath(), 'message' => $violation->getMessage()];
        }
        return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function getNotFound(): JsonResponse
    {
        return $this->json(
            [
                'errors' => [['field' => 'id', 'message' => 'Not found']]
            ], 
            Response::HTTP_NOT_FOUND
        );
    }

    protected function getLimit(Request $request): int
    {
        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT_PER_PAGE);
        return min( max($limit, 1),  self::MAX_LIMIT_PER_PAGE);
    }

    protected function getPage(Request $request, int $totalPages): int
    {
        $page = max(1, $request->query->getInt('page', 1));
        return min($page, $totalPages);
    }
}