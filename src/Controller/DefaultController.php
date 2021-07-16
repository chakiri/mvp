<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Store;
use App\Entity\Profile;
use App\Form\CompanyImageType;
use App\Form\HomeSearchType;
use App\Form\ProfileImageType;
use App\Form\SearchStoreType;
use App\Form\StoreImageType;
use App\Repository\CompanyRepository;
use App\Repository\StoreRepository;
use App\Service\Communities;
use App\Service\Error\Error;
use App\Service\GetCompanies;
use App\Service\ImageCropper;
use App\Service\Search\InfoSearch;
use App\Service\Search\SearchHandler;
use App\Service\ServiceSetting;
use App\Service\Session\ExternalStoreSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ServiceRepository;
use Symfony\Component\HttpFoundation\Response;


class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function homePage(Request $request, SearchHandler $searchHandler, ServiceSetting $serviceSetting, CompanyRepository $companyRepository, ServiceRepository $serviceRepository)
    {

        //Get search form
        $form = $this->createForm(HomeSearchType::class, null);

        $form->handleRequest($request);

        //If search
        if ($form->isSubmitted() && $form->isValid()){

            //Get results from searching
            $services = $searchHandler->getResultsExtern($form->get('query')->getData(), $form->get('lat')->getData(), $form->get('lon')->getData());

            $distances = $serviceSetting->getDistancesServicesWithLatLon($services, $form->get('lat')->getData(), $form->get('lon')->getData());
            $locations = $serviceSetting->getCityServices($services);
            $labeled = $serviceSetting->isLabeledServices($services);

            //Options redirect
            $options = [
                'form' => $form->createView(),
                'query' => $form->get('query')->getData(),
                'results' => $services,
                'distances' => $distances,
                'locations' => $locations,
                'labeled' => $labeled,
            ];
            return $this->render("extern/search.html.twig", $options);
        }

        $allCompanies = $companyRepository->getAllCompanies();
        $allServices = $serviceRepository->findAll();

        return $this->render('extern/home_page.html.twig', [
            'form' => $form->createView(),
            'countAllCompanies' => count($allCompanies),
            'countServices' => count($allServices)
        ]);
    }

    /**
     * @Route("/map", name="map")
     */
    public function getClients(StoreRepository $storeRepository, CompanyRepository $companyRepository)
    {
       $stores = $storeRepository->findAll();
       $companies =$companyRepository->findAll();

       $allStores = null;
       $allCompanies= null;

       foreach ($stores as $store) {
          if($store->getLatitude() != null && $store->getLongitude() != null ) {
              $adresse = $store->getAddressNumber().' '. $store->getAddressStreet(). ' '.$store->getAddressPostCode();
              $allStores = $allStores . "{\"name\": \"" . $store->getName() . "\", \"lat\": \"" . $store->getLatitude() . "\",\"lng\": \"" . $store->getLongitude() . "\",\"adress\": \"" . $adresse . "\" },";
          }

       }
        foreach ($companies as $company) {
            if($company->isValid() === true && $company->getLatitude() != null && $company->getLongitude() != null ) {
                $adresse = $company->getAddressNumber().' '. $company->getAddressStreet(). ' '.$company->getAddressPostCode();
                $allCompanies = $allCompanies. "{\"name\": \"" . $company->getName() . "\", \"lat\": \"" . $company->getLatitude() . "\",\"lng\": \"" . $company->getLongitude() . "\",\"adress\": \"" . $adresse . "\" },";
            }
        }
        $all = $allStores. $allCompanies ;

        $storesJson = rtrim($all, ",");
        return  new Response(
            '{
                       "stores" : [
                        '.$storesJson.'
                      ]
                    }',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }

    /**
     * @Route("/company/{id}/updateCompanyImage", name="company_update_image")
     * @Route("/store/{id}/updateStoreImage", name="store_update_image")
     * @Route("/account/{id}/updateProfileImage", name="profile_update_image")
     */
    public function updateImageForm(Request $request,EntityManagerInterface $manager,  Company $company = null, Store $store = null, Profile $profile = null, ImageCropper $imageCropper,  Error $error)
    {
        if ( $request->get('_route') == 'company_update_image') {
            $formType = CompanyImageType::class;
            $entity = $company;
        }
        if ( $request->get('_route') == 'store_update_image'){
            $formType = StoreImageType::class;
            $entity = $store;
        }
        if ( $request->get('_route') == 'profile_update_image'){
            $formType = ProfileImageType::class;
            $entity = $profile;
        }

        $form = $this->createForm( $formType,  $entity);
        $form->handleRequest($request);

        if($form->isSubmitted()) {
            if ($form->get('imageFile')->isValid()) {
                $imageCropper->move_directory( $entity);
                $manager->persist( $entity);
                $manager->flush();
                return new JsonResponse([
                    'message' => 'Votre photo a été bien modifier'
                ]);
            }else{
                return new JsonResponse( array(
                    'result' => 0,
                    'message' => 'Invalid form',
                    'data' => $error->getErrorMessages($form)
                ));
            }
        }else {
            return $this->render('default/modals/upload/updateImage.html.twig', [
                'ImageForm' => $form->createView(),
                'entity' =>  $entity,
            ]);
        }
    }

}
