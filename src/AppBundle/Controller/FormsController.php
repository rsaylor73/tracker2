<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class FormsController extends Controller
{
    /**
     * @Route("/updateform", name="updateform")
     */
    public function updateformAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('Commonservices')->getsessiondata();
        $userID = $session->get('id');

        $dotID = $request->query->get('dotID');
        $route = $request->query->get('route');
        $id = $request->query->get('id');
        $projectID = $request->query->get('projectID');
        $dotproject = $request->query->get('dotproject');
        $subaccount = $request->query->get('subaccount');
        $projecttypeID = $request->query->get('projecttypeID');
        $description = $request->query->get('description');
        $est_const_cost = $request->query->get('est_const_cost');
        $est_ad_date = $request->query->get('est_ad_date');
        $regionID = $request->query->get('regionID');
        $contacts = $request->query->get('contacts');
        $project_phase = $request->query->get('project_phase');
        $review_type = $request->query->get('review_type');
        $date_received = $request->query->get('date_received');
        $date_completed = $request->query->get('date_completed');
        $reviewID = $request->query->get('reviewID');
        $label = $request->query->get('label');
        $value = "";

        switch ($label) {
            case "Project #":
                $value = $dotproject;
                $sql = "UPDATE `projects` SET `dotproject` = ? WHERE `id` = '$projectID'";
                break;

            case "Sub Account":
                $value = $subaccount;
                $sql = "UPDATE `projects` SET `subaccount` = ? WHERE `id` = '$projectID'";
                break;

            case "projecttypeID": // view project
                $value = $projecttypeID;
                $sql = "UPDATE `projects` SET `projecttypeID` = ? WHERE `id`= '$projectID'";
                break;

            case "description": // view project
                $value = $description;
                $sql = "UPDATE `projects` SET `description` = ? WHERE `id` = '$projectID'";
                break;

            case "est_const_cost": // view project
                $value = $est_const_cost;
                $sql = "UPDATE `projects` SET `est_const_cost` = ? WHERE `id` = '$projectID'";
                break;

            case "est_ad_date": // view project
                $value = $est_ad_date;
                $sql = "UPDATE `projects` SET `est_ad_date` = ? WHERE `id` = '$projectID'";
                break;

            case "regionID": // view project
                $value = $regionID;
                $sql = "UPDATE `projects` SET `regionID` = ? WHERE `id` = '$projectID'";
                break;

            case "contactID": // view project
                $value = $contacts;
                $sql = "UPDATE `projects` SET `contactID` = ? WHERE `id` = '$projectID'";
                break;

            case "Project Phase":
                $value = $project_phase;
                $sql = "UPDATE `review` SET `project_phase` = ? WHERE `reviewID` = '$reviewID'";
                break;

            case "Review Type":
                $value = $review_type;
                $sql = "UPDATE `review` SET `review_type` = ? WHERE `reviewID` = '$reviewID'";
                break;

            case "Date Received":
                $value = $date_received;
                $sql = "UPDATE `review` SET `date_received` = ? WHERE `reviewID` = '$reviewID'";
                break;

            case "Date Completed":
                $value = $date_completed;
                $sql = "UPDATE `review` SET `date_completed` = ? WHERE `reviewID` = '$reviewID'";
                break;
        }
        if ($value != "") {
            $result = $em->getConnection()->prepare($sql);
            $result->bindValue(1, $value);
            $result->execute();
            $type = "success";

            return $this->render('modal/forms_flash_message.html.twig', [
                'type' => $type,
                'label' => $label,
                'text' => 'has been updated.',
            ]);
        } else {
            $type = "danger";

            return $this->render('modal/forms_flash_message.html.twig', [
                'type' => $type,
                'label' => $label,
                'text' => 'failed to updated.',
            ]);
        }
    }
}
