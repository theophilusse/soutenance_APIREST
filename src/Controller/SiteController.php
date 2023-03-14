<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Trajet; // TODO
use App\Entity\Personne; // TODO
use App\Entity\User; // TODO


use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SiteController extends AbstractController
{
    #[Route('/', name: 'accueil')]
    public function index(): Response
    {
        return $this->render('accueil/index.html.twig', [
            //'listeTrajet' => liste($doctrine),
        ]);
    }

    #[Route('/mentions-legales', name: 'mentions-legales')]
    public function mention(): Response
    {
        return $this->render('mentions-legales/index.html.twig', []);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('contact/index.html.twig', []);
    }

    #[Route('/rejoindre', name: 'rejoindre')]
    public function rejoindre(ManagerRegistry $doctrine, Request $request)
    {
        $default = [
            'erreur_username' => '',
            'erreur_pass' => '',
            'erreur_mail' => '',
            'v_username' => '',
            'v_mail' => ''
        ];
        if ($request->isMethod('get'))
            return $this->render('inscription/index.html.twig', $default);
        else if ($request->isMethod('post'))
        {
            $errorCss = "background-color: red;";

            $username = $request->request->get('username');
            $pass = $request->request->get('pass');
            $mail = $request->request->get('mail');
            if ($this->isEmpty($username) || $this->isEmpty($pass) || $this->isEmpty($mail))
                return $this->render('inscription/index.html.twig', [
                    'erreur_username' => $this->isEmpty($username) ? $errorCss : '',
                    'erreur_pass' => $this->isEmpty($pass) ? $errorCss : '',
                    'erreur_mail' => $this->isEmpty($mail) ? $errorCss : '',
                    'v_username' => $this->getData($username),
                    'v_mail' => $this->getData($mail)
                ]);

            $entityManager = $doctrine->getManager();
            $conn = $entityManager->getConnection();
            $stmt = $conn->prepare('SELECT * FROM user p WHERE p.username != :username');
            $resultSet = $stmt->executeQuery(['username' => $this->sanitize($username)]);

            if (sizeof($resultSet) != 0)
                return $this->render('inscription/index.html.twig', [
                    'erreur_username' => $errorCss,
                    'erreur_pass' => $this->isEmpty($pass) ? $errorCss : '',
                    'erreur_mail' => $this->isEmpty($mail) ? $errorCss : '',
                    'v_username' => '',
                    'v_mail' => $this->getData($mail)
                ]);
            $stmt = $conn->prepare('INSERT INTO user (username, password, api_token) VALUES (:username, :password, :api_token)');
            $resultSet = $stmt->executeQuery([
                'username' => $this->sanitize($username),
                'password' => password_hash($password),
                'api_token' => 'aaaaaa'
            ]);
            return $this->redirect($this->generateUrl('login'));
        }
        return $this->render('not_found/index.html.twig', []);
    }

    #[Route('/rechercher', name: 'rechercher')]
    public function rechercher(ManagerRegistry $doctrine, Request $request)
    {
        $default = [
            'erreur_depart' => '',
            'erreur_destination' => '',
            'v_depart' => '',
            'v_destination' => '',
            'v_date' => '',
            'trajets' => []
        ];
        if ($request->isMethod('get'))
            return $this->render('rechercher/index.html.twig', $default);
        else if ($request->isMethod('post'))
        {
            $errorCss = "background-color: red;";

            $depart = $request->request->get('depart');
            $destination = $request->request->get('destination');
            $date = $request->request->get('date');
            if ($this->isEmpty($depart) || $this->isEmpty($destination))
                return $this->render('rechercher/index.html.twig', [
                    'erreur_depart' => $this->isEmpty($depart) ? $errorCss : '',
                    'erreur_destination' => $this->isEmpty($destination) ? $errorCss : '',
                    'v_depart' => $this->getData($depart),
                    'v_destination' => $this->getData($destination),
                    'v_date' => $this->getData($date),
                    'trajets' => []
                ]);

            $entityManager = $doctrine->getManager();
            $conn = $entityManager->getConnection();
            // TODO
            /*
            $stmt = $conn->prepare('SELECT * INTO trajet (depart_id, destination_id) VALUES (:depart, :desitnation)');
            $resultSet = $stmt->executeQuery([
                'depart' => $this->sanitize($depart),
                'destination' => $this->sanitize($depart)
            ]);
            */
            //$trajets = $entityManager->getRepository(Trajet::class)->findBy(array('depart_id' => $depart, 'destination_id' => $destination),array('date' => 'DESC'),1 ,0);
            $trajets = $entityManager->getRepository(Trajet::class)->findBy(array('depart' => $depart, 'destination' => $destination),array('date' => 'DESC'),1 ,0);
            $data = [];

            foreach ($trajets as $trajet) {
                if ($trajet->getDate() < $date)
                    break;
                $data[] = [
                    'id' => $trajet->getId(),
                    'conducteur' => $trajet->getConducteur()->getNomComplet(),
                    'depart' => $trajet->getDepart()->getNom(),
                    'destination' => $trajet->getDestination()->getNom(),
                    'voiture' => $trajet->getVoiture()->getMarque()->getNom() . ' ' . $trajet->getVoiture()->getModele(),
                    'date' => $trajet->getDate()->format('Y-m-d H:i:s'),
                    'distance' => $trajet->getDistance(),
                ];
            }
            return $this->render('rechercher/index.html.twig', [
                'erreur_depart' => '',
                'erreur_destination' => '',
                'v_depart' => $this->getData($depart),
                'v_destination' => $this->getData($destination),
                'v_date' => $this->getData($date),
                'trajets' => $data
            ]);
        }
        return $this->render('not_found/index.html.twig', []);
    }

    #[Route('/publier', name: 'publier')]
    public function publier(ManagerRegistry $doctrine, Request $request)
    {
        $default = [
            'erreur_depart' => '',
            'erreur_destination' => '',
            'erreur_voiture' => '',
            'erreur_date' => '',
            'erreur_distance' => '',
            'v_depart' => '',
            'v_destination' => '',
            'v_voiture' => '',
            'v_date' => '',
            'v_distance' => ''
        ];
        $isConnected = true;
        if ($isConnected == false)
            return $this->redirect($this->generateUrl('login'));
        else if ($request->isMethod('get'))
            return $this->render('publier/index.html.twig', $default);
        else if ($request->isMethod('post'))
        {
            $errorCss = "background-color: red;";

            $depart = $request->request->get('depart');
            $destination = $request->request->get('destination');
            $voiture = $request->request->get('voiture');
            $date = $request->request->get('date');
            $distance = $request->request->get('distance');
            if (
                $this->isEmpty($depart) ||
                $this->isEmpty($destination) ||
                $this->isEmpty($voiture) ||
                $this->isEmpty($date) ||
                $this->isEmpty($distance)
                )
                return $this->render('publier/index.html.twig', [
                    'erreur_depart' => $this->isEmpty($depart) ? $errorCss : '',
                    'erreur_destination' => $this->isEmpty($destination) ? $errorCss : '',
                    'erreur_voiture' => $this->isEmpty($voiture) ? $errorCss : '',
                    'erreur_date' => $this->isEmpty($date) ? $errorCss : '',
                    'erreur_distance' => $this->isEmpty($distance) ? $errorCss : '',
                    'v_depart' => $this->getData($depart),
                    'v_destination' => $this->getData($destination),
                    'v_voiture' => $this->getData($voiture),
                    'v_date' => $this->getData($date),
                    'v_distance' => $this->getData($distance)
                ]);

            $entityManager = $doctrine->getManager();
            $conn = $entityManager->getConnection();
            $stmt = $conn->prepare(
                'INSERT INTO trajet (conducteur_id, depart_id, destination_id, voiture_id, date, distance) VALUES (:conducteur, :depart, :destination, :voiture, :date, :distance)');
            $resultSet = $stmt->executeQuery([
                'conducteur' => 0, // TODO user->getId()
                'depart' => $depart,
                'destination' => $destination,
                'voiture' => $voiture,
                'date' => $date,
                'distance' => $distance
            ]);
            return $this->redirect($this->generateUrl('login'));
        }
        return $this->render('not_found/index.html.twig', []);
    }

    #[Route('/login', name: 'login')]
    public function login(ManagerRegistry $doctrine, Request $request)
    {
        $default = [
            'erreur_username' => '',
            'erreur_password' => '',
            'v_username' => ''
        ];
        if ($request->isMethod('get'))
            return $this->render('login/index.html.twig', $default);
        else if ($request->isMethod('post'))
        {
            $errorCss = "background-color: red;";

            $login = $request->request->get('login');
            $password = $request->request->get('password');
            if ($this->isEmpty($login) || $this->isEmpty($password))
                return $this->render('login/index.html.twig', [
                    'erreur_username' => $this->isEmpty($login) ? $errorCss : '',
                    'erreur_password' => $this->isEmpty($password) ? $errorCss : '',
                    'v_username' => $this->getData($login)
            ]);

            $entityManager = $doctrine->getManager();
            $conn = $entityManager->getConnection();
            $stmt = $conn->prepare('SELECT * FROM user where username = :login');
            $resultSet = $stmt->executeQuery([
                'login' => $login, // TODO user->getId()
            ]);
            foreach ($resultSet as $result){
                if ($result->password == password_hash($password))
                    return $this->redirect($this->generateUrl('compte'));
            }
            return $this->redirect($this->generateUrl('login'));
        }
        return $this->render('not_found/index.html.twig', []);
    }

    #[Route('/logout', name: 'logout')]
    public function deconnexion(ManagerRegistry $doctrine): Response
    {
        return $this->redirect($this->generateUrl('accueil'));
    }

    #[Route('/compte', name: 'compte')]
    public function compte(ManagerRegistry $doctrine): Response
    {
        $isConnected = true;
        if ($isConnected == false)
            return $this->redirect($this->generateUrl('login'));
        return $this->render('compte/index.html.twig', [
            'username' => 'TODO', // $user->getPrenom()
            'total_trajets' => 'TODO', // $user->getNombreTrajet()
            'derniere_voiture' => 'TODO' // $user->getDerniereVoiture()
        ]);
    }

    public function sanitize($input)
    {
        return (htmlspecialchars(strip_tags($input)));
    }

    public function isEmpty($reqObj)
    {
        return ((is_string($reqObj) && strlen($reqObj) == 0) || (is_array($reqObj) && (empty($reqObj) || sizeof($reqObj) == 0)));
    }

    public function getData($obj)
    {
        if (empty($obj))
            return ('');
        else if (is_string($obj))
            return ($obj);
        else if (is_array($obj))
        {
            if (sizeof($obj) < 1)
                return ('');
            return ($obj[0]);
        }
        return ('');
    }
}
