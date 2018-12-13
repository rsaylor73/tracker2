<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;

/**
 * Note: This controller has no use
 * other then the start of the site
 * then redirects to the login if not logged
 * in.
 */

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboardAction(Request $request)
    {   
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $id = $session->get('id');

        $userType = $session->get('userType');
        switch ($userType) {
            case "staff":
                $sql = "SELECT `states` FROM `user` WHERE `id` = '$id'";
                $result = $em->getConnection()->prepare($sql);
                $result->execute();
                $states = "";
                while ($row = $result->fetch()) {
                    $states = $row['states'];
                }
                if ($states != "") {
                    $states = unserialize($states);
                }
                $states_list = "";
                if (is_array($states)) {
                    foreach ($states as $key => $value) {
                        $states_list .= "'$value',";
                    }
                }
                $states_list = substr($states_list, 0, -1);


                $sql = "SELECT * FROM `dots` WHERE `stateID` IN ($states_list) ORDER BY `name` ASC";
                $result = $em->getConnection()->prepare($sql);
                $result->execute();
                $data = array();
                $i = "0";
                while ($row = $result->fetch()) {
                    foreach ($row as $key => $value) {
                        $data[$i][$key] = $value;
                    }
                    $i++;
                }
                return $this->render('dashboard/staff.html.twig', [
                    'data' => $data,
                ]);
                break;
            
            case "client":
                return $this->redirectToRoute('clientdashboard');
                break;
            default:
                return $this->redirectToRoute('clientdashboard');
                break; 
        }
    }


}
