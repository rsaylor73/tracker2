<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;

/**
 * @Route("admin")
 */
class AdminController extends Controller
{
    /**
     * @Route("/", methods={"GET","POST"}, name="tls_list_users")
     */
    public function homeAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
        $pagenator = $this->get('knp_paginator');
    	//$query = $em->getRepository(User::class)->findAll();
        $query = $em->getRepository(User::class)->getListOfUsers();

        $results = $pagenator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 20)
        );

        return $this->render('admin/list_users.html.twig', [
        	'data' => $results,
        ]);
    }

    /**
     * @Route("/newuser", methods="GET", name="tls_new_user")
     */
    public function newuserAction(Request $request)
    {
        $username = $request->query->get('username');
        $email = $request->query->get('email');
        $first = $request->query->get('first');
        $last = $request->query->get('last');

        return $this->render('admin/new_user.html.twig', [
            'username' => $username,
            'email' => $email,
            'first' => $first,
            'last' => $last,
        ]);
    }

    /**
     * @Route("/saveuser", methods="POST", name="tls_save_user")
     */
    public function saveuserAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $first = $request->request->get('first');
        $last = $request->request->get('last');
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $email = $request->request->get('email');
        $enabled = $request->request->get('enabled');
        $user_roles = $request->request->get('user_roles');
        $states = $request->request->get('states');
        /**
         * check if username is available
         */
        $repository = $em->getRepository(User::class);
        $result = $repository->findByUsername(['username' => $username]);
        $count = count($result);
        if ($count > 0) {
            $this->addFlash('danger', 'The username entered is not available.');
            return $this->redirectToRoute('tls_new_user', [
                'username' => $username,
                'email' => $email,
            ]);
        }

        /**
         * check if email is available
         */
        $repository = $em->getRepository(User::class);
        $result = $repository->findByEmail(['email' => $email]);
        $count = count($result);
        if ($count > 0) {
            $this->addFlash('danger', 'The email entered is not available.');
            return $this->redirectToRoute('tls_new_user', [
                'username' => $username,
                'email' => $email,
            ]);
        }

        /**
         * Add the user
         */

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $usernameCanonical = strtolower($username);
        $emailCanonical = strtolower($email);
        if ($enabled == "true") {
            $enabled = true;
        } else {
            $enabled = false;
        }

        $newuser = new User();
        $newuser->setFirst($first);
        $newuser->setLast($last);
        $newuser->setUsername($username);
        $newuser->setUsernameCanonical($usernameCanonical);
        $newuser->setEmail($email);
        $newuser->setEmailCanonical($emailCanonical);
        $newuser->setEnabled($enabled);
        $newuser->setPassword($password_hash);
        $newuser->setRoles($user_roles);
        $newuser->setStates(serialize($states));
        $newuser->setUserType($request->request->get('userType'));

        $em->persist($newuser);
        $em->flush();

        if ($newuser->getUsername() != "") {
            $this->addFlash('success', 'The user was added.');
        } else {
            $this->addFlash('danger', 'The user failed added.');
        }

        return $this->redirectToRoute('tls_list_users');        
    }

    /**
     * @Route("/deleteuser/{id}", methods="GET", name="tls_delete_user")
     */
    public function deleteuserAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($id);
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'The user was removed.');
        return $this->redirectToRoute('tls_list_users');         
    }

    /**
     * @Route("/edit/{id}", methods="GET", name="tls_edit_user")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $sql =
            "
            SELECT
                u.id,
                u.first,
                u.last,
                u.states,
                u.username,
                u.email,
                u.enabled,
                u.roles,
                u.userType

            FROM
                AppBundle:User u

            WHERE
                u.id = :id
            "
        ;
        $query = $em->createQuery($sql);
        $query->setParameter('id', $id);
        $results = $query->execute();

        $states = unserialize($results[0]['states']);
        $results[0]['states'] = $states;

        //$data = $em->getRepository(User::class)->find($id);
        return $this->render('admin/edit_user.html.twig', [
            'data' => $results,
        ]);
    }

    /**
     * @Route("/updateuser", methods="POST", name="tls_update_user")
     */
    public function updateuserAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $id = $request->request->get('id');
        $first = $request->request->get('first');
        $last = $request->request->get('last');
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $email = $request->request->get('email');
        $enabled = $request->request->get('enabled');
        $user_roles = $request->request->get('user_roles');
        $states = $request->request->get('states');
        $userType = $request->request->get('userType');

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $usernameCanonical = strtolower($username);
        $emailCanonical = strtolower($email);
        if ($enabled == "true") {
            $enabled = true;
        } else {
            $enabled = false;
        }

        $updateuser = $em->getRepository(User::class)->find($id);
        if (!$updateuser) {
            throw $this->createNotFoundException(
                'The user could not be found for id '.$id
            );
        }

        /**
         * Update the username if requested.
         * TBD : Need to check if username is available
         */
        if ($username != "") {
            $repository = $em->getRepository(User::class);
            $result = $repository->findByUsername(['username' => $username]);
            $count = count($result);
            if ($count > 1) {
                $this->addFlash('danger', 'The username entered is not available.');
                return $this->redirectToRoute('tls_list_users');
            }

            $updateuser->setUsername($username);
            $updateuser->setUsernameCanonical($usernameCanonical);
        }

        /**
         * Update the email if requested.
         */
        if ($email != "") {
            /**
             * Get the current email
             */
            $data = $em->getRepository(User::class)->find($id);
            $current_email = $data->getEmail();
            if ($current_email != $email) {
                $repository = $em->getRepository(User::class);
                $result = $repository->findByEmail(['email' => $email]);
                $count = count($result);
                if ($count > 0) {
                    $this->addFlash('danger', 'The email entered is not available.');
                    return $this->redirectToRoute('tls_list_users');
                }
            }

            $updateuser->setEmail($email);
            $updateuser->setEmailCanonical($emailCanonical);
        }

        /**
         * Update the user status
         */
        $updateuser->setEnabled($enabled);

        /**
         * Update the contact details
         */
        $updateuser->setFirst($first);
        $updateuser->setLast($last);

        $updateuser->setUserType($userType);

        /**
         * Update the password if requested.
         */
        if ($password != "") {
            $updateuser->setPassword($password_hash);
        }

        /**
         * Update the user role.
         */
        $user_roles = array_unique($user_roles);
        $updateuser->setRoles($user_roles);

        $updateuser->setStates(serialize($states));

        /**
         * Commit the changes to the database
         */
        $em->persist($updateuser);
        $em->flush();

        $this->addFlash('success', 'The user was updated.');
        return $this->redirectToRoute('tls_list_users'); 
    }
}
