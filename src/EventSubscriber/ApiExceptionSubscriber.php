<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = 500;
        $message = 'Erreur serveur.';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() !== '' ? $exception->getMessage() : $this->defaultMessage($statusCode);
        } elseif ($exception instanceof AccessDeniedException) {
            $statusCode = 403;
            $message = 'Acces refuse.';
        }

        if ($statusCode >= 500 && !$this->kernel->isDebug()) {
            $message = 'Erreur serveur.';
        }

        $payload = [
            'error' => $message,
            'status' => $statusCode,
        ];

        if ($statusCode >= 500 && $this->kernel->isDebug()) {
            $payload['exception'] = $exception::class;
        }

        $event->setResponse(new JsonResponse($payload, $statusCode));
    }

    private function defaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Requete invalide.',
            401 => 'Non authentifie.',
            403 => 'Acces refuse.',
            404 => 'Ressource introuvable.',
            default => 'Erreur serveur.',
        };
    }
}
