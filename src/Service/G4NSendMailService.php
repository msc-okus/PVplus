<?php

namespace App\Service;

use App\Entity\AlertMessages;
use App\Entity\Anlage;
use App\Entity\Ticket;
use App\Entity\User;
use App\Helper\G4NTrait;
use App\Repository\AlertMessagesRepository;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class G4NSendMailService
{
    use G4NTrait;

    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly AlertMessagesRepository $alertMessagesRepository,
        private readonly TicketRepository        $ticketRepository,
        private readonly AnlagenRepository       $anlagenRepository,
        private readonly MailerInterface         $mailer,
        private readonly LoggerInterface         $logger,
        private readonly Environment             $twig,
    ) {
    }

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     * @throws \Exception
     */
    public function sendAlertMessage(Anlage $anlage, Ticket $ticket): void
    {
          if ($anlage->isAllowSendAlertMail() === true){
              // Create a unique token for the alert verification link
              $token = bin2hex(random_bytes(16)); // Ensure you have a strong, unique token

              $subject = '' . $anlage->getAnlName() . '. Please review immediately the Alert ID: ' . $ticket->getId();
              $message = 'A critical issue has been detected in the system. Please review immediately the Alert ID: ' . $ticket->getId();

              // Store the token with the alert message details in the database
              $alertMessage = new AlertMessages();
              $alertMessage->setAnlagenId($anlage->getAnlId());
              $alertMessage->setAlertId($ticket->getId());
              $alertMessage->setEventType($ticket->getErrorType());
              $alertMessage->setAlertType($ticket->getAlertType());
              $alertMessage->setSubject($subject);
              $alertMessage->setMessage($message);
              $alertMessage->setToken($token);
              $alertMessage->setStamp(static::getCetTime('OBJECT'));
              $alertMessage->setStatusId(0);
              $alertMessage->setStatusIdLast('0');

              $this->em->persist($alertMessage);
              $this->em->flush();

              $this-> send( $alertMessage,$anlage->getAlertMailReceiver(),false);
          }
    }

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function resendAlertMessage():void
    {
        $alerts = $this->alertMessagesRepository->findBy(["checked"=>null]);

        if ($alerts !== null){
            foreach($alerts as $alert){
                $ticket=$this->ticketRepository->find($alert->getAlertId());
                $anlage=$this->anlagenRepository->find($alert->getAnlagenId());

                if($ticket !== null && $anlage !== null && $anlage->getAlertCheckInterval() >0 && $ticket->getStatus() !==90 && time()-$alert->getStamp()->getTimestamp() >= $anlage->getAlertCheckInterval()*60){
                    $this->send($alert,$anlage->getAlertMailReceiver(),true);
                }

            }
        }
    }


    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    private function send(AlertMessages $alertMessage, array $receivers , ?bool $remember=false): void
    {
        $emailAddresses=[];

        foreach($receivers as $receiver){
            $emailAddresses[]= new Address($receiver);
        }

        foreach ($emailAddresses as $address) {
            $token= $alertMessage->getToken();
            $user = urlencode($address->getAddress());
            $link = "https://dev.g4npvplus.de/verify?token=$token&email=$user";
            $htmlContent = $this->twig->render('email/alertTicketMessage.html.twig', [
                'subject' => $remember?'REMEMBER! '.$alertMessage->getSubject():$alertMessage->getSubject(),
                'message' => $alertMessage->getMessage(),
                'link' => $link
            ]);
            $email = new Email();
            $email->from(new Address('noreply@g4npvplus.de', 'PVplus Alert System'))
                ->to($address)
                ->subject($alertMessage->getSubject())
                ->html($htmlContent);

            $this->mailer->send($email);
        }
    }

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function sendOneTimePassword(User $user, string $otp): void
    {
        $htmlContent = $this->twig->render('email/rawMail.html.twig', [
            'subject' => 'Your requested one time Password',
            'message' => 'Your requested one time Password: <b>' . $otp . '</b>',
            'name'    => $user->getName(),
        ]);
        $email = new Email();
        $email->from(new Address('noreply@g4npvplus.de', 'PVplus Alert System'))
            ->to($user->getEmail())
            ->subject('Your requested one time Password')
            ->html($htmlContent);

        $this->mailer->send($email);
    }
}
