<?php

/*
 * This file is part of the Adverts Plugin.
 *
 * (c) Paweł Mikołajczuk <mikolajczuk.private@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package AHS\AdvertsPluginBundle
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 */

namespace AHS\AdvertsPluginBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Newscoop\EventDispatcher\Events\GenericEvent;

/**
 * Event lifecycle management
 */
class LifecycleSubscriber implements EventSubscriberInterface
{
    private $em;

    private $pluginsService;

    private $translator;

    private $scheduler;

    private $cronjobs;

    private $systemPreferences;

    public function __construct($em, $pluginsService, $translator, $scheduler, $systemPreferences)
    {
        $this->em = $em;
        $this->pluginsService = $pluginsService;
        $this->translator = $translator;
        $this->scheduler = $scheduler;
        $this->systemPreferences = $systemPreferences;
        $appDirectory = realpath(__DIR__.'/../../../../application/console');
        $this->cronjobs = array(
            "Deactivate expired classifieds." => array(
                'command' => $appDirectory . ' classifieds:deactivate',
                'schedule' => '20 * * * *',
            ),
        );
    }

    public function install(GenericEvent $event)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $tool->updateSchema($this->getClasses(), true);

        $this->em->getProxyFactory()->generateProxyClasses($this->getClasses(), __DIR__ . '/../../../../library/Proxy');
        $this->setPermissions();
        $this->addJobs();
        $this->systemPreferences->AdvertsNotificationEmail = $this->systemPreferences->EmailFromAddress;
        $this->systemPreferences->AdvertsValidTime = 7;
        $this->systemPreferences->AdvertsMaxClassifiedsPerUser = 5;
    }

    public function update(GenericEvent $event)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $tool->updateSchema($this->getClasses(), true);

        $this->em->getProxyFactory()->generateProxyClasses($this->getClasses(), __DIR__ . '/../../../../library/Proxy');
        $this->setPermissions();
        $this->addJobs();
    }

    public function remove(GenericEvent $event)
    {
        $this->removeSettings();
        $this->removePermissions();
        $this->removeJobs();
    }

    /**
     * Save plugin permissions into database
     */
    private function setPermissions()
    {
        $this->pluginsService->savePluginPermissions($this->pluginsService->collectPermissions($this->translator->trans('ads.menu.name')));
    }

    /**
     * Remove plugin permissions
     */
    private function removePermissions()
    {
        $this->pluginsService->removePluginPermissions($this->pluginsService->collectPermissions($this->translator->trans('ads.menu.name')));
    }

    /**
     * Add plugin cron jobs
     */
    private function addJobs()
    {
        foreach ($this->cronjobs as $jobName => $jobConfig) {
            $this->scheduler->registerJob($jobName, $jobConfig);
        }
    }

    /**
     * Remove plugin cron jobs
     */
    private function removeJobs()
    {
        foreach ($this->cronjobs as $jobName => $jobConfig) {
            $this->scheduler->removeJob($jobName, $jobConfig);
        }
    }

    /**
     * Clean up system preferences
     *
     * @return void
     */
    private function removeSettings()
    {
        $this->systemPreferences->delete('AdvertsNotificationEmail');
        $this->systemPreferences->delete('AdvertsValidTime');
        $this->systemPreferences->delete('AdvertsMaxClassifiedsPerUser');
        $this->systemPreferences->delete('AdvertsReviewStatus');
        $this->systemPreferences->delete('AdvertsEnableNotify');
    }

    public static function getSubscribedEvents()
    {
        return array(
            'plugin.install.ahs_adverts_plugin_bundle' => array('install', 1),
            'plugin.update.ahs_adverts_plugin_bundle' => array('update', 1),
            'plugin.remove.ahs_adverts_plugin_bundle' => array('remove', 1),
        );
    }

    private function getClasses()
    {
        return array(
          $this->em->getClassMetadata('AHS\AdvertsPluginBundle\Entity\Announcement'),
          $this->em->getClassMetadata('AHS\AdvertsPluginBundle\Entity\Category'),
          $this->em->getClassMetadata('AHS\AdvertsPluginBundle\Entity\Image'),
          $this->em->getClassMetadata('AHS\AdvertsPluginBundle\Entity\User'),
        );
    }
}
