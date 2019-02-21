<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Notifications
 *
 * @ORM\Table(name="notifications")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\NotificationsRepository")
 */
class Notifications
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
     * @ORM\Column(name="etat_notification", type="string", length=255)
     */
    private $etatNotification;
    
    /**
     * @var string
     *
     * @ORM\Column(name="lien_notification", type="string", length=255)
     */
    private $lienNotification;

    /**
     * @var string
     *
     * @ORM\Column(name="description_notification", type="string", length=512)
     */
    private $descriptionNotification;
    
    /**
     * @ORM\ManyToOne(targetEntity="Eleves", cascade="remove", inversedBy="listeNotifications")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $eleve;
    
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
     * @return Eleves
     */
    public function getEleve()
    {
        return $this->eleve;
    }
    
    /**
     * @param Eleves $eleve
     */
    public function setEleve(Eleves $eleve)
    {
        $this->eleve = $eleve;
        
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
     * Set etatNotification
     *
     * @param string $etatNotification
     *
     * @return Notifications
     */
    public function setEtatNotification($etatNotification)
    {
        $this->etatNotification = $etatNotification;

        return $this;
    }

    /**
     * Get etatNotification
     *
     * @return string
     */
    public function getEtatNotification()
    {
        return $this->etatNotification;
    }
    
    /**
     * Set lienNotification
     *
     * @param string $lienNotification
     *
     * @return Notifications
     */
    public function setLienNotification($lienNotification)
    {
        $this->lienNotification = $lienNotification;
        
        return $this;
    }
    
    /**
     * Get lienNotification
     *
     * @return string
     */
    public function getLienNotification()
    {
        return $this->lienNotification;
    }

    /**
     * Set descriptionNotification
     *
     * @param string $descriptionNotification
     *
     * @return Notifications
     */
    public function setDescriptionNotification($descriptionNotification)
    {
        $this->descriptionNotification = $descriptionNotification;

        return $this;
    }

    /**
     * Get descriptionNotification
     *
     * @return string
     */
    public function getDescriptionNotification()
    {
        return $this->descriptionNotification;
    }
    
}

