<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
	public function getListOfUsers()
	{
		$sql =
			"
			SELECT
				u.first,
				u.last,
				u.username,
				u.enabled,
				u.states,
				u.roles,
				u.id
			FROM
				AppBundle:User u
			"
		;
        $results = $this->getEntityManager()
        ->createQuery($sql)
        ->getResult();

        $states = "";
        $temp = "";

        foreach ($results as $key => $value) {
        	$states = $results[$key]['states'];
        	$temp = unserialize($states);
        	$results[$key]['states'] = $temp;
        }
        return ($results);
	}
}
