<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Seance
 *
 * @ORM\Table(name="seances")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\SeancesRepository")
 */
class Seance
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
     * @ORM\ManyToOne(targetEntity="Matieres")
     */
    private $matiere;

    /**
     * @ORM\ManyToOne(targetEntity="Professeurs")
     */
    private $professeur;

    /**
     * @ORM\ManyToOne(targetEntity="Horaires")
     */
    private $horaire;

    /**
     * @ORM\ManyToOne(targetEntity="Salles")
     */
    private $salle;
    
    
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
     * @return String
     */
    public function getMatiere()
    {
        return $this->matiere;
    }

    /**
     * @param Matieres $matiere
     */
    public function setMatiere($matiere)
    {
        $this->matiere = $matiere;
    }

    /**
     * @return Professeurs
     */
    public function getProfesseur()
    {
        return $this->professeur;
    }

    /**
     * @param Professeurs $professeur
     */
    public function setProfesseur($professeur)
    {
        $this->professeur = $professeur;
    }

    /**
     * @return Horaires
     */
    public function getHoraire()
    {
        return $this->horaire;
    }

    /**
     * @param Horaires $horaire
     */
    public function setHoraire($horaire)
    {
        $this->horaire = $horaire;
    }

    /**
     * @return Salles
     */
    public function getSalle()
    {
        return $this->salle;
    }

    /**
     * @param Salles $salle
     */
    public function setSalle($salle)
    {
        $this->salle = $salle;
    }

}

