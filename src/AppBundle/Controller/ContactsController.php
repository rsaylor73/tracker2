<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ContactsController extends Controller
{

    /**
     * @Route("/contacts", name="contacts")
     */
    public function contactsAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $sql = "
        SELECT
            `c`.`id`,
            `c`.`first`,
            `c`.`last`,
            `c`.`phone`,
            `c`.`email`,
            `s`.`state_abbr`

        FROM
            `contacts` c

        LEFT JOIN `state` s ON `c`.`stateID` = `s`.`state_id`

        WHERE
            1

        ORDER BY `s`.`state` ASC, `c`.`last` ASC, `c`.`first` ASC
        ";
        $data = array();
        $i = "0";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            foreach ($row as $key => $value) {
                $data[$i][$key] = $value;
            }
            $i++;
        }
        return $this->render('contacts/list.html.twig', [
            'data' => $data,
        ]);
    }

    /**
     * @Route("/editcontact/{id}", name="editcontact")
     */
    public function editcontactAction(Request $request, $id)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $sql = "
        SELECT
            `c`.`id`, 
            `c`.`first`,
            `c`.`last`,
            `c`.`email`,
            `c`.`phone`

        FROM
            `contacts` c

        WHERE
            `c`.`id` = '$id'
        ";
        $data = array();
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            foreach ($row as $key => $value) {
                $data[$key] = $value;
            }
        }
        return $this->render('contacts/edit.html.twig', [
            'data' => $data,
        ]);
    }

    /**
     * @Route("/updatecontact", name="updatecontact")
     */
    public function updatecontactAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $id = $request->request->get('id');
        $first = $request->request->get('first');
        $last = $request->request->get('last');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');

        $sql = "UPDATE `contacts` SET
        `first` = ?,
        `last` = ?,
        `email` = ?,
        `phone` = ?
        WHERE `id` = '$id'
        ";
        $result = $em->getConnection()->prepare($sql);
        $result->bindValue(1, $first);
        $result->bindValue(2, $last);
        $result->bindValue(3, $email);
        $result->bindValue(4, $phone);
        $result->execute();

        $this->addFlash('success', 'The contact was updated.');
        return $this->redirectToRoute('contacts');
    }

    /**
     * @Route("/deletecontact/{id}", name="deletecontact")
     */
    public function deletecontactAction(Request $request, $id)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $sql = "DELETE FROM `contacts` WHERE `id` = '$id'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();

        $this->addFlash('success', 'The contact was deleted.');
        return $this->redirectToRoute('contacts');
    }

    /**
     * @Route("/addcontact", name="addcontact")
     */
    public function addcontactAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $dotID = $request->query->get('dotID');
        $mode = $request->query->get('mode');
        $projectID = $request->query->get('projectID');
        $label = $request->query->get('label');
        $ajax = $request->query->get('ajax');

        return $this->render('contacts/new.html.twig', [
            'dotID' => $dotID,
            'mode' => $mode,
            'projectID' => $projectID,
            'label' => $label,
            'ajax' => $ajax,
        ]);
    }

    /**
     * @Route("/savecontact", name="savecontact")
     */
    public function savecontactAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $dotID = $request->query->get('dotID');
        $mode = $request->query->get('mode');
        $projectID = $request->query->get('projectID');
        $first = $request->query->get('first');
        $last = $request->query->get('last');
        $email = $request->query->get('email');
        $phone = $request->query->get('phone');

        $sql = "SELECT `stateID` FROM `dots` WHERE `id` = '$_GET[dotID]'";
        $stateID = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $stateID = $row['stateID'];
        }

        $sql = "INSERT INTO `contacts` (`stateID`,`first`,`last`,`email`,`phone`) VALUES
        ('$stateID','$first','$last','$email','$phone')";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $contactID = $em->getConnection()->lastInsertId();

        $sql = "
        SELECT
            `c`.`id`,
            `c`.`first`,
            `c`.`last`,
            `c`.`email`

        FROM
            `dots` d, `contacts` c

        WHERE
            `d`.`id` = '$dotID'
            AND `d`.`stateID` = `c`.`stateID`

        ORDER BY `c`.`last` ASC, `c`.`first` ASC
        ";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $contacts = "";
        while ($row = $result->fetch()) {
            if ($row['id'] == $contactID) {
                $contacts .= "<option selected value=\"$row[id]\">$row[first] $row[last]</option>";
            } else {
                $contacts .= "<option value=\"$row[id]\">$row[first] $row[last]</option>";
            }
        }

        if ($mode == "view") {
            return $this->render('contacts/view_contact_list.html.twig', [
                'contacts' => $contacts,
            ]);
        } else {
            return $this->render('contacts/view_contact_list2.html.twig', [
                'contacts' => $contacts,
            ]);
        }
    }
}
