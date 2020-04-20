<?php

namespace App\Controller;

use App\Entity\DashboardNotification;
use App\Entity\Post;
use App\Entity\PostLike;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\DashboardNotificationRepository;
use App\Repository\PostLikeRepository;
use App\Repository\RecommandationRepository;
use App\Repository\PostRepository;
use App\Repository\AbuseRepository;
use Symfony\Component\HttpFoundation\Response;



class PostController extends AbstractController
{

   /**
     * @Route("/post/create", name="post_create")
     */
    public function create(Request $request, EntityManagerInterface $manager){
      $post = new Post();
      $post->setUser($this->getUser());
      $form = $this->createForm(PostType::class, $post);
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {

          $manager->persist($post);
          $manager->flush();
          $this->addFlash('success', 'Le post a bien été publié. !');
          return $this->redirectToRoute('dashboard', [
             'id' => $post->getId()
          ]);
      }
      return $this->render('post/create.html.twig', [
          'PostForm' => $form->createView(),
         
      ]);
  }



    /**
    * @Route("/post", name="post")
    */

    public function index()
    {
        return $this->render('post/show.html.twig', [
            'controller_name' => 'PostController',
        ]);
    }

    /**
    * @Route("/post/{slug}", name="post_show")
    */

    public function show()
    {
        return $this->render('post/show.html.twig', [
            
        ]);
    }

    /**
    * @Route("/post/{id}/edit", name="post_edit")
    */

    public function edit(Post $post, EntityManagerInterface $manager, Request $request, PostRepository $repository)
    {
        
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            $manager->persist($post);
            $manager->flush();

           return $this->redirectToRoute('post_show', [
               
           ]);

        }
        return $this->render('post/form.html.twig', [
            'company' => $post,
            'EditPostorm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/post/{id}/delete", name="post_delete")
     */

    public function delete(Request $request, EntityManagerInterface $manager, PostRepository $postRepository,PostLikeRepository $postLikeRepository,CommentRepository $commentRepository,DashboardNotificationRepository $dashboardNotificationRepo, AbuseRepository $abuseRepo, $id)
    {
     $post = $postRepository->findOneByID($id);
    
    
     $postLikes = $postLikeRepository->findByPost($post);
     $comments = $commentRepository->findByPost($post);
     $dashboardNotifications = $dashboardNotificationRepo->findByPost($post);
     $abuses = $abuseRepo->findByPost($post);
     
     foreach ($postLikes as $object) {
        $manager->remove($object);
     }
     foreach ($abuses as $object) {
        $manager->remove($object);
     }

     foreach ($comments as $object) {
        $abuses = $abuseRepo->findByComment($object);
        if($abuses !=null ){
        foreach ($abuses as $obj) {
        $manager->remove($obj);
        }
    }
        $manager->remove($object);
     }
     foreach ($dashboardNotifications as $object) {
        $manager->remove($object);
     }
     
     $manager->remove($post);
     $manager->flush();
     $response = new Response(
        'Content',
        Response::HTTP_OK,
        ['content-type' => 'text/html']
    );
    return $response;

    }
    
    /**
    * @Route("/post/{id}/update-post-likes/{variable}", name="post_update_likes_number")
    */
    public function updateLikesNumber(Post $post, EntityManagerInterface $manager, Request $request, PostRepository $repository, PostLikeRepository $postLikeRepository, DashboardNotificationRepository $dashboardNotificationRepo, $variable)
    {
        $like = new PostLike();
        $user = $this->getUser();
        $dashboardNotification = new DashboardNotification();
        if($variable == 'add'){
          $likesNumber = $post->getLikesNumber() + 1 ;
          $dashboardNotification->setType('like');
          $dashboardNotification->setSeen(0);
          $dashboardNotification->setPost($post);
          $dashboardNotification->setUser($this->getUser());
          $dashboardNotification->setOwner($post->getUser());
        } else{
            $likesNumber = $post->getLikesNumber() - 1 ;
        }
        
        
        $post->setLikesNumber($likesNumber);
        $manager->persist($post);
        if($variable == 'add'){
            $like->setUser( $user);
            $like->setPost($post);
            $manager->persist($like);
            $manager->persist($dashboardNotification);
        }
        else{
            $type ='like';
            $like =  $postLikeRepository->findOneByPostAndUser($user, $post);
            $dashboardNotification = $dashboardNotificationRepo->findOneByPostAndUser($user, $post, $type);
            $manager->remove($dashboardNotification);
            $manager->remove($like);
        }
        $manager->flush();
        $response = new Response(
            'Content',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
        return $response;
        
    }




}
