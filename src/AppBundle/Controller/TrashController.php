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
             $user = $this->get('security.token_storage')->getToken()->getUser();

             $trash = $this->getDoctrine()
                           ->getRepository('AppBundle:Todo')
                           ->findBy(
                                array('deleted' => 1, 'user_id' => $user->getId()),
                                array('trashedDate' => 'DESC')
                           );

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
                   return $this->render('trash/trash.html.twig', array(
                       'trash' => $trash
                   ));
             }
       }

      /**
       * @Route("/delete/{id}", name="trash_add")
       */
       public function moveToTrashAction($id)
       {
             $user = $this->get('security.token_storage')->getToken()->getUser();

             $em = $this->getDoctrine()->getManager();
             $todo = $em->getRepository('AppBundle:Todo')->find($id);

             if($todo->getUserId() == $user->getId())
             {
                 $now = new\DateTime('now');
                 $todo->setDeleted('1');
                 $todo->setTrashedDate($now);

                 $em->flush();

                 $this->addFlash(
                     'notice',
                     'The Todo has been successfully moved to the trash can!'
                 );
             }
             else
             {
                 $this->addFlash('error', 'Access denied!');
             }

             return $this->redirectToRoute('todo_list');
       }

        /**
         * @Route("/restore/{id}", name="trash_restore")
         */
         public function restoreTodoAction($id)
         {
               $user = $this->get('security.token_storage')->getToken()->getUser();

               $em = $this->getDoctrine()->getManager();
               $todo = $em->getRepository('AppBundle:Todo')->find($id);

               if($todo->getUserId() == $user->getId())
               {
                   $todo->setDeleted('0');

                   $em->flush();

                   $this->addFlash(
                        'notice',
                        'The Todo has been successfully restored!'
                   );
               }
               else
               {
                   $this->addFlash('error', 'Access denied!');
               }

               return $this->redirectToRoute('trash_list');
         }

         /**
          * @Route("/remove/{id}", name="trash_remove")
          */
          public function removePermanentlyAction($id)
          {
                $user = $this->get('security.token_storage')->getToken()->getUser();

                $em = $this->getDoctrine()->getManager();
                $todo = $em->getRepository('AppBundle:Todo')->find($id);

                if($todo->getUserId() == $user->getId())
                {
                    $em->remove($todo);
                    $em->flush();

                    $this->addFlash(
                          'notice',
                          'The Todo has been permanently removed!'
                    );
                }
                else
                {
                    $this->addFlash('error', 'Access denied!');
                }

                return $this->redirectToRoute('trash_list');
          }
}
