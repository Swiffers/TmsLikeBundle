<?php

/**
 *
 * @author:  Eddie BARRACO <eddie.barraco@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\LikeBundle\Controller\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Util\Codes;
use JMS\Serializer\SerializationContext;
use Tms\Bundle\RestBundle\Formatter\AbstractHypermediaFormatter;
use Tms\Bundle\LikeBundle\Entity\UriLike;
use Tms\Bundle\LikeBundle\Form\UriLikeType;

/**
 * UriLike API REST controller
 */
class ApiUriLikeController extends FOSRestController
{
    /**
     * [GET] /urilikes
     * Retrieve a set of urilikes
     *
     * @QueryParam(name="uri", nullable=true, description="(optional) Uri")
     * @QueryParam(name="user_id", nullable=true, description="(optional) User id")
     * @QueryParam(name="limit", requirements="^\d+$", default=20, strict=true, nullable=true, description="(optional) Limit")
     * @QueryParam(name="offset", requirements="^\d+$", strict=true, nullable=true, description="(optional) Offset")
     * @QueryParam(name="page", requirements="^\d+$", strict=true, nullable=true, description="(optional) Page number")
     * @QueryParam(name="sort", array=true, nullable=true, description="(optional) Sort")
     *
     * @param string  $uri
     * @param string  $user_id
     * @param integer $limit
     * @param integer $offset
     * @param integer $page
     * @param array   $sort
     */
    public function getUrilikesAction(
        $uri     = null,
        $user_id = null,
        $limit   = null,
        $offset  = null,
        $page    = null,
        $sort    = null
    )
    {
        $view = $this->view(
            $this
                ->get('tms_rest.formatter.factory')
                ->create(
                    'orm_collection',
                    $this->getRequest()->get('_route'),
                    $this->getRequest()->getRequestFormat()
                )
                ->setObjectManager(
                    $this->get('doctrine.orm.entity_manager'),
                    $this
                        ->get('tms_like.manager.uri_like')
                        ->getEntityClass()
                )
                ->setCriteria(array(
                    'uri' => $uri,
                    'userId' => $user_id
                ))
                ->setExtraQuery(array(
                    'uri' => $uri,
                    'user_id' => $user_id
                ))
                ->setSort($sort)
                ->setLimit($limit)
                ->setOffset($offset)
                ->setPage($page)
                ->format()
            ,
            Codes::HTTP_OK
        );

        $serializationContext = SerializationContext::create()
            ->setGroups(array(
                AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_COLLECTION
            ))
        ;
        $view->setSerializationContext($serializationContext);

        return $this->handleView($view);
    }

    /**
     * [GET] /urilikes/{id}
     * Retrieve an urilike
     *
     * @Route(requirements={"id" = "^[a-zA-Z0-9_-]+$"})
     *
     * @param string  $id
     */
    public function getUrilikeAction($id)
    {
        try {
            $view = $this->view(
                $this
                    ->get('tms_rest.formatter.factory')
                    ->create(
                        'item',
                        $this->getRequest()->get('_route'),
                        $this->getRequest()->getRequestFormat()
                    )
                    ->setObjectManager(
                        $this->get('doctrine.orm.entity_manager'),
                        $this
                            ->get('tms_like.manager.uri_like')
                            ->getEntityClass()
                    )
                    ->format()
                ,
                Codes::HTTP_OK
            );

            $serializationContext = SerializationContext::create()
                ->setGroups(array(
                    AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ITEM
                ))
            ;

            $view->setSerializationContext($serializationContext);

            return $this->handleView($view);
        } catch(NotFoundHttpException $e) {
            return $this->handleView($this->view(
                array(),
                $e->getStatusCode()
            ));
        }
    }

    /**
     * [POST] /urilikes
     * Create a urilike
     */
    public function postUrilikeAction(Request $request)
    {
        $uriLike = new UriLike();
        $form = $this->createForm(UriLikeType::class, $uriLike, array(
            'csrf_protection' => false,
        ));

        $data = $request->request->all();

        $form->submit($data);

        if ($form->isValid()) {
            try {
                $this
                    ->get('tms_like.manager.uri_like')
                    ->add($uriLike)
                ;

                $view = $this->view(
                    $this
                    ->get('tms_rest.formatter.factory')
                    ->create(
                        'item',
                        $this->getRequest()->get('_route'),
                        $this->getRequest()->getRequestFormat(),
                        array('id' => $uriLike->getId())
                    )
                    ->setObjectManager(
                        $this->get('doctrine.orm.entity_manager'),
                        $this
                        ->get('tms_like.manager.uri_like')
                        ->getEntityClass()
                    )
                    ->format()
                    ,
                    Codes::HTTP_CREATED
                );

                $serializationContext = SerializationContext::create()
                    ->setGroups(array(
                        AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ITEM
                    ))
                ;
                $view->setSerializationContext($serializationContext);

                return $this->handleView($view);
            } catch (\Exception $e) {
                $view = $this->view(
                    array('error' => $e->getMessage()),
                    Codes::HTTP_INTERNAL_SERVER_ERROR
                );

                return $this->handleView($view);
            }
        } else {
            $errors = array();

            foreach ($form->getErrors() as $key => $error) {
                $errors[] = $error->getMessage();
            }

            $view = $this->view(
                array('errors' => $errors),
                Codes::HTTP_BAD_REQUEST
            );

            return $this->handleView($view);
        }
    }
}
