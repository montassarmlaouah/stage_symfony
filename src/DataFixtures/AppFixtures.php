<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Categorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Créer une catégorie
        $categorie = new Categorie();
        $categorie->setName('Électronique')
                  ->setImage('electronique.jpg')
                  ->setDescription('Appareils électroniques comme téléphones et ordinateurs.');
        $manager->persist($categorie);

        // Ajouter plusieurs articles à la catégorie
        for ($i = 1; $i <= 5; $i++) {
            $article = new Article();
            $article->setTitle("Article $i")
                    ->setContent("Description de l'article $i")
                    ->setPrice(mt_rand(100, 500))
                    ->setCategorie($categorie); // Associer l'article à la catégorie
            $manager->persist($article);
        }

        $manager->flush();
    }
}
