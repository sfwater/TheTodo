<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Todo;
use AppBundle\Form\TodoType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use \DateTime;

class TodoController extends Controller
{
    /**
     * @Route("/", name="todo_list")
     */
     public function listAction()
     {
         $currentUser = $this->get('security.token_storage')->getToken()->getUser();
         $userid = $currentUser->getId();

         $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($userid);

         $todoOrder = $user->getTodoOrder();

         $changeHash = $user->getChanged();

         $todos = $this->getDoctrine()
                       ->getRepository('AppBundle:Todo')
                       ->findBy(array('deleted' => 0, 'user_id' => $userid), array('id' => 'DESC')
         );

         return $this->render('todo/index.html.twig', array(
             'todos' => $todos,
             'saved_order' => $todoOrder,
             'changeHash' => $changeHash
         ));
}

    /**
     * @Route("/create", name="todo_create")
     */
     public function createAction(Request $request)
     {
         $todo = new Todo();
         $form = $this->createForm(TodoType::class, $todo);
         $form->handleRequest($request);

         if($form->isSubmitted() && $form->isValid())
         {
             // Share link hash
             $salt = md5('linkHashSalt');

             // Get data
             $name = $form['name']->getData();
             $description = $form['description']->getData();
             $priority = $form['priority']->getData();
             $dueDate = DateTime::createFromFormat('d/m/Y H:i', $form['dueDate']->getData());
             $now = new\DateTime('now');

             $user = $this->get('security.token_storage')->getToken()->getUser();

             $currentUser = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($user->getId());

             $todo->setName($name);
             $todo->setDescription($description);
             $todo->setPriority($priority);
             $todo->setDueDate($dueDate);
             $todo->setCreateDate($now);
             $todo->setTrashedDate($now);
             $todo->setEditDate($now);
             $todo->setUserId($user->getId());
             $todo->setLinkHash('HASH');

             $currentUser->setChanged(md5($now->format('U')));

             if( strlen($description) > 32767 )
             {
                 $this->addFlash('error', 'Todo description must not contain more than 65535 characters!');
             }
             elseif( strlen($name) > 255 )
             {
                 $this->addFlash('error', 'Todo name must not contain more than 255 characters!');
             }
             elseif( ( strlen($name) <= 255 ) && ( strlen($description) <= 32767 ) )
             {

                 $em = $this->getDoctrine()->getManager();
                 $em->persist($todo);
                 $em->flush();

                 $id = $todo->getId();

                 $linkHash = md5($salt.$id);

                 $todo->setLinkHash($linkHash);

                 $em->flush();

                 $this->addFlash(
                     'notice',
                     'A new todo has been successfully created!'
                 );
                 return $this->redirectToRoute('todo_details', array('id' => $id));
             }
             else
             {
                 $this->addFlash('error', 'An unknown error occurred!');
             }
        }

        return $this->render('todo/create.html.twig', array(
            'form' => $form->createView()
        ));
    }

  /**
   * @Route("/edit/{id}", name="todo_edit")
   */
      public function editAction($id, Request $request)
      {
          $user = $this->get('security.token_storage')->getToken()->getUser();

          $todo = $this->getDoctrine()
                       ->getRepository('AppBundle:Todo')
                       ->findOneById($id);

          $currentUser = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($user->getId());

          if($todo->getUserId() == $user->getId())
          {
              $now = new\DateTime('now');
              $todo->setName($todo->getName());
              $todo->setDescription($todo->getDescription());
              $todo->setPriority($todo->getPriority());
              $todo->setDueDate($todo->getDueDate()->format('d/m/Y H:i'));

              $form = $this->createForm(TodoType::class, $todo);

              $form->handleRequest($request);

              if($form->isSubmitted() && $form->isValid())
              {
                  // Get data
                  $name = $form['name']->getData();
                  $description = $form['description']->getData();
                  $priority = $form['priority']->getData();
                  $dueDate = DateTime::createFromFormat('d/m/Y H:i', $form['dueDate']->getData());

                  $em = $this->getDoctrine()->getManager();

                  $todo = $em->getRepository('AppBundle:Todo')->findOneById($id);
                  $todo->setName($name);
                  $todo->setDescription($description);
                  $todo->setPriority($priority);
                  $todo->setDueDate($dueDate);
                  $todo->setEditDate($now);

                  if( strlen($description) > 32767 )
                  {
                      $this->addFlash('error', 'Todo description must not contain more than 65535 characters!');
                  }
                  elseif( strlen($name) > 255 )
                  {
                      $this->addFlash('error', 'Todo name must not contain more than 255 characters!');
                  }
                  elseif( ( strlen($name) <= 255 ) && ( strlen($description) <= 32767 ) )
                  {
                      $currentUser->setChanged(md5($now->format('U')));

                      $em->flush();

                      $this->addFlash(
                        'notice',
                        'The Todo has been successfully updated!'
                      );

                      return $this->redirectToRoute('todo_details', array('id' => $id));
                  }
                  else
                  {
                      $this->addFlash('error', 'An unknown error occurred!');
                  }
              }

              return $this->render('todo/edit.html.twig', array(
                  'todo' => $todo,
                  'form' => $form->createView()
              ));
          }
          else
          {
              $this->addFlash('error', 'Access denied!');
              return $this->redirectToRoute('todo_list');
          }
      }

      /**
       * @Route("/details/{id}", name="todo_details")
       */
      public function detailsAction($id)
      {
            $user = $this->get('security.token_storage')->getToken()->getUser();

            $todo = $this->getDoctrine()
                         ->getRepository('AppBundle:Todo')
                         ->find($id);

            if($todo->getUserId() == $user->getId())
            {
                if($todo->getDeleted() == 1)
                {
                    return $this->redirectToRoute('trash_list');
                }
                else
                {
                    if( $todo->getCreateDate() < $todo->getEditDate() )
                    {
                        return $this->render('todo/details.html.twig', array(
                            'todo' => $todo,
                            'edited' => true
                        ));
                    }
                    return $this->render('todo/details.html.twig', array(
                        'todo' => $todo,
                        'edited' => false
                    ));
                }
            }
            else
            {
                $this->addFlash('error', 'Access denied!');
                return $this->redirectToRoute('todo_list');
            }

      }

      /**
       * @Route("/share/{hash}", name="todo_shared")
       */
      public function sharedAction($hash)
      {
          $securityContext = $this->container->get('security.authorization_checker');

          if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
          {
              $user = $this->get('security.token_storage')->getToken()->getUser();
              $userid = $user->getId();
          }
          else
          {
              $userid = -1;
          }

          $todo = $this->getDoctrine()
                       ->getRepository('AppBundle:Todo')
                       ->findOneByLinkHash($hash);

          if(!$todo)
          {
              $this->addFlash('error', 'The todo was not found');
              return $this->redirectToRoute('todo_list');
          }
          else
          {
              $todoUserId = $todo->getUserId();

              $todoOwner = $this->getDoctrine()
                           ->getRepository('AppBundle:User')
                           ->findOneById($todoUserId);

              $ownerUsername = $todoOwner->getUsername();

              if($todoUserId == $userid)
              {
                  // It's the todo owner!
                  if($todo->getIsPublic() == 0)
                  {
                      $em = $this->getDoctrine()->getManager();

                      $todo->setIsPublic(1);

                      $em->flush();

                      $this->addFlash('notice', 'This todo is now publicly shared!');
                  }

                  return $this->render('todo/public.html.twig', array(
                      'todo' => $todo,
                      'isOwner' => 1,
                      'owner' => $todoOwner
                  ));
              }
              else
              {
                  // It's not the todo owner!
                  if($todo->getIsPublic() == 0)
                  {
                      $this->addFlash('error', 'This todo is not publicly available!');
                      return $this->redirectToRoute('todo_list');
                  }
                  else
                  {
                      return $this->render('todo/public.html.twig', array(
                          'todo' => $todo,
                          'isOwner' => 0,
                          'owner' => $todoOwner
                      ));
                  }
              }
          }
      }

      /**
       * @Route("/unshare/{hash}", name="todo_unshare")
       */
      public function unshareAction($hash)
      {
          $securityContext = $this->container->get('security.authorization_checker');

          if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
          {
              $user = $this->get('security.token_storage')->getToken()->getUser();
              $userid = $user->getId();

              $todo = $this->getDoctrine()
                           ->getRepository('AppBundle:Todo')
                           ->findOneByLinkHash($hash);

              if(!$todo)
              {
                  $this->addFlash('error', 'The todo was not found');
                  return $this->redirectToRoute('todo_list');
              }
              else
              {
                  if($todo->getUserId() == $userid)
                  {
                      $em = $this->getDoctrine()->getManager();

                      $todo->setIsPublic(0);

                      $em->flush();

                      $this->addFlash('notice', 'This todo is no longer publicly shared!');
                      return $this->redirectToRoute('todo_details', array('id' => $todo->getId()));
                  }

              }
          }
          $this->addFlash('error', 'Access denied!');
          return $this->redirectToRoute('todo_list');
      }

      /**
       * @Route("/order/apply", name="todo_reorder")
       */
      public function reorderAction(Request $request)
      {
          if ($request->isXMLHttpRequest()) {
              $data = $request->query->get('data');

              $currentUser = $this->get('security.token_storage')->getToken()->getUser();
              $userid = $currentUser->getId();

              $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($userid);

              $em = $this->getDoctrine()->getManager();

              $user->setTodoOrder($data);

              $now = new\DateTime('now');

              $user->setChanged(md5($now->format('U')));

              $em->flush();

              return new JsonResponse(array('success' => true));
          }

          return new Response('Invalid request', 400);
      }

      /**
       * @Route("/api/v1/changed", name="todo_changed")
       */
      public function changedAction(Request $request)
      {
          $currentUser = $this->get('security.token_storage')->getToken()->getUser();
          $userid = $currentUser->getId();

          $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($userid);

          $changed = $user->getChanged();

          return new JsonResponse(array('changeHash' => $changed));
      }

      /**
       * @Route("/api/v1/todos", name="todo_list_api")
       */
      public function apiTodosAction()
      {
          $currentUser = $this->get('security.token_storage')->getToken()->getUser();
          $userid = $currentUser->getId();

          $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneById($userid);

          $todoOrder = $user->getTodoOrder();

          $order = explode(",", $todoOrder);

          $todos = $this->getDoctrine()
                        ->getRepository('AppBundle:Todo')
                        ->findBy(array('deleted' => 0, 'user_id' => $userid), array('id' => 'ASC')
          );

          $todolist = array();

          foreach($order as $key => $value)
          {
              foreach($todos as $k => $todo)
              {
                  if($todo->getId() == $value) {
                      $element = array('id' => $todo->getId(), 'priority' => $todo->getPriority(), 'name' => $todo->getName(), 'dueDate' => $todo->getDueDate());
                      array_push($todolist, $element);
                      unset($todos[$k]);
                  }
              }
          }

          foreach($todos as $todo)
          {
              $element = array('id' => $todo->getId(), 'priority' => $todo->getPriority(), 'name' => $todo->getName(), 'dueDate' => $todo->getDueDate());
              array_unshift($todolist, $element);
          }

          return $this->render('api/todos.html.twig', array(
              'todos' => $todolist,
              'saved_order' => $todoOrder
          ));
      }
}
