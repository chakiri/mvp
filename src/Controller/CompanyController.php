<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Service\InitTopic;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Form\CompanyType;
use App\Repository\RecommandationRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\BarCode;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class CompanyController extends AbstractController
{
    private $barCode;

    public function __construct(BarCode $barCode)
    {

        $this->barCode = $barCode;

    }


    /**
     * @Route("/company/{slug}", name="company_show")
     */
    public function show(Company $company, RecommandationRepository $recommandationRepository, UserRepository $userRepo, ServiceRepository $servicesRepo)
    {
        $recommandations = $recommandationRepository->findBy(['company' => $company, 'status'=>'Validated']);
        $users = $userRepo->findBy(['company' => $company]);

        $score = 0;
        foreach ($users as $user){
            if ($user->getScore()) $score += $user->getScore()->getPoints();
        }

        $services = $company->getServices()->toArray();

        return $this->render('company/show.html.twig', [
            'company' => $company,
            'recommandations'=> $recommandations,
            'users' => $users,
            'countServices' => count($services),
            'services' => array_slice($services, 0, 3),
            'score' => $score
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN_COMPANY")
     * @Route("/company/{id}/edit", name="company_edit")
     */
    public function edit(Company $company, EntityManagerInterface $manager, Request $request, InitTopic $initTopic, $id)
    {

        if($id == $this->getUser()->getCompany()->getId())
        {



        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form['imageFile']->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename =  $originalFilename;
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('entreprise_logos'),
                        $newFilename
                    );

                } catch (FileException $e) {}
                $company->setLogo($newFilename);
            }

            $company->setIsCompleted(true);
            /*** generate bar code*/
             $company->setBarCode($this->barCode->generate($company->getId()));
            /**end ******/
            $manager->persist($company);

            $manager->flush();

            //init topic company category to user
            $initTopic->init($company->getCategory());

           return $this->redirectToRoute('company_show', [
               'slug' => $company->getSlug()
           ]);

        }
        return $this->render('company/form.html.twig', [
            'company' => $company,
            'EditCompanyForm' => $form->createView(),
        ]);
        }
        else{
            return $this->redirectToRoute('page_not_found', []);
        }
    }
}
