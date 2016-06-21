<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Transaction;
use AppBundle\Entity\Profile;
use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class TransactionController extends Controller
{
    /**
     * @Route("/api/transactions}")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function getTransactionsAction()
    {

        $em = $this->getDoctrine()->getManager();

        // create query
        $qb = $em->createQueryBuilder()
            ->select('t', 'tp', 'te', 'tpu')
            ->from('AppBundle:Transaction', 't')
            ->leftJoin('t.profile', 'tp')
            ->leftJoin('tp.user', 'tpu')
            ->leftJoin('t.event', 'te')
            ->getQuery()
            ->getArrayResult();
        ;

        return new JsonResponse($qb);
    }

    /**
     * @param $transaction_id
     *
     * @Route("/api/transactions}")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function getTransactionAction($transaction_id)
    {
        $em = $this->getDoctrine()->getManager();

        // create query
        $transaction = $em->createQueryBuilder()
            ->select('t', 'tp', 'te')
            ->from('AppBundle:Transaction', 't')
            ->leftJoin('t.profile', 'tp')
            ->leftJoin('t.event', 'te')
            ->where('t.id = :transaction_id')
            ->setParameter('transaction_id', $transaction_id)
            ->getQuery()
            ->getArrayResult();
        ;

        if(!$transaction)
        {
            throw new HttpException(404, "Transaction not found");
        }

        return new JsonResponse($transaction);
    }

    /**
     * @Route("/api/transactions")
     * @Method("POST")
     *
     * @return JsonResponse
     */
    public function postTransactionAction(Request $request)
    {
        // parameters required
        $parameters_required = explode(',','event,profile');

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

        $event = $em->getRepository('AppBundle:Event')->findOneById($request->get('event'));
        $profile = $em->getRepository('AppBundle:Profile')->findOneById($request->get('profile'));

        // create user
        $transaction = new Transaction();
        $transaction
            ->setEvent($event)
            ->setProfile($profile)
            ->setSum(0)
        ;

        $event->addProfile($profile);
        $profile->addEvent($event);
        // Persist to database
        $em->persist($transaction, $event, $profile);
        $em->flush();

        return new JsonResponse($transaction);
    }

    /**
     * @param $transaction_id
     *
     * @Route("/api/transactions/{id}")
     * @Method("DELETE")
     *
     * @return JsonResponse
     */
    public function deleteTransactionAction($transaction_id)
    {
        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Find live
        $transaction = $em->getRepository('AppBundle:Transaction')->find($transaction_id);

        // Check if live is not found
        if (!$transaction) {
            // 404 Not Found
            throw new HttpException(404, 'Transaction not found');
        }

        // Set state to deleted
        $em->remove($transaction);

        // Persist changes to database
        $em->flush();

        return new JsonResponse(['Success'=>true]);
    }

    /**
     * @param $transaction_id
     *
     * @Route("/api/transactions/{transaction_id}")
     * @Method("PUT")
     *
     * @return JsonResponse
     */
    public function putEventAction(Request $request, $transaction_id) {
        $transaction_id = $request->get('transaction');

        $em = $this->getDoctrine()->getManager();
        $transaction = $em->getRepository('AppBundle:Transaction')->findOneById($transaction_id);

        if(!$transaction)
        {
            // 404 Not Found
            // https://github.com/cartesiaeducation/api/wiki/Conventions-:-protocole-HTTP-1.1#404-not-found
            throw new HttpException(404, 'event not found');
        }

        $update_fields = explode(',','sum,settled');
        foreach($update_fields as $update_field) {
            if($update_field == 'sum') {
                $transaction->setSum($request->get('sum'));
            }
            if($update_field == 'settled') {
                $transaction->setSettled($request->get('settled'));
            }
        }

        $em->persist($transaction);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

}