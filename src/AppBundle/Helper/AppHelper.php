<?php

namespace AppBundle\Helper;

// Injection Classes
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// Entities
use AppBundle\Entity\Setting;
use CmsBundle\Entity\Image;
use CmsBundle\Entity\Article;

// on Source
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class AppHelper 
{
	
	protected $serviceContainer;
	protected $tokenStorage;
	protected $userManager;
	protected $entityManager;
	protected $router;
	
	protected $user;
	
	public function __construct(
		ContainerInterface $serviceContainer, 
		TokenStorageInterface $tokenStorage,
		UserManagerInterface $userManager, 
		EntityManagerInterface $entityManager, 
		UrlGeneratorInterface $router
	){
		
		$this->serviceContainer = $serviceContainer;
		$this->tokenStorage = $tokenStorage;
		$this->userManager = $userManager;
		$this->entityManager = $entityManager;
		$this->router = $router;
		
		$this->user = $this->tokenStorage->getToken()->getUser();
	}
	public function curlRequest($url, $params=array()){
		
		if(!$url) return;
		
		$curl = curl_init($url);
		
		curl_setopt($curl,CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl,CURLOPT_COOKIEJAR,      'cookie');
		curl_setopt($curl,CURLOPT_COOKIEFILE,     'tmp');
		curl_setopt($curl,CURLOPT_FOLLOWLOCATION, TRUE);
		
		return curl_exec($curl);
		
	}
	public function getParameter($name)
	{
		return $this->serviceContainer->getParameter($name);
	}
	public function getSetting($slug)
	{
		$setting = $this->entityManager->getRepository('AppBundle:Setting')->findOneBySlug($slug);
		if(!$setting) return null;
		return $setting->getValue();
		
	}
	public function setSetting($slug, $value=null)
	{
		
		$setting = $setting = $this->entityManager->getRepository('AppBundle:Setting')->findOneBySlug($slug);
		if(!$setting) $setting = new Setting();
		
		$setting->setSlug($slug);
		$setting->setValue($value);
		
		$this->entityManager->persist($setting);
		$this->entityManager->flush();
		
		return $setting;
	}
	public function setSettings($key, $parameters)
	{
		foreach($parameters as $slug=>$value)
		{
			$setting_slug = $key.'_'.$slug;
			$this->setSetting($setting_slug, $value);
		}
		
	}
	public function renderSetting($setting_slug, $params = array())
	{
		$source = $this->getSetting($setting_slug);
		if(!$source) return null;
		
		$params['login'] = $this->router->generate('fos_user_security_login', array(), UrlGeneratorInterface::ABSOLUTE_URL);
		$params['resetting'] = $this->router->generate('fos_user_resetting_request', array(), UrlGeneratorInterface::ABSOLUTE_URL);
		$params['profile'] = $this->router->generate('fos_user_profile_show', array(), UrlGeneratorInterface::ABSOLUTE_URL);
		$params['homepage'] = $this->router->generate('site_index', array(), UrlGeneratorInterface::ABSOLUTE_URL);
		
		$env = new \Twig_Environment(new \Twig_Loader_Array());
		$template = $env->createTemplate($source);
		
		return $template->render($params);
		
	}
	public function sendEmailBySetting($to, $subject_slug, $body_slug, $bcc=false, $params=array())
	{
		$subject = $this->renderSetting($subject_slug, $params);
		$body = $this->renderSetting($body_slug, $params);
		return $this->sendEmail($to, $subject, $body, $params, $bcc);
	}
	public function sendEmail($to, $subject, $body, $params=array(), $bcc=false)
	{
		$message = \Swift_Message::newInstance()
		    ->setSubject( $subject )
		    ->setTo( $to )
		    ->setBody( $body )
		;
		if( isset($param['from']) ){
			$message->setFrom($param['from']);
		} else {
			$message->setFrom( $this->serviceContainer->getParameter('mailer_address') );
		}
		if($bcc){
			$message->setBcc( $this->serviceContainer->getParameter('mailer_address') );
		} else {
			if( isset($param['bcc']) ) $message->setBcc($param['bcc']);
		}
		if( isset($param['cc']) ){
			$message->setCc($param['cc']);
		}
		return $this->serviceContainer->get('mailer')->send($message);
		
	}
	public function hasAdmin()
	{
	    $qb = $this->entityManager->createQueryBuilder();
	    $qb->select('count(u)')
	        ->from('AppBundle:User', 'u')
	        ->where('u.roles LIKE :roles')
	        ->setParameter('roles', '%ROLE_ADMIN%');
	
	    return (int)$qb->getQuery()->getSingleScalarResult();
	    
	}
}