<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Model\ModelInterface;
use SimpleDatabase\Repository\BaseRepository;
use SimpleStructure\Tool\Paginator;

final class BaseRepositoryJoinTest extends TestCase
{
    /** @var ConnectionInterface|MockObject */
    private $connectionMock;

    /** @var QueryInterface|MockObject */
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

        date_default_timezone_set('Europe/Warsaw');
    }

    /** Test getting by query */
    public function testGettingByQuery()
    {
        $this->connectionMock
            ->expects($this->exactly(1))
            ->method('select')
            ->with(['pt.id as pt_id', 'pt.product_id as pt_product_id', 'pd.id as pd_id', 'pd.producer_id as pd_producer_id', 'pr.id as pr_id'], 'test_product_type_table', 'pt')
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(2))
            ->method('join')
            ->withConsecutive(
                ['test_product_table', 'pd', ['pt.product_id = pd.id']],
                ['test_producer_table', 'pr', ['pd.producer_id = pr.id']]
            )
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('where')
            ->with(['pt.id = :id'])
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('limit')
            ->with(1)
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('bindParam')
            ->with(':id', QueryInterface::PARAM_INT)
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('execute')
            ->with([':id' => 123])
            ->willReturn([
                [
                    'pt_id' => '123',
                    'pt_product_id' => '39',
                    'pd_id' => '39',
                    'pd_producer_id' => '77',
                    'pr_id' => '77',
                ],
            ])
        ;

        $producerRepository = new TestProducerRepository($this->connectionMock);
        $productRepository = new TestProductRepository($this->connectionMock);
        $productTypeRepository = new TestProductTypeRepository($this->connectionMock);
        $productTypeRepository->setRelatedRepositories($producerRepository, $productRepository);
        $result = $productTypeRepository->getStructureById(123);

        /** @var TestProducerModel $testProducerModelInstance */
        $testProducerModelInstance = $producerRepository->createDbModelInstance([
            'id' => '77',
        ]);
        /** @var TestProductModel $testProductModelInstance */
        $testProductModelInstance = $productRepository->createDbModelInstance([
            'id' => '39',
            'producer_id' => '77',
        ]);
        /** @var TestProductTypeModel $testProductTypeModelInstance */
        $testProductTypeModelInstance = $productTypeRepository->createDbModelInstance([
            'id' => '123',
            'product_id' => '39',
        ]);
        $testProducerModelInstance->addProduct($testProductModelInstance);
        $testProductModelInstance->setProducer($testProducerModelInstance);
        $testProductModelInstance->addProductType($testProductTypeModelInstance);
        $testProductTypeModelInstance->setProduct($testProductModelInstance);
        $this->assertEquals($testProductTypeModelInstance, $result);
    }

    /** Test getting by query paginated */
    public function testGettingByQueryPaginated()
    {
        $this->connectionMock
            ->expects($this->exactly(1))
            ->method('select')
            ->with(['pt.id as pt_id', 'pt.product_id as pt_product_id', 'pd.id as pd_id', 'pd.producer_id as pd_producer_id', 'pr.id as pr_id'], 'test_product_type_table', 'pt')
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(2))
            ->method('join')
            ->withConsecutive(
                ['test_product_table', 'pd', ['pt.product_id = pd.id']],
                ['test_producer_table', 'pr', ['pd.producer_id = pr.id']]
            )
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('limit')
            ->with(5, 10)
            ->willReturn($this->queryMock)
        ;
        $this->queryMock
            ->expects($this->exactly(2))
            ->method('execute')
            ->with([])
            ->willReturnOnConsecutiveCalls([
                ['count' => 27],
            ], [
                [
                    'pt_id' => '123',
                    'pt_product_id' => '39',
                    'pd_id' => '39',
                    'pd_producer_id' => '77',
                    'pr_id' => '77',
                ],
            ])
        ;
        $this->queryMock
            ->expects($this->exactly(1))
            ->method('cloneSelect')
            ->with('count(*) as count')
            ->willReturn($this->queryMock)
        ;

        $producerRepository = new TestProducerRepository($this->connectionMock);
        $productRepository = new TestProductRepository($this->connectionMock);
        $productTypeRepository = new TestProductTypeRepository($this->connectionMock);
        $productTypeRepository->setRelatedRepositories($producerRepository, $productRepository);
        $results = $productTypeRepository->getStructurePaginated(3, 5);

        /** @var TestProducerModel $testProducerModelInstance */
        $testProducerModelInstance = $producerRepository->createDbModelInstance([
            'id' => '77',
        ]);
        /** @var TestProductModel $testProductModelInstance */
        $testProductModelInstance = $productRepository->createDbModelInstance([
            'id' => '39',
            'producer_id' => '77',
        ]);
        /** @var TestProductTypeModel $testProductTypeModelInstance */
        $testProductTypeModelInstance = $productTypeRepository->createDbModelInstance([
            'id' => '123',
            'product_id' => '39',
        ]);
        $testProducerModelInstance->addProduct($testProductModelInstance);
        $testProductModelInstance->setProducer($testProducerModelInstance);
        $testProductModelInstance->addProductType($testProductTypeModelInstance);
        $testProductTypeModelInstance->setProduct($testProductModelInstance);
        $this->assertInstanceOf(Paginator::class, $results);
        $this->assertEquals([$testProductTypeModelInstance], $results->getArrayCopy());
        $this->assertEquals(5, $results->pack);
        $this->assertEquals(3, $results->page);
        $this->assertEquals(6, $results->pages);
    }
}

class TestProducerRepository extends BaseRepository
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this
            ->setModelClass(TestProducerModel::class)
            ->setStructure('test_producer_table')
            ->addInt('id', null, ['id' => true], true)
        ;
    }

    public function createDbModelInstance(array $data)
    {
        return parent::createDbModelInstance($data);
    }
}

class TestProductRepository extends BaseRepository
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this
            ->setModelClass(TestProductModel::class)
            ->setStructure('test_product_table')
            ->addInt('id', null, ['id' => true], true)
            ->addInt('producerId', 'producer_id')
        ;
    }

    public function createDbModelInstance(array $data)
    {
        return parent::createDbModelInstance($data);
    }
}

class TestProductTypeRepository extends BaseRepository
{
    /** @var TestProducerRepository */
    private $producerRepository;

    /** @var TestProductRepository */
    private $productRepository;

    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this
            ->setModelClass(TestProductTypeModel::class)
            ->setStructure('test_product_type_table')
            ->addInt('id', null, ['id' => true], true)
            ->addInt('productId', 'product_id')
        ;
    }

    public function setRelatedRepositories(TestProducerRepository $producerRepository,
        TestProductRepository $productRepository)
    {
        $this->producerRepository = $producerRepository;
        $this->productRepository = $productRepository;
    }

    public function getStructureById($id)
    {
        $modelRelationsQuery = $this
            ->prepareSelectAllQuery('pt')
            ->join($this->productRepository, 'pd', ['pt.product_id = pd.id'], ['pt' => 'setProduct'],
                ['pt' => 'addProductType'])
            ->join($this->producerRepository, 'pr', ['pd.producer_id = pr.id'], ['pd' => 'setProducer'],
                ['pd' => 'addProduct'])
        ;
        $this
            ->createSelectAllQuery($modelRelationsQuery)
            ->where(['pt.id = :id'])
            ->limit(1)
            ->bindParam(':id', QueryInterface::PARAM_INT)
        ;
        /** @var TestProductTypeModel[] $productTypes */
        $productTypes = $this->getAllByQuery($modelRelationsQuery, [
            ':id' => $id,
        ]);
        if (count($productTypes) === 0) {
            return null;
        }

        return $productTypes[0];
    }

    public function getStructurePaginated($page, $pack = 20)
    {
        $modelRelationsQuery = $this
            ->prepareSelectAllQuery('pt')
            ->join($this->productRepository, 'pd', ['pt.product_id = pd.id'], ['pt' => 'setProduct'],
                ['pt' => 'addProductType'])
            ->join($this->producerRepository, 'pr', ['pd.producer_id = pr.id'], ['pd' => 'setProducer'],
                ['pd' => 'addProduct'])
        ;
        $this->createSelectAllQuery($modelRelationsQuery);

        return $this->getAllByQueryPaginated($modelRelationsQuery, [], $page, $pack, true);
    }

    public function createDbModelInstance(array $data)
    {
        return parent::createDbModelInstance($data);
    }
}

class TestProducerModel implements ModelInterface
{
    private $id;
    private $products = [];

    public function getId()
    {
        return $this->id;
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function addProduct(TestProductModel $product)
    {
        $this->products[] = $product;

        return $this;
    }
}

class TestProductModel implements ModelInterface
{
    private $id;
    private $producerId;
    private $producer;
    private $productTypes = [];

    public function getId()
    {
        return $this->id;
    }

    public function getProducerId()
    {
        return $this->producerId;
    }

    public function setProducerId($producerId)
    {
        $this->producerId = $producerId;

        return $this;
    }

    public function getProducer()
    {
        return $this->producer;
    }

    public function setProducer(TestProducerModel $producer)
    {
        $this->producer = $producer;

        return $this;
    }

    public function getProductType()
    {
        return $this->productTypes;
    }

    public function addProductType(TestProductTypeModel $productType)
    {
        $this->productTypes[] = $productType;

        return $this;
    }
}

class TestProductTypeModel implements ModelInterface
{
    private $id;
    private $productId;
    private $product;

    public function getId()
    {
        return $this->id;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct(TestProductModel $product)
    {
        $this->product = $product;

        return $this;
    }
}
