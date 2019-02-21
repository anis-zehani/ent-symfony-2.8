<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Classe
 *
 * @ORM\Table(name="classe")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\ClasseRepository")
 */
class Classe
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
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;
    
    /**
     * @ORM\OneToMany(targetEntity="NotificationsClasse" , mappedBy="classe")
     */
    private $listeNotificationsClasse;
    
    /**
     * @ORM\OneToMany(targetEntity="ObservationsGenerales" , mappedBy="classe")
     */
    private $listeObservationsGenerales;
    
    /**
     * @ORM\OneToMany(targetEntity="ActivitesScolaires" , mappedBy="classe")
     */
    private $listeActivitesScolaires;
    
    /**
     * @ORM\OneToMany(targetEntity="Devoirs" , mappedBy="classe")
     */
    private $listeDevoirs;
    
    /**
     * @ORM\OneToMany(targetEntity="MenuCantine" , mappedBy="classe")
     */
    private $listeMenuCantine;
    
    /**
     * @ORM\OneToMany(targetEntity="Telechargements" , mappedBy="classe")
     */
    private $listeTelechargements;
    
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
     * Set nom
     *
     * @param string $nom
     *
     * @return Classe
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        //return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }
    
    ///**************GESTION DES NOTIFICATIONS*****************///
    
    public function __construct()
    {
        if(!empty($this->$listeNotificationsClasse))
        {
            $this->$listeNotificationsClasse = new ArrayCollection();
        }
        
    }
    
    /**
     * @return ArrayCollection
     */
    public function getListeNotificationsClasse()
    {
        return $this->$listeNotificationsClasse;
    }
    
    /**
     * Add NotificationClasse
     *
     * @param Classe $classe
     *
     * @return NotificationsClasse
     */
    public function addNotificationClasse(Classe $classe)
    {
        $this->$listeNotificationsClasse[] = $classe;
        return $this;
    }
    
    /**
     * Remove NotificationClasse
     *
     * @param Classe $classe
     */
    public function removeNotificationClasse(Classe $classe)
    {
        $this->$listeNotificationsClasse->removeElement($classe);
    }
}

