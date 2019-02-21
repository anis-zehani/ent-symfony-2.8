<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Horaires
 *
 * @ORM\Table(name="horaires")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\HorairesRepository")
 */
class Horaires
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
     * @ORM\Column(name="intervalle", type="string", length=255)
     */
    private $intervalle;
    
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
     * Set intervalle
     *
     * @param string $intervalle
     *
     * @return Horaires
     */
    public function setIntervalle($intervalle)
    {
        $this->intervalle = $intervalle;

        return $this;
    }

    /**
     * Get intervalle
     *
     * @return string
     */
    public function getIntervalle()
    {
        return $this->intervalle;
    }
}

