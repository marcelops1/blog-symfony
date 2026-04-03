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
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Posts')]
#[Route('/api/posts')]
final class PostController extends AbstractController
{
    #[OA\Get(
        path: '/api/posts',
        summary: 'List posts (paginated)',
        parameters: [
            new OA\Parameter(name: 'page',   in: 'query', required: false,
                description: 'Page number (min 1)', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit',  in: 'query', required: false,
                description: 'Items per page (max 100)', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'status', in: 'query', required: false,
                description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'archived'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of posts',
                content: new OA\JsonContent(ref: new Model(type: \App\Application\Post\DTO\PostListDTO::class)),
            ),
            new OA\Response(response: 400, description: 'Invalid status value',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Post(
        path: '/api/posts',
        summary: 'Create a new post',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content', 'authorId'],
                properties: [
                    new OA\Property(property: 'title',    type: 'string', example: 'Clean Architecture with PHP'),
                    new OA\Property(property: 'content',  type: 'string', example: 'A deep dive into layered design patterns...'),
                    new OA\Property(property: 'authorId', type: 'string', format: 'uuid', example: '01906bef-0000-7000-8000-000000000001'),
                    new OA\Property(property: 'slug',     type: 'string', nullable: true, example: 'clean-architecture-with-php',
                        description: 'Auto-generated from title when omitted'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Post created (status: draft)',
                content: new OA\JsonContent(ref: new Model(type: \App\Application\Post\DTO\PostDTO::class)),
            ),
            new OA\Response(response: 400, description: 'Missing or invalid field',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Author not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Slug already in use',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Get(
        path: '/api/posts/by-slug/{slug}',
        summary: 'Find a post by slug',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true,
                description: 'Post slug (lowercase letters, numbers and hyphens)',
                schema: new OA\Schema(type: 'string', example: 'clean-architecture-with-php')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post found',
                content: new OA\JsonContent(ref: new Model(type: \App\Application\Post\DTO\PostDTO::class)),
            ),
            new OA\Response(response: 400, description: 'Invalid slug format',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Post not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Get(
        path: '/api/posts/{id}',
        summary: 'Find a post by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                description: 'Post UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post found',
                content: new OA\JsonContent(ref: new Model(type: \App\Application\Post\DTO\PostDTO::class)),
            ),
            new OA\Response(response: 400, description: 'Invalid UUID',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Post not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Put(
        path: '/api/posts/{id}',
        summary: 'Update a post',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                description: 'Post UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title',   type: 'string', example: 'Updated Title'),
                    new OA\Property(property: 'content', type: 'string', example: 'Updated content body...'),
                    new OA\Property(property: 'slug',    type: 'string', nullable: true, example: 'updated-title'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post updated',
                content: new OA\JsonContent(ref: new Model(type: \App\Application\Post\DTO\PostDTO::class)),
            ),
            new OA\Response(response: 400, description: 'Missing or invalid field',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Post not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Slug already in use',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Delete(
        path: '/api/posts/{id}',
        summary: 'Delete a post',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                description: 'Post UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Post deleted'),
            new OA\Response(response: 400, description: 'Invalid UUID',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Post not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Patch(
        path: '/api/posts/{id}/publish',
        summary: 'Publish a post (DRAFT → PUBLISHED)',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                description: 'Post UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post published',
                content: new OA\JsonContent(ref: new Model(type: \App\Application\Post\DTO\PostDTO::class)),
            ),
            new OA\Response(response: 400, description: 'Invalid UUID',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Post not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid status transition (e.g. already published or archived)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Patch(
        path: '/api/posts/{id}/archive',
        summary: 'Archive a post (PUBLISHED → ARCHIVED)',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                description: 'Post UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post archived',
                content: new OA\JsonContent(ref: new Model(type: \App\Application\Post\DTO\PostDTO::class)),
            ),
            new OA\Response(response: 400, description: 'Invalid UUID',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Post not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid status transition (e.g. post is not published)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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
