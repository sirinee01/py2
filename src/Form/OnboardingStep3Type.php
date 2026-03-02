<?php
// src/Form/OnboardingStep3Type.php

namespace App\Form;

use App\Entity\Progress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class OnboardingStep3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('proteinIntake', IntegerType::class, [
                'label' => 'Average protein intake per day? (grams)',
                'attr' => [
                    'placeholder' => 'Protein in grams',
                    'min' => 0,
                    'max' => 300,
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('carbIntake', IntegerType::class, [
                'label' => 'Average carbohydrate intake per day? (grams)',
                'attr' => [
                    'placeholder' => 'Carbs in grams',
                    'min' => 0,
                    'max' => 500,
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('fatIntake', IntegerType::class, [
                'label' => 'Average fat intake per day? (grams)',
                'attr' => [
                    'placeholder' => 'Fat in grams',
                    'min' => 0,
                    'max' => 200,
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('dietaryRestrictions', TextareaType::class, [
                'label' => 'Do you have any dietary restrictions or allergies?',
                'attr' => [
                    'placeholder' => 'E.g., vegetarian, gluten-free, lactose intolerant, etc.',
                    'rows' => 3,
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Progress::class,
        ]);
    }
}