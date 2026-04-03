<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use App\Application\Post\Archive\ArchivePostCommand;
use App\Application\Post\Archive\ArchivePostHandler;
use App\Application\Post\Create\CreatePostCommand;
use App\Application\Post\Create\CreatePostHandler;
use App\Application\Post\Delete\DeletePostCommand;
use App\Application\Post\Delete\DeletePostHandler;
use App\Application\Post\Find\FindPostByIdHandler;
use App\Application\Post\Find\FindPostByIdQuery;
use App\Application\Post\Find\FindPostBySlugHandler;
use App\Application\Post\Find\FindPostBySlugQuery;
use App\Application\Post\List\ListPostsHandler;
use App\Application\Post\List\ListPostsQuery;
use App\Application\Post\Publish\PublishPostCommand;
use App\Application\Post\Publish\PublishPostHandler;
use App\Application\Post\Update\UpdatePostCommand;
use App\Application\Post\Update\UpdatePostHandler;
use App\Domain\Post\Exception\InvalidPostStatusTransitionException;
use App\Domain\Post\Exception\PostNotFoundException;
use App\Domain\Post\Exception\SlugAlreadyExistsException;
use App\Domain\Author\Exception\AuthorNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/posts')]
final class PostController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(
        Request $request,
        ListPostsHandler $handler,
    ): JsonResponse {
        try {
            $result = $handler->handle(new ListPostsQuery(
                page:   (int) ($request->query->get('page', 1)),
                limit:  (int) ($request->query->get('limit', 10)),
                status: $request->query->get('status'),
            ));
        } catch (\ValueError $e) {
            return $this->badRequest('Invalid status value. Allowed: draft, published, archived.');
        }

        return $this->json($result);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        CreatePostHandler $handler,
    ): JsonResponse {
        $data    = $this->parseJson($request);
        $missing = $this->requireFields($data, ['title', 'content', 'authorId']);

        if ($missing !== null) {
            return $missing;
        }

        try {
            $dto = $handler->handle(new CreatePostCommand(
                title:    $data['title'],
                content:  $data['content'],
                authorId: $data['authorId'],
                slug:     $data['slug'] ?? null,
            ));
        } catch (AuthorNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (SlugAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->json($dto, Response::HTTP_CREATED);
    }

    #[Route('/by-slug/{slug}', methods: ['GET'])]
    public function showBySlug(
        string $slug,
        FindPostBySlugHandler $handler,
    ): JsonResponse {
        try {
            $dto = $handler->handle(new FindPostBySlugQuery($slug));
        } catch (PostNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->json($dto);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(
        string $id,
        FindPostByIdHandler $handler,
    ): JsonResponse {
        try {
            $dto = $handler->handle(new FindPostByIdQuery($id));
        } catch (PostNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->json($dto);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        string $id,
        Request $request,
        UpdatePostHandler $handler,
    ): JsonResponse {
        $data    = $this->parseJson($request);
        $missing = $this->requireFields($data, ['title', 'content']);

        if ($missing !== null) {
            return $missing;
        }

        try {
            $dto = $handler->handle(new UpdatePostCommand(
                postId:  $id,
                title:   $data['title'],
                content: $data['content'],
                slug:    $data['slug'] ?? null,
            ));
        } catch (PostNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (SlugAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->json($dto);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        string $id,
        DeletePostHandler $handler,
    ): JsonResponse {
        try {
            $handler->handle(new DeletePostCommand($id));
        } catch (PostNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/publish', methods: ['PATCH'])]
    public function publish(
        string $id,
        PublishPostHandler $handler,
    ): JsonResponse {
        try {
            $dto = $handler->handle(new PublishPostCommand($id));
        } catch (PostNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (InvalidPostStatusTransitionException $e) {
            return $this->unprocessable($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        return $this->json($dto);
    }

    #[Route('/{id}/archive', methods: ['PATCH'])]
    public function archive(
        string $id,
        ArchivePostHandler $handler,
    ): JsonResponse {
        try {
            $dto = $handler->handle(new ArchivePostCommand($id));
        } catch (PostNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (InvalidPostStatusTransitionException $e) {
            return $this->unprocessable($e->getMessage());
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

    private function unprocessable(string $message): JsonResponse
    {
        return $this->json(['error' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
