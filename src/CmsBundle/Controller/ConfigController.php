<?php

namespace CmsBundle\Controller;

use CmsBundle\Entity\Page;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Config controller.
 *
 * @Route("/")
 */
class ConfigController extends Controller
{
    /**
     * @Route("admin/site/config/page/{slug}", name="site_config_page", requirements={"slug"="privacy|term"})
     * @Method({"GET", "POST"})
     */
    public function configPageAction(Request $request)
    {

	    $user = $this->getUser();
	    
        $page = new Page();
        $page->setSlug( $request->get('slug') );
        $page->setCreatedUser($user);
        
        $default_page_title = $this->get('translator')->trans('page.default.'.$request->get('slug').'_title', [], 'message');
        $default_page_body = $this->get('translator')->trans('page.default.'.$request->get('slug').'_body', [], 'message');
        
        $page->setTitle($default_page_title);
        $page->setBody($default_page_body);
        
        $form = $this->createForm('CmsBundle\Form\Type\ConfigPageFormType', $page);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($page);
            $em->flush();
            return $this->redirectToRoute('site_index');
        }
        
        return $this->render('@CmsBundle/Resources/views/Config/new.html.twig', array(
            'page' => $page,
            'form' => $form->createView(),
        ));
    }
    	
}