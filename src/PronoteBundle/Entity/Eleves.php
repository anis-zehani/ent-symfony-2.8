<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Eleves
 *
 * @ORM\Table(name="eleves")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\ElevesRepository")
 */
class Eleves
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
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=255)
     */
    private $prenom;

    /**
     * @ORM\ManyToOne(targetEntity="Parents", inversedBy="enfants")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $parent;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Classe")
     */
    private $classe;
    
    /**
     * @ORM\OneToMany(targetEntity="Notifications" , mappedBy="eleve")
     */
    private $listeNotifications;
    
    /**
     * @ORM\OneToMany(targetEntity="Notes" , mappedBy="eleve")
     */
    private $listeNotes;
    
    /**
     * @ORM\OneToMany(targetEntity="Assiduite" , mappedBy="eleve")
     */
    private $listeAssiduites;
    
    /**
     * @ORM\OneToMany(targetEntity="Bulletin" , mappedBy="eleve")
     */
    private $listeBulletins;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Ecole")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $ecole;
    
    ///**************Getters et Setters Ecole (ManyToOne)*****************///
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
     * @return Eleves
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
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

    /**
     * Set prenom
     *
     * @param string $prenom
     *
     * @return Eleves
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * @return Parents
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Parents $parent
     */
    public function setParent(Parents $parent)
    {
        $this->parent = $parent;
        
    }

    //Getters et Setters Classe (ManyToOne)
    /**
     * @return Classe
     */
    public function getClasse()
    {
        return $this->classe;
    }

    /**
     * @param Classe $classe
     */
    public function setClasse(Classe $classe)
    {
        $this->classe = $classe;
        //return this;
    }
    
    
    ///**************GESTION DES NOTIFICATIONS*****************///
    
    public function __construct()
    {
        if(!empty($this->$listeNotifications))
        {
            $this->$listeNotifications = new ArrayCollection();
        }
        
    }
    
    /**
     * @return ArrayCollection
     */
    public function getListeNotifications()
    {
        return $this->$listeNotifications;
    }
    
    /**
     * Add Notification
     *
     * @param Eleves $eleve
     *
     * @return Notifications
     */
    public function addNotification(Eleves $eleve)
    {
        $this->$listeNotifications[] = $eleve;
        return $this;
    }
    
    /**
     * Remove Notification
     *
     * @param Eleves $eleve
     */
    public function removeNotification(Eleves $eleve)
    {
        $this->$listeNotifications->removeElement($eleve);
    }
    


}

