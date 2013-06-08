<?php

namespace PHPCR\Tests\Util\Console\Command;

use Symfony\Component\Console\Application;
use PHPCR\RepositoryInterface;
use PHPCR\Util\Console\Command\WorkspaceNodeUpdateCommand;

class WorkspaceNodeUpdateCommandTest extends BaseCommandTest
{
    public function setUp()
    {
        parent::setUp();
        $this->application->add(new WorkspaceNodeUpdateCommand());
        $this->queryManager = $this->getMock(
            'PHPCR\Query\QueryManagerInterface'
        );
        $this->query = $this->getMock('PHPCR\Query\QueryInterface');
    }

    public function provideNodeUpdate()
    {
        return array(
            array(array(
                'setProp' => array(array('foo', 'bar')),
                'removeProp' => array('bar'),
                'addMixin' => array('mixin1'),
                'removeMixin' => array('mixin1'),
            )),
        );
    }

    /**
     * @dataProvider provideNodeUpdate
     */
    public function testNodeUpdate($options)
    {
        $options = array_merge(array(
            'setProp' => array(),
            'removeProp' => array(),
            'addMixin' => array(),
            'removeMixin' => array(),
        ), $options);

        $this->session->expects($this->once())
            ->method('getWorkspace')
            ->will($this->returnValue($this->workspace));
        $this->workspace->expects($this->once())
            ->method('getQueryManager')
            ->will($this->returnValue($this->queryManager));
        $this->queryManager->expects($this->once())
            ->method('createQuery')
            ->with('SELECT foo FROM foo', 'sql')
            ->will($this->returnValue($this->query));
        $this->query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array(
                $this->row1,
            )));
        $this->row1->expects($this->once())
            ->method('getNode')
            ->will($this->returnValue($this->node1));

        $args = array(
            'query' => 'SELECT foo FROM foo',
            '--language' => 'sql',
            '--force' => true,
            '--set-prop' => array(),
            '--remove-prop' => array(),
            '--add-mixin' => array(),
            '--remove-mixin' => array(),
        );

        foreach ($options['setProp'] as $setProp)
        {
            list($prop, $value) = $setProp;
            $this->node1->expects($this->at(0))
                ->method('setProperty')
                ->with($prop, $value);

            $args['--set-prop'][] = $prop.'='.$value;
        }

        foreach ($options['removeProp'] as $prop)
        {
            $this->node1->expects($this->at(1))
                ->method('setProperty')
                ->with($prop, null);

            $args['--remove-prop'][] = $prop;
        }

        foreach ($options['addMixin'] as $mixin)
        {
            $this->node1->expects($this->once())
                ->method('addMixin')
                ->with($mixin);

            $args['--add-mixin'][] = $mixin;
        }

        foreach ($options['removeMixin'] as $mixin)
        {
            $this->node1->expects($this->once())
                ->method('removeMixin')
                ->with($mixin);

            $args['--remove-mixin'][] = $mixin;
        }

        $ct = $this->executeCommand('phpcr:workspace:node:update', $args);
    }
}
