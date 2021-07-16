<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Service;
use App\Form\HomeSearchType;
use App\Form\SearchStoreType;
use App\Repository\CommuneRepository;
use App\Repository\CompanyRepository;
use App\Repository\ExpertMeetingRepository;
use App\Repository\RecommandationRepository;
use App\Repository\ServiceRepository;
use App\Repository\StoreRepository;
use App\Repository\StoreServicesRepository;
use App\Service\Communities;
use App\Service\GetCompanies;
use App\Service\Search\InfoSearch;
use App\Service\Search\SearchHandler;
use App\Service\ServiceSetting;
use App\Service\Session\ExternalStoreSession;
use App\Service\Utility\likeMatch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @Route("/extern")
 */
class ExternController extends AbstractController
{
    /**
     * @Route("/search", name="extern_search")
     */
    public function externSearch(Request $request, SearchHandler $searchHandler, ServiceSetting $serviceSetting)
    {
        $services = $searchHandler->getResultsExtern('');

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

        $labeled = $serviceSetting->isLabeledServices($services);

        return $this->render("extern/search.html.twig", [
            'form' => $form->createView(),
            'results' => $services,
            'labeled' => $labeled,
        ]);
    }

    /**
     * @Route("/api/communes", name="extern_api_communes", methods="GET", options={"expose"=true})
     */
    public function getCommunes(Request $request, CommuneRepository $communeRepository)
    {
        $query = $request->get('query');

        $communes = $communeRepository->getCommunes($query, 20);

        $communesData = [];
        foreach ($communes as $commune){
            $communesData[] = [
                'value' => $commune->getPostalCode() . ' - ' . $commune->getName(),
                'data' => $commune,
            ];
        }

        return $this->json($communesData, 200);
    }

    /**
     * @Route("/api/locate", name="extern_api_locate", methods="GET", options={"expose"=true})
     */
    public function getInfoLocate(Request $request, HttpClientInterface $client, likeMatch $likeMatch)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        $url = 'https://geo.api.gouv.fr/communes?lat=' . $lat . '&lon=' . $lon;

        $result = null;
        if ($lat && $lon){
            $response = $client->request(
                'GET',
                $url
            );

            $result = $response->toArray()[0];
        }

        return $this->json($result, 200);
    }

    /**
     * @Route("/service/{id}", name="extern_service")
     */
    public function externService(Service $service, StoreServicesRepository $storeServicesRepository, RecommandationRepository $recommandationRepository, ExpertMeetingRepository $expertMeetingRepository, ServiceRepository $serviceRepository)
    {
        //Get store Service if it's an association
        $storeService = $storeServicesRepository->findOneBy(['store' => $this->getUser()->getStore(), 'service' => $service]);

        $recommandations = $recommandationRepository->findBy(['service' => $service, 'status'=>'Validated']);

        //Get expert Meeting
        $expertMeeting = $expertMeetingRepository->getLastsExpertMeeting(1);

        $similarServices = $serviceRepository->findAllByCategory($service->getCategory(), $service->getId());

        return $this->render("extern/service.html.twig", [
            'service' => $service,
            'storeService' => $storeService,
            'recommandations'=> $recommandations,
            'expertMeeting'=> $expertMeeting[0],
            'similarServices'=> $similarServices,
        ]);
    }

    /**
     * A supprimer
     *
     * @Route("/company/{id}", name="extern_company")
     */
    public function externCompany(Company $company, ExpertMeetingRepository $expertMeetingRepository, RecommandationRepository $recommandationRepository)
    {
        //Get expert Meeting
        $expertMeeting = $expertMeetingRepository->getLastsExpertMeeting(1);

        $recommandations = $recommandationRepository->findByCompanyWithoutServices($company, 'Validated');

        $services = $company->getServices()->toArray();

        return $this->render("extern/company.html.twig", [
            'company' => $company,
            'services' => array_slice($services, -4, 4),
            'recommandations'=> $recommandations,
            'expertMeeting'=> $expertMeeting[0],
        ]);
    }

    /**
     * A supprimer
     *
     * @Route("/all/category", name="extern_all_category")
     */
    public function externAllCategories(Request $request)
    {
        return $this->render("extern/all_categories.html.twig");
    }

    /**
     * A supprimer
     *
     * @Route("/region", name="extern_region")
     */
    public function externRegion(Request $request)
    {
        return $this->render("extern/region.html.twig");
    }

    /**
     * @Route("/homestore", name="homestore", options={"expose"=true})
     */
    public function homeStore(StoreRepository $storeRepository, Communities $communities, ExternalStoreSession $externalStoreSession, Request $request, ServiceRepository $serviceRepository, SearchHandler $searchHandler, CompanyRepository $companyRepository, GetCompanies $getCompanies, InfoSearch $infoSearch, ServiceSetting $serviceSetting)
    {
        //Get store if passed in parameter
        if ($request->get('store'))  $store = $storeRepository->findOneBy(['reference' => $request->get('store')]);

        //Get localisation if passed in parameter
        if ($request->get('locate'))  $locate = $request->get('locate');

        //If not store in params
        if (!isset($store)){
            //Get all stores
            $stores = $storeRepository->getAllStores();

            //If not locate neither
            if (!isset($locate)){
                return $this->render("default/home_store.html.twig", [
                    'store' => null,
                    'stores' => $stores
                ]);
            }

            //Get lat & lon from locate
            $locate = explode(',', $locate);

            //Get closer store form geo-localisation
            $store = $communities->getCloserStore($stores, $locate[0], $locate[1]);
        }

        //Set store ref in session
        $externalStoreSession->setReference($store);

        //Get local services of store
        $allCompanies = $getCompanies->getAllCompanies($store);
        $services = $serviceRepository->findByLocalServicesWithLimit($allCompanies, 12);
        $companies = $companyRepository->findBySearch('', $allCompanies);

        //Get search form
        $form = $this->createForm(SearchStoreType::class, null, ['store' => $store]);

        $form->handleRequest($request);

        //If search
        if ($form->isSubmitted() && $form->isValid()){

            //Get results from searching
            $results = $searchHandler->getResultsExtern($allCompanies, $form->get('querySearch')->getData());

            //Get infos from each company
            $infos = $infoSearch->getInfosCompanies($results);

            //Options rediredct
            $options = [
                'query' => $form->get('querySearch')->getData(),
                'results' => $results,
                'nbRecommandationsCompanies' => $infos['nbRecommandations'],
                'distancesCompanies' => $infos['distances'],
                'store' => $store,
            ];

            return $this->render("search/external/search.html.twig", $options);
        }

        //Get infos of services
        $infosServices = $serviceSetting->getInfosServices($services, $store);

        //Get infos of companies
        $infosCompanies = $infoSearch->getInfosCompanies($companies, $store);

        //Render options
        $options = [
            'form' => $form->createView(),
            'store' => $store,
            'stores' => $stores = $storeRepository->getAllStores(),
            'companies' => $companies,
            'services' => $services,
            'nbRecommandationsServices' => $infosServices['nbRecommandations'],
            'distancesServices' => $infosServices['distances'],
            'nbRecommandationsCompanies' => $infosCompanies['nbRecommandations'],
            'distancesCompanies' => $infosCompanies['distances'],
        ];

        return $this->render("default/home_store.html.twig", $options);
    }
}