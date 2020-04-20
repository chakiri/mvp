<?php

namespace App\Controller;

use App\Repository\RecommandationRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Profile;
use App\Form\ProfileType;

class ProfileController extends AbstractController
{
    /**
     * @Route("/account/{id}", name="profile_show")
     */
    public function show(Profile $profile, ServiceRepository $serviceRepository, RecommandationRepository $recommandationRepository)
    {
        $services = $serviceRepository->findBy(['user' => $profile->getUser()], ['createdAt' => 'DESC']);

        $allRecommandations = [];
        //dd($services);
        foreach ($services as $service){
            $recommandations = $recommandationRepository->findBy(['service' => $service]);
            if ($recommandations)
                $allRecommandations = array_merge($allRecommandations, $recommandations);
        }

        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
            'services' => array_slice($services, 0, 3),
            'countServices' => count($services),
            'recommandations' => $allRecommandations
        ]);
    }

    /**
     * @Route("/account/{id}/edit", name="profile_edit")
     */
    public function form(Profile $profile, EntityManagerInterface $manager, Request $request)
    {
        $form = $this->createForm(ProfileType::class, $profile);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $profile->setIsCompleted(true);

            $manager->persist($profile);
            $manager->flush();

            return $this->redirectToRoute('profile_show', [
               'id' => $profile->getId()
            ]);
        }

        return $this->render('profile/form.html.twig', [
            'EditProfileForm' => $form->createView(),
        ]);
    }
}
