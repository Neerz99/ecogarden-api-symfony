<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/meteo')]
class MeteoController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route('/{ville}', name: 'meteo_by_city', methods: ['GET'])]
    public function byCity(string $ville): JsonResponse
    {
        if (trim($ville) === '') {
            throw new BadRequestHttpException('Le parametre "ville" est obligatoire.');
        }

        return $this->json($this->getWeatherForCity($ville));
    }

    #[Route('', name: 'meteo_current_user_city', methods: ['GET'])]
    public function byCurrentUserCity(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifie.'], 401);
        }

        $ville = $user->getVille();
        if ($ville === null || trim($ville) === '') {
            throw new BadRequestHttpException('Aucune ville associee au compte utilisateur.');
        }

        return $this->json($this->getWeatherForCity($ville));
    }

    /**
     * @return array<string, mixed>
     */
    private function getWeatherForCity(string $city): array
    {
        $normalized = trim($city);
        $cacheKey = 'weather_' . md5(mb_strtolower($normalized));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($normalized): array {
            $item->expiresAfter(1800);

            try {
                $geoResponse = $this->httpClient->request('GET', 'https://geocoding-api.open-meteo.com/v1/search', [
                    'timeout' => 10,
                    'query' => [
                        'name' => $normalized,
                        'count' => 1,
                        'language' => 'fr',
                        'format' => 'json',
                    ],
                ]);

                $geoData = $geoResponse->toArray(false);
                if (!isset($geoData['results'][0])) {
                    throw new NotFoundHttpException('Ville introuvable.');
                }

                $location = $geoData['results'][0];

                $weatherResponse = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                    'timeout' => 10,
                    'query' => [
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                        'current' => 'temperature_2m,relative_humidity_2m,weather_code',
                        'timezone' => 'auto',
                    ],
                ]);

                $weatherData = $weatherResponse->toArray(false);
                if (!isset($weatherData['current'])) {
                    throw new ServiceUnavailableHttpException(null, 'Impossible de recuperer la meteo.');
                }

                return [
                    'ville' => $location['name'],
                    'pays' => $location['country'] ?? null,
                    'temperature' => $weatherData['current']['temperature_2m'] ?? null,
                    'humidite' => $weatherData['current']['relative_humidity_2m'] ?? null,
                    'code_meteo' => $weatherData['current']['weather_code'] ?? null,
                    'source' => 'open-meteo',
                ];
            } catch (TransportExceptionInterface) {
                throw new ServiceUnavailableHttpException(null, 'Service meteo indisponible.');
            }
        });
    }
}
