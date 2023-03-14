<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Trajet;
use App\Entity\Personne;
use App\Entity\Ville;
use App\Entity\Voiture;
use App\Security\TokenAuthenticator;

use Symfony\Component\Cache\Adapter\PdoAdapter;

class TrajetController extends AbstractController
{
    /*public function liste(ManagerRegistry $doctrine)
    {
        $manager = $doctrine->getManager();
        $trajetRepository = $manager->getRepository(Trajet::class);

        $listeTrajet = $trajetRepository->findAll();

        return $listeTrajet;
    }*/

    #[Route('/trajet/recherche', name: 'recherche', methods: ['POST'])]
    /**
     * @Route("/trajet/recherche", methods={"POST"})
     */
    public function recherche(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $departId = $request->query->get('depart_id');
        $destinationId = $request->query->get('destination_id');
        $date = new \DateTime($request->query->get('date')); // TODO formatter l'entree

        $entityManager = $doctrine->getManager();

        /*$depart = $entityManager->getRepository(Ville::class)->find($departId);
        if (!$depart) {
            return new JsonResponse(['error' => 'Ville de départ non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $destination = $entityManager->getRepository(Ville::class)->find($destinationId);

        if (!$destination) {
            return new JsonResponse(['error' => 'Ville de destination non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }*/

        $trajets = $entityManager->getRepository(Trajet::class)->findBy(
            //array('depart_id' => $depart, 'destination_id' => $destination, 'date' => $date),
            array('depart' => $departId, 'destination' => $destinationId, 'date' => $date),
            array('date' => 'DESC'),
            1 ,0
        );

        $data = [];

        foreach ($trajets as $trajet) {
            $data[] = [
                'id' => $trajet->getId(),
                'conducteur' => [
                    'id' => $trajet->getConducteur()->getId(),
                    'prenom' => $trajet->getConducteur()->getPrenom()
                ],
                'depart' => [
                    'id' => $trajet->getDepart()->getId(),
                    'nom' => $trajet->getDepart()->getNom()
                ],
                'destination' => [
                    'id' => $trajet->getDestination()->getId(),
                    'nom' => $trajet->getDestination()->getNom()
                ],
                'voiture' => $trajet->getVoiture()->getMarque()->getNom() . ' ' . $trajet->getVoiture()->getModele(),
                'date' => $trajet->getDate()->format('Y-m-d H:i:s'),
                'distance' => $trajet->getDistance(),
            ];
        }

        return new JsonResponse($data);
    }

    /*
    #[Route('/recherche/criteres', name: 'criteres_recherche')]
    public function recherche_criteres()
    {
        // TODO
        ;
    }

    #[Route('/recherche/resultat', name: 'resultat_recherche')]
    public function recherche_resultat()
    {
        // TODO
        ;
    }
    */

    #[Route('/trajet', name: 'trajet_insert', methods: ['POST'])]
    /**
     * @Route("/trajet", methods={"POST"})
     */
    public function insert(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $departId = $request->query->get('depart_id');
        $destinationId = $request->query->get('destination_id');
        $conducteurId = $request->query->get('conducteur_id');
        $voitureId = $request->query->get('voiture_id');
        $date = new \DateTime($request->query->get('date'));
        $distance = $request->query->get('distance');

        $entityManager = $doctrine->getManager();

        $depart = $entityManager->getRepository(Ville::class)->find($departId);

        if (!$depart) {
            return new JsonResponse(['error' => 'Ville de départ non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $destination = $entityManager->getRepository(Ville::class)->find($destinationId);

        if (!$destination) {
            return new JsonResponse(['error' => 'Ville de destination non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $conducteur = $entityManager->getRepository(Personne::class)->find($conducteurId);

        if (!$conducteur) {
            return new JsonResponse(['error' => 'Conducteur non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $voiture = $entityManager->getRepository(Voiture::class)->find($voitureId);

        if (!$voiture) {
            return new JsonResponse(['error' => 'Voiture non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        //$time = strtotime('10/16/2003');
        //$newformat = date('Y-m-d',$time);
        //2003-10-16

        $trajet = new Trajet();
        $trajet->setDepart($depart);
        $trajet->setDestination($destination);
        $trajet->setConducteur($conducteur);
        $trajet->setVoiture($voiture);
        $trajet->setDate($date);
        $trajet->setDistance($distance);

        $entityManager->persist($trajet);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Trajet ajouté avec succès'], JsonResponse::HTTP_CREATED);
    }

    /// PASSAGER PAS TESTE

    ///
    /*
    public function getPassager(): Collection
    {
        return $this->passager;
    }

    public function addPassager(Personne $passager): self
    {
        if (!$this->passager->contains($passager)) {
            $this->passager->add($passager);
        }

        return $this;
    }

    public function removePassager(Personne $passager): self
    {
        $this->passager->removeElement($passager);

        return $this;
    }*/
    ///

    #[Route('/trajet/{id}/passager', name: 'trajet_list_passager', methods: ['GET'])]
    public function liste_passager(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $trajet = $entityManager->getRepository(Trajet::class)->find($id);
        if (!$trajet)
            return new JsonResponse(['error' => 'Trajet non trouve.']);
        $passagers = $trajet->getPassager();
        $data = [];

        foreach ($passagers as $passager) {
            $data[] = [
                'id' => $passager->getId(),
                'nom' => $passager->getNom(),
                'prenom' => $passager->getPrenom(),
                'telephone' => $passager->getTelephone(),
                'mail' => $passager->getMail()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/trajet/{id}/passager/{idPassager}', name: 'trajet_select_passager', methods: ['GET'])]
    public function select_passager(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id, int $idPassager): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $trajet = $entityManager->getRepository(Trajet::class)->find($id);
        if (!$trajet)
            return new JsonResponse(['error' => 'Trajet non trouve.']);
        $passagers = $trajet->getPassager();
        foreach ($passagers as $passager)
            if ($passager->getId() == $idPassager)
                return new JsonResponse([
                    'id' => $passager->getId(),
                    'nom' => $passager->getNom(),
                    'prenom' => $passager->getPrenom(),
                    'telephone' => $passager->getTelephone(),
                    'mail' => $passager->getMail()
                ]);
        return new JsonResponse(['error' => 'Passager non trouve.']);
    }

    #[Route('/trajet/{id}/passager', name: 'trajet_insert_passager', methods: ['POST'])]
    public function insert_passager(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $trajet = $entityManager->getRepository(Trajet::class)->find($id);
        $idPassager = $request->query->get('passager');
        if (!$trajet)
            return new JsonResponse(['error' => 'Trajet non trouve.']);
        $passager = $trajet->getPassager();

        //return new JsonResponse(['debug' => count($passager)]);
        if (!$passager || count($passager) >= $trajet->getPlaces())
            return new JsonResponse(['error' => 'Trajet complet.']);
        $personne = null;
        if ($idPassager != null)
        {
            $personne = $entityManager->getRepository(Personne::class)->find($idPassager);
            /*
            if ($personne && ($trajet->getConducteur()->getId() == $personne->getId() || $trajet->addPassager($personne) == null)) // Contrainte
                return new JsonResponse(['error' => 'Personne deja passagere.']);
            */
            if ($personne && ($trajet->getConducteur()->getId() == $personne->getId()))
                return new JsonResponse(['error' => 'AA.']);
            if ($personne && $trajet->addPassager($personne) == null) // Contrainte
                return new JsonResponse(['error' => 'BB.']);
        }
        if (!$personne)
            return new JsonResponse(['error' => 'Personne non trouve.']);
        $entityManager->persist($trajet);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Passager insere avec succes.']);
    }

    #[Route('/trajet/{id}/passager/{idPassager}', name: 'trajet_delete_passager', methods: ['DELETE'])]
    public function delete_passager(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id, int $idPassager): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $trajet = $entityManager->getRepository(Trajet::class)->find($id);
        $personne = $entityManager->getRepository(Personne::class)->find($idPassager);
        if (!$trajet)
            return new JsonResponse(['error' => 'Trajet non trouve.']);
        if (!$personne)
            return new JsonResponse(['error' => 'Personne non trouve.']);
        $trajet->removePassager($personne);
        $entityManager->persist($trajet);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Passager ejecte avec succes.']);
    }
    /// PASSAGER PAS TESTE

    #[Route('/trajet/{id}', name: 'trajet_update', methods: ['PUT'])]
    /**
     * @Route("/trajets/{id}", methods={"PUT"})
     */
    public function update(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $trajet = $entityManager->getRepository(Trajet::class)->find($id);

        if (!$trajet) {
            return new JsonResponse(['error' => 'Trajet non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $conducteur = $request->query->get('conducteur');
        $depart = $request->query->get('depart');
        $destination = $request->query->get('destination');
        $date = $request->query->get('date');
        $distance = $request->query->get('distance');
        $voiture = $request->query->get('voiture');

        if ($conducteur != null)
        {
            $cond = $entityManager->getRepository(Personne::class)->find($conducteur);
            if ($cond)
                $trajet->setConducteur($cond);
        }
        if ($depart != null)
        {
            $dep = $entityManager->getRepository(Ville::class)->find($depart);
            if ($dep)
                $trajet->setDepart($dep);
        }
        if ($destination != null)
        {
            $dest = $entityManager->getRepository(Ville::class)->find($destination);
            if ($dest)
                $trajet->setDestination($dest);
        }
        if ($date != null)
            $trajet->setDate($date);
        if ($distance != null)
            $trajet->setDistance($distance);
        if ($voiture != null)
        {
            $voit = $entityManager->getRepository(Voiture::class)->find($voiture);
            if ($voit)
                $trajet->setVoiture($voit);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($trajet);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Trajet mise à jour'], Response::HTTP_OK);
    }

    #[Route('/trajet/{id}', name: 'trajet_delete', methods: ['DELETE'])]
    /**
     * @Route("/trajet/{id}", methods={"DELETE"})
     */
    public function delete(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $trajet = $entityManager->getRepository(Trajet::class)->find($id);

        if (!$trajet) {
            return new JsonResponse(['error' => 'Trajet non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($trajet);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Trajet supprimé avec succès']);
    }

    #[Route('/trajet', name: 'liste_trajet', methods: ['GET'])]
    /**
     * @Route("/trajet", methods={"GET"})
     */
    public function liste(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $trajets = $entityManager->getRepository(Trajet::class)->findAll();

        $data = [];

        foreach ($trajets as $trajet) {
            $passagers = $trajet->getPassager();
            $passagerJson = [];
            foreach ($passagers as $p) {
                $passagerJson = [
                    'id' => $p->getId(),
                    'nom' => $p->getNom(),
                    'prenom' => $p->getPrenom(),
                    'telephone' => $p->getTelephone(),
                    'mail' => $p->getMail(),
                ];
            }
            $data[] = [
                'id' => $trajet->getId(),
                'conducteur' => [
                    'id' => $trajet->getConducteur()->getId(),
                    'prenom' => $trajet->getConducteur()->getPrenom()
                ],
                'passager' => $passagerJson,
                'depart' => [
                    'id' => $trajet->getDepart()->getId(),
                    'nom' => $trajet->getDepart()->getNom()
                ],
                'destination' => [
                    'id' => $trajet->getDestination()->getId(),
                    'nom' => $trajet->getDestination()->getNom()
                ],
                'voiture' => $trajet->getVoiture()->getMarque()->getNom() . ' ' . $trajet->getVoiture()->getModele(),
                'date' => $trajet->getDate()->format('Y-m-d H:i:s'),
                'distance' => $trajet->getDistance(),
            ];
        }

        return new JsonResponse($data);
    }
}
