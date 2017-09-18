<?php
/**
 * YAWIK
 *
 * @copyright (c) 2013 - 2016 Cross Solution (http://cross-solution.de)
 * @license   MIT
 */

return array(
    'controllers' => [
        'factories' => [
            'Solr/Console' => 'Solr\Factory\Controller\ConsoleControllerFactory'
        ]
    ],
    'doctrine' => [
        'eventmanager' => [
            'odm_default' => [
                'subscribers' => [
                    'Solr/Listener/JobEventSubscriber'
                ]
            ]
        ]
    ],
    'options' => [
        'Solr/Options/Module' => [
            'class' => '\Solr\Options\ModuleOptions',
        ]
    ],

    'event_manager' => [
        'Core/CreatePaginator/Events' => [
            'listeners' => [
                'Solr/Listener/CreatePaginator' => [
                    \Core\Listener\Events\CreatePaginatorEvent::EVENT_CREATE_PAGINATOR, 'onCreatePaginator'
                ]
            ]
        ]
    ],

    'service_manager' => [
        'invokables' => [
            'Solr/Listener/CreatePaginator' => 'Solr\Listener\CreatePaginatorListener',
        ],
        'factories' => [
            'Solr/Manager' => ['Solr\Bridge\Manager','factory'],
            'Solr/ResultConverter' => ['Solr\Bridge\ResultConverter','factory'],
            'Solr/Listener/JobEventSubscriber' => [\Solr\Listener\JobEventSubscriber::class,'factory'],
        ]
    ],

    'paginator_manager' => [
        'factories' => [
            // replace Jobs/Board paginator with this paginator
            'Solr/Jobs/Board' => 'Solr\Paginator\JobsBoardPaginatorFactory',
        ]
    ],

    'filters' => [
        'factories' => [
            'Solr/Jobs/PaginationQuery' => 'Solr\Factory\Filter\JobBoardPaginationQueryFactory',
            //'Solr/Filter/JobEntityToSolrDocument' => 'Solr\Factory\Filter\EntityToDocument\JobEntityToSolrDocumentFactory',
        ]
    ],
);