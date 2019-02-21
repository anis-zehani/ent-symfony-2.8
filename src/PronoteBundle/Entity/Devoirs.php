<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Devoirs
 *
 * @ORM\Table(name="devoirs")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\DevoirsRepository")
 */
class Devoirs
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="donne_le", type="datetime")
     */
    private $donneLe;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="donne_pour", type="datetime")
     */
    private $donnePour;

    /**
     * @var string
     *
     * @ORM\Column(name="details_devoir", type="string", length=1024)
     */
    private $detailsDevoir;

    /**
     * @ORM\ManyToOne(targetEntity="Matieres", inversedBy="listeDevoirs")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $matiere;

    /**
     * @ORM\ManyToOne(targetEntity="Classe", inversedBy="listeDevoirs")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $classe;
    
    /**
     * @ORM\ManyToOne(targetEntity="Professeurs", inversedBy="listeDevoirs")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $professeur;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Ecole")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $ecole;
    
    /**************Getters et Setters Ecole (ManyToOne)*****************/
    /**
     * @return Ecole
     */
    public function getEcole()
    {
        return $this->ecole;
    }
    
    /**
     * @param Ecole $ecole
     */
    public function setEcole(Ecole $ecole)
    {
        $this->ecole = $ecole;
        
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set donneLe
     *
     * @param \DateTime $donneLe
     *
     * @return Devoirs
     */
    public function setDonneLe($donneLe)
    {
        $this->donneLe = $donneLe;

        return $this;
    }

    /**
     * Get donneLe
     *
     * @return \DateTime
     */
    public function getDonneLe()
    {
        return $this->donneLe;
    }

    /**
     * Set donnePour
     *
     * @param \DateTime $donnePour
     *
     * @return Devoirs
     */
    public function setDonnePour($donnePour)
    {
        $this->donnePour = $donnePour;

        return $this;
    }

    /**
     * Get donnePour
     *
     * @return \DateTime
     */
    public function getDonnePour()
    {
        return $this->donnePour;
    }

    /**
     * Set detailsDevoir
     *
     * @param string $detailsDevoir
     *
     * @return Devoirs
     */
    public function setDetailsDevoir($detailsDevoir)
    {
        $this->detailsDevoir = $detailsDevoir;

        return $this;
    }

    /**
     * Get detailsDevoir
     *
     * @return string
     */
    public function getDetailsDevoir()
    {
        return $this->detailsDevoir;
    }

    /**
     * @return Classe
     */
    public function getClasse()
    {
        return $this->classe;
    }

    /**
     * @param Classe $idClasse
     */
    public function setClasse($idClasse)
    {
        $this->classe = $idClasse;
    }
    
    /**
     * @return Professeurs
     */
    public function getProfesseur()
    {
        return $this->professeur;
    }
    
    /**
     * @param Professeurs $idProfesseur
     */
    public function setProfesseur($idProfesseur)
    {
        $this->professeur = $idProfesseur;
    }

    /**
     * @return Matieres
     */
    public function getMatiere()
    {
        return $this->matiere;
    }

    /**
     * @param Matieres $idMatiere
     */
    public function setIdMatiere($idMatiere)
    {
        $this->matiere = $idMatiere;
    }
}

