<?php

namespace AppBundle\Admin;

use AppBundle\Entity\Event;
use AppBundle\Event\EventUpdatedEvent;
use AppBundle\Events;
use AppBundle\Form\EventCategoryType;
use AppBundle\Form\UnitedNationsCountryType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\DateRangePickerType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EventAdmin extends AbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_per_page' => 32,
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt',
    ];

    private $dispatcher;

    public function __construct($code, $class, $baseControllerName, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->dispatcher = $dispatcher;
    }

    public function getTemplate($name)
    {
        if ('show' === $name) {
            return 'admin/event/show.html.twig';
        }

        if ('edit' === $name) {
            return 'admin/event/edit.html.twig';
        }

        return parent::getTemplate($name);
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->with('Événement', array('class' => 'col-md-7'))
                ->add('name', null, [
                    'label' => 'Nom',
                    'format_title_case' => true,
                ])
                ->add('category', null, [
                    'label' => 'Catégorie',
                ])
                ->add('committee', null, [
                    'label' => 'Comité organisateur',
                ])
                ->add('description', null, [
                    'label' => 'Description',
                    'attr' => [
                        'rows' => '3',
                    ],
                ])
                ->add('beginAt', null, [
                    'label' => 'Date de début',
                ])
                ->add('finishAt', null, [
                    'label' => 'Date de fin',
                ])
                ->add('createdAt', null, [
                    'label' => 'Date de création',
                ])
                ->add('participantsCount', null, [
                    'label' => 'Nombre de participants',
                ])
                ->add('status', 'choice', [
                    'label' => 'Statut',
                    'choices' => Event::STATUSES,
                    'catalogue' => 'forms',
                ])
                ->add('published', null, [
                    'label' => 'Publié',
                ])
            ->end()
            ->with('Adresse', array('class' => 'col-md-5'))
                ->add('postAddress.address', TextType::class, [
                    'label' => 'Rue',
                ])
                ->add('postAddress.postalCode', TextType::class, [
                    'label' => 'Code postal',
                ])
                ->add('postAddress.cityName', TextType::class, [
                    'label' => 'Ville',
                ])
                ->add('postAddress.country', UnitedNationsCountryType::class, [
                    'label' => 'Pays',
                ])
                ->add('postAddress.latitude', TextType::class, [
                    'label' => 'Latitude',
                ])
                ->add('postAddress.longitude', TextType::class, [
                    'label' => 'Longitude',
                ])
            ->end()
        ;
    }

    public function postUpdate($object)
    {
        $event = new EventUpdatedEvent($object->getOrganizer(), $object);

        $this->dispatcher->dispatch(Events::EVENT_UPDATED, $event);
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('Événement', array('class' => 'col-md-7'))
                ->add('name', null, [
                    'label' => 'Nom',
                ])
                ->add('category', EventCategoryType::class, [
                    'label' => 'Catégorie',
                ])
                ->add('committee', null, [
                    'label' => 'Comité organisateur',
                ])
                ->add('description', null, [
                    'label' => 'Description',
                    'attr' => [
                        'rows' => '3',
                    ],
                ])
                ->add('beginAt', null, [
                    'label' => 'Date de début',
                ])
                ->add('finishAt', null, [
                    'label' => 'Date de fin',
                ])
                ->add('status', ChoiceType::class, [
                    'label' => 'Statut',
                    'choices' => Event::STATUSES,
                    'choice_translation_domain' => 'forms',
                ])
                ->add('published', null, [
                    'label' => 'Publié',
                ])
            ->end()
            ->with('Adresse', array('class' => 'col-md-5'))
                ->add('postAddress.address', TextType::class, [
                    'label' => 'Rue',
                ])
                ->add('postAddress.postalCode', TextType::class, [
                    'label' => 'Code postal',
                ])
                ->add('postAddress.cityName', TextType::class, [
                    'label' => 'Ville',
                ])
                ->add('postAddress.country', UnitedNationsCountryType::class, [
                    'label' => 'Pays',
                ])
                ->add('postAddress.latitude', TextType::class, [
                    'label' => 'Latitude',
                ])
                ->add('postAddress.longitude', TextType::class, [
                    'label' => 'Longitude',
                ])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, [
                'label' => 'Nom',
            ])
            ->add('category', null, [
                'label' => 'Type',
                'field_type' => EventCategoryType::class,
                'show_filter' => true,
            ])
            ->add('createdAt', DateRangeFilter::class, [
                'label' => 'Date de création',
                'field_type' => DateRangePickerType::class,
            ])
            ->add('beginAt', DateRangeFilter::class, [
                'label' => 'Date de début',
                'show_filter' => true,
                'field_type' => DateRangePickerType::class,
            ])
            ->add('organizer.firstName', null, [
                'label' => 'Prénom de l\'organisateur',
                'show_filter' => true,
            ])
            ->add('organizer.lastName', null, [
                'label' => 'Nom de l\'organisateur',
                'show_filter' => true,
            ])
            ->add('city', CallbackFilter::class, [
                'label' => 'Ville',
                'field_type' => TextType::class,
                'callback' => function (ProxyQuery $qb, string $alias, string $field, array $value) {
                    if (!$value['value']) {
                        return;
                    }

                    $qb->andWhere(sprintf('LOWER(%s.postAddress.cityName)', $alias).' LIKE :cityName');
                    $qb->setParameter('cityName', '%'.strtolower($value['value']).'%');

                    return true;
                },
            ])
            ->add('published', BooleanFilter::class, [
                'label' => 'Publié',
            ])
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name', null, [
                'label' => 'Nom',
            ])
            ->add('committee', null, [
                'label' => 'Comité organisateur',
            ])
            ->add('organizer', null, [
                'label' => 'Organisateur',
                'template' => 'admin/event/list_organizer.html.twig',
            ])
            ->add('beginAt', null, [
                'label' => 'Date de début',
            ])
            ->add('_location', null, [
                'label' => 'Lieu',
                'virtual_field' => true,
                'template' => 'admin/event/list_location.html.twig',
            ])
            ->add('category', null, [
                'label' => 'Catégorie',
            ])
            ->add('participantsCount', null, [
                'label' => 'Participants',
            ])
            ->add('status', null, [
                'label' => 'Statut',
                'template' => 'admin/event/list_status.html.twig',
            ])
            ->add('_action', null, [
                'virtual_field' => true,
                'template' => 'admin/event/list_actions.html.twig',
            ])
        ;
    }
}
