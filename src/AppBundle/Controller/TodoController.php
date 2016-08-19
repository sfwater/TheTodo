<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Todo;
use AppBundle\Form\TodoType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use \DateTime;

class TodoController extends Controller
{
    /**
     * @Route("/", name="todo_list")
     */
     public function listAction()
     {
         $user = $this->get('security.token_storage')->getToken()->getUser();

         $todos = $this->getDoctrine()
                       ->getRepository('AppBundle:Todo')
                       ->findBy(
                            array('deleted' => 0, 'user_id' => $user->getId()),
                            array('dueDate' => 'ASC')
         );

         return $this->render('todo/index.html.twig', array(
             'todos' => $todos
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
             // Get data
             $name = $form['name']->getData();
             $description = $form['description']->getData();
             $priority = $form['priority']->getData();
             $dueDate = DateTime::createFromFormat('d/m/Y H:i', $form['dueDate']->getData());
             $now = new\DateTime('now');

             $user = $this->get('security.token_storage')->getToken()->getUser();

             $todo->setName($name);
             $todo->setDescription($description);
             $todo->setPriority($priority);
             $todo->setDueDate($dueDate);
             $todo->setCreateDate($now);
             $todo->setTrashedDate($now);
             $todo->setUserId($user->getId());

             if( strlen($description) > 65535 )
             {
                 $this->addFlash('error', 'Todo description must not contain more than 65535 characters!');
             }
             elseif( strlen($name) > 255 )
             {
                 $this->addFlash('error', 'Todo name must not contain more than 255 characters!');
             }
             elseif( ( strlen($name) <= 255 ) && ( strlen($description) <= 65535 ) )
             {
                 $em = $this->getDoctrine()->getManager();
                 $em->persist($todo);
                 $em->flush();

                 $this->addFlash(
                     'notice',
                     'A new todo has been successfully created!'
                 );
                 return $this->redirectToRoute('todo_details', array('id' => $todo->getId()));
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
                       ->find($id);

          if($todo->getUserId() == $user->getId())
          {
              $now = new\DateTime('now');
              $todo->setName($todo->getName());
              $todo->setDescription($todo->getDescription());
              $todo->setPriority($todo->getPriority());
              $todo->setDueDate($todo->getDueDate()->format('d/m/Y H:i'));
              $todo->setCreateDate($now);

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

                  $todo = $em->getRepository('AppBundle:Todo')->find($id);
                  $todo->setName($name);
                  $todo->setDescription($description);
                  $todo->setPriority($priority);
                  $todo->setDueDate($dueDate);
                  $todo->setCreateDate($now);

                  if( strlen($description) > 65535 )
                  {
                      $this->addFlash('error', 'Todo description must not contain more than 65535 characters!');
                  }
                  elseif( strlen($name) > 255 )
                  {
                      $this->addFlash('error', 'Todo name must not contain more than 255 characters!');
                  }
                  elseif( ( strlen($name) <= 255 ) && ( strlen($description) <= 65535 ) )
                  {
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
                    return $this->render('todo/details.html.twig', array(
                        'todo' => $todo
                    ));
                }
            }
            else
            {
                $this->addFlash('error', 'Access denied!');
                return $this->redirectToRoute('todo_list');
            }

      }
}
