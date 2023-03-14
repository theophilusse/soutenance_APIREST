<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Voiture;
use App\Entity\Marque;

use App\Security\TokenAuthenticator;

class VoitureController extends AbstractController
{
    #[Route('/voiture', name: 'voiture_insert', methods: ['POST'])]
    /**
     * @Route("/voiture", methods={"POST"})
     */
    public function insert(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $immatriculation = $request->query->get('immatriculation');
        $places = $request->query->get('places');
        $modele = $request->query->get('modele');
        $marqueId = $request->query->get('marque_id');

        $entityManager = $doctrine->getManager();

        $marque = $entityManager->getRepository(Marque::class)->find($marqueId);

        if (!$marque) {
            return new JsonResponse(['error' => 'Marque non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $voiture = new Voiture();
        $voiture->setImmatriculation($immatriculation);
        $voiture->setPlaces($places);
        $voiture->setModele($modele);
        $voiture->setMarque($marque);

        $entityManager->persist($voiture);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Voiture ajoutée avec succès'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/voiture/{id}', name: 'voiture_delete', methods: ['DELETE'])]
    /**
     * @Route("/voiture/{id}", methods={"DELETE"})
     */
    public function delete(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $voiture = $entityManager->getRepository(Voiture::class)->find($id);

        if (!$voiture) {
            return new JsonResponse(['error' => 'Voiture non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($voiture);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Voiture supprimée avec succès']);
    }

    #[Route('/voiture', name: 'voiture_liste', methods: ['GET'])]
    /**
     * @Route("/voiture", methods={"GET"})
     */
    public function liste(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $voitures = $entityManager->getRepository(Voiture::class)->findAll();

        $data = [];

        foreach ($voitures as $voiture) {
            $data[] = [
                'id' => $voiture->getId(),
                'immatriculation' => $voiture->getImmatriculation(),
                'places' => $voiture->getPlaces(),
                'modele' => $voiture->getModele(),
                'marque' => [
                    'id' => $voiture->getMarque()->getId(),
                    'nom' => $voiture->getMarque()->getNom(),
                ],
            ];
        }

        return new JsonResponse($data);
    }
}
