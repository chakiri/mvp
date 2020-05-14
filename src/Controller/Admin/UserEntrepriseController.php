<?php
namespace App\Controller\Admin;
use App\Service\TopicHandler;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use App\Entity\User;
use App\Entity\Profile;
use App\Repository\UserRepository;
use App\Repository\UserTypeRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Service\Email;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;



class UserEntrepriseController extends EasyAdminController
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    private $userTypeRepo;
    private $userRepo;
    private $topicHandler;
    private $email;
    private $token;

  
    public function __construct(UserPasswordEncoderInterface $passwordEncoder, UserTypeRepository $userTypeRepo, UserRepository $userRepo, TopicHandler $topicHandler, Email $email, TokenGeneratorInterface $tokenGenerator)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->userTypeRepo = $userTypeRepo;
        $this->userRepo = $userRepo;
        $this->topicHandler = $topicHandler;
        $this->email = $email;
        $this->token = $tokenGenerator->generateToken();
    }
    
    public function persistUserEntrepriseEntity($user)
    {
        $currentUser = $this->getUser();
        $type = $this->userTypeRepo->findOneBy(['id'=> 6]);
        $user->setStore($currentUser->getStore());
        if($currentUser->getCompany()!= null )
        {
            $user->setCompany($currentUser->getCompany());
        }

        $user->setRoles(['ROLE_USER']);
        $user->setType($type);

        $this->updatePassword($user);
        $user->setResetToken($this->token);
        parent::persistEntity($user);

        $profile = new Profile();
        $profile->setUser($user);
        parent::persistEntity($profile);

        /* add admin topics to user */
        $this->topicHandler->addAdminTopicsToUser($user);
        /* add company topic to user */
        $this->topicHandler->initCompanyTopic($currentUser->getCompany(), $user);
        /* add category company topic to user */
        $this->topicHandler->initCategoryCompanyTopic($currentUser->getCompany()->getCategory(), $user);

        /*send email confirmation*/
        $url = $this->generateUrl('security_new_account', ['token' => $this->token], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->email->send($this->token, $url, $user,'createNewAccount.html.twig','Bienvenu à Beevup');

    }

    public function updateUserEntrepriseEntity($user)
    {
        $currentUser = $this->getUser();
        $user->setStore($currentUser->getStore());
        if($currentUser->getCompany()!= null )
        {
            $user->setCompany($currentUser->getCompany());
        }


        $this->updatePassword($user);
        parent::updateEntity($user);
        
    }

    public function updatePassword(User $user)
    {   
        if (!empty($user->getPassword()) && strlen($user->getPassword())< 50) {
             $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
        }
    }
    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null)
    {
        $company = $this->getUser()->getCompany();
        $user = parent::getUser();
        $user = $user->getRoles();
        $dqlFilter = sprintf('entity.company = %s', $company->getId());
        $list = parent::createListQueryBuilder($entityClass, $sortDirection, $sortField, $dqlFilter);
    
    
        return $list;
    }
}