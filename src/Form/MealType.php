<?php

namespace App\Form;

use App\Entity\Meal;
use App\Entity\NutritionPlan;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MealType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g., Grilled Chicken Salad'],
                'label' => 'Meal Name'
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Describe the meal...'],
                'label' => 'Description'
            ])
            ->add('calories', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Calories'],
                'label' => 'Calories'
            ])
            ->add('protein', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Protein in grams'],
                'label' => 'Protein (g)',
                'required' => false
            ])
            ->add('carbs', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Carbs in grams'],
                'label' => 'Carbohydrates (g)',
                'required' => false
            ])
            ->add('fat', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Fat in grams'],
                'label' => 'Fat (g)',
                'required' => false
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Meal Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('nutritionPlans', EntityType::class, [
                'class' => NutritionPlan::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'attr' => ['class' => 'form-select'],
                'label' => 'Assign to Nutrition Plans',
                'required' => false
            ])
            ->add('mealTime', ChoiceType::class, [
                'choices' => [
                    'Breakfast' => 'breakfast',
                    'Lunch' => 'lunch',
                    'Dinner' => 'dinner',
                    'Snack' => 'snack'
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Meal Time'
            ])
            ->add('dayOfWeek', ChoiceType::class, [
                'choices' => [
                    'Monday' => 1,
                    'Tuesday' => 2,
                    'Wednesday' => 3,
                    'Thursday' => 4,
                    'Friday' => 5,
                    'Saturday' => 6,
                    'Sunday' => 7
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Day of Week',
                'required' => false,
                'placeholder' => 'Select day (optional)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Meal::class,
        ]);
    }
}