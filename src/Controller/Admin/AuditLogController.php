<?php

namespace App\Controller\Admin;

use App\Repository\AuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/audit-log')]
class AuditLogController extends AbstractController
{
    #[Route('/', name: 'admin_audit_log_index')]
    public function index(Request $request, AuditLogRepository $repo): Response
    {
        $entityType = $request->query->get('entity_type');
        $limit = $request->query->getInt('limit', 100);

        if ($entityType) {
            $qb = $repo->createQueryBuilder('a')
                ->andWhere('a.entityType = :type')
                ->setParameter('type', $entityType)
                ->orderBy('a.performedAt', 'DESC')
                ->setMaxResults($limit);
            $logs = $qb->getQuery()->getResult();
        } else {
            $logs = $repo->findRecent($limit);
        }

        return $this->render('admin/audit_log/index.html.twig', [
            'logs' => $logs,
            'current_filter' => $entityType,
        ]);
    }
}
