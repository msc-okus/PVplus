<?php

namespace App\Service;

use App\Entity\AlertMessages;
use App\Entity\Anlage;
use App\Entity\AnlageEventMail;
use App\Entity\Ticket;
use App\Helper\G4NTrait;
use App\Repository\AnlageEventMailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MessageService
{
    use G4NTrait;


    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly AnlageEventMailRepository $anlageEventMail,
        private PiiCryptoService $encryptService,
        private LoggerInterface $logger
    )
    {
    }

    public function sendMessage(Anlage $anlage, $eventType, $alertType, $subject, $message, $attachedFiles = false, $g4nAlert = true, $g4nAdmin = false, $upAlert = false):void
    {
        $alertEmailG4n = new Address('os@green4net.com', 'Oliver Skadow');
        $adminEmailG4n = new Address('mr@green4net.com', 'Matthias Reinhardt');
        $to = '';

        $email = new TemplatedEmail();
        $email->from(new Address('alert@g4npvplus.net', 'PVplus Alert System'));

        // Nur wenn in der Anlage sendWarnMail = yes ist
        if ($anlage->getSendWarnMail()) {
            // Nur wenn es keine IO Fehlermeldungen sind ($alertType >=3 wenn EventType = alert)
            if ($alertType >= 3 || $eventType != 'alert') {
                /** @var AnlageEventMail $recipents */
                $recipents = $this->anlageEventMail->findBy(['anlage' => $anlage->getAnlId(), 'event' => $eventType], ['sendType' => 'ASC']);

                foreach ($recipents as $recipent) {
                    switch ($recipent->getSendType()) {
                        case 'to':
                            if ($to == '') {
                                $email->To(new Address($recipent->getMail(), $recipent->getFirstName().' '.$recipent->getLastname()));
                            } else {
                                $email->addTo(new Address($recipent->getMail(), $recipent->getFirstName().' '.$recipent->getLastname()));
                            }
                            $to .= $recipent->getMail().', ';
                            break;
                        case 'cc':
                            $email->addCc(new Address($recipent->getMail(), $recipent->getFirstName().' '.$recipent->getLastname()));
                            break;
                        case 'bcc':
                            $email->addBcc(new Address($recipent->getMail(), $recipent->getFirstName().' '.$recipent->getLastname()));
                            break;
                    }
                }
            }
            if ($attachedFiles) {
                foreach ($attachedFiles as $attachedFile) {
                    $email->attach($attachedFile['file'], $attachedFile['filename']);
                }
            }
            $email
                ->subject($subject)
                ->htmlTemplate('email/alertMessage.html.twig')
                ->context([
                    'message' => $message,
                ]);

            if ($upAlert && false) { // temporär abgeschaltet
                if ($to == '' || $to == 'G4N') {
                    // $email->to($alertEmailKast);
                } else {
                    // $email->addCc($alertEmailKast);
                }
                $to .= 'UP Alert';
            }

            if ($g4nAlert) {
                if ($to == '') {
                    $email->to($alertEmailG4n);
                } else {
                    $email->addBcc($alertEmailG4n);
                }
                $to .= 'G4N';
            }
            if ($g4nAdmin) {
                $email->addCc($adminEmailG4n);
            }

            $this->mailer->send($email);

            $this->logMessage($anlage, $subject, $message, $eventType, $alertType, $to);


            sleep(2);
        }
    }



    public function sendMessageToMaintenance($subject, $message, $to, $name, $Tam, $attachedFiles = false, $ticket = false):void{
        $email = new TemplatedEmail();
        $email->from(new Address('noreply@g4npvplus.de', 'noreply G4N'));
        $email->to(new Address($to, $name));
        $hashedkey = $this->encryptService->hashData($ticket->getSecurityToken());
        $email
            ->subject($subject)
            ->htmlTemplate('email/notificationMail.html.twig')
            ->context([
                'name'      => $name,
                'message'   => $message,
                'ticket'    => $ticket,
                'key'       => $hashedkey,
                'tam' => $Tam
            ]);

        if ($attachedFiles) {
            foreach ($attachedFiles as $attachedFile) {
                $email->attach($attachedFile['file'], $attachedFile['filename']);
            }
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {

        }

    }
    public function sendConfirmationMessageToMaintenance($subject, $message, $to, $name, $attachedFiles = false, $ticket = false):void{
        $email = new TemplatedEmail();
        $email->from(new Address('noreply@g4npvplus.de', 'noreply G4N'));
        $email->to(new Address($to, $name));
        $hashedkey = $this->encryptService->hashData($ticket->getSecurityToken());
        $email
            ->subject($subject)
            ->htmlTemplate('email/confirmationMail.html.twig')
            ->context([
                'name'      => $name,
                'message'   => $message,
                'ticket'    => $ticket,
                'key'       => $hashedkey
            ]);

        if ($attachedFiles) {
            foreach ($attachedFiles as $attachedFile) {
                $email->attach($attachedFile['file'], $attachedFile['filename']);
            }
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {

        }

    }

    public function sendRawMessage($subject, $message, $to, $name, $attachedFiles = false):void{
        $email = new TemplatedEmail();
        $email->from(new Address('noreply@g4npvplus.de', 'noreply G4N'));
        $email->to(new Address($to, $name));
        $email
            ->subject($subject)
            ->htmlTemplate('email/rawMail.html.twig')
            ->context([
                'name'      => $name,
                'message'   => $message,
            ]);

        if ($attachedFiles) {
            foreach ($attachedFiles as $attachedFile) {
                $email->attach($attachedFile['file'], $attachedFile['filename']);
            }
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {

        }

    }
    public function logMessage(Anlage $anlage, $subject, $message, $eventType, $alertType, $to)
    {
        $alertMessage = new AlertMessages();
        $alertMessage->setAnlagenId($anlage->getAnlId());
        $alertMessage->setEventType($eventType);
        $alertMessage->setAlertType($alertType);
        $alertMessage->setEmailRecipient($to);
        $alertMessage->setSubject($subject);
        $alertMessage->setMessage($message);
        $alertMessage->setStatusId('0');
        $alertMessage->setStatusIdLast('0');
        $alertMessage->setStamp(static::getCetTime('OBJECT'));
        $this->em->persist($alertMessage);
        $this->em->flush();
    }






}

