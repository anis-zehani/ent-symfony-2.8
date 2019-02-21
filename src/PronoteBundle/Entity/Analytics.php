<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Analytics
 *
 * @ORM\Table(name="analytics")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\AnalyticsRepository")
 */
class Analytics
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
     * @var string
     *
     * @ORM\Column(name="profil", type="string", length=255)
     */
    private $profil;
    
    /**
     * @var int
     *
     * @ORM\Column(name="compteur", type="integer")
     */
    private $compteur;
    
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
     * Set profil
     *
     * @param string $profil
     *
     * @return Analytics
     */
    public function setProfil($profil)
    {
        $this->profil = $profil;

        return $this;
    }

    /**
     * Get profil
     *
     * @return string
     */
    public function getProfil()
    {
        return $this->profil;
    }
    
    /**
     * Set compteur
     *
     * @param int $compteur
     *
     * @return Analytics
     */
    public function setCompteur($compteur)
    {
        $this->compteur = $compteur;
        
        return $this;
    }
    
    /**
     * Get compteur
     *
     * @return int
     */
    public function getCompteur()
    {
        return $this->compteur;
    }
}

