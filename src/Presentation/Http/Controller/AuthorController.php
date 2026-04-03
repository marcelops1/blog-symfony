<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use App\Application\Author\Create\CreateAuthorCommand;
use App\Application\Author\Create\CreateAuthorHandler;
use App\Application\Author\Find\FindAuthorByIdHandler;
use App\Application\Author\Find\FindAuthorByIdQuery;
use App\Domain\Author\Exception\AuthorNotFoundException;
use App\Domain\Author\Exception\EmailAlreadyExistsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/authors')]
final class AuthorController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        CreateAuthorHandler $handler,
    ): JsonResponse {
        $data = $this->parseJson($request);

        $missing = $this->requireFields($data, ['name', 'email']);
        if ($missing !== null) {
            return $missing;
        }

        try {
            $dto = $handler->handle(new CreateAuthorCommand(
                name:  $data['name'],
                email: $data['email'],
                bio:   $data['bio'] ?? null,
            ));
        } catch (EmailAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->json($dto, Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(
        string $id,
        FindAuthorByIdHandler $handler,
    ): JsonResponse {
        try {
            $dto = $handler->handle(new FindAuthorByIdQuery($id));
        } catch (AuthorNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->json($dto);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function parseJson(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }

    private function requireFields(array $data, array $fields): ?JsonResponse
    {
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                return $this->badRequest(sprintf('Field "%s" is required.', $field));
            }
        }
        return null;
    }

    private function badRequest(string $message): JsonResponse
    {
        return $this->json(['error' => $message], Response::HTTP_BAD_REQUEST);
    }

    private function notFound(string $message): JsonResponse
    {
        return $this->json(['error' => $message], Response::HTTP_NOT_FOUND);
    }

    private function conflict(string $message): JsonResponse
    {
        return $this->json(['error' => $message], Response::HTTP_CONFLICT);
    }
}
