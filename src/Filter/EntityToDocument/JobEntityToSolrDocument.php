<?php
/**
 * YAWIK
 *
 * @filesource
 * @copyright (c) 2013 - 2016 Cross Solution (http://cross-solution.de)
 * @license   MIT
 * @author Miroslav Fedeleš <miroslav.fedeles@gmail.com>
 * @since 0.27
 */
namespace Solr\Filter\EntityToDocument;

use Solr\Options\ModuleOptions;
use Laminas\Filter\FilterInterface;
use Jobs\Entity\Job as JobEntity;
use SolrInputDocument;
use InvalidArgumentException;
use Solr\Bridge\Util;
use Laminas\Filter\StripTags;

class JobEntityToSolrDocument implements FilterInterface
{
    /**
     * @var $options ModuleOptions
     */
    protected $options;

    public function __construct($options)
    {
        $this->options=$options;
    }

    /**
     * @see \Laminas\Filter\FilterInterface::filter()
     * @param JobEntity $job
     * @return SolrInputDocument
     */
    public function filter($job)
    {
        if (!$job instanceof JobEntity) {
            throw new InvalidArgumentException(sprintf('$job must be instance of "%s"', JobEntity::class));
        }
        
        $document = new SolrInputDocument();
        $document->addField('id', $job->getId());
        $document->addField('applyId', $job->getApplyId());
        $document->addField('entityName', 'job');
        $document->addField('title', $job->getTitle());
        $document->addField('applicationEmail', $job->getContactEmail());
        if ($job->getLink()) {
            $document->addField('link', $job->getLink());
        }
        if ($job->getDateCreated()) {
            $document->addField('dateCreated', Util::convertDateTime($job->getDateCreated()));
        }
        if ($job->getDateModified()) {
            $document->addField('dateModified', Util::convertDateTime($job->getDateModified()));
        }
        if ($job->getDatePublishStart()) {
            $document->addField('datePublishStart', Util::convertDateTime($job->getDatePublishStart()));
        }
        if ($job->getDatePublishEnd()) {
            $document->addField('datePublishEnd', Util::convertDateTime($job->getDatePublishEnd()));
        }
        $document->addField('isActive', $job->isActive());
        $document->addField('lang', $job->getLanguage());
        $this->processLocation($job, $document);
        if ($job->getCompany(false)) {
            $document->addField('organizationName', $job->getCompany(false));
            if ($job->getLogoRef()) {
                $document->addField('companyLogo', $job->getLogoRef());
            }
        } elseif (!is_null($job->getOrganization())) {
            $this->processOrganization($job, $document);
        }
        
        $plainText = $job->getMetaData('plainText');
        
        if ($plainText) {
            $html = $plainText;
        } else {
            $templateValues = $job->getTemplateValues();
            $description = $templateValues->getDescription();
            $stripTags = new StripTags();
            $stripTags->setAttributesAllowed([])->setTagsAllowed([]);
            $description = $stripTags->filter($description);
    
            $qualification = $stripTags($templateValues->getQualifications());
            $requirements = $stripTags($templateValues->getRequirements());
            $benefits = $stripTags($templateValues->getBenefits());
            $html = "$description " . $job->getTitle() ." $requirements $qualification $benefits";
        }
        
        $document->addField('html', $html);

        foreach ($job->getClassifications()->getProfessions()->getItems() as $profession) { /* @var  $profession \Jobs\Entity\Category */
            $document->addField('profession_MultiString', $profession->getName());
        }
        foreach ($job->getClassifications()->getEmploymentTypes()->getItems() as $employmentType) { /* @var  $employmentType \Jobs\Entity\Category */
            $document->addField('employmentType_MultiString', $employmentType->getName());
        }
        foreach ($job->getClassifications()->getIndustries()->getItems() as $industry) { /* @var  $employmentType \Jobs\Entity\Category */
            $document->addField('industry_MultiString', $industry->getName());
        }


        return $document;
    }
    
    /**
     * @param JobEntity $job
     * @return array
     */
    public function getDocumentIds(JobEntity $job)
    {
        $ids = [$job->getId()];
            
        /* @var $location \Jobs\Entity\Location */
        foreach ($job->getLocations() as $location) {
            if (is_object($location->getCoordinates())) {
                $ids[] = $this->getLocationDocumentId($job, Util::convertLocationCoordinates($location));
            }
        }
        
        return $ids;
    }
    
    /**
     * @param JobEntity $job
     * @param SolrInputDocument $document
     */
    public function processOrganization(JobEntity $job, SolrInputDocument $document)
    {
        if (!is_null($job->getOrganization()->getImage())) {
            $uri = $job->getOrganization()
                ->getImage()
                ->getUri();
            $document->addField('companyLogo', $uri);
        }
        $document->addField('organizationName', $job->getOrganization()->getOrganizationName()->getName());
        $document->addField('organizationId', $job->getOrganization()->getId());
    }

    /**
     * @param JobEntity $job
     * @param SolrInputDocument $document
     */
    public function processLocation(JobEntity $job, SolrInputDocument $document)
    {
        /* @var $location \Jobs\Entity\Location */
        foreach ($job->getLocations() as $location) {
            $loc = new SolrInputDocument();
            $loc->addField('entityName', 'location');
            if (is_object($location->getCoordinates())) {
                $coordinate = Util::convertLocationCoordinates($location);
                $region = $location->getRegion();
                $city = $location->getCity();
                $loc->addField('point', $coordinate);
                $loc->addField('latLon', $coordinate);
                $document->addField('locations', $coordinate);
                $document->addField('points', $coordinate);
                $loc->addField('id', $this->getLocationDocumentId($job, $coordinate));
                $loc->addField('city', $city);
                $loc->addField('country', $location->getCountry());
                $loc->addField('region', $region);
                $loc->addField('postalCode', $location->getPostalCode());
                $document->addField('region_MultiString', $region);
                $document->addField('city_MultiString', $city);
                $document->addChildDocument($loc);
            }
        }
        
        $document->addField('location', $job->getLocation());
    }
    
    /**
     * @param JobEntity $job
     * @param string $coordinate
     * @return string
     */
    protected function getLocationDocumentId(JobEntity $job, $coordinate)
    {
        return $job->getId() . '-' . $coordinate;
    }
}
