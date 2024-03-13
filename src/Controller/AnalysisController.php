<?php

namespace App\Controller;

use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AnalysisController extends AbstractController
{
    #[Route(path: '/analysis/create', name: 'app_analysis_create')]
    public function create()
    {
        //to come
    }
    #[Route(path: '/analysis', name: 'app_analysis_list')]
    public function list(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository, AnlagenRepository $anlagenRepo): Response
    {

        $searchyear = date('Y');
        $searchstatus = $searchtype = $searchmonth = $anlage = '';
        $queryBuilder = $reportsRepository->getWithSearchQueryBuilder($anlage, $searchstatus, $searchtype, $searchmonth, $searchyear);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        if ($request->query->get('ajax') || $request->isXmlHttpRequest()) {
            return $this->render('reporting/_inc/_listReports.html.twig', [
                'pagination' => $pagination,
                'searchyear' => $searchyear,
                'month'      => $searchmonth,
                'type'       => $searchtype,
                'status'     => $searchstatus,
                'anlage'     => $anlage,
            ]);
        }

        return $this->render('/analysis/list.html.twig',[
            'pagination' => $pagination,
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'type'       => $searchtype,
            'status'     => $searchstatus,
            'anlage'     => $anlage,
        ]);
    }
    #[Route(path: '/analysis/search', name: 'app_analysis_search', methods: ['GET', 'POST'])]
    public function search(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository): Response
    {
        $anlage = $request->query->get('anlage');
        $searchstatus = $request->query->get('searchstatus');
        $searchtype = $request->query->get('searchtype');
        $searchmonth = $request->query->get('searchmonth');
        $searchyear = $request->query->get('searchyear');
        $page = $request->query->getInt('page', 1);

        $queryBuilder = $reportsRepository->getWithSearchQueryBuilder($anlage, $searchstatus, $searchtype, $searchmonth, $searchyear);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            20
        );
        return $this->render('analysis/_inc/_listReports.html.twig', [
            'pagination' => $pagination,
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'type'       => $searchtype,
            'status'     => $searchstatus,
            'anlage'     => $anlage,
        ]);
    }
    #[Route(path: '/analysis/delete/{id}', name: 'app_analysis_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete($id, ReportsRepository $reportsRepository, EntityManagerInterface $em): Response
    {
        $report = $reportsRepository->find($id);
        if ($report) {
            $em->remove($report);
            $em->flush();
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

}
