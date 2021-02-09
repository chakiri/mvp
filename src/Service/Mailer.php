<?php

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\CreateContact;
use SendinBlue\Client\Model\SendSmtpEmail;
use SendinBlue\Client\Model\UpdateContact;
use Twig\Environment;


class Mailer
{
    private $templating;

    private $logger;

    private $tempates_ids;

    public function __construct(Environment $templating, LoggerInterface $mailerLogger)
    {
        $this->templating = $templating;
        $this->logger = $mailerLogger;
        $this->tempates_ids = $this->getTemplatesIds();
    }

    //Get config From Api Key
    private function getConfig()
    {
        return Configuration::getDefaultConfiguration()->setApiKey('api-key', $_ENV['SENDINBLUE_API_KEY']);
    }

    //Function to send emails wth Sendinblue templates
    public function sendEmailWithTemplate($email, ?array $params, string $templateIdName): void
    {
        $config = $this->getConfig();

        $apiInstance = new TransactionalEmailsApi(new Client(), $config);

        $sendSmtpEmail = new SendSmtpEmail();

        $sendSmtpEmail['sender'] = ['name' => $_ENV['DEFAULT_EMAIL_NAME'], 'email' => $_ENV['DEFAULT_EMAIL']];
        $sendSmtpEmail['to'] = [['email' => $email]];
        $sendSmtpEmail['templateId'] = $this->tempates_ids[$templateIdName];
        $sendSmtpEmail['params'] = $params;

        try {
            $apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    //Get list of all contacts on SendinBlue Api
    public function getContacts()
    {
        $config = $this->getConfig();

        $apiInstance = new ContactsApi(new Client(), $config);

        try {
            return $apiInstance->getContacts();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    //Create a contact on Sendinblue Api
    public function createContact($email, $listId): void
    {
        $config = $this->getConfig();

        $apiInstance = new ContactsApi(new Client(), $config);

        $createContact = new CreateContact();
        $createContact['email'] = $email;
        $createContact['listIds'] = [$listId];

        try {
            $apiInstance->createContact($createContact);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    //Update contact attributes
    public function updateContact($email, array $attributes): void
    {
        $config = $this->getConfig();

        $apiInstance = new ContactsApi(new Client(), $config);

        $updateContact= new UpdateContact();

        $updateContact['attributes'] = $attributes;

        try {
            $apiInstance->updateContact($email, $updateContact);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    //Check if contact exist on Api
    public function isContact($email): bool
    {
        $config = $this->getConfig();

        $apiInstance = new ContactsApi(new Client(), $config);

        try {
            $apiInstance->getContactInfo($email);
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    //Function to send emails by Sendinblue SMTP with Twig templates
    public function sendEmailWithInternTemplate($subject, $email, array $content, $template): void
    {
        $config = $this->getConfig();

        $apiInstance = new TransactionalEmailsApi(new Client(), $config);

        $sendSmtpEmail = new SendSmtpEmail();

        $sendSmtpEmail['subject'] = $subject;
        $sendSmtpEmail['sender'] = ['name' => $_ENV['DEFAULT_EMAIL_NAME'], 'email' => $_ENV['DEFAULT_EMAIL']];
        $sendSmtpEmail['to'] = [['email' => $email]];
        $sendSmtpEmail['htmlContent'] = $this->templating->render('emails/'.$template, $content);

        try {
            $apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function getTemplatesIds(): array
    {
        if ($_ENV['APP_ENV'] === 'dev'){
            $templates_ids = [
                'password_forgotten' => 3,
                'confirm_inscription' => 2,
                'welcome_message' => 4,
                'recommandation' => 5,
                'daily_chat' => 10,
                'first_message' => 7,
                'sponsorship' => 6,
                'inscription_invitation' => 9,
                'recap_becontacted' => 8,
            ];
        }elseif ($_ENV['APP_ENV'] === 'prod') {
            $templates_ids = [
                'password_forgotten' => 36,
                'confirm_inscription' => 35,
                'welcome_message' => 34,
                'recommandation' => 33,
                'daily_chat' => 32,
                'first_message' => 31,
                'sponsorship' => 30,
                'inscription_invitation' => 29,
                'recap_becontacted' => 28,
            ];
        }

        return $templates_ids;
    }
}