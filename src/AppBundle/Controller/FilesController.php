<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FilesController extends Controller
{

    /**
     * @Route("/viewpdf/{id}", name="viewpdf")
     */
    public function viewpdfAction(Request $request, $id = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $upload_dir = $this->container->getParameter('upload_dir');

        $sql = "
        SELECT
            `pdf_file_server`,
            `pdf_file_client`,
            `pdf_file_type`,
            `pdf_file_size`
        FROM
            `review`

        WHERE
            `reviewID` = '$id'
        ";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $pdf_file = "";
        $original_name = "";
        while ($row = $result->fetch()) {
            $pdf_file = $row['pdf_file_server'];
            $original_name = $row['pdf_file_client'];
        }
        if(file_exists($upload_dir.$pdf_file)) {
            $filename=$upload_dir.$pdf_file;
            //return new BinaryFileResponse($filename);
            $response = new BinaryFileResponse($filename);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $original_name
            );
            return($response);
        } else {
            throw new NotFoundHttpException('404 PDF Not Found or the review does not have a PDF file.');
        }
    }

    /**
     * @Route("/upload_xml/{id}", name="upload_xml")
     */
    public function uploadxmlAction(Request $request, $id = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        return $this->render('modal/review_upload_xml.html.twig', [
            "reviewID" => $id,
        ]);
    }

    /**
     * @Route("/upload_pdf/{id}", name="upload_pdf")
     */
    public function uploadpdfAction(Request $request, $id = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        return $this->render('modal/review_upload_pdf.html.twig', [
            "reviewID" => $id,
        ]);
    }

    /**
     * @Route("/upload_cost/{id}", name="upload_cost")
     */
    public function uploadcostAction(Request $request, $id = '')
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        return $this->render('modal/review_upload_cost.html.twig', [
            "reviewID" => $id,
        ]);
    }

   /**
     * @Route("/save_xml", name="save_xml")
     */
    public function savexmlAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();
        $upload_dir = $this->container->getParameter('upload_dir');
        $reviewID = $request->request->get('reviewID');

        $file = $request->files->get('xml_file');
        $filename = $request->files->get('xml_file')->getClientOriginalName();
        $ext = $request->files->get('xml_file')->getClientOriginalExtension();
        $newfile = date("U") . rand(100, 1000) . "." . $ext;

        if ($ext != "xml") {
            $this->addFlash('danger', "The system was expecting an XML file. You uploaded an $ext file.");
            return $this->redirectToRoute('viewreview', [
                'id' => $reviewID,
            ]);
        }

        $sql = "SELECT `projectID` FROM `review` WHERE `reviewID` = '$reviewID'";
        $result = $em->getConnection()->prepare($sql);
        $result->execute();
        $projectID = "";
        while ($row = $result->fetch()) {
            $projectID = $row['projectID'];
        }

        /**
         * found Symfony way not documented very well
         * so using tradditional php way.
         */
        $tmpName  = $_FILES['xml_file']['tmp_name'];
        $newlocation = $upload_dir . $newfile;
        move_uploaded_file($tmpName, $newlocation);

        $this->get('Commonservices')->processXml($reviewID, $projectID, $newlocation);
        $this->addFlash('success', 'The XML file was uploaded and processed.');
        return $this->redirectToRoute('viewreview', [
            'id' => $reviewID,
        ]);
    }

   /**
     * @Route("/save_pdf", name="save_pdf")
     */
    public function savepdfAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();

        $upload_dir = $this->container->getParameter('upload_dir');
        $reviewID = $request->request->get('reviewID');

        $file = $request->files->get('pdf_file');
        $filename = $request->files->get('pdf_file')->getClientOriginalName();
        $ext = $request->files->get('pdf_file')->getClientOriginalExtension();
        $newfile = date("U") . rand(100, 1000) . "." . $ext;

        if ($ext != "pdf") {
            $this->addFlash('danger', "The system was expecting an PDF file. You uploaded an $ext file.");
            return $this->redirectToRoute('viewreview', [
                'id' => $reviewID,
            ]);
        }

        /**
         * found Symfony way not documented very well
         * so using tradditional php way.
         */
        $fileSize = $_FILES['pdf_file']['size'];
        $fileType = $_FILES['pdf_file']['type'];
        $tmpName  = $_FILES['pdf_file']['tmp_name'];
        $newlocation = $upload_dir . $newfile;
        move_uploaded_file($tmpName, $newlocation);

        $sql = "UPDATE `review` SET 
        `pdf_file_server` = '$newfile',
        `pdf_file_client` = '$filename',
        `pdf_file_size` = '$fileSize',
        `pdf_file_type` = '$fileType'
        WHERE `reviewID` = '$reviewID'
        ";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();

        $this->addFlash('success', 'The PDF file was uploaded and processed.');
        return $this->redirectToRoute('viewreview', [
            'id' => $reviewID,
        ]);
    }

   /**
     * @Route("/save_cost", name="save_cost")
     */
    public function savecostAction(Request $request)
    {
        /**
         * This will display the modal search window
         */
        $em = $this->getDoctrine()->getManager();
        $upload_dir = $this->container->getParameter('upload_dir');
        $reviewID = $request->request->get('reviewID');

        $file = $request->files->get('cost_file');
        $filename = $request->files->get('cost_file')->getClientOriginalName();
        $ext = $request->files->get('cost_file')->getClientOriginalExtension();
        $newfile = date("U") . rand(100, 1000) . "." . $ext;

        if ($ext != "pdf") {
            $this->addFlash('danger', "The system was expecting an PDF file. You uploaded an $ext file.");
            return $this->redirectToRoute('viewreview', [
                'id' => $reviewID,
            ]);
        }

        /**
         * found Symfony way not documented very well
         * so using tradditional php way.
         */
        $fileSize = $_FILES['cost_file']['size'];
        $fileType = $_FILES['cost_file']['type'];
        $tmpName  = $_FILES['cost_file']['tmp_name'];
        $newlocation = $upload_dir . $newfile;
        move_uploaded_file($tmpName, $newlocation);

        $sql = "UPDATE `review` SET 
        `cost_file_server` = '$newfile',
        `cost_file_client` = '$filename',
        `cost_file_size` = '$fileSize',
        `cost_file_type` = '$fileType'
        WHERE `reviewID` = '$reviewID'
        ";

        $result = $em->getConnection()->prepare($sql);
        $result->execute();

        $this->addFlash('success', 'The PDF file was uploaded and processed.');
        return $this->redirectToRoute('viewreview', [
            'id' => $reviewID,
        ]);
    }
}
