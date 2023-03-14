<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
//use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;

use Symfony\Component\Validator\Validator\ValidatorInterface;
//use Symfony\Component\Security\PasswordEncoderInterface;

use App\Security\TokenAuthenticator;

use App\Entity\Personne;
use App\Entity\User;

use App\Entity\Voiture;

//class InscriptionController extends Controller
class InscriptionController extends AbstractController
{
    /*
    private  UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncode)
    {
        $this->passwordEncoder = $passwordEncode;
    }*/

    #[Route('/inscription', name: 'inscription', methods: ['POST'])]
    /**
     * @Route("/inscription", name="inscription", methods={"POST"})
     */
    public function inscription(Request $request, TokenAuthenticator $auth, ValidatorInterface $validator, ManagerRegistry $doctrine)
    //public function inscription(Request $request, SecurityConfig $security, ValidatorInterface $validator, ManagerRegistry $doctrine)
    {
        //$data = json_decode($request->getContent(), true); // $data['password'] ..
        $username=$request->query->get('username');
        $password=$request->query->get('password');

        $user = new User();
        $user->setLogin($username);
        $user->setRoles(["ROLE_USER"]);
        //$user->setPassword($hasher->hashPassword(PasswordAuthenticatedUserInterface::class, $password));
        $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
                /*
        $token = $this->get('lexik_jwt_authentication.encoder')->encode([
            'username' => $user->getLogin(),
            'exp' => time() + $tokenTime
        ]);
        */
        $token = $this->get_lexik_jwt_authentication_encoder_encode([
            'username' => $username,
            'exp' => time() + rand() * 1234
        ]);
        $user->setToken($token);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage()
                ];
            }
            return new JsonResponse(['errors' => $errorsArray], 400);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'id_user' => $user->getId(),
            'api_token' => $token]);
    }

    public function get_lexik_jwt_authentication_encoder_encode($arg)
    {
        $secret = "J'adoreSymfony";
        $token = "";

        $i = 0;
        while ($i < strlen($secret))
        {
            $token .= chr(
                ((
                    $arg['exp']
                    + ord(ord($secret[$i]) ^ ord($arg['username'][$i % strlen($arg['username'])]) )
                ) % 26) + 97
            );
            $i++;
        }
        return ($token);
    }

    /*
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
    */

    #[Route('/inscription/{id}', name: 'inscription_delete', methods: ['DELETE'])]
    /**
     * @Route("/inscription/{id}", name="delete_user", methods={"DELETE"})
     */
    public function delete(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['id' => $id]);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 401);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User deleted successfully'], 200);
    }

    #[Route('/inscription/{id}', name: 'inscription_select', methods: ['GET'])]
    /**
     * @Route("/inscription/{id}", methods={"GET"})
     */
    public function select(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth, int $id): JsonResponse
    {
        $auth->authenticate($request);

        //$entityManager = $this->container->get('doctrine.orm.entity_manager');
        //$entityManager = $doctrine->getManager();
        $entityManager = $doctrine->getManager();

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }

        $data = [
            'id' => $user->getId(),
            'username' => $user->getLogin(),
            'password' => $user->getPassword()
        ];

        return new JsonResponse($data);
    }

    #[Route('/inscription', name: 'inscription_liste', methods: ['GET'])]
    /**
     * @Route("/inscription", name="liste_user", methods={"GET"})
     */
    public function liste(ManagerRegistry $doctrine, Request $request, TokenAuthenticator $auth)
    {
        $auth->authenticate($request);

        $entityManager = $doctrine->getManager();
        $users = $entityManager->getRepository(User::class)->findAll();

        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'username' => $user->getLogin(),
                'password' => $user->getPassword(),
            ];
        }
        return new JsonResponse($data, 200);
    }

    #[Route('/connexion', name: 'connexion', methods: ['POST'])]
    /**
     * @Route("/connexion", name="connexion", methods={"POST"})
     */
    public function login(Request $request, TokenAuthenticator $auth, ValidatorInterface $validator, ManagerRegistry $doctrine)
    {
        /*
            Symfony\Bridge\Doctrine\Security\User\EntityUserProvider::loadUserByIdentifier(): Return value must be of type Symfony\Component\Security\Core\User\UserInterface, App\Entity\User returned
        */

        $tokenTime = 3600; // 1 h
        $username = $request->query->get('username');
        $password = $request->query->get('password');

        $entityManager = $doctrine->getManager();
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid credentials'], 401);
        }

        //if (!$encoder->isPasswordValid($user, $password)) {
        if (!password_verify($password, $user->getPassword())) {
            return new JsonResponse(['message' => 'Invalid credentials'], 401);
        }

        //$token = $this->get('lexik_jwt_authentication.encoder')->encode([
        $token = $this->get_lexik_jwt_authentication_encoder_encode([
            'username' => $username,
            'exp' => time() + rand() * 1234
        ]);
        $user->setToken($token);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage()
                ];
            }
            return new JsonResponse(['errors' => $errorsArray], 400);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'id_user' => $user->getId(),
            'api_token' => $token]);
    }
    /*
    public function login(Request $request, PasswordEncoderInterface $passwordEncoder)
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new BadCredentialsException('Invalid username or password');
        }

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findOneBy(['username' => $username]);

        if (!$user) {
            throw new BadCredentialsException('Invalid username or password');
        }

        if (!$passwordEncoder->isPasswordValid($user, $password)) {
            throw new BadCredentialsException('Invalid username or password');
        }

        return new JsonResponse([
            'username' => $user->getLogin(),
            'roles' => $user->getRoles(),
        ]);
    }
    */
    /*
    public function connexion(Request $request)
    {
        $default = [
            'erreur_username' => '',
            'erreur_password' => '',
            'v_username' => ''
        ];
        if ($request->isMethod('get'))
            return $this->render('connexion/index.html.twig', $default);
        else if ($request->isMethod('post'))
        {
            $errorCss = "background-color: red;";

            $username = $request->request->get('username');
            $password = $request->request->get('password');
            if ($this->isEmpty($username) || $this->isEmpty($password))
                return $this->render('connexion/index.html.twig', [
                    'erreur_username' => $this->isEmpty($username) ? $errorCss : '',
                    'erreur_password' => $this->isEmpty($password) ? $errorCss : '',
                    'v_username' => $this->getData($username)
                ]);
            return $this->redirect($this->generateUrl('accueil'));
            //return $this->render('register/index.html.twig', $default);
        }
        return $this->render('not_found/index.html.twig', []);
    }*/
}
