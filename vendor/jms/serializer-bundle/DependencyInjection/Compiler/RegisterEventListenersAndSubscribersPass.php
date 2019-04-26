<?php

namespace JMS\SerializerBundle\DependencyInjection\Compiler;

use JMS\Serializer\EventDispatcher\EventDispatcher;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterEventListenersAndSubscribersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $listeners = array();
        $listenerServices = array();
        foreach ($container->findTaggedServiceIds('jms_serializer.event_listener') as $id => $tags) {

            foreach ($tags as $attributes) {
                if (!isset($attributes['event'])) {
                    throw new \RuntimeException(sprintf('The tag "jms_serializer.event_listener" of service "%s" requires an attribute named "event".', $id));
                }

                $class = isset($attributes['class'])
                    ? strtolower($container->getParameterBag()->resolveValue($attributes['class']))
                    : null;

                $format = isset($attributes['format']) ? $attributes['format'] : null;
                $method = isset($attributes['method']) ? $attributes['method'] : EventDispatcher::getDefaultMethodName($attributes['event']);
                $priority = isset($attributes['priority']) ? (integer)$attributes['priority'] : 0;

                if (class_exists(ServiceLocatorTagPass::class) || $container->getDefinition($id)->isPublic()) {
                    $listenerServices[$id] = new Reference($id);
                    $listeners[$attributes['event']][$priority][] = array(array($id, $method), $class, $format);
                } else {
                    $listeners[$attributes['event']][$priority][] = array(array(new Reference($id), $method), $class, $format);
                }
            }
        }

        foreach ($container->findTaggedServiceIds('jms_serializer.event_subscriber') as $id => $tags) {
            $subscriberClass = $container->getDefinition($id)->getClass();

            $subscriberClassReflectionObj = new \ReflectionClass($subscriberClass);

            if (!$subscriberClassReflectionObj->implementsInterface('JMS\Serializer\EventDispatcher\EventSubscriberInterface')) {
                throw new \RuntimeException(sprintf('The service "%s" (class: %s) does not implement the EventSubscriberInterface.', $id, $subscriberClass));
            }

            foreach (call_user_func(array($subscriberClass, 'getSubscribedEvents')) as $eventData) {
                if (!isset($eventData['event'])) {
                    throw new \RuntimeException(sprintf('The service "%s" (class: %s) must return an event for each subscribed event.', $id, $subscriberClass));
                }

                $class = isset($eventData['class']) ? strtolower($eventData['class']) : null;
                $format = isset($eventData['format']) ? $eventData['format'] : null;
                $method = isset($eventData['method']) ? $eventData['method'] : EventDispatcher::getDefaultMethodName($eventData['event']);
                $priority = isset($eventData['priority']) ? (integer)$eventData['priority'] : 0;

                if (class_exists(ServiceLocatorTagPass::class) || $container->getDefinition($id)->isPublic()) {
                    $listenerServices[$id] = new Reference($id);
                    $listeners[$eventData['event']][$priority][] = array(array($id, $method), $class, $format);
                } else {
                    $listeners[$eventData['event']][$priority][] = array(array(new Reference($id), $method), $class, $format);
                }
            }
        }

        if ($listeners) {
            array_walk($listeners, function (&$value, $key) {
                ksort($value);
            });

            foreach ($listeners as &$events) {
                $events = call_user_func_array('array_merge', $events);
            }

            $container->findDefinition('jms_serializer.event_dispatcher')
                ->addMethodCall('setListeners', array($listeners));
        }

        if (class_exists(ServiceLocatorTagPass::class)) {
            $serviceLocator = ServiceLocatorTagPass::register($container, $listenerServices);
            $container->getDefinition('jms_serializer.event_dispatcher')->replaceArgument(0, $serviceLocator);
        }
    }
}
