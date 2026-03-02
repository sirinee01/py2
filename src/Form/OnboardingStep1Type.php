<?php
// src/Form/OnboardingStep1Type.php

namespace App\Form;

use App\Entity\Progress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class OnboardingStep1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('age', IntegerType::class, [
                'label' => 'How old are you?',
                'attr' => [
                    'placeholder' => 'Enter your age',
                    'min' => 13,
                    'max' => 120,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your age']),
                    new Range(['min' => 13, 'max' => 120, 'notInRangeMessage' => 'Age must be between {{ min }} and {{ max }}'])
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'What is your gender?',
                'choices' => [
                    'Male' => 'male',
                    'Female' => 'female',
                    'Other' => 'other',
                    'Prefer not to say' => 'not_specified'
                ],
                'expanded' => true,
                'multiple' => false,
                'mapped' => false, // THIS IS CRITICAL - gender is not in Progress entity
                'constraints' => [
                    new NotBlank(['message' => 'Please select your gender'])
                ]
            ])
            ->add('height', NumberType::class, [
                'label' => 'What is your height? (cm)',
                'attr' => [
                    'placeholder' => 'Height in centimeters',
                    'min' => 100,
                    'max' => 250,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your height']),
                    new Range(['min' => 100, 'max' => 250, 'notInRangeMessage' => 'Height must be between {{ min }}cm and {{ max }}cm'])
                ]
            ])
            ->add('weight', NumberType::class, [
                'label' => 'What is your current weight? (kg)',
                'attr' => [
                    'placeholder' => 'Weight in kilograms',
                    'min' => 30,
                    'max' => 300,
                    'step' => 0.1,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your weight']),
                    new Range(['min' => 30, 'max' => 300, 'notInRangeMessage' => 'Weight must be between {{ min }}kg and {{ max }}kg'])
                ]
            ])
            ->add('activityLevel', ChoiceType::class, [
                'label' => 'What is your activity level?',
                'choices' => [
                    'Sedentary (little or no exercise)' => 'sedentary',
                    'Lightly active (light exercise 1-3 days/week)' => 'lightly_active',
                    'Moderately active (moderate exercise 3-5 days/week)' => 'moderately_active',
                    'Very active (hard exercise 6-7 days/week)' => 'very_active',
                    'Extra active (very hard exercise & physical job)' => 'extra_active'
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Please select your activity level'])
                ]
            ])
            ->add('workoutsPerWeek', ChoiceType::class, [
                'label' => 'How many workouts do you do per week?',
                'choices' => [
                    '0' => 0,
                    '1-2' => 2,
                    '3-4' => 4,
                    '5-6' => 6,
                    '7+' => 7
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Please select your workout frequency'])
                ]
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