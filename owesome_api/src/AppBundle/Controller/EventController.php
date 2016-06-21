<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class EventController extends Controller
{
    /**
     * @Route("/api/events")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function getEventsAction()
    {

        $em = $this->getDoctrine()->getManager();

        // create query
        $qb = $em->createQueryBuilder()
            ->select('e','et','ep','ec','ecu')
            ->from('AppBundle:Event', 'e')
            ->leftJoin('e.transactions', 'et')
            ->leftJoin('e.profiles', 'ep')
            ->leftJoin('e.createdBy', 'ec')
            ->leftJoin('ec.user','ecu')
            ->getQuery()
            ->getArrayResult();
        ;

        return new JsonResponse($qb);
    }

    /**
     * @param $event_id
     *
     * @Route("/api/events")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function getEventAction($event_id)
    {
        $em = $this->getDoctrine()->getManager();

        // create query
        $event = $em->createQueryBuilder()
            ->select('e','et','ep','ec','ecu','etp','etpu')
            ->from('AppBundle:Event', 'e')
            ->leftJoin('e.transactions', 'et')
            ->leftJoin('et.profile', 'etp')
            ->leftJoin('etp.user', 'etpu')
            ->leftJoin('e.profiles', 'ep')
            ->leftJoin('e.createdBy', 'ec')
            ->leftJoin('ec.user','ecu')
            ->where('e.id = :event_id')
            ->setParameter('event_id', $event_id)
            ->getQuery()
            ->getArrayResult();
        ;

        if(!$event)
        {
            throw new HttpException(404, "Event not found");
        }

        return new JsonResponse($event);
    }

    /**
     * @Route("/api/events")
     * @Method("POST")
     *
     * @return JsonResponse
     */
    public function postEventAction(Request $request)
    {
        // parameters required
        $parameters_required = explode(',','name,address,date');

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

        $profile = $em->getRepository('AppBundle:Profile')->findOneById($request->get('organizer'));

        // create user
        $event = new Event();
        $event
            ->setName($request->get('name'))
            ->setAddress($request->get('address'))
            ->setDate(new \DateTime($request->get('date')))
            ->setCreatedBy($profile)
            ->setTotal(0)
        ;

        // Persist to database
        $em->persist($event);
        $em->flush();

        $event_id = $event->getId();

        return new JsonResponse([$event, $event_id]);
    }

    /**
     * @param $event_id
     *
     * @Route("/api/events/{id}")
     * @Method("DELETE")
     *
     * @return JsonResponse
     */
    public function deleteEventAction($event_id)
    {
        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Find live
        $event = $em->getRepository('AppBundle:Event')->find($event_id);

        // Check if live is not found
        if (!$event) {
            // 404 Not Found
            throw new HttpException(404, 'Event not found');
        }

        // Set state to deleted
        $em->remove($event);

        // Persist changes to database
        $em->flush();

        return new JsonResponse(['Success'=>true]);
    }

    public function getByProfileAction($profile_id)
    {
        $em = $this->getDoctrine()->getManager();

        // create query
        $event = $em->createQueryBuilder()
            ->select('e','et','ep','ec','ecu')
            ->from('AppBundle:Event', 'e')
            ->leftJoin('e.transactions', 'et')
            ->leftJoin('e.profiles', 'ep')
            ->leftJoin('e.createdBy', 'ec')
            ->leftJoin('ec.user','ecu')
            ->where('e.profiles.id = :profile_id')
            ->orWhere('e.user.id = :profile_id')
            ->setParameter('profile_id', $profile_id)
            ->getQuery()
            ->getArrayResult();
        ;

        if(!$event)
        {
            throw new HttpException(404, "Event not found");
        }

        return new JsonResponse($event);
    }


    /**
     * @param $event_id
     *
     * @Route("/api/events/{event_id}")
     * @Method("DELETE")
     *
     * @return JsonResponse
     */
    public function putEventAction(Request $request, $event_id) {
        $event_id = $request->get('event');

        $em = $this->getDoctrine()->getManager();
        $event = $em->getRepository('AppBundle:Event')->findOneById($event_id);

        if(!$event)
        {
            // 404 Not Found
            // https://github.com/cartesiaeducation/api/wiki/Conventions-:-protocole-HTTP-1.1#404-not-found
            throw new HttpException(404, 'event not found');
        }

        $update_fields = explode(',','total,finished');
        foreach($update_fields as $update_field) {
            if($update_field == 'total') {
                $event->setTotal($request->get('total'));
            }
            if($update_field == 'finished') {
                $event->setFinished($request->get('finished'));
            }
        }

        $em->persist($event);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }
}