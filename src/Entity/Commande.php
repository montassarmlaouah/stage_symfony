<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $date = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $status = null;

    

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToMany(targetEntity: Article::class)]
    #[ORM\JoinTable(name: 'commande_articles')]
    private Collection $articles;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $idarticle = null;

    
    public function getIdarticle(): ?string
    {
        return $this->idarticle;
    }



   

    #[ORM\Column(type: 'string', length: 50)]
    private $livraison;

    #[ORM\Column(type: 'string', length: 50)]
    private $paiement;

    #[ORM\Column(type: 'float')]
    private $total;


    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $adresse;

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getLivraison(): ?string
    {
        return $this->livraison;
    }

    public function setLivraison(string $livraison): self
    {
        $this->livraison = $livraison;
        return $this;
    }

    public function getPaiement(): ?string
    {
        return $this->paiement;
    }

    public function setPaiement(string $paiement): self
    {
        $this->paiement = $paiement;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $sizes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $colors = null;

 

    public function getSizes(): ?string
    {
        return $this->sizes;
    }

    public function setSizes(?string $sizes): self
    {
        $this->sizes = $sizes;
        return $this;
    }

    public function getColors(): ?string
    {
        return $this->colors;
    }

    public function setColors(?string $colors): self
    {
        $this->colors = $colors;
        return $this;
    }
    public function setIdarticle(string $idarticle): self
    {
        $this->idarticle = $idarticle;
    
        return $this;
    }
    
    public function __construct()
    {
        $this->articles = new ArrayCollection();

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal($total): self
    {
        $this->total = (float) $total;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        $this->articles->removeElement($article);

        return $this;
    }
    #[ORM\Column(type: 'integer')]
    private int $quantite;

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }
    /**
     * Retourne les noms des articles associés à la commande.
     * @return string[]
     */
    public function getArticleNames(): array
    {
        return $this->articles->map(function (Article $article) {
            return $article->getTitle(); // Supposez que `getTitle()` retourne le nom de l'article
        })->toArray();
    }
    // Assuming you have a session-based storage for items
    public function addCommandeItemToSession(): void
    {
        // Start the session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Add the item to the session
        $_SESSION['commande_items'][] = $this->getId();
    }
    /**
 * Retourne les titres des articles associés à la commande sous forme de chaîne.
 * @return string
 */
public function getArticlesAsString(): string
{
    return implode(', ', $this->articles->map(function (Article $article) {
        return $article->getTitle(); // Supposez que `getTitle()` retourne le nom de l'article
    })->toArray());
}

}
