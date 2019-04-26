<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\ChoiceList\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\ChoiceList\Factory\PropertyAccessDecorator;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PropertyAccessDecoratorTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedFactory;

    /**
     * @var PropertyAccessDecorator
     */
    private $factory;

    protected function setUp()
    {
        $this->decoratedFactory = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface')->getMock();
        $this->factory = new PropertyAccessDecorator($this->decoratedFactory);
    }

    public function testCreateFromChoicesPropertyPath()
    {
        $choices = [(object) ['property' => 'value']];

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromChoices')
            ->with($choices, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($choices, $callback) {
                return array_map($callback, $choices);
            }));

        $this->assertSame(['value'], $this->factory->createListFromChoices($choices, 'property'));
    }

    public function testCreateFromChoicesPropertyPathInstance()
    {
        $choices = [(object) ['property' => 'value']];

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromChoices')
            ->with($choices, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($choices, $callback) {
                return array_map($callback, $choices);
            }));

        $this->assertSame(['value'], $this->factory->createListFromChoices($choices, new PropertyPath('property')));
    }

    public function testCreateFromLoaderPropertyPath()
    {
        $loader = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromLoader')
            ->with($loader, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($loader, $callback) {
                return $callback((object) ['property' => 'value']);
            }));

        $this->assertSame('value', $this->factory->createListFromLoader($loader, 'property'));
    }

    // https://github.com/symfony/symfony/issues/5494
    public function testCreateFromChoicesAssumeNullIfValuePropertyPathUnreadable()
    {
        $choices = [null];

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromChoices')
            ->with($choices, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($choices, $callback) {
                return array_map($callback, $choices);
            }));

        $this->assertSame([null], $this->factory->createListFromChoices($choices, 'property'));
    }

    // https://github.com/symfony/symfony/issues/5494
    public function testCreateFromChoiceLoaderAssumeNullIfValuePropertyPathUnreadable()
    {
        $loader = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromLoader')
            ->with($loader, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($loader, $callback) {
                return $callback(null);
            }));

        $this->assertNull($this->factory->createListFromLoader($loader, 'property'));
    }

    public function testCreateFromLoaderPropertyPathInstance()
    {
        $loader = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromLoader')
            ->with($loader, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($loader, $callback) {
                return $callback((object) ['property' => 'value']);
            }));

        $this->assertSame('value', $this->factory->createListFromLoader($loader, new PropertyPath('property')));
    }

    public function testCreateViewPreferredChoicesAsPropertyPath()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred) {
                return $preferred((object) ['property' => true]);
            }));

        $this->assertTrue($this->factory->createView(
            $list,
            'property'
        ));
    }

    public function testCreateViewPreferredChoicesAsPropertyPathInstance()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred) {
                return $preferred((object) ['property' => true]);
            }));

        $this->assertTrue($this->factory->createView(
            $list,
            new PropertyPath('property')
        ));
    }

    // https://github.com/symfony/symfony/issues/5494
    public function testCreateViewAssumeNullIfPreferredChoicesPropertyPathUnreadable()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred) {
                return $preferred((object) ['category' => null]);
            }));

        $this->assertFalse($this->factory->createView(
            $list,
            'category.preferred'
        ));
    }

    public function testCreateViewLabelsAsPropertyPath()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label) {
                return $label((object) ['property' => 'label']);
            }));

        $this->assertSame('label', $this->factory->createView(
            $list,
            null, // preferred choices
            'property'
        ));
    }

    public function testCreateViewLabelsAsPropertyPathInstance()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label) {
                return $label((object) ['property' => 'label']);
            }));

        $this->assertSame('label', $this->factory->createView(
            $list,
            null, // preferred choices
            new PropertyPath('property')
        ));
    }

    public function testCreateViewIndicesAsPropertyPath()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label, $index) {
                return $index((object) ['property' => 'index']);
            }));

        $this->assertSame('index', $this->factory->createView(
            $list,
            null, // preferred choices
            null, // label
            'property'
        ));
    }

    public function testCreateViewIndicesAsPropertyPathInstance()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label, $index) {
                return $index((object) ['property' => 'index']);
            }));

        $this->assertSame('index', $this->factory->createView(
            $list,
            null, // preferred choices
            null, // label
            new PropertyPath('property')
        ));
    }

    public function testCreateViewGroupsAsPropertyPath()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label, $index, $groupBy) {
                return $groupBy((object) ['property' => 'group']);
            }));

        $this->assertSame('group', $this->factory->createView(
            $list,
            null, // preferred choices
            null, // label
            null, // index
            'property'
        ));
    }

    public function testCreateViewGroupsAsPropertyPathInstance()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label, $index, $groupBy) {
                return $groupBy((object) ['property' => 'group']);
            }));

        $this->assertSame('group', $this->factory->createView(
            $list,
            null, // preferred choices
            null, // label
            null, // index
            new PropertyPath('property')
        ));
    }

    // https://github.com/symfony/symfony/issues/5494
    public function testCreateViewAssumeNullIfGroupsPropertyPathUnreadable()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label, $index, $groupBy) {
                return $groupBy((object) ['group' => null]);
            }));

        $this->assertNull($this->factory->createView(
            $list,
            null, // preferred choices
            null, // label
            null, // index
            'group.name'
        ));
    }

    public function testCreateViewAttrAsPropertyPath()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label, $index, $groupBy, $attr) {
                return $attr((object) ['property' => 'attr']);
            }));

        $this->assertSame('attr', $this->factory->createView(
            $list,
            null, // preferred choices
            null, // label
            null, // index
            null, // groups
            'property'
        ));
    }

    public function testCreateViewAttrAsPropertyPathInstance()
    {
        $list = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\ChoiceListInterface')->getMock();

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, null, $this->isInstanceOf('\Closure'))
            ->will($this->returnCallback(function ($list, $preferred, $label, $index, $groupBy, $attr) {
                return $attr((object) ['property' => 'attr']);
            }));

        $this->assertSame('attr', $this->factory->createView(
            $list,
            null, // preferred choices
            null, // label
            null, // index
            null, // groups
            new PropertyPath('property')
        ));
    }
}
