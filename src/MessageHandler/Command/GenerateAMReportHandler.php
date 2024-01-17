<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateAMReport;
use App\Repository\AnlagenRepository;
use App\Service\AssetManagementService;
use App\Service\LogMessagesService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;
#[AsMessageHandler]
class GenerateAMReportHandler
{
    public function __construct(
        private readonly AssetManagementService $assetManagement,
        private readonly LogMessagesService $logMessages,
        private readonly AnlagenRepository $anlagenRepo,
        private Environment $twig
    )
    {
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __invoke(GenerateAMReport $generateAMReport): void
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateAMReport->getAnlageId());
        $userId = $generateAMReport->getUserId();

        $logId = $generateAMReport->getlogId();
        $this->logMessages->updateEntry($logId, 'working', 0);
        $html =  $this->twig->render('logMessages/_prozessReady.html.twig', [
            'message' => 'Ready',
        ]);

        $this->assetManagement->createAmReport($anlage, $generateAMReport->getMonth(), $generateAMReport->getYear(), $generateAMReport->getUserId(), $logId);
        $this->logMessages->updateEntry($logId, 'done', 100);
        $myfile = fopen("newfile1.txt", "w") or die("Unable to open file!");
        $txt = "John Doe\n";
        fwrite($myfile, $txt);

        fwrite($myfile, $html);
        fclose($myfile);
    }
}