<?php

namespace App\Controller;

use App\Entity\Favorit;
use App\Repository\UserRepository;
use App\Repository\FavoritRepository;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class FavoritController extends AbstractController
{

    /**
     * @Route("/favorit/add/{userId}", name="favorit_add")
     */

     public function addFavorit(Request $request, EntityManagerInterface $manager,UserRepository $userRepo, $userId)
     {
        $user =  $userRepo->findOneBy(['id' => $userId]);
        $favorit =  new Favorit();
        $favorit->setUser($this->getUser())
                ->setFavoritUser($user);
        $manager->persist($favorit);
        $manager->flush();
        $response = new Response(
              'Content',
              Response::HTTP_OK,
              ['content-type' => 'text/html']
              );
              return $response;
     }

         /**
          * @Route("/favorit/delete/{userId}", name="favorit_delete")
          */

        public function deleteFavorit(Request $request, EntityManagerInterface $manager,FavoritRepository $favoritRepo, $userId)
        {

         $favorit =  $favoritRepo->findOneBy(['user' => $this->getUser(), 'favoritUser' => $userId]);
         $manager->remove($favorit);
         $manager->flush();
         $response = new Response(
                       'Content',
                       Response::HTTP_OK,
                       ['content-type' => 'text/html']
                       );
                       return $response;

        }
         /**
          * @Route("/favoritCompany/add/{companyId}", name="favorit_company_add")
          */

          public function addFavoritCompany(Request $request, EntityManagerInterface $manager,UserRepository $userRepo, CompanyRepository $companyRepo,  $companyId)
          {
             $company =  $companyRepo->findOneBy(['id' => $companyId]);
             $administratorEmail = $company->getEmail();
             $user = $userRepo->findOneBy(['email' => $administratorEmail]);
             $favorit =  new Favorit();
             $favorit->setUser($this->getUser())
                     ->setFavoritUser($user)
                     ->setCompany($company);
             $manager->persist($favorit);
             $manager->flush();
             $response = new Response(
                   'Content',
                   Response::HTTP_OK,
                   ['content-type' => 'text/html']
                   );
                   return $response;
          }

              /**
               * @Route("/favoritCompany/delete/{companyId}", name="favorit_company_delete")
               */

             public function deleteFavoritCompany(Request $request, EntityManagerInterface $manager,FavoritRepository $favoritRepo, $companyId)
             {

              $favorit =  $favoritRepo->findOneBy(['user' => $this->getUser(), 'company' => $companyId]);
              $manager->remove($favorit);
              $manager->flush();
              $response = new Response(
                            'Content',
                            Response::HTTP_OK,
                            ['content-type' => 'text/html']
                            );
                            return $response;

             }


}