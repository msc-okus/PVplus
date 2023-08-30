<?php

namespace App\Service;

use App\Entity\AlertMessages;
use App\Helper\G4NTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class G4NSendMailService
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant,
        private EntityManagerInterface $em,
        private MailerInterface $mailer
    ) {
    }

    public function SendAlertMail($to, $subject, $message, $alertType = 0, $anlagenId = 0, $statusId = 0, $statusIdLast = 0)
    {
        /*
        $email =  new TemplatedEmail();
        $alertEmailG4n = new Address('alert@g4npvplus.de', 'Alert Email');
        $email
            ->from(new Address('noreply@g4npvplus.de', 'PVplus Alert System'))
            ->to($alertEmailG4n)
            ->subject($subject)
            ->htmlTemplate('email/alertMessage.html.twig')
            ->context([
                'message' => $message,
            ]);
        if ($to) {
            $email->addTo($to);
        }

        $this->mailer->send($email);
        sleep(2);
*/
        $alertMessage = new AlertMessages();
        $alertMessage->setAlertType($alertType);
        $alertMessage->setAnlagenId($anlagenId);
        ($to) ? $alertMessage->setEmailRecipient($to) : $alertMessage->setEmailRecipient($alertEmailG4n);
        $alertMessage->setSubject($subject);
        $alertMessage->setMessage($message);
        $alertMessage->setStatusId('0');
        $alertMessage->setStatusIdLast('0');
        $alertMessage->setStamp($this->getCetTime('OBJECT'));
        $this->em->persist($alertMessage);
        $this->em->flush();
    }
}
