<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Profile;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ProfileController extends Controller
{
    /**
     * @Route("/api/profiles}")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function getProfilesAction()
    {

        $em = $this->getDoctrine()->getManager();

        // create query
        $qb = $em->createQueryBuilder()
            ->select('p', 'pu', 'pt', 'po')
            ->addSelect('pe')
            ->from('AppBundle:Profile', 'p')
            ->leftJoin('p.user', 'pu')
            ->leftJoin('p.events', 'pe')
            ->leftJoin('p.transactions', 'pt')
            ->leftJoin('p.organizer', 'po')
            ->getQuery()
            ->getArrayResult();
        ;

        return new JsonResponse($qb);
    }

    /**
     * @param $profile_id
     *
     * @Route("/api/profiles}")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function getProfileAction($profile_id)
    {
        $em = $this->getDoctrine()->getManager();

        // create query
        $profile = $em->createQueryBuilder()
            ->select('p', 'pu', 'pt', 'po')
            ->addSelect('pe')
            ->from('AppBundle:Profile', 'p')
            ->leftJoin('p.user', 'pu')
            ->leftJoin('p.events', 'pe')
            ->leftJoin('p.transactions', 'pt')
            ->leftJoin('p.organizer', 'po')
            ->where('p.id = :profile_id')
            ->setParameter('profile_id', $profile_id)
            ->getQuery()
            ->getArrayResult();
        ;

        if(!$profile)
        {
            throw new HttpException(404, "profile not found");
        }

        return new JsonResponse($profile);
    }

    /**
     * @Route("/api/profiles")
     * @Method("POST")
     *
     * @return JsonResponse
     */
    public function postProfileAction(Request $request)
    {
        // parameters required
        $parameters_required = explode(',','username,password,email,phone');

        // check if all required parameters are defined
        foreach($parameters_required as $parameter)
        {
            if(!$request->get($parameter))
            {
                // 422 Unprocessable
                // https://github.com/cartesiaeducation/api/wiki/Conventions-:-protocole-HTTP-1.1#422-unprocessable
                throw new HttpException(422, $parameter.' is required');
            }
        }

        // get entity manager
        $em = $this->getDoctrine()->getManager();

        // create user
        $user = new User();
        $user
            ->setUsername($request->get('username'))
            ->setEmail($request->get('email'))
            ->setPlainPassword($request->get('password'))
            ->setTxtpassword($request->get('password'))
        ;

        // init creation comment
        $profile = new Profile();
        $profile
            ->setPhone($request->get('phone'))
            ->setUser($user)
        ;

        // Persist to database
        $em->persist($profile);
        $em->flush();

        return new JsonResponse($profile);
    }

    /**
     * @param $profile_id
     *
     * @Route("/api/profiles/{id}")
     * @Method("DELETE")
     *
     * @return JsonResponse
     */
    public function deleteProfileAction($profile_id)
    {
        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Find live
        $profile = $em->getRepository('AppBundle:Profile')->find($profile_id);

        // Check if live is not found
        if (!$profile) {
            // 404 Not Found
            throw new HttpException(404, 'Profile not found');
        }

        // Set state to deleted
        $em->remove($profile);

        // Persist changes to database
        $em->flush();

        return new JsonResponse(['Success'=>true]);
    }


}