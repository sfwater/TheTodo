<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Todo;

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
                  ->findByDeleted('0', array('dueDate' => 'ASC'));

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
        $todo->setTrashedDate($now);


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
}
