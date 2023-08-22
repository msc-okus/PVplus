<?php

namespace App\Controller;

use App\Repository\LogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends BaseController
{
    #[Route(path: '/log', name: 'app_log_list')]
    public function list(Request $request, PaginatorInterface $paginator, LogRepository $logRepo): Response
    {
        $q = $request->query->get('q');

        $queryBuilder = $logRepo->getWithSearchQueryBuilder($q);

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            20                                         /* limit per page */
        );

        return $this->render('log/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
