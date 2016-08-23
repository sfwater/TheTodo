<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\RegistrationType;
use AppBundle\Form\UserAccountType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\TextType;

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
        $userid = -1;

        if(!$user)
        {
            $this->addFlash('error', 'Profile could not be found.');
            return $this->redirectToRoute('todo_list');
        }
        else
        {
            $securityContext = $this->container->get('security.authorization_checker');
            if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
            {
                $userid = $this->get('security.token_storage')->getToken()->getUser()->getId();
            }

            $todos = $this->getDoctrine()->getRepository('AppBundle:Todo')->findBy(array('user_id' => $user->getId(), 'isPublic' => 1, 'deleted' => 0), array('dueDate' => 'ASC'));
        }

        return $this->render('user/profile.html.twig', array(
            'user' => $user,
            'todos' => $todos,
            'user_id' => $userid
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
            $userid = $this->get('security.token_storage')->getToken()->getUser()->getId();

            $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($userid);

            return $this->redirectToRoute('user_profile', array(
                'id' => $user->getId()
            ));
        }
    }

    /**
     * @Route("/settings", name="user_settings")
     */
    public function settingsAction()
    {
        $userid = $this->get('security.token_storage')->getToken()->getUser()->getId();
        return $this->render('user/settings.html.twig', array(
            'delete_parameter' => base64_encode($userid)
        ));
    }

    /**
     * @Route("/delete_account/{id_base64}", name="user_delete")
     */
    public function deleteAccountAction($id_base64, Request $request)
    {
        $id = base64_decode($id_base64);
        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        if( $id == $currentUser->getId() )
        {
            $defaultData = array('username' => '');

            $form = $this->createFormBuilder( $defaultData )->add('username', TextType::class)->getForm();

            $form->handleRequest( $request );

            if($form->isSubmitted() && $form->isValid())
            {
                $username = $form['username']->getData();
                if( $username == $currentUser->getUsername() )
                {
                    // Delete account
                    $em = $this->getDoctrine()->getManager();

                    $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($id);

                    $todos = $this->getDoctrine()->getRepository('AppBundle:Todo')->findBy(array('user_id' => $user->getId()));

                    foreach($todos as $todo)
                    {
                        $em->remove($todo);
                    }

                    $em->remove($user);
                    $em->flush();

                    $this->addFlash('notice', 'Account successfully deleted!');

                    $this->get('security.token_storage')->setToken(null);
                    $this->get('session')->clear();

                    return $this->redirectToRoute('login');
                }
                else
                {
                    $this->addFlash('error', 'Invalid username entered');
                }
            }

            return $this->render('user/delete_account.html.twig', array(
                'form' => $form->createView()
            ));
        }
        else
        {
            $this->addFlash('error', 'Access denied!');
            return $this->redirectToRoute('user_settings');
        }
    }
}
