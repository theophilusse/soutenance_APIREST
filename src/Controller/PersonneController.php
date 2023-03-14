<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User; // TODO
use App\Entity\Personne; // TODO

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

use App\Security\TokenAuthenticator;

class PersonneController extends AbstractController
{
    #[Route('/personne', name: 'personne', methods: ['POST'])]
    /**
     * @Route("/personne", methods={"POST"})
     */
    public function insert(Request $request, TokenAuthenticator $auth, ManagerRegistry $doctrine): JsonResponse
    {
        $auth->authenticate($request);

        $nom = $request->query->get('nom');
        $prenom = $request->query->get('prenom');
        $telephone = $request->query->get('telephone');
        $mail = $request->query->get('mail');

        $personne = new Personne();
        $personne->setNom($nom);
        $personne->setPrenom($prenom);
        $personne->setTelephone($telephone);
        $personne->setMail($mail);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($personne);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Personne created!'], Response::HTTP_CREATED);
    }

    #[Route('/personne/{id}', name: 'personne_delete', methods: ['DELETE'])]
    /**
     * @Route("/personne/{id}", methods={"DELETE"})
     */
    public function delete(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse // POURQUOI CASTER EN INT? COMMENT EVITER EXCEPTION SI ALPHANUMERIQUE?
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $personne = $entityManager->getRepository(Personne::class)->find($id);

        if (!$personne) {
            return new JsonResponse(['status' => 'No personne found for id '.$id ]);
        }

        $entityManager->remove($personne);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Personne deleted']);
    }

    #[Route('/personne', name: 'personne_liste', methods: ['GET'])]
    /**
     * @Route("/personne", methods={"GET"})
     */
    public function list(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $personnes = $entityManager->getRepository(Personne::class)->findAll();

        $data = [];

        foreach ($personnes as $personne) {
            $data[] = [
                'id' => $personne->getId(),
                'nom' => $personne->getNom(),
                'prenom' => $personne->getPrenom(),
                'telephone' => $personne->getTelephone(),
                'mail' => $personne->getMail(),
            ];
        }

        return new JsonResponse($data);
    }

    // TODO
    /*
    public function listePassagers()
    {
        // TODO
        ;
    }

    public function listeConducteur()
    {
        // TODO
        ;
    }
    */

    #[Route('/personne/{id}', name: 'personne_select', methods: ['GET'])]
    /**
     * @Route("/personnes/{id}", methods={"GET"})
     */
    public function select(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $personne = $entityManager->getRepository(Personne::class)->find($id);

        if (!$personne) {
            return new JsonResponse(['error' => 'Personne non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $personne->getId(),
            'nom' => $personne->getNom(),
            'prenom' => $personne->getPrenom(),
            'telephone' => $personne->getTelephone(),
            'mail' => $personne->getMail(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/personne/{id}', name: 'personne_update', methods: ['PUT'])]
    /**
     * @Route("/personne/{id}", methods={"PUT"})
     */
    public function update(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $personne = $entityManager->getRepository(Personne::class)->find($id);

        if (!$personne) {
            return new JsonResponse(['error' => 'Personne non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $nom = $request->query->get('nom');
        $prenom = $request->query->get('prenom');
        $telephone = $request->query->get('telephone');
        $mail = $request->query->get('mail');

        if ($nom != null)
            $personne->setNom($nom);
        if ($prenom != null)
            $personne->setPrenom($prenom);
        if ($telephone != null)
            $personne->setTelephone($telephone);
        if ($mail != null)
            $personne->setMail($mail);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($personne);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Personne mise à jour'], Response::HTTP_OK);

        ///////////////////////////////////////////
        /*
        //$nameConverter = new OrgPrefixNameConverter();
        $normalizer = new ObjectNormalizer();//null, $nameConverter);//$classMetadataFactory);
        $serializer = new Serializer([$normalizer]);
        //$requestData = json_decode($request->getContent(), true); // TODO
        //$json = $serializer->serialize($requestData, 'json');
        //$json = $serializer->serialize($request->getContent(), 'json');
        $pers = $serializer->deserialize(
            $request->getContent(),
            //$json,
            Personne::class,
            'json',//);//,
            ['personne' => $personne] // Populate deserialized JSON content into existing/new entity
        );

        $entityManager->persist($pers);
        $entityManager->flush();
        return new JsonResponse(['success' => 'Personne mise à jour'], Response::HTTP_OK);
        */
        ///////////////////////////////////////////

        /*
        $requestData = json_decode($request->getContent(), true);

        $form = $this->createForm(Personne::class, $personne);
        $form->submit($requestData);

        //if ($form->isValid()) {
        if (true) {
            $entityManager->flush();
            return new JsonResponse(['success' => 'Personne mise à jour'], Response::HTTP_OK);
        } else {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }
        */
    }
}
