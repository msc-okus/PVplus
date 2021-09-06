<?php

namespace App\Service;

use App\Entity\AlertMessages;
use App\Entity\Anlage;
use App\Entity\AnlageEventMail;
use App\Helper\G4NTrait;
use App\Repository\AnlageEventMailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MessageService
{
    use G4NTrait;
    private $anlageEventMail;
    private $mailer;
    private $em;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer, AnlageEventMailRepository $anlageEventMail)
    {

        $this->anlageEventMail = $anlageEventMail;
        $this->mailer = $mailer;
        $this->em = $em;
    }

    public function sendMessage(Anlage $anlage, $eventType, $alertType, $subject, $message, $attachedFiles = false, $g4nAlert = true, $g4nAdmin = false, $upAlert = false)
    {
        $alertEmailG4n = new Address('alert@g4npvplus.de', 'PV+ Alert Email');
        $adminEmailG4n = new Address('admin@g4npvplus.de', 'PV+ Admin Email');
        $alertEmailKast = new Address('t.recke@upgmbh.com', 'Tobias Recke');
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
                                $email->To(new Address($recipent->getMail(), $recipent->getFirstName() . ' ' . $recipent->getLastname()));
                            } else {
                                $email->addTo(new Address($recipent->getMail(), $recipent->getFirstName() . ' ' . $recipent->getLastname()));
                            }
                            $to .= $recipent->getMail() . ', ';
                            break;
                        case 'cc':
                            $email->addCc(new Address($recipent->getMail(), $recipent->getFirstName() . ' ' . $recipent->getLastname()));
                            break;
                        case 'bcc':
                            $email->addBcc(new Address($recipent->getMail(), $recipent->getFirstName() . ' ' . $recipent->getLastname()));
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
                    $email->to($alertEmailKast);
                } else {
                    $email->addCc($alertEmailKast);
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
                $email->addBcc($adminEmailG4n);
            }

            $this->mailer->send($email);
            $this->logMessage($anlage, $subject, $message, $eventType, $alertType, $to);
            sleep(2);
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
        $alertMessage->setStatusId("0");
        $alertMessage->setStatusIdLast("0");
        $alertMessage->setStamp($this->getCetTime('OBJECT'));
        $this->em->persist($alertMessage);
        $this->em->flush();
    }
}