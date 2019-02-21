<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Salles
 *
 * @ORM\Table(name="salles")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\SallesRepository")
 */
class Salles
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
     * @ORM\Column(name="identificateur", type="string", length=255)
     */
    private $identificateur;
    
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
     * Set identificateur
     *
     * @param string $identificateur
     *
     * @return Salles
     */
    public function setIdentificateur($identificateur)
    {
        $this->identificateur = $identificateur;

        return $this;
    }

    /**
     * Get identificateur
     *
     * @return string
     */
    public function getIdentificateur()
    {
        return $this->identificateur;
    }
}

