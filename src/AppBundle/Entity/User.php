<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="`user`")
 */

class User extends BaseUser
{

    /**
     * Note: This entity does not use migrations. Running migrations
     * will destroy all the data
     */

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;
    // Note: The getter is not required because the parent class User 
    // already has the getId() method defined.

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $first;
    
    public function getFirst()
    {
        return $this->first;
    }
    public function setFirst($first)
    {
        $this->first = $first;
        return $this;
    }

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $last;

    public function getLast()
    {
        return $this->last;
    }
    public function setLast($last)
    {
        $this->last = $last;
        return $this;
    }

    /**
     * @ORM\Column(type="string", name="userType", nullable=true)
     */
    protected $userType;  

    /**
     * @return mixed
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * @param mixed $userType
     *
     * @return self
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;

        return $this;
    }

    /**
     * @ORM\Column(type="text", name="states", nullable=true)
     */
    protected $states; 

    /**
     * @return mixed
     */
    public function getStates()
    {
        return $this->states;
    }


    /**
     * @param mixed $userType
     *
     * @return self
     */
    public function setStates($states)
    {
        $this->states = $states;

        return $this;
    }
}
