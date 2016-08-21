<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\RegistrationType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    /**
     * @Route("/create_account", name="user_create")
     */
    public function createUserAction(Request $request)
    {
        // Check if user is already logged in
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            $this->addFlash('notice', 'Logged in');
            return $this->redirectToRoute('todo_list');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            if( strlen($form['plainPassword']->getData()) < 8 || strlen($form['plainPassword']->getData()) > 16 )
            {
                $this->addFlash('passwordIssue', 'Your password must be between 8 and 16 characters long.');
            }
            if( strlen($form['username']->getData()) < 5 || strlen($form['username']->getData()) > 32 )
            {
                $this->addFlash('usernameIssue', 'Your username must be between 5 and 32 characters long.');
            }
            else
            {
                // Add a new user
                $now = new\DateTime('now');

                $password = $this->get('security.password_encoder')
                                 ->encodePassword($user, $user->getPlainPassword());

                $user->setPassword($password);
                $user->setCreateDate($now);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash('notice', 'A new account has been created.');

                return $this->redirectToRoute('todo_list');
            }
        }
        return $this->render('user/create.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/profile/{id}", name="user_profile")
     */
    public function displayProfileAction($id)
    {
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($id);

        if(!$user)
        {
            $this->addFlash('error', 'Profile could not be found.');
            return $this->redirectToRoute('todo_list');
        }
        else
        {
            $todos = $this->getDoctrine()->getRepository('AppBundle:Todo')->findBy(array('user_id' => $user->getId(), 'isPublic' => '1'), array('dueDate' => 'ASC'));
        }

        return $this->render('user/profile.html.twig', array(
            'user' => $user,
            'todos' => $todos
        ));
    }

    /**
     * @Route("/profile", name="user_profile_redirect")
     */
    public function profileRedirectAction()
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            $user = $this->get('security.token_storage')->getToken()->getUser();
            return $this->redirectToRoute('user_profile', array(
                'id' => $user->getId()
            ));
        }
        else
        {
            return $this->redirectToRoute('login');
        }
    }
}
