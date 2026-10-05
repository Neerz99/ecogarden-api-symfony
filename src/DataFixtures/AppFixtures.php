<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Conseil;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // ---- Utilisateurs ----

        $admin = new User();
        $admin->setLogin('admin');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setVille('Paris');
        $manager->persist($admin);

        $user1 = new User();
        $user1->setLogin('enzo');
        $user1->setPassword($this->hasher->hashPassword($user1, 'enzo1234'));
        $user1->setVille('Arras');
        $manager->persist($user1);

        $user2 = new User();
        $user2->setLogin('bob');
        $user2->setPassword($this->hasher->hashPassword($user2, 'bob1234'));
        $user2->setVille('Lyon');
        $manager->persist($user2);

        // ---- Conseils jardin ----

        $conseils = [
            ['contenu' => 'Nettoyez les outils de jardinage pour éviter les maladies.', 'mois' => [1]],
            ['contenu' => 'Taillez les rosiers avant le débourrement.', 'mois' => [2]],
            ['contenu' => 'Semez les tomates en godets à l\'intérieur.', 'mois' => [3]],
            ['contenu' => 'Plantez les pommes de terre en pleine terre.', 'mois' => [4]],
            ['contenu' => 'Paillez les massifs pour conserver l\'humidité.', 'mois' => [5]],
            ['contenu' => 'Arrosez abondamment les légumes du potager.', 'mois' => [6]],
            ['contenu' => 'Récoltez les courgettes avant qu\'elles grossissent trop.', 'mois' => [7]],
            ['contenu' => 'Fertilisez les plantes avec du compost maison.', 'mois' => [8]],
            ['contenu' => 'Ramassez les pommes et les poires à maturité.', 'mois' => [9]],
            ['contenu' => 'Plantez les bulbes de tulipes et narcisses.', 'mois' => [10]],
            ['contenu' => 'Protégez les plantes fragiles du gel.', 'mois' => [11]],
            ['contenu' => 'Préparez le compost avec les feuilles mortes.', 'mois' => [12]],
        ];

        foreach ($conseils as $data) {
            $conseil = new Conseil();
            $conseil->setContenu($data['contenu']);
            $conseil->setMois($data['mois']);
            $manager->persist($conseil);
        }

        $manager->flush();
    }
}
