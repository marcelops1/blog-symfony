<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PingController extends AbstractController
{
    #[OA\Get(
        path: '/ping',
        summary: 'Health check',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service is up',
                content: new OA\JsonContent(
                    required: ['status', 'date', 'php_version'],
                    properties: [
                        new OA\Property(property: 'status',      type: 'string', example: 'ok'),
                        new OA\Property(property: 'date',        type: 'string', format: 'date-time', example: '2024-01-15 10:30:00'),
                        new OA\Property(property: 'php_version', type: 'string', example: '8.5.0'),
                    ],
                ),
            ),
        ],
    )]
    #[Route('/ping', name: 'ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'status'      => 'ok',
            'date'        => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
        ]);
    }
}
