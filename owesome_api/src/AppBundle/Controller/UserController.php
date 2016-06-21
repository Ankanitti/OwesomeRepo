<?php
/**
 * Created by PhpStorm.
 * User: Ankanitti
 * Date: 14/06/16
 * Time: 01:15
 */

namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class UserController extends Controller
{

    /**
     * @Route("/api/users")
     * @Method("POST")
     *
     * @return JsonResponse
     */
    public function postUserAction(Request $request)
    {
        // parameters required
        $parameters_required = explode(',', 'username,password');
        // check if all required parameters are defined
        foreach ($parameters_required as $parameter) {
            if (!$request->get($parameter)) {
                // 422 Unprocessable
                // https://github.com/cartesiaeducation/api/wiki/Conventions-:-protocole-HTTP-1.1#422-unprocessable
                throw new HttpException(422, $parameter . ' is required');
            }
        }
        // get entity manager
        $em = $this->getDoctrine()->getManager();
        // create user
        $user = $em->getRepository('AppBundle:User')->findOneBy(['username' => $request->get('username')]);

        if (!$user) {
            return new JsonResponse(['Success' => false, 'Message' => 'username does not exist']);
        }
        if ($user->getTxtPassword() != $request->get('password')) {
            return new JsonResponse(['Success' => false, 'Message' => 'password is wrong']);
        }
        $profile_id = $em->getRepository('AppBundle:Profile')->findProfileIdByUser($user);
        return new JsonResponse(['Success' => true, 'id' => $profile_id]);
    }
}