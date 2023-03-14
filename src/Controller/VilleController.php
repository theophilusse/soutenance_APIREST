<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Ville;

use App\Security\TokenAuthenticator;

class VilleController extends AbstractController
{
    #[Route('/ville', name: 'ville_insert', methods: ['POST'])]
    /**
     * @Route("/ville", methods={"POST"})
     */
    public function insert(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $nom = $request->query->get('nom');
        $zip = $request->query->get('zip');

        $ville = new Ville();
        $ville->setNom($nom);
        $ville->setZip($zip);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($ville);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Ville ajoutée avec succès'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/ville/{id}', name: 'ville_delete', methods: ['DELETE'])]
    /**
     * @Route("/ville/{id}", methods={"DELETE"})
     */
    public function delete(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $ville = $entityManager->getRepository(Ville::class)->find($id);

        if (!$ville) {
            return new JsonResponse(['error' => 'Ville non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($ville);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Ville supprimée avec succès']);
    }

    #[Route('/ville', name: 'ville_liste', methods: ['GET'])]
    /**
     * @Route("/ville", methods={"GET"})
     */
    public function liste(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $villes = $entityManager->getRepository(Ville::class)->findAll();

        $data = [];

        foreach ($villes as $ville) {
            $data[] = [
                'id' => $ville->getId(),
                'nom' => $ville->getNom(),
                'zip' => $ville->getZip(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/ville/codePostal', name: 'codePostal_liste', methods: ['GET'])]
    /**
     * @Route("/ville", methods={"GET"})
     */
    public function listeCodePostal(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $villes = $entityManager->getRepository(Ville::class)->findAll();

        $data = [];

        foreach ($villes as $ville) {
            $data[] = [
                'zip' => $ville->getZip()
            ];
        }

        return new JsonResponse($data);
    }
}
