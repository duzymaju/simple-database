<?php

use PHPUnit\Framework\TestCase;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Exception\RepositoryException;
use SimpleDatabase\Model\ModelInterface;
use SimpleDatabase\Repository\BaseRepository;

final class BaseRepositoryTest extends TestCase
{
    /** @var ConnectionInterface|PHPUnit_Framework_MockObject_MockObject */
    private $connectionMock;

    /** @var QueryInterface|PHPUnit_Framework_MockObject_MockObject */
    private $queryMock;

    /** @before */
    public function setupMocks()
    {
        $this->connectionMock = $this->createMock(ConnectionInterface::class);
        $this->queryMock = $this->createMock(QueryInterface::class);

        $this->connectionMock
            ->method('escape')
            ->willReturnCallback(function ($text) {
                return $text;
            })
        ;
    }

    /** Test counting by */
    public function testCountingBy()
    {
        $this->connectionMock
            ->expects($this->exactly(1))
            ->method('select')
            ->with('COUNT(*) AS count', 'TestTable')
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(4))
            ->method('bindParam')
            ->withConsecutive(
                [ 'boolField', QueryInterface::PARAM_BOOL ],
                [ 'stringDbField1', QueryInterface::PARAM_STRING ],
                [ 'stringDbField2', QueryInterface::PARAM_STRING ],
                [ 'stringDbField3', QueryInterface::PARAM_NULL ]
            )
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('where')
            ->with([
                'boolField = :boolField',
                'stringDbField IN (:stringDbField1, :stringDbField2, :stringDbField3)',
            ])
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('execute')
            ->with([
                'boolField' => true,
                'stringDbField1' => 'abc',
                'stringDbField2' => 'def',
                'stringDbField3' => null,
            ])
            ->willReturn([
                [ 'count' => '5' ],
            ])
        ;

        $repository = new TestRepository($this->connectionMock);
        $result = $repository->countBy([
            'boolField' => true,
            'stringField' => [ 'abc', 'def', null ],
        ]);

        $this->assertEquals(5, $result);
    }

    /** Test getting by */
    public function testGettingBy()
    {
        $this->connectionMock
            ->expects($this->exactly(1))
            ->method('select')
            ->with('*', 'TestTable')
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(3))
            ->method('bindParam')
            ->withConsecutive(
                [ 'floatId', QueryInterface::PARAM_FLOAT ],
                [ 'jsonStructure', QueryInterface::PARAM_STRING ],
                [ 'jsonAssocStructure', QueryInterface::PARAM_STRING ]
            )
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('where')
            ->with([
                'floatId = :floatId',
                'jsonStructure = :jsonStructure',
                'jsonAssocStructure = :jsonAssocStructure',
            ])
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('orderBy')
            ->with([
                'id ASC',
                'stringDbField DESC',
                'RAND()',
            ])
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('limit')
            ->with(10, 5)
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('execute')
            ->with([
                'floatId' => 4.15,
                'jsonStructure' => '{"a":2}',
                'jsonAssocStructure' => '{"b":["b1","b2"]}',
            ])
            ->willReturn([
                [
                    'id' => '3',
                    'floatId' => '4.15',
                    'stringDbField' => 'abc',
                    'boolField' => '0',
                    'jsonStructure' => '{"a":2}',
                    'jsonAssocStructure' => '{"b":["b1","b2"]}',
                ],
            ])
        ;

        $objectStructure1 = new stdClass();
        $objectStructure1->a = 2;
        $objectStructure2 = [ 'b' => [ 'b1', 'b2' ] ];
        $repository = new TestRepository($this->connectionMock);
        $results = $repository->getBy([
            'id2' => 4.15,
            'jsonStructure' => $objectStructure1,
            'jsonAssocStructure' => $objectStructure2,
        ], [
            'id' => 'ASC',
            'stringField' => 'desc',
            'Rand',
        ], 10, 5);

        $testModelInstance = $repository->createModelInstance([
            'id' => '3',
            'floatId' => '4.15',
            'stringDbField' => 'abc',
            'boolField' => '0',
            'jsonStructure' => '{"a":2}',
            'jsonAssocStructure' => '{"b":["b1","b2"]}',
        ]);
        $this->assertEquals([ $testModelInstance ], $results);
    }

    /** Test saving new element */
    public function testSavingNewElement()
    {
        $this->connectionMock
            ->expects($this->exactly(1))
            ->method('insert')
            ->with('TestTable')
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(4))
            ->method('bindParam')
            ->withConsecutive(
                [ 'stringDbField', QueryInterface::PARAM_STRING ],
                [ 'boolField', QueryInterface::PARAM_BOOL ],
                [ 'jsonStructure', QueryInterface::PARAM_STRING ],
                [ 'jsonAssocStructure', QueryInterface::PARAM_NULL ]
            )
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('set')
            ->with([
                'stringDbField = :stringDbField',
                'boolField = :boolField',
                'jsonStructure = :jsonStructure',
                'jsonAssocStructure = :jsonAssocStructure',
            ])
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('execute')
            ->with([
                'stringDbField' => 'abc',
                'boolField' => false,
                'jsonStructure' => '{"a":2}',
                'jsonAssocStructure' => null,
            ])
            ->willReturn(null)
        ;

        $repository = new TestRepository($this->connectionMock);
        $objectStructure1 = new stdClass();
        $objectStructure1->a = 2;
        $testModelInstance = new TestModel();
        $testModelInstance->setStringField('abc');
        $testModelInstance->setBoolField(false);
        $testModelInstance->setJsonStructure($objectStructure1);
        $repository->save($testModelInstance);
    }

    /** Test saving existed element */
    public function testSavingExistedElement()
    {
        $this->connectionMock
            ->expects($this->exactly(1))
            ->method('update')
            ->with('TestTable')
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(6))
            ->method('bindParam')
            ->withConsecutive(
                [ 'stringDbField', QueryInterface::PARAM_STRING ],
                [ 'boolField', QueryInterface::PARAM_BOOL ],
                [ 'jsonStructure', QueryInterface::PARAM_STRING ],
                [ 'jsonAssocStructure', QueryInterface::PARAM_NULL ],
                [ 'id', QueryInterface::PARAM_INT ],
                [ 'floatId', QueryInterface::PARAM_FLOAT ]
            )
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('set')
            ->with([
                'stringDbField = :stringDbField',
                'boolField = :boolField',
                'jsonStructure = :jsonStructure',
                'jsonAssocStructure = :jsonAssocStructure',
            ])
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('where')
            ->with([
                'id = :id',
                'floatId = :floatId',
            ])
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('execute')
            ->with([
                'id' => 3,
                'floatId' => 4.15,
                'stringDbField' => 'abc',
                'boolField' => false,
                'jsonStructure' => '{"a":2}',
                'jsonAssocStructure' => null,
            ])
            ->willReturn(null)
        ;

        $repository = new TestRepository($this->connectionMock);
        $testModelInstance = $repository->createModelInstance([
            'id' => '3',
            'floatId' => '4.15',
            'stringDbField' => 'abc',
            'boolField' => '0',
            'jsonStructure' => '{"a":2}',
        ]);
        $repository->save($testModelInstance);
    }

    /** Test throwing exception during saving element */
    public function testThrowingExceptionDuringSavingElement()
    {
        $repository = new TestRepository($this->connectionMock);
        $testModelInstance = $repository->createModelInstance([
            'floatId' => '4.15',
            'stringDbField' => 'abc',
            'boolField' => '0',
            'jsonStructure' => '{"a":2}',
        ]);

        try {
            $repository->save($testModelInstance);
            $this->fail('Unexpected success.');
        } catch (Exception $exception) {
            $this->assertTrue($exception instanceof RepositoryException);
            $this->assertEquals('Model contains invalid values and can not be saved.', $exception->getMessage());
        }
    }
}

class TestRepository extends BaseRepository
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this->setModelClass('TestModel');
        $this
            ->setStructure('TestTable')
            ->addInt('id', null, true)
            ->addFloat('id2', 'floatId', true)
            ->addString('stringField', 'stringDbField')
            ->addBool('boolField')
            ->addJson('jsonStructure')
            ->addJsonAssoc('jsonAssocStructure')
        ;
    }

    public function countBy(array $conditions)
    {
        return parent::countBy($conditions);
    }

    public function getById(...$ids)
    {
        return parent::getById(...$ids);
    }

    public function getByIdOr404(...$ids)
    {
        return parent::getByIdOr404(...$ids);
    }

    public function getOneBy(array $conditions, array $order = [])
    {
        return parent::getOneBy($conditions, $order);
    }

    public function getOneByOr404(array $conditions, array $order = [])
    {
        return parent::getOneByOr404($conditions, $order);
    }

    public function getBy(array $conditions, array $order = [], $limit = null, $offset = 0)
    {
        return parent::getBy($conditions, $order, $limit, $offset);
    }

    public function getPaginated(array $conditions, array $order = [], $page = 1, $pack = null)
    {
        return parent::getPaginated($conditions, $order, $page, $pack);
    }

    public function save(ModelInterface $model)
    {
        return parent::save($model);
    }

    public function createModelInstance(array $data)
    {
        return parent::createModelInstance($data);
    }
}

class TestModel extends ModelInterface
{
    private $id;
    private $id2;
    private $stringField;
    private $boolField;
    private $jsonStructure;
    private $jsonAssocStructure;

    public function getId()
    {
        return $this->id;
    }

    public function getId2()
    {
        return $this->id2;
    }

    public function getStringField()
    {
        return $this->stringField;
    }

    public function setStringField($stringField)
    {
        $this->stringField = $stringField;
    }

    public function getBoolField()
    {
        return $this->boolField;
    }

    public function setBoolField($boolField)
    {
        $this->boolField = $boolField;
    }

    public function getJsonStructure()
    {
        return $this->jsonStructure;
    }

    public function setJsonStructure($jsonStructure)
    {
        $this->jsonStructure = $jsonStructure;
    }

    public function getJsonAssocStructure()
    {
        return $this->jsonAssocStructure;
    }

    public function setJsonAssocStructure($jsonAssocStructure)
    {
        $this->jsonAssocStructure = $jsonAssocStructure;
    }
}
