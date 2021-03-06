<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/10/17
 * Time: 16:07
 * PHP version 7
 */
namespace Controller;

use Model\AddressManager;
use Model\Event;
use Filter\Text;
use Model\EventManager;
use Model\PictureManager;
use \Swift_Mailer;
use \Swift_Message;
use \Swift_SmtpTransport;

/**
 * Class HomeController
 *
 */
class HomeController extends AbstractController
{
    /**
     * @param array $userData
     * @return array
     */
    private function verifMail(array $userData): array
    {
        $errorsForm = [];
        $carConformity = "#[a-zA-ZÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ' ]$#";
        $numberConformity = " #^[0-9]{2}[-/ ]?[0-9]{2}[-/ ]?[0-9]{2}[-/ ]?[0-9]{2}[-/ ]?[0-9]{2}?$# ";
        if (empty($userData['lastname'])) {
            $errorsForm['lastname'] = "Votre nom doit être indiqué";
        } elseif (!preg_match($carConformity, $userData['lastname'])) {
            $errorsForm['lastname'] = "Votre nom ne doit pas contenir de caractères spéciaux";
        }
        if (empty($userData['firstname'])) {
            $errorsForm['firstname'] = "Votre prénom doit être indiqué";
        } elseif (!preg_match($carConformity, $userData['firstname'])) {
            $errorsForm['firstname'] = "Votre prénom ne doit pas contenir de caractères spéciaux";
        }
        if (empty($userData['email'])) {
            $errorsForm['email'] = "Votre mail doit être indiqué";
        } elseif (!preg_match(" /^.+@.+\.[a-zA-Z]{2,}$/ ", $userData['email'])) {
            $errorsForm['email'] = "Le format de l'email n'est pas correct";
        }
        if (empty($userData['num'])) {
            $errorsForm['num'] = "Votre numéro de téléphone doit être indiqué";
        } elseif (!preg_match($numberConformity, $userData['num'])) {
            $errorsForm['num'] = "Le numéro de téléphone renseigné est incorrect";
        }
        if (empty($userData['message'])) {
            $errorsForm['message'] = "Vous devez écrire un message";
        } elseif (!preg_match($carConformity, $userData['message'])) {
            $errorsForm['message'] = "Votre message ne doit pas contenir de caractère non-autorisés";
        }

        return $errorsForm;
    }

    /**
     * @param array $userData
     * @return string
     */
    private function sendMail(array $userData): string
    {
        $transport = (new Swift_SmtpTransport('smtp.gmail.com', 465))
            ->setUsername(APP_MAIL_USERNAME)
            ->setPassword(APP_MAIL_PASSWORD)
            ->setEncryption(APP_MAIL_ENCRYPTION);
        $mailer = new Swift_Mailer($transport);
        $message = new Swift_Message();
        $message->setSubject('Message formulaire aslo45');
        $message->setFrom([$userData['email'] => $userData['lastname'] . ' ' . $userData['firstname']]);
        $message->addTo(APP_MAIL_ADDTO, 'recipient name');
        $message->addReplyTo($userData['email'], $userData['email']);
        $message->setBody($userData['message']);
        $result = $mailer->send($message);
        return $result;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function index()
    {

        $addressManager = new AddressManager($this->getPdo());
        $addreses = $addressManager->selectAll();
        $eventHomes = new EventManager($this->getPdo());
        $importantEvents = $eventHomes->selectViewed();
        $pictureManager = new PictureManager($this->getPdo());
        $pictures = $pictureManager->selectPictureHomeAll();
        $coords = [];

        foreach ($addreses as $address) {
            $addressInfos = $addressManager->getAdressInfos(
                $address->gym_address
                . ' '
                . $address->zip_code
            )["features"][0]["geometry"]["coordinates"];
            $coords[] = [$addressInfos[1], $addressInfos[0]];
        }

        $errors = $userData = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = $_POST;
            $textFilter = new Text();
            $textFilter->setTexts($userData);
            $userData = $textFilter->filter();
            $errors = $this->verifMail($userData);
            if (empty($errors)) {
                $this->sendMail($userData);
                header('location:/');
                exit();
            }
        }

        return $this->twig->render(
            'Home/index.html.twig',
            [
                'errors' => $errors,
                'post' => $userData,
                'addreses' => $addreses,
                'coords' => $coords,
                'pictures' => $pictures,
                'importantEvents'=>$importantEvents
            ]
        );
    }
}
