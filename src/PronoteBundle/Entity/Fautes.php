<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fautes
 *
 * @ORM\Table(name="fautes")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\FautesRepository")
 */
class Fautes
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
     * @ORM\Column(name="typeFaute", type="string", length=255)
     */
    private $typeFaute;
    

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
     * Set typeFaute
     *
     * @param string $typeFaute
     *
     * @return Fautes
     */
    public function setTypeFaute($typeFaute)
    {
        $this->typeFaute = $typeFaute;

        return $this;
    }

    /**
     * Get typeFaute
     *
     * @return string
     */
    public function getTypeFaute()
    {
        return $this->typeFaute;
    }
}

