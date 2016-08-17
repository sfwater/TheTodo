<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Todo;
use AppBundle\Entity\Trash;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use \DateTime;

class TodoController extends Controller
{
  /**
   * @Route("/", name="todo_list")
   */
  public function listAction()
  {
    $todos = $this->getDoctrine()
                  ->getRepository('AppBundle:Todo')
                  ->findBy(array(), array('dueDate' => 'ASC'));

      return $this->render('todo/index.html.twig', array(
        'todos' => $todos
      ));
  }

  /**
   * @Route("/create", name="todo_create")
   */
  public function createAction(Request $request)
  {
    $todo = new Todo;

    $priorityChoices = array('Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High');

    $form = $this->createFormBuilder( $todo )
                 ->add('name', TextType::class)
                 ->add('description', TextareaType::class)
                 ->add('priority', ChoiceType::class, array('choices' => $priorityChoices))
                 ->add('dueDate', TextType::class)
                 ->getForm();

      $form->handleRequest($request);

      if($form->isSubmitted() && $form->isValid())
      {
        // Get data
        $name = $form['name']->getData();
        $description = $form['description']->getData();
        $priority = $form['priority']->getData();
        $dueDate_raw = $form['dueDate']->getData();
        $dueDate = DateTime::createFromFormat('d/m/Y H:i', $dueDate_raw);

        $now = new\DateTime('now');

        $todo->setName($name);
        $todo->setDescription($description);
        $todo->setPriority($priority);
        $todo->setDueDate($dueDate);
        $todo->setCreateDate($now);

        $em = $this->getDoctrine()->getManager();

        $em->persist($todo);
        $em->flush();

        $this->addFlash(
          'notice',
          'A new todo has been successfully created!'
        );

        return $this->redirectToRoute('todo_list');
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
    $todo = $this->getDoctrine()
                 ->getRepository('AppBundle:Todo')
                 ->find($id);

    $priorityChoices = array('Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High');

    $now = new\DateTime('now');

    $todo->setName($todo->getName());
    $todo->setDescription($todo->getDescription());
    $todo->setPriority($todo->getPriority());
    $todo->setDueDate($todo->getDueDate()->format('d/m/Y H:i'));
    $todo->setCreateDate($now);

    $form = $this->createFormBuilder( $todo )
                 ->add('name', TextType::class)
                 ->add('description', TextareaType::class)
                 ->add('priority', ChoiceType::class, array('choices' => $priorityChoices))
                 ->add('dueDate', TextType::class)
                 ->getForm();

    $form->handleRequest($request);

    if($form->isSubmitted() && $form->isValid())
    {
      // Get data
      $name = $form['name']->getData();
      $description = $form['description']->getData();
      $priority = $form['priority']->getData();

      $dueDate_raw = $form['dueDate']->getData();
      $dueDate = DateTime::createFromFormat('d/m/Y H:i', $dueDate_raw);

      $em = $this->getDoctrine()->getManager();
      $todo = $em->getRepository('AppBundle:Todo')->find($id);

      $todo->setName($name);
      $todo->setDescription($description);
      $todo->setPriority($priority);
      $todo->setDueDate($dueDate);
      $todo->setCreateDate($now);

      $em->flush();

        $this->addFlash(
          'notice',
          'The Todo has been successfully updated!'
        );

        return $this->redirectToRoute('todo_list');
    }



    return $this->render('todo/edit.html.twig', array(
      'todo' => $todo,
      'form' => $form->createView()
    ));
  }

  /**
   * @Route("/details/{id}", name="todo_details")
   */
  public function detailsAction($id)
  {
    $todo = $this->getDoctrine()
                 ->getRepository('AppBundle:Todo')
                 ->find($id);

    return $this->render('todo/details.html.twig', array(
      'todo' => $todo
    ));
  }

  /**
   * @Route("/delete/{id}", name="todo_trash")
   */
   public function deleteAction($id)
   {
     $em = $this->getDoctrine()->getManager();
     $todo = $em->getRepository('AppBundle:Todo')->find($id);

     $trash = new Trash;
     $trash->setTodoName($todo->getName());
     $trash->setTodoPriority($todo->getPriority());
     $trash->setTodoDescription($todo->getDescription());
     $trash->setTodoDueDate($todo->getDueDate());
     $trash->setTodoCreateDate($todo->getCreateDate());

     $em->persist($trash);
     $em->remove($todo);
     $em->flush();

     $this->addFlash(
       'notice',
       'The Todo has been successfully moved to the trash can!'
     );

     return $this->redirectToRoute('todo_list');
   }

   /**
    * @Route("/trash", name="trash_list")
    */
    public function listTrashAction()
    {
      $trash = $this->getDoctrine()
                    ->getRepository('AppBundle:Trash')
                    ->findBy(array(), array('todoCreateDate' => 'DESC'));

      return $this->render('todo/trash.html.twig', array(
        'trash' => $trash
      ));
    }

    /**
     * @Route("/restore/{id}", name="trash_restore")
     */
     public function restoreTodoAction($id)
     {
       $em = $this->getDoctrine()->getManager();
       $trash = $em->getRepository('AppBundle:Trash')->find($id);

       $todo = new Todo;
       $todo->setName($trash->getTodoName());
       $todo->setPriority($trash->getTodoPriority());
       $todo->setDescription($trash->getTodoDescription());
       $todo->setDueDate($trash->getTodoDueDate());
       $todo->setCreateDate($trash->getTodoCreateDate());

       $em->persist($todo);
       $em->remove($trash);
       $em->flush();

       $this->addFlash(
         'notice',
         'The Todo has been successfully restored!'
       );

       return $this->redirectToRoute('trash_list');
     }

   /**
    * @Route("/remove/{id}", name="trash_remove")
    */
    public function removeTodoAction($id)
    {
      $em = $this->getDoctrine()->getManager();
      $trash = $em->getRepository('AppBundle:Trash')->find($id);

      $em->remove($trash);
      $em->flush();

      $this->addFlash(
        'notice',
        'The Todo has been permanently removed!'
      );

      return $this->redirectToRoute('trash_list');
    }

   /**
    * @Route("/signup", name="user_create")
    */
    public function createUserAction($id)
    {
      return ;
    }

    /**
     * @Route("/login", name="user_login")
     */
     public function loginAction($id)
     {
       return ;
     }
}
