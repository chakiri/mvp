<?php


namespace App\Service;
use App\Repository\TypeServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;


class Email
{
    private $mailer;
    private $templating;

    public function __construct(\Swift_Mailer $mailer, \Twig\Environment $templating)
    {

        $this->mailer     = $mailer;
        $this->templating = $templating;

    }
   public function send($token,$url, $user, $template, $subject='Mail de confirmation')
    {



        $message = (new \Swift_Message($subject))
            ->setFrom($_ENV['DEFAULT_EMAIL'])
            ->setTo($user->getEmail())
            ->setBody(
                $this->templating->render(
                    'emails/'.$template,
                    [    'url' => $url,
                        'user'=> $user
                    ]
                ),

                'text/html'
            )
        ;
        $this->mailer->send($message);
    }
}