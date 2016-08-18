<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Todo;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TrashController extends Controller
{
  /**
   * @Route("/trash", name="trash_list")
   */
   public function listTrashAction()
   {
     $trash = $this->getDoctrine()
                   ->getRepository('AppBundle:Todo')
                   ->findByDeleted('1', array('trashedDate' => 'DESC'));

     if(empty($trash))
     {
       $this->addFlash(
         'notice',
         'The trash can is empty!'
       );
       return $this->redirectToRoute('todo_list');
     }
     else
     {
       return $this->render('todo/trash.html.twig', array(
         'trash' => $trash
       ));
     }
   }

  /**
   * @Route("/delete/{id}", name="trash_add")
   */
   public function moveToTrashAction($id)
   {
     $em = $this->getDoctrine()->getManager();
     $todo = $em->getRepository('AppBundle:Todo')->find($id);

     $now = new\DateTime('now');

     $todo->setDeleted('1');
     $todo->setTrashedDate($now);

     $em->flush();

     $this->addFlash(
       'notice',
       'The Todo has been successfully moved to the trash can!'
     );

     return $this->redirectToRoute('todo_list');
   }

    /**
     * @Route("/restore/{id}", name="trash_restore")
     */
     public function restoreTodoAction($id)
     {
       $em = $this->getDoctrine()->getManager();
       $todo = $em->getRepository('AppBundle:Todo')->find($id);

       $todo->setDeleted('0');

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
      public function removePermanentlyAction($id)
      {
        $em = $this->getDoctrine()->getManager();
        $todo = $em->getRepository('AppBundle:Todo')->find($id);

        $em->remove($todo);
        $em->flush();

        $this->addFlash(
          'notice',
          'The Todo has been permanently removed!'
        );

        return $this->redirectToRoute('trash_list');
      }
}
