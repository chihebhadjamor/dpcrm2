<?php

namespace App\Form;

use App\Entity\Account;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Account Name',
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('website', UrlType::class, [
                'label' => 'Website',
                'required' => false,
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => Account::getAvailableStatuses(),
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('contacts', HiddenType::class, [
                'attr' => ['id' => 'account_contacts_json'],
                'required' => false,
            ]);

        // Add a data transformer to convert between array and JSON string
        $builder->get('contacts')
            ->addModelTransformer(new CallbackTransformer(
                // Transform array to string (entity to form)
                function ($contactsArray) {
                    if (null === $contactsArray) {
                        return '';
                    }
                    return json_encode($contactsArray);
                },
                // Transform string to array (form to entity)
                function ($contactsJson) {
                    if (!$contactsJson) {
                        return [];
                    }
                    return json_decode($contactsJson, true);
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Account::class,
        ]);
    }
}
