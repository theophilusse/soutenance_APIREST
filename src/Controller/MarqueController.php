<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Marque;

use App\Security\TokenAuthenticator;

class MarqueController extends AbstractController
{
    #[Route('/marque', name: 'marque', methods: ['POST'])]
    /**
     * @Route("/marque", methods={"POST"})
     */
    public function insert(Request $request, TokenAuthenticator $auth, ManagerRegistry $doctrine): JsonResponse
    {
        $auth->authenticate($request);

        $nom = $request->query->get('nom');

        $marque = new Marque();
        $marque->setNom($nom);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($marque);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Marque ajoutée avec succès'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/marque/{id}', name: 'marque_delete', methods: ['DELETE'])]
    /**
     * @Route("/marque/{id}", methods={"DELETE"})
     */
    public function delete(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $marque = $entityManager->getRepository(Marque::class)->find($id);

        if (!$marque) {
            return new JsonResponse(['error' => 'Marque non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($marque);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Marque supprimée avec succès']);
    }

    #[Route('/marque', name: 'marque_liste', methods: ['GET'])]
    /**
     * @Route("/marque", methods={"GET"})
     */
    public function liste(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $marques = $entityManager->getRepository(Marque::class)->findAll();

        $data = [];

        foreach ($marques as $marque) {
            $data[] = [
                'id' => $marque->getId(),
                'nom' => $marque->getNom(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/marque/{id}', name: 'marque_select', methods: ['GET'])]
    /**
     * @Route("/marque/{id}", methods={"GET"})
     */
    public function select(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $marque = $entityManager->getRepository(Marque::class)->find($id);

        if (!$marque) {
            throw $this->createNotFoundException(
                'No marque found for id '.$id
            );
        }

        $data = [
            'id' => $marque->getId(),
            'nom' => $marque->getNom()
        ];

        return new JsonResponse($data);
    }
}
