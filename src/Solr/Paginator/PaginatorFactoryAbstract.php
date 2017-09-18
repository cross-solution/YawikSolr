<?php
/**
 * YAWIK
 *
 * @filesource
 * @copyright (c) 2013 - 2016 Cross Solution (http://cross-solution.de)
 * @license   MIT
 */

namespace Solr\Paginator;

use Core\Paginator\PaginatorService;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Solr\Bridge\ResultConverter;
use Solr\Options\ModuleOptions;
use Solr\Paginator\Adapter\SolrAdapter;
use Solr\Facets;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract class for Solr paginator factory
 *
 * @author Anthonius Munthi <me@itstoni.com>
 * @author Miroslav Fedeleš <miroslav.fedeles@gmail.com>
 * @since 0.26
 * @package Solr\Paginator
 */
abstract class PaginatorFactoryAbstract implements FactoryInterface
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * Set creation options
     *
     * @param  array $options
     *
     * @return void
     */
    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getCreationOptions()
    {
        return $this->options;
    }
	
	public function __invoke( ContainerInterface $container, $requestedName, array $options = null )
	{
		/* @var PaginatorService $serviceLocator */
		/* @var ResultConverter $resultConverter */
		$filter             = $container->get('FilterManager')->get($this->getFilter());
		$options            = $container->get('Solr/Options/Module');
		$connectPath        = $this->getConnectPath($options);
		$solrClient         = $container->get('Solr/Manager')->getClient($connectPath);
		$resultConverter    = $container->get('Solr/ResultConverter');
		$adapter            = new SolrAdapter($solrClient, $filter, $resultConverter, new Facets(), $this->options);
		$service            = new Paginator($adapter);
		
		$this->setCreationOptions([]);
		return $service;
	}
	
    /**
     * pagination service name
     *
     * @return string
     */
    abstract protected function getFilter();

    /**
     *
     * Get connection path for this paginator
     *
     * @param   ModuleOptions $options
     * @return  string
     */
    abstract protected function getConnectPath(ModuleOptions $options);
}