<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ConfigUserFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	    $builder->add('username');
	    $builder->add('email');		
	    $builder->add('fname');
	    $builder->add('lname');
	    $builder->add('zip');
	    $builder->add('address');
	    $builder->add('tel', TelType::class);
    }
    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }
	public function configureOptions(OptionsResolver $resolver)
	{
	    $resolver->setDefaults(array(
	        'validation_groups' => array('Profile'),
	    ));
	}
}
