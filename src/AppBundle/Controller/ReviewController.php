<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ReviewController extends Controller
{
    /**
     * @Route("/viewreview/{id}", name="viewreview")
     */
    public function viewreviewAction(Request $request, $id = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();
        $no_charts = "0";
        $found_xml = "0";
        $reviewID = "";
        $projectID = "";

        $sql = "
        SELECT
            `r`.`reviewID`,
            `d`.`logo`,
            `p`.`dotproject`,
            `p`.`subaccount`,
            `p`.`id` AS 'projectID',
            `r`.`review_type`,
            `r`.`project_phase`,
            `r`.`date_received`,
            `r`.`date_completed`,
            `r`.`pdf_file_client`,
            `s`.`state`

        FROM
            `review` r, `projects` p, `dots` d, `state` s

        WHERE
            `r`.`reviewID` = '$id'
            AND `r`.`projectID` = `p`.`id`
            AND `p`.`dotID` = `d`.`id`
            AND `d`.`stateID` = `s`.`state_id`
        ";
        //print "$sql<br>";
        //die;
        $data = array();
        $pdf = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $SubmittalTypes = "";
        while ($row = $result->fetch()) {
            foreach ($row as $key => $value) {
                $data[$key] = $value;
            }
            if ($row['pdf_file_client'] != "") {
                $pdf = "1";
            }
        
            // project phase
            $SubmittalTypes = $this->get('Commonservices')->getSubmittalTypes($row['project_phase'], $row['state']);
            $reviewID = $row['reviewID'];
            $projectID = $row['projectID'];
        }

        // get XML data
        $dataview = $this->get('Commonservices')->viewData($reviewID, $projectID, $search = '');

        // charts
        $found_data = "0";
        $sql = "SELECT `series` FROM `xml_data` WHERE `projectID` = '$projectID' 
        AND `reviewID` = '$reviewID' LIMIT 1";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $found_data = "1";
        }

        $stacked_column = "";
        $chart_data = "";
        $pie2 = "";
        $pie3 = "";
        $pie4 = "";

        $discipline_bar = "";
        $comment_bar = "";

        if ($found_data == "1") {
            $found_xml = "1";

            // disciplines
            $discipline = $this->get('Commonservices')->generateDisciplines($projectID, $reviewID);

            $title = "Test 1";
            $sub1 = "Test 2";
            $sub2 = "Test 3";
            $name1 = "Test 4";
            $name2 = "Test 5";

            $disciplinedata = "";
            $labels = "";
            foreach ($discipline as $key => $value) {
                $disciplinedata .= "$value,";
                $labels .= "'$key',";
            }
            $disciplinedata = substr($disciplinedata, 0, -1);
            $labels = substr($labels, 0, -1);

            $discipline_bar = $this->get('Commonservices')->barChart('chart10','discipline', $disciplinedata, $labels, 'Disciplines', 'Number of Comments', 'Comment Distribution');
            // end disciplines

            // Comment Tyes
            $comment_type = $this->get('Commonservices')->generateCommentTypes($projectID, $reviewID);

            $commentdata = "";
            $labels = "";
            foreach ($comment_type as $key => $value) {
                $commentdata .= "$value,";
                $labels .= "'$key',";
            }
            $commentdata = substr($commentdata, 0, -1);
            $labels = substr($labels, 0, -1);

            $comment_bar = $this->get('Commonservices')->barChart('chart11','comment', $commentdata, $labels, 'Comment Types', 'Number of Comments', 'Comment Distribution');
            // end comment types

            $cdata = $this->get('Commonservices')->generateStackedData($projectID, $reviewID);
            
            $chart_data = $cdata['chart_data'];
            $chart_category = $cdata['chart_category'];
            
            $stacked_column = $this->get('Commonservices')->stackedColumn('container1', $chart_category, 'Comment Distribution', 'Distribution', $chart_data);

            $chart_data = $this->get('Commonservices')->generatePieData($projectID, $reviewID, 'Category');

            $pie2 = $this->get('Commonservices')->pieChart('container2', 'Categories', $chart_data);

            $chart_data = $this->get('Commonservices')->generatePieDataSpecial($projectID, $reviewID, 'Comment_Type', 'Category', 'Biddability');
            $pie3 = $this->get('Commonservices')->pieChart('container3', 'Biddability', $chart_data);

            $chart_data = $this->get('Commonservices')->generatePieDataSpecial($projectID, $reviewID, 'Comment_Type', 'Category', 'Constructability');
            $pie4 = $this->get('Commonservices')->pieChart('container4', 'Constructability', $chart_data);
        } else {
            $no_charts = "1";
        }

        return $this->render('reviews/view.html.twig', [
            "stacked_column" => $stacked_column,
            "pie2" => $pie2,
            "pie3" => $pie3,
            "pie4" => $pie4,
            "no_charts" => $no_charts,
            "SubmittalTypes" => $SubmittalTypes,
            "dataview" => $dataview,
            "found_xml" => $found_xml,
            "data" => $data,
            "pdf" => $pdf,
            'discipline_bar' => $discipline_bar,
            'comment_bar' => $comment_bar,
        ]);
    }

    /**
     * @Route("/open_review/{dotID}", name="open_review")
     */
    public function openreviewAction(Request $request, $dotID = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $sql = "SELECT `id`,`dotproject` FROM `projects` WHERE `dotID` = '$dotID' ORDER BY `dotproject` ASC";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $options = "";
        while ($row = $result->fetch()) {
            $options .= "<option value=\"$row[id]\">$row[dotproject]</option>";
        }

        return $this->render('modal/open_review.html.twig', [
            "dotID" => $dotID,
            "options" => $options,
        ]);
    }

    /**
     * @Route("/load_review", name="load_review")
     */
    public function loadreviewAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $dotproject = $request->query->get('dotproject');
        $sql = "
        SELECT
            `r`.`reviewID`,
            `s`.`Description`

        FROM
            `review` r, `SubmittalTypes` s

        WHERE
            `r`.`projectID` = '$dotproject'
            AND `r`.`project_phase` = `s`.`id`

        ORDER BY `s`.`Description` ASC
        ";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $opt = "";
        while ($row = $result->fetch()) {
            $opt .= "<option value=\"$row[reviewID]\">$row[Description]</option>";
        }
        if ($opt == "") {
            $opt = "<option value=\"\">There are none please click Add New Review</option>";
        }

        return $this->render('reviews/load_review.html.twig', [
            "opt" => $opt,
        ]);
    }

    /**
     * @Route("/deletereview/{id}/{projectID}", name="deletereview")
     */
    public function deletereviewAction(Request $request, $id = '', $projectID = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();
        $no_charts = "0";
        $found_xml = "0";

        $sql = "DELETE FROM `review` WHERE `reviewID` = '$id'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();

        $this->addFlash('success', 'The review was deleted.');
        return $this->redirectToRoute('view_project', [
            'id' => $projectID,
        ]);
    }

    /**
     * @Route("/new_review/{dotID}", name="new_review")
     */
    public function newreviewAction(Request $request, $dotID = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $sql = "
        SELECT
            `p`.`id`,
            `p`.`dotproject`

        FROM
            `projects` p

        WHERE
            `p`.`dotID` = '$dotID'

        ORDER BY `dotproject` ASC
        ";
        $options = "";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $options .= "<option value=\"$row[id]\">$row[dotproject]</option>";
        }

        return $this->render('modal/new_review.html.twig', [
            'options' => $options,
            'dotID' => $dotID,
        ]);
    }

    /**
     * @Route("/create_review/{dotID}/{dotproject}", name="create_review")
     */
    public function createreviewAction(Request $request, $dotID = '', $dotproject = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $sql = "
        SELECT
            `s`.`state`
        FROM
            `dots` d, `state` s
        WHERE
            `d`.`id` = '$dotID'
            AND `d`.`stateID` = `s`.`state_id`
        ";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $state = "";
        while ($row = $result->fetch()) {
            $state = $row['state'];
        }

        $sql = "
        SELECT
            `p`.`id`,
            `p`.`dotproject`,
            `p`.`subaccount`,
            `p`.`description`,
            `r`.`name` AS 'region',
            `t`.`project_type`

        FROM
            `projects` p

        LEFT JOIN `region` r ON `p`.`regionID` = `r`.`id`
        LEFT JOIN `project_type` t ON `p`.`projecttypeID` = `t`.`id`

        WHERE
            `p`.`dotID` = '$dotID'
            AND `p`.`id` = '$dotproject'
        ";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $data = array();
        while ($row = $result->fetch()) {
            foreach ($row as $key => $value) {
                $data[$key] = $value;
            }
        }

        $SubmittalTypes = $this->get('Commonservices')->getSubmittalTypes(null, $state); 

        return $this->render('reviews/create_review.html.twig', [
            'dotID' => $dotID,
            'dotproject' => $dotproject,
            'data' => $data,
            'SubmittalTypes' => $SubmittalTypes,
        ]);
    }

    /**
     * @Route("/save_review", name="save_review")
     */
    public function savereviewAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $dotID = $request->request->get('dotID');
        $dotproject = $request->request->get('dotproject');
        $review_type = $request->request->get('review_type');
        $project_phase = $request->request->get('project_phase');
        $date_received = $request->request->get('date_received');

        $sql = "INSERT INTO `review` 
        (`projectID`,`review_type`,`project_phase`,`date_received`)
        VALUES
        ('$dotproject','$review_type','$project_phase','$date_received')
        ";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $reviewID = $em->getConnection()->lastInsertId();

        if ($reviewID != "") {
            $this->addFlash('success', 'The review was added.');
            return $this->redirectToRoute('viewreview', [
                'id' => $reviewID,
            ]);
        } else {
            $this->addFlash('danger', 'The review failed to add.');
            return $this->redirectToRoute('dots', [
                'id' => $dotID,
            ]);  
        }
    }
}
